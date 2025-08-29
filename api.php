<?php
/**
 * api.php – Proxy für Shared Mobility API /identify
 * - Sammelt mehrere Seiten (pages, default 4) und führt zusammen
 * - Dedupliziert nach Feature-ID
 * - Serverseitiger Filter: pickup_type (stabil). Fahrzeugtyp wird clientseitig gefiltert.
 * - Fallbacks bei 0 Treffern (lockert pickup/avail)
 * - Logging mit Treffermenge, Seiten und Notices
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function log_line(string $msg): void {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $file = $dir . '/sharedmobility.log';
    $ts = date('c'); $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
    @file_put_contents($file, "[$ts] [$ip] $msg\n", FILE_APPEND);
}
function build_url(array $params, array $filters): string {
    $base = 'https://api.sharedmobility.ch/v1/sharedmobility/identify';
    $qs = http_build_query($params);
    foreach ($filters as $f) { $qs .= '&' . rawurlencode('filters') . '=' . rawurlencode($f); }
    return $base . '?' . $qs;
}
function call_upstream(string $url): array {
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json','User-Agent: SharedMobility-Finder-CH/2.0']
    ]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err  = curl_error($ch);
    curl_close($ch); $ms = (int)round((microtime(true)-$start)*1000);
    return ['code'=>$code, 'body'=>$body, 'err'=>$err, 'ms'=>$ms];
}
function normalize_response(?string $json): ?array {
    if ($json === null || $json === '') return null;
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    // Die API gibt manchmal ein leeres Array zurück, manchmal ein Objekt mit geoJson...
    if (is_array($data) && (empty($data) || isset($data[0]['type']))) {
        return ['geoJsonSearchInformations' => $data];
    }
    return $data;
}
function collect_pages(array $baseParams, array $filters, int $startOffset, int $pages): ?array {
    $map = []; $pagesDone = 0; $totalFetched = 0;
    for ($i=0; $i<$pages; $i++){
        $params = $baseParams;
        $params['offset'] = $startOffset + ($i*50);
        $url = build_url($params, $filters);
        $resp = call_upstream($url);
        log_line(sprintf('REQ url="%s" code=%d time_ms=%d', $url, $resp['code'], $resp['ms']));
        if ($resp['body'] === false || $resp['code'] >= 500) return null;
        if ($resp['code'] >= 400) return null; // Client-Fehler nicht weiterverfolgen
        $data = normalize_response($resp['body']);
        if ($data === null) return null;
        $feats = $data['geoJsonSearchInformations'] ?? [];
        foreach ($feats as $f){
            $id = $f['properties']['id'] ?? $f['id'] ?? null;
            if ($id !== null) $map[$id] = $f;
        }
        $pagesDone++; $totalFetched += count($feats);
        if (count($feats) < 50) break; // Ende der Paginierung erreicht
    }
    return ['geoJsonSearchInformations'=>array_values($map), '_pages'=>$pagesDone, '_rawFetched'=>$totalFetched];
}

// Inputs
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
$tolerance = isset($_GET['tolerance']) ? max(10, intval($_GET['tolerance'])) : 1200;
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$avail = $_GET['avail'] ?? 'any';
$pickup = $_GET['pickup'] ?? 'any';
$pages = isset($_GET['pages']) ? max(1, min(5, intval($_GET['pages']))) : 4; // Max 5 Seiten pro Call

if ($lat === null || $lon === null) {
    http_response_code(400);
    $msg = 'Fehlende Parameter: lat und lon erforderlich';
    log_line("400 BadRequest: $msg");
    echo json_encode(['error'=>$msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$baseParams = [
    'geometryFormat' => 'geojson',
    'offset' => $offset,
    'Tolerance' => $tolerance,
    'Geometry' => $lon . ',' . $lat
];
$filters = [];
if ($avail === 'true') $filters[] = 'ch.bfe.sharedmobility.available=true';
elseif ($avail === 'false') $filters[] = 'ch.bfe.sharedmobility.available=false';
if ($pickup === 'station_based') $filters[] = 'ch.bfe.sharedmobility.pickup_type=station_based';
elseif ($pickup === 'free_floating') $filters[] = 'ch.bfe.sharedmobility.pickup_type=free_floating';

// 1) Sammeln
$data = collect_pages($baseParams, $filters, $offset, $pages);
if ($data === null) {
    http_response_code(502);
    log_line("502 UpstreamError during collection");
    echo json_encode(['error'=>'Upstream Fehler'], JSON_UNESCAPED_UNICODE);
    exit;
}
$count = count($data['geoJsonSearchInformations']);
$noticeParts = [];

// 2) Fallbacks, wenn leer
if ($count === 0 && ($pickup !== 'any' || $avail !== 'any')) {
    $filtersLoose = []; // Alle Filter zurücksetzen, ausser Geolocation
    $data2 = collect_pages($baseParams, $filtersLoose, $offset, $pages);
    if ($data2 && count($data2['geoJsonSearchInformations']) > 0){
        $data = $data2; $count = count($data2['geoJsonSearchInformations']); $noticeParts[]="Filter wurden für mehr Ergebnisse gelockert";
    }
}

log_line("RESULT count={$count} pages=" . ($data['_pages'] ?? 0) . " rawFetched=" . ($data['_rawFetched'] ?? 0) . (!empty($noticeParts) ? " notice=\"".implode(', ',$noticeParts)."\"" : ""));
if (!empty($noticeParts)) $data['proxy_notice'] = "Hinweis: " . implode(", ", $noticeParts) . ".";

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
