<?php /* index.php – Shared Mobility Finder (fix + robust) */ ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Shared Mobility Finder</title>
  <meta name="theme-color" content="#0ea5e9">
  <style>
    :root{
      --bg:#0b1220; --panel:#0f172a; --accent:#0ea5e9; --accent-2:#22d3ee;
      --text:#e5e7eb; --muted:#9ca3af; --ok:#10b981; --warn:#f59e0b; --bad:#ef4444;
      --chip:#1f2937; --shadow:0 8px 24px rgba(0,0,0,.25); --radius:16px;
    }
    *{box-sizing:border-box}
    body{
      margin:0; background: radial-gradient(1200px 800px at 20% 0%, #111827 0, #0b1220 55%, #070b15 100%);
      color:var(--text); font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Arial;
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    }
    header{position:sticky; top:0; z-index:20; background:linear-gradient(135deg,var(--accent),var(--accent-2));
      color:#001018; padding:16px 16px 20px; box-shadow:var(--shadow)}
    header .title{display:flex; gap:12px; align-items:center; font-weight:700; font-size:18px}
    header .subtitle{font-size:13px; opacity:.9}
    main{padding:16px; max-width:1000px; margin:0 auto}
    .controls{
      display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto; gap:10px;
      background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08);
      padding:12px; border-radius:var(--radius); backdrop-filter: blur(6px);
    }
    @media (max-width:820px){ .controls{grid-template-columns:1fr 1fr 1fr; grid-auto-rows:minmax(42px,auto)} }
    @media (max-width:540px){ .controls{grid-template-columns:1fr 1fr; grid-auto-rows:minmax(42px,auto)} }
    label{font-size:12px; color:var(--muted); display:block; margin-bottom:6px}
    select,input[type="number"]{
      width:100%; padding:10px 12px; border-radius:12px; border:1px solid #253047;
      background:#0e1526; color:var(--text); outline:none;
    }
    .btn{appearance:none; border:none; cursor:pointer; padding:12px 14px; border-radius:12px;
      background:linear-gradient(135deg, #14b8a6, #0ea5e9); color:#001018; font-weight:700; box-shadow:var(--shadow)}
    .btn[disabled]{opacity:.6; cursor:not-allowed}
    .btn.secondary{background:#182235; color:var(--text); border:1px solid #2a3a5a}
    .status{font-size:12px; color:var(--muted); margin:12px 4px 0}
    .loader{display:none; margin:8px 4px; height:3px; width:100%; background:#0f172a; overflow:hidden; border-radius:999px}
    .loader::after{content:""; display:block; height:100%; width:30%; background:linear-gradient(90deg, #22d3ee, #0ea5e9); animation:slide 1.2s infinite}
    @keyframes slide{0%{transform:translateX(-30%)} 50%{transform:translateX(130%)} 100%{transform:translateX(130%)}}
    .notice{margin:10px 4px; font-size:12px; color:#a3a3a3}
    .table-wrap{margin-top:12px; background:rgba(255,255,255,.02); border:1px solid rgba(255,255,255,.08); border-radius:var(--radius); overflow:hidden}
    .table{width:100%; border-collapse:collapse}
    thead{background:#0f1a2d}
    th,td{padding:12px 10px; text-align:left; vertical-align:top}
    th{font-size:12px; color:#c7d2fe; border-bottom:1px solid rgba(255,255,255,.1)}
    tbody tr{border-bottom:1px solid rgba(255,255,255,.06)}
    tbody tr:hover{background:rgba(255,255,255,.02)}
    .what{display:flex; gap:10px; align-items:center}
    .icon{width:28px; height:28px; display:inline-block}
    .name{font-weight:600}
    .chips{display:flex; flex-wrap:wrap; gap:6px; margin-top:6px}
    .chip{font-size:11px; background:var(--chip); color:#cbd5e1; padding:3px 8px; border-radius:999px; border:1px solid #2a3a5a}
    .location{display:flex; flex-direction:column; gap:6px}
    .link{font-size:13px; color:#93c5fd; text-decoration:none}
    .link:hover{text-decoration:underline}
    .expand-btn{font:inherit; background:#0d1324; color:#cbd5e1; border:1px solid #273250; padding:8px 10px; border-radius:10px; cursor:pointer}
    .details{background:#0b1220; border-top:1px dashed #233150}
    .details-inner{padding:12px 10px; display:grid; gap:8px}
    .kv{display:grid; grid-template-columns:160px 1fr; gap:8px}
    @media (max-width:680px){ .kv{grid-template-columns:120px 1fr} }
    .muted{color:var(--muted)}
    .overflow{overflow-x:auto}
    .pill{display:inline-block; padding:2px 8px; border-radius:999px; border:1px solid #2a3a5a; background:#121a2b; font-size:11px}
    .err{color:#fca5a5}
  </style>
</head>
<body>
<header>
  <div class="title">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M4 17h16M6 17l1-9h10l1 9M8 17v2a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2" stroke="#001018" stroke-width="1.6"/><path d="M10 6h4a2 2 0 0 1 2 2H8a2 2 0 0 1 2-2Z" stroke="#001018" stroke-width="1.6"/></svg>
    <div>
      <div>Shared Mobility Finder</div>
      <div class="subtitle">Autos, Scooter, Velos in deiner Nähe</div>
    </div>
  </div>
</header>

<main>
  <section class="controls" id="controls">
    <div>
      <label for="radius">Radius (m)</label>
      <input type="number" id="radius" value="1200" min="100" step="100">
    </div>
    <div>
      <label for="type">Typ</label>
      <select id="type">
        <option value="any" selected>Alle</option>
        <option value="car">Auto</option>
        <option value="scooter">Scooter</option>
        <option value="bicycle">Velo</option>
        <option value="moped">Moped</option>
        <option value="station_based">Station basiert</option>
        <option value="free_floating">Free Floating</option>
      </select>
    </div>
    <div>
      <label for="avail">Nur verfügbare</label>
      <select id="avail">
        <option value="any" selected>Egal</option>
        <option value="true">Ja</option>
        <option value="false">Nein</option>
      </select>
    </div>
    <div>
      <label for="offset">Offset (50er Schritte)</label>
      <input type="number" id="offset" value="0" min="0" step="50" title="Startindex für die nächste Seite">
    </div>
    <div style="display:flex; gap:8px; align-items:end">
      <button class="btn" id="refreshBtn">Aktualisieren</button>
      <button class="btn secondary" id="moreBtn" title="Weitere 50 laden">Mehr laden</button>
    </div>
  </section>

  <div class="status" id="status">Standort wird erfragt…</div>
  <div class="loader" id="loader"></div>

  <div class="notice">
    Offset: die API liefert je 50 Einträge. Offset 0 zeigt 1–50, 50 zeigt 51–100, 100 zeigt 101–150. Nutze „Mehr laden“.
  </div>

  <section class="table-wrap">
    <div class="overflow">
      <table class="table" id="resultTable" aria-live="polite">
        <thead>
          <tr>
            <th style="min-width:220px">Was</th>
            <th style="min-width:360px">Details</th>
            <th style="min-width:240px">Wo</th>
          </tr>
        </thead>
        <tbody id="tbody"><tr><td colspan="3" class="muted" style="padding:16px">Noch keine Daten.</td></tr></tbody>
      </table>
    </div>
  </section>

  <div id="errorBox" class="status err" style="display:none"></div>
</main>

<script>
let isLoading = false;
const state = { userPos:null, items:[], lastQuery:null };

const icons = {
  car:`<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 13l2-5a3 3 0 0 1 3-2h6a3 3 0 0 1 3 2l2 5" stroke="#93c5fd" stroke-width="1.6"/><circle cx="7" cy="16" r="2" fill="#10b981"/><circle cx="17" cy="16" r="2" fill="#10b981"/><path d="M3 13h18v3H3z" fill="#1f2937"/></svg>`,
  scooter:`<svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="6" cy="17" r="2.5" stroke="#93c5fd"/><circle cx="18" cy="17" r="2.5" stroke="#93c5fd"/><path d="M12 7l3 8" stroke="#22d3ee" stroke-width="1.6"/><path d="M5 17h8" stroke="#64748b"/><path d="M12 7h4l1 3" stroke="#22d3ee" stroke-width="1.6"/></svg>`,
  bicycle:`<svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="6" cy="16" r="3" stroke="#93c5fd"/><circle cx="18" cy="16" r="3" stroke="#93c5fd"/><path d="M6 16l5-7h4" stroke="#22d3ee" stroke-width="1.6"/><path d="M12 9l2 7" stroke="#22d3ee" stroke-width="1.6"/></svg>`,
  moped:`<svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="7" cy="16" r="2" fill="#10b981"/><circle cx="17" cy="16" r="2" fill="#10b981"/><path d="M4 16h9l2-5h3" stroke="#93c5fd" stroke-width="1.6"/><path d="M14 7h4" stroke="#22d3ee" stroke-width="1.6"/></svg>`,
  station:`<svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M6 20V6a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v14" stroke="#93c5fd" stroke-width="1.6"/><path d="M9 9h6M9 13h6" stroke="#22d3ee"/></svg>`,
  default:`<svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#93c5fd"/><path d="M12 7v6l4 2" stroke="#22d3ee" stroke-width="1.6"/></svg>`
};

function iconFor(type, pickup){
  const arr = Array.isArray(type) ? type : [type||''];
  const t = arr.join(',').toLowerCase();
  if (t.includes("car") || t.includes("auto")) return icons.car;
  if (t.includes("scoot")) return icons.scooter;
  if (t.includes("bike") || t.includes("bicycle")) return icons.bicycle;
  if (t.includes("moped") || t.includes("mofa")) return icons.moped;
  if ((pickup||"").toLowerCase().includes("station")) return icons.station;
  return icons.default;
}
function metersToHuman(m){ return (m < 950) ? `${Math.round(m)} m` : `${(m/1000).toFixed(1)} km`; }
function haversine(lat1, lon1, lat2, lon2){
  const R = 6371000, toRad = d=>d*Math.PI/180;
  const dLat = toRad(lat2-lat1), dLon = toRad(lon2-lon1);
  const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLon/2)**2;
  return 2*R*Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}
function escapeHTML(s){ return String(s??"").replace(/[&<>"']/g,c=>({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c])); }

function normalizeVehicleType(vt){
  if (Array.isArray(vt)) return [...new Set(vt.map(String))].join(", ");
  return vt || "";
}

function flatDetails(properties){
  const out = [], p=properties||{}, st=p.station||{}, stat=st.status||{}, veh=p.vehicle||{}, vstat=veh.status||{}, prov=p.provider||{};
  out.push(["Anbieter", prov.name || prov.id || ""]);
  if (p.vehicle_type) out.push(["Fahrzeugtyp", normalizeVehicleType(p.vehicle_type)]);
  if (p.pickup_type) out.push(["Pickup-Typ", p.pickup_type]);
  out.push(["Verfügbar", p.available===true?"Ja":p.available===false?"Nein":"Unbekannt"]);
  out.push(["Station", st.name || ""]);
  out.push(["Adresse", st.address || ""]);
  if (st.postcode) out.push(["PLZ", st.postcode]);
  if (st.region_id) out.push(["Region", st.region_id]);
  if (typeof stat.installed==="boolean") out.push(["Station installiert", stat.installed?"Ja":"Nein"]);
  if (typeof stat.renting==="boolean") out.push(["Miete möglich", stat.renting?"Ja":"Nein"]);
  if (typeof stat.returning==="boolean") out.push(["Rückgabe möglich", stat.returning?"Ja":"Nein"]);
  if (typeof stat.num_vehicle_available==="number") out.push(["Fahrzeuge verfügbar", stat.num_vehicle_available]);
  if (typeof vstat.disabled==="boolean") out.push(["Fahrzeug deaktiviert", vstat.disabled?"Ja":"Nein"]);
  if (typeof vstat.reserved==="boolean") out.push(["Fahrzeug reserviert", vstat.reserved?"Ja":"Nein"]);
  const apps=prov.apps||{}, ios=(apps.ios||{}).store_uri||[], andr=(apps.android||{}).store_uri||[];
  if (ios.length) out.push(["iOS App", ios.join(", ")]);
  if (andr.length) out.push(["Android App", andr.join(", ")]);
  Object.keys(veh).forEach(k=>{
    const lk=k.toLowerCase();
    if (lk.includes("battery") || lk.includes("charge") || lk.includes("akku")) out.push(["Akkustand ("+k+")", String(veh[k])]);
  });
  return out;
}

function statusLabel(it){
  const s=[];
  if (it.raw?.vehicle?.status?.disabled===true) s.push("deaktiviert");
  if (it.raw?.vehicle?.status?.reserved===true) s.push("reserviert");
  if (s.length) return s.join(", ");
  if (it.available===true) return "bereit";
  if (it.available===false) return "nicht verfügbar";
  return "unbekannt";
}

function render(items){
  const tbody=document.getElementById('tbody'); tbody.innerHTML="";
  if (!items.length){ tbody.innerHTML = `<tr><td colspan="3" class="muted" style="padding:16px">Keine Treffer. Erhöhe den Radius oder lockere die Filter.</td></tr>`; return; }
  items.forEach((it, idx)=>{
    const icon = iconFor(it.vehicle_type, it.pickup_type);
    const chips = [
      (it.available===true)?'<span class="chip">verfügbar</span>':(it.available===false)?'<span class="chip">nicht verfügbar</span>':'<span class="chip">unbekannt</span>',
      (it.station?.status?.renting===true)?'<span class="chip">Miete möglich</span>':'',
      (it.station?.status?.returning===true)?'<span class="chip">Rückgabe möglich</span>':'',
      (typeof it.station?.status?.num_vehicle_available==="number")?`<span class="chip">${it.station.status.num_vehicle_available} frei</span>`:'',
      `<span class="chip">${escapeHTML(normalizeVehicleType(it.vehicle_type) || it.pickup_type || "Typ n/a")}</span>`,
      `<span class="chip">${metersToHuman(it.distance)}</span>`
    ].join("");
    const gLink = `https://www.google.com/maps/search/?api=1&query=${it.lat},${it.lon}`;
    const detailsKVs = flatDetails(it.raw).map(([k,v])=>`<div class="kv"><div class="muted">${escapeHTML(k)}</div><div>${escapeHTML(v)}</div></div>`).join("");
    const frag=document.createElement('tbody');
    frag.innerHTML = `
      <tr>
        <td class="what">${icon}<div><div class="name">${escapeHTML(it.title)}</div><div class="chips">${chips}</div></div></td>
        <td>
          <div><strong>Anbieter:</strong> ${escapeHTML(it.provider?.name || it.provider?.id || "Unbekannt")}</div>
          <div><strong>Status:</strong> ${escapeHTML(statusLabel(it))}</div>
          <div style="margin-top:8px"><button class="expand-btn" data-exp="${idx}">Weitere Details</button></div>
        </td>
        <td>
          <div class="location">
            <div>${escapeHTML(it.station?.name || "Ohne Stationsname")}</div>
            <div class="muted">${escapeHTML(it.station?.address || "")}</div>
            <a class="link" href="${gLink}" target="_blank" rel="noopener">In Google Maps öffnen</a>
          </div>
        </td>
      </tr>
      <tr class="details" id="details-${idx}" style="display:none"><td colspan="3"><div class="details-inner">${detailsKVs || '<div class="muted">Keine weiteren Details vorhanden.</div>'}</div></td></tr>
    `;
    while(frag.firstElementChild){ document.getElementById('tbody').appendChild(frag.firstElementChild); }
  });
  document.querySelectorAll('.expand-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id=btn.getAttribute('data-exp'), el=document.getElementById('details-'+id);
      const vis = el.style.display !== 'none';
      el.style.display = vis ? 'none' : '';
      btn.textContent = vis ? 'Weitere Details' : 'Details schliessen';
    });
  });
}

async function geolocate(){
  return new Promise((resolve,reject)=>{
    if (!('geolocation' in navigator)) return reject(new Error('Geolocation nicht verfügbar'));
    navigator.geolocation.getCurrentPosition(
      pos=> resolve({lat: pos.coords.latitude, lon: pos.coords.longitude, accuracy: pos.coords.accuracy}),
      err=> reject(err),
      {enableHighAccuracy:true, timeout:12000, maximumAge:30000}
    );
  });
}

function readControls(){
  return {
    radius: Number(document.getElementById('radius').value||1200),
    offset: Number(document.getElementById('offset').value||0),
    avail: document.getElementById('avail').value||'any',
    type: document.getElementById('type').value||'any'
  };
}

function applyClientTypeFilter(items, type){
  if (!items.length || !type || type==='any') return items;
  const t = type.toLowerCase();
  return items.filter(it=>{
    const vt = (Array.isArray(it.vehicle_type) ? it.vehicle_type.join(',') : (it.vehicle_type||'')).toLowerCase();
    const pk = (it.pickup_type||'').toLowerCase();
    if (t==='station_based') return pk.includes('station');
    if (t==='free_floating') return pk.includes('free');
    return vt.includes(t);
  });
}

async function fetchItems(q){
  if (isLoading) return {items:state.items, proxyNotice:null};
  isLoading = true;
  document.getElementById('refreshBtn').disabled = true;
  document.getElementById('moreBtn').disabled = true;

  const loader=document.getElementById('loader'); loader.style.display='block';
  const status=document.getElementById('status');
  const errBox=document.getElementById('errorBox'); errBox.style.display='none'; errBox.textContent='';
  try{
    const url = new URL('api.php', location.href);
    url.searchParams.set('lat', String(q.lat)); url.searchParams.set('lon', String(q.lon));
    url.searchParams.set('tolerance', String(q.radius));
    url.searchParams.set('offset', String(q.offset));
    url.searchParams.set('avail', String(q.avail));
    url.searchParams.set('type', String(q.type||'any'));
    const res = await fetch(url.toString(), {headers:{'Accept':'application/json'}});
    const text = await res.text();
    if (!res.ok){
      errBox.style.display='block'; errBox.textContent = `Fehler vom Server (${res.status}).`;
      throw new Error("HTTP "+res.status+" "+text);
    }
    let data;
    try{ data = JSON.parse(text); } catch(e){ throw new Error("Ungueltiges JSON: "+e.message); }

    // Robust: Objekt- oder Array-Form akzeptieren
    const feats = Array.isArray(data) ? data : (data.geoJsonSearchInformations || []);
    let items = feats.map(f=>{
      const coords=f.geometry?.coordinates||[null,null];
      const lonF=Number(coords[0]), latF=Number(coords[1]);
      const p=f.properties||{}, st=p.station||{};
      const dist=(state.userPos && isFinite(latF)&&isFinite(lonF)) ? haversine(state.userPos.lat, state.userPos.lon, latF, lonF) : NaN;
      const title = normalizeVehicleType(p.vehicle_type) || st.name || p.pickup_type || 'Angebot';
      return {
        id:p.id||f.id||'', title, provider:p.provider, vehicle_type:p.vehicle_type||'',
        pickup_type:p.pickup_type||'', available:p.available, station:st, raw:p,
        lon:lonF, lat:latF, distance:dist
      };
    }).sort((a,b)=>(a.distance||Infinity)-(b.distance||Infinity));

    // Clientseitiger Typ-Filter als Sicherheitsnetz
    items = applyClientTypeFilter(items, q.type);
    return {items, proxyNotice:data.proxy_notice||null, count: items.length};
  } catch(e){
    console.error(e);
    errBox.style.display='block';
    errBox.textContent = 'Abruf fehlgeschlagen. Prüfe Internet, Standortfreigabe oder versuche es erneut.';
    return {items:[], proxyNotice:null};
  } finally {
    loader.style.display='none';
    status.textContent = 'Gefundene Einträge werden angezeigt.';
    isLoading = false;
    document.getElementById('refreshBtn').disabled = false;
    document.getElementById('moreBtn').disabled = false;
  }
}

async function run(initial=false){
  const s=document.getElementById('status');
  if (initial){
    s.textContent = "Standort wird erfragt…";
    try{
      const pos = await geolocate();
      state.userPos = {lat:pos.lat, lon:pos.lon};
      s.textContent = `Standort erfasst, Genauigkeit ca. ${Math.round(pos.accuracy)} m. Lade Daten…`;
    }catch(e){
      state.userPos = null;
      s.textContent = "Standort nicht freigegeben. Bitte erlauben, sonst keine Ergebnisse in deiner Nähe.";
      document.getElementById('tbody').innerHTML = `<tr><td colspan="3" class="muted" style="padding:16px">Ohne Standort keine Suche. Erlaube den Standort und lade neu.</td></tr>`;
      return;
    }
  }
  const ui = readControls();
  state.lastQuery = {lat: state.userPos.lat, lon: state.userPos.lon, radius: ui.radius, offset: ui.offset, avail: ui.avail, type: ui.type};
  const {items, proxyNotice} = await fetchItems(state.lastQuery);
  state.items = (ui.offset>0) ? [...state.items, ...items] : items;
  render(state.items);
  if (proxyNotice){
    const box=document.getElementById('errorBox'); box.style.display='block';
    box.textContent = proxyNotice;
  }
}

document.getElementById('refreshBtn').addEventListener('click', ()=>{ document.getElementById('offset').value="0"; run(false); });
document.getElementById('moreBtn').addEventListener('click', ()=>{ const off=Number(document.getElementById('offset').value||0)+50; document.getElementById('offset').value=String(off); run(false); });

// Start
run(true);
</script>
</body>
</html>
