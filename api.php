<?php
/**
 * api.php – Proxy für Shared Mobility API /identify
 * Fixes & Features:
 *  - Normalisiert die Antwort: nacktes Array -> { geoJsonSearchInformations: [...] }
 *  - Filter: avail=true|false|any; type (nur pickup_type serverseitig), Fahrzeugtyp wird clientseitig gefiltert
 *  - Fallbacks: wenn 0 Treffer, lockere Typ und danach avail
 *  - Logging: logs/sharedmobility.log (Request, Dauer, Trefferzahl, Fallback-Hinweise)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function log_line(string $msg): void {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $file = $dir . '/sharedmobility.log';
    $ts = date('c');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
    @file_put_contents($file, "[$ts] [$ip] $msg\n", FILE_APPEND);
}

function build_url(array $params, array $filters): string {
    $base = 'https://api.sharedmobility.ch/v1/sharedmobility/identify';
    $q = $params;
    $qs = http_build_query($q);
    foreach ($filters as $f) {
        $qs .= '&' . rawurlencode('filters') . '=' . rawurlencode($f);
    }
    return $base . '?' . $qs;
}

function call_upstream(string $url): array {
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: SharedMobility-Demo/3.0 (+local)'
        ]
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $ms = (int)round((microtime(true)-$start)*1000);
    return ['code'=>$code, 'body'=>$body, 'err'=>$err, 'ms'=>$ms];
}

function parse_json_to_object(?string $json): ?array {
    if ($json === null || $json === '') return null;
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;

    // Normalisierung: Wenn ein nacktes Feature-Array kommt, wickle es ein
    if (is_array($data) && isset($data[0]['type']) && ($data[0]['type']==='Feature' || isset($data[0]['geometry']))) {
        return ['geoJsonSearchInformations' => $data];
    }
    return $data;
}

// Eingaben
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
$tolerance = isset($_GET['tolerance']) ? max(10, intval($_GET['tolerance'])) : 1200;
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$avail = $_GET['avail'] ?? 'any'; // true|false|any
$type = $_GET['type'] ?? 'any';   // car|scooter|bicycle|moped|station_based|free_floating|any

if ($lat === null || $lon === null) {
    http_response_code(400);
    $msg = 'Fehlende Parameter: lat und lon erforderlich';
    log_line("400 BadRequest: $msg");
    echo json_encode(['error'=>$msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$params = [
    'geometryFormat' => 'geojson',
    'offset' => $offset,
    'Tolerance' => $tolerance,
    'Geometry' => $lon . ',' . $lat // API erwartet lon,lat
];

// Filter aufbauen
$filters = [];
if ($avail === 'true') {
    $filters[] = 'ch.bfe.sharedmobility.available=true';
} elseif ($avail === 'false') {
    $filters[] = 'ch.bfe.sharedmobility.available=false';
}
// Typ serverseitig nur für pickup_type (stabil), Fahrzeugtyp clientseitig
if ($type === 'station_based') {
    $filters[] = 'ch.bfe.sharedmobility.pickup_type=station_based';
} elseif ($type === 'free_floating') {
    $filters[] = 'ch.bfe.sharedmobility.pickup_type=free_floating';
}

$url = build_url($params, $filters);
$resp = call_upstream($url);
log_line("REQ url=\"$url\" code={$resp['code']} time_ms={$resp['ms']}");

if ($resp['body'] === false || $resp['code'] >= 500) {
    http_response_code(502);
    log_line("502 UpstreamError err=\"{$resp['err']}\" bodyLen=".strlen((string)$resp['body']));
    echo json_encode(['error'=>'Upstream Fehler','status'=>$resp['code'],'message'=>$resp['err']], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($resp['code'] >= 400) {
    http_response_code(502);
    log_line("502 UpstreamHTTP{$resp['code']} bodyLen=".strlen((string)$resp['body']));
    echo json_encode(['error'=>'Upstream HTTP Fehler','status'=>$resp['code']], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = parse_json_to_object($resp['body']);
if ($data === null) {
    http_response_code(502);
    log_line("502 InvalidJSON bodyFirst100=" . substr((string)$resp['body'],0,100));
    echo json_encode(['error'=>'Ungueltige Antwort des Upstreams'], JSON_UNESCAPED_UNICODE);
    exit;
}
$count = is_array($data['geoJsonSearchInformations'] ?? null) ? count($data['geoJsonSearchInformations']) : 0;

// Fallbacks wenn leer
$noticeParts = [];
if ($count === 0) {
    // a) Typ-Filter lockern (pickup_type entfernen)
    $filtersLoose = array_values(array_filter($filters, fn($f)=>strpos($f,'pickup_type=')===false));
    $url2 = build_url($params, $filtersLoose);
    $resp2 = call_upstream($url2);
    log_line("FALLBACK A url=\"$url2\" code={$resp2['code']} time_ms={$resp2['ms']}");
    $data2 = parse_json_to_object($resp2['body']);
    $count2 = is_array($data2['geoJsonSearchInformations'] ?? null) ? count($data2['geoJsonSearchInformations']) : 0;
    if ($resp2['code'] < 400 && $data2 !== null && $count2 > 0) {
        $data = $data2; $count = $count2; $noticeParts[] = "Typ-Filter gelockert";
    }
}
if ($count === 0 && $avail !== 'any') {
    // b) avail lockern
    $filtersAvailAny = array_values(array_filter($filters, fn($f)=>strpos($f,'available=')===false));
    $url3 = build_url($params, $filtersAvailAny);
    $resp3 = call_upstream($url3);
    log_line("FALLBACK B url=\"$url3\" code={$resp3['code']} time_ms={$resp3['ms']}");
    $data3 = parse_json_to_object($resp3['body']);
    $count3 = is_array($data3['geoJsonSearchInformations'] ?? null) ? count($data3['geoJsonSearchInformations']) : 0;
    if ($resp3['code'] < 400 && $data3 !== null && $count3 > 0) {
        $data = $data3; $count = $count3; $noticeParts[] = "Verfuegbarkeits-Filter gelockert";
    }
}

log_line("RESULT count={$count}" . (!empty($noticeParts) ? " notice=\"".implode(', ',$noticeParts)."\"" : ""));

// Hinweis für das Frontend
if (!empty($noticeParts)) {
    $data['proxy_notice'] = "Hinweis: " . implode(", ", $noticeParts) . ".";
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
