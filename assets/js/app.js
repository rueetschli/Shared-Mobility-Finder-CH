// assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
  const API_ITEMS_PER_PAGE = 50;
  const PAGES_TO_COLLECT = 4; // Mehr Seiten pro Request abfragen, um weniger Requests zu machen
  const SCROLL_BUFFER = 500; // Pixel vom unteren Rand, bei denen das Nachladen ausgelöst wird

  // Zustand der App
  const state = {
    userPos: null,
    allItems: [],
    currentOffset: 0,
    isLoading: false,
    allDataLoaded: false,
    lastQuery: {}
  };

  // Referenzen auf DOM-Elemente
  const DOMElements = {
    radius: document.getElementById('radius'),
    vehType: document.getElementById('vehType'),
    pickup: document.getElementById('pickup'),
    avail: document.getElementById('avail'),
    status: document.getElementById('status'),
    errorBox: document.getElementById('errorBox'),
    suggestBox: document.getElementById('suggestBox'),
    resultsContainer: document.getElementById('results-container'),
    loader: document.getElementById('loader')
  };

  // SVG-Icons (unverändert)
  const icons = {
    car: `<svg class="card-icon" viewBox="0 0 24 24" fill="none"><path d="M3 13l2-5a3 3 0 0 1 3-2h6a3 3 0 0 1 3 2l2 5" stroke="#93c5fd" stroke-width="1.6"/><circle cx="7" cy="16" r="2" fill="#10b981"/><circle cx="17" cy="16" r="2" fill="#10b981"/><path d="M3 13h18v3H3z" fill="#1f2937"/></svg>`,
    scooter: `<svg class="card-icon" viewBox="0 0 24 24" fill="none"><circle cx="6" cy="17" r="2.5" stroke="#93c5fd"/><circle cx="18" cy="17" r="2.5" stroke="#93c5fd"/><path d="M12 7l3 8" stroke="#22d3ee" stroke-width="1.6"/><path d="M5 17h8" stroke="#64748b"/><path d="M12 7h4l1 3" stroke="#22d3ee" stroke-width="1.6"/></svg>`,
    bicycle: `<svg class="card-icon" viewBox="0 0 24 24" fill="none"><circle cx="6" cy="16" r="3" stroke="#93c5fd"/><circle cx="18" cy="16" r="3" stroke="#93c5fd"/><path d="M6 16l5-7h4" stroke="#22d3ee" stroke-width="1.6"/><path d="M12 9l2 7" stroke="#22d3ee" stroke-width="1.6"/></svg>`,
    cargo: `<svg class="card-icon" viewBox="0 0 24 24" fill="none"><rect x="3" y="10" width="8" height="5" stroke="#93c5fd"/><circle cx="7" cy="17" r="2" fill="#10b981"/><circle cx="17" cy="17" r="2" fill="#10b981"/><path d="M11 12h6l2 3" stroke="#22d3ee" stroke-width="1.6"/></svg>`,
    moped: `<svg class="card-icon" viewBox="0 0 24 24" fill="none"><circle cx="7" cy="16" r="2" fill="#10b981"/><circle cx="17" cy="16" r="2" fill="#10b981"/><path d="M4 16h9l2-5h3" stroke="#93c5fd" stroke-width="1.6"/><path d="M14 7h4" stroke="#22d3ee" stroke-width="1.6"/></svg>`,
    station: `<svg class="card-icon" viewBox="0 0 24 24" fill="none"><path d="M6 20V6a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v14" stroke="#93c5fd" stroke-width="1.6"/><path d="M9 9h6M9 13h6" stroke="#22d3ee"/></svg>`,
    default: `<svg class="card-icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#93c5fd"/><path d="M12 7v6l4 2" stroke="#22d3ee" stroke-width="1.6"/></svg>`
  };

  // Hilfsfunktionen
  const escapeHTML = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': '&quot;', "'": '&#39;' }[c]));
  const metersToHuman = m => (m < 950) ? `${Math.round(m)} m` : `${(m / 1000).toFixed(1)} km`;
  const haversine = (lat1, lon1, lat2, lon2) => {
    const R = 6371000, toRad = d => d * Math.PI / 180;
    const dLat = toRad(lat2 - lat1), dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
    return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  };

  // Normalisierung der Fahrzeugtypen
  const VEH_LABELS = { e_scooter: 'E-Scooter', bike: 'Bike', e_bike: 'E-Bike', e_cargobike: 'E-CargoBike', car: 'Car', e_car: 'E-Car', moped: 'Moped' };
  const VEH_ALIASES = { 'e-scooter': 'e_scooter', 'scooter': 'e_scooter', 'bicycle': 'bike', 'e-bike': 'e_bike', 'e-cargobike': 'e_cargobike', 'cargobike': 'e_cargobike', 'pkw': 'car', 'e-car': 'e_car' };
  const PROVIDER_HINT_VEH = { 'voiscooters.com': 'e_scooter', 'tier': 'e_scooter', 'lime': 'e_scooter', 'publibike': 'bike', 'velospot': 'bike', 'carvelo2go': 'e_cargobike', 'mobility': 'car', '2em': 'car' };

  function canonicalVehSet(p) {
    const list = Array.isArray(p?.vehicle_type) ? p.vehicle_type : (p?.vehicle_type ? [p.vehicle_type] : []);
    const set = new Set();
    for (const raw of list) {
      const key = String(raw || '').trim().toLowerCase();
      if (VEH_ALIASES[key]) set.add(VEH_ALIASES[key]);
      else if (VEH_LABELS[key]) set.add(key);
    }
    if (set.size === 0) {
      const provId = String(p?.provider?.id || '').toLowerCase();
      if (PROVIDER_HINT_VEH[provId]) set.add(PROVIDER_HINT_VEH[provId]);
    }
    return [...set];
  }

  function primaryIconFor(types, pickup) {
    const t = new Set(types);
    if (t.has('e_scooter')) return icons.scooter;
    if (t.has('e_bike') || t.has('bike')) return icons.bicycle;
    if (t.has('e_cargobike')) return icons.cargo;
    if (t.has('e_car') || t.has('car')) return icons.car;
    if (t.has('moped')) return icons.moped;
    if ((pickup || '').toLowerCase().includes('station')) return icons.station;
    return icons.default;
  }
  
  // Rendert die Karten in den Container
  function render(items, append = false) {
    if (!append) {
      DOMElements.resultsContainer.innerHTML = "";
    }

    if (items.length === 0 && !append) {
      DOMElements.resultsContainer.innerHTML = `<p class="status">Keine Treffer für diese Filterkombination. Versuche, die Filter anzupassen.</p>`;
      return;
    }

    const frag = document.createDocumentFragment();
    items.forEach(it => {
      const card = document.createElement('div');
      card.className = 'card';
      card.id = `item-${it.id.replace(/[^a-zA-Z0-9]/g, '-')}`;

      const icon = primaryIconFor(it.types, it.pickup_type);
      const typeChips = it.types.map(t => `<span class="chip">${VEH_LABELS[t] || t}</span>`).join("");
      const availabilityChip = it.available === true ? '<span class="chip">Verfügbar</span>' : it.available === false ? '<span class="chip">Nicht verfügbar</span>' : '';
      
      const prov = it.provider || {};
      const detailsKVs = flatDetails(it.raw).map(([k, v]) => `<div class="details-kv"><div class="key">${escapeHTML(k)}</div><div>${v}</div></div>`).join("");
	  
	  // KORREKTUR: Der Google Maps Link wurde korrigiert und verbessert.
      const mapLink = `https://www.google.com/maps/search/?api=1&query=${it.lat},${it.lon}`;

      card.innerHTML = `
        <div class="card-header">
          ${icon}
          <div class="card-title">${escapeHTML(prov.name || prov.id)}</div>
          <div class="card-distance">${metersToHuman(it.distance)}</div>
        </div>
        <div class="card-body">
          <div class="location-name">${escapeHTML(it.mainName)}</div>
          <div class="location-address">${escapeHTML(it.subAddress)}</div>
          <div class="chips">${availabilityChip}${typeChips}</div>
        </div>
        <div class="details-panel" style="display: none;">${detailsKVs}</div>
        <div class="card-footer">
          <a class="link" href="${mapLink}" target="_blank" rel="noopener">Auf Karte zeigen</a>
          <button class="expand-btn">Details</button>
        </div>
      `;
      frag.appendChild(card);
    });

    DOMElements.resultsContainer.appendChild(frag);
  }

  // Event-Delegation für die Detail-Buttons
  DOMElements.resultsContainer.addEventListener('click', e => {
    if (e.target.matches('.expand-btn')) {
      const card = e.target.closest('.card');
      const details = card.querySelector('.details-panel');
      const isVisible = details.style.display !== 'none';
      details.style.display = isVisible ? 'none' : 'block';
      e.target.textContent = isVisible ? 'Details' : 'Schliessen';
    }
  });
  
  // Extrahiert Details für die Detailansicht
  function flatDetails(p) {
    const out = [];
    const prov = p.provider || {};
    const st = p.station || {};
    const stStatus = st.status || {};
    const veh = p.vehicle || {};
    const vehStatus = veh.status || {};

    if (p.pickup_type) out.push(["Pickup-Typ", escapeHTML(p.pickup_type)]);
    if (typeof stStatus.num_vehicle_available === "number") out.push(["Fahrzeuge frei", escapeHTML(stStatus.num_vehicle_available)]);
    if (typeof stStatus.renting === "boolean") out.push(["Miete möglich", stStatus.renting ? "Ja" : "Nein"]);
    if (typeof stStatus.returning === "boolean") out.push(["Rückgabe möglich", stStatus.returning ? "Ja" : "Nein"]);
    if (typeof vehStatus.disabled === "boolean") out.push(["Deaktiviert", vehStatus.disabled ? "Ja" : "Nein"]);
    if (typeof vehStatus.reserved === "boolean") out.push(["Reserviert", vehStatus.reserved ? "Ja" : "Nein"]);
    
    const iosApp = prov.apps?.ios?.store_uri?.[0];
    if (iosApp && iosApp !== 'null') {
        out.push(["iOS App", `<a href="${escapeHTML(iosApp)}" class="link" target="_blank">Im App Store öffnen</a>`]);
    }
    const androidApp = prov.apps?.android?.store_uri?.[0];
    if (androidApp && androidApp !== 'null') {
        out.push(["Android App", `<a href="${escapeHTML(androidApp)}" class="link" target="_blank">Im Play Store öffnen</a>`]);
    }

    return out;
  }
  
  // Holt Daten von der API
  async function fetchItems(query) {
    state.isLoading = true;
    DOMElements.loader.style.display = 'block';
    DOMElements.errorBox.style.display = 'none';
    DOMElements.suggestBox.style.display = 'none';

    try {
      const url = new URL('api.php', location.href);
      url.searchParams.set('lat', String(query.lat));
      url.searchParams.set('lon', String(query.lon));
      url.searchParams.set('tolerance', String(query.radius));
      url.searchParams.set('offset', String(query.offset));
      url.searchParams.set('avail', String(query.avail));
      url.searchParams.set('pickup', String(query.pickup));
      url.searchParams.set('pages', String(PAGES_TO_COLLECT));

      const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
      const data = await res.json();

      if (!res.ok) throw new Error(data.error || `Serverfehler (${res.status})`);

      const feats = data.geoJsonSearchInformations || [];
      
      if (feats.length < (API_ITEMS_PER_PAGE * PAGES_TO_COLLECT)) {
          state.allDataLoaded = true;
      }

      const newItems = feats.map(f => {
        const p = f.properties || {};
        const coords = f.geometry?.coordinates || [null, null];
        const lon = Number(coords[0]), lat = Number(coords[1]);
        const dist = (state.userPos && isFinite(lat) && isFinite(lon)) ? haversine(state.userPos.lat, state.userPos.lon, lat, lon) : NaN;
        const types = canonicalVehSet(p);
        
        let mainName = p.station?.name || (types.map(t => VEH_LABELS[t]).join(', ')) || 'Fahrzeug';
        let subAddress = p.station?.address || '';
        if (subAddress && mainName.includes(subAddress.split(',')[0])) {
            subAddress = '';
        }

        return {
          id: p.id || f.id || '',
          provider: p.provider,
          types,
          pickup_type: p.pickup_type || '',
          available: p.available,
          station: p.station || {},
          raw: p,
          lon, lat, distance: dist,
          mainName,
          subAddress
        };
      });
      
      return newItems;
    } catch (e) {
      console.error(e);
      DOMElements.errorBox.textContent = 'Abruf fehlgeschlagen. Bitte prüfe deine Internetverbindung und Standortfreigabe.';
      DOMElements.errorBox.style.display = 'block';
      return [];
    } finally {
      state.isLoading = false;
      DOMElements.loader.style.display = 'none';
    }
  }

  // Hauptfunktion, die alles steuert
  async function run(isNewSearch = false) {
    if (state.isLoading) return;

    if (isNewSearch) {
      state.currentOffset = 0;
      state.allItems = [];
      state.allDataLoaded = false;
      DOMElements.resultsContainer.innerHTML = "";
    }
    
    if (state.allDataLoaded) {
        DOMElements.status.textContent = "Alle verfügbaren Angebote in der Umgebung geladen.";
        return;
    }

    if (!state.userPos) {
      DOMElements.status.textContent = "Standort konnte nicht ermittelt werden.";
      return;
    }

    const controls = readControls();
    const query = {
      lat: state.userPos.lat,
      lon: state.userPos.lon,
      radius: controls.radius,
      offset: state.currentOffset,
      avail: controls.avail,
      vehType: controls.vehType,
      pickup: controls.pickup
    };
    state.lastQuery = query;

    DOMElements.status.textContent = "Lade Angebote...";
    const rawItems = await fetchItems(query);
    
    let filteredItems = applyClientFilters(rawItems, query);
    
    const existingIds = new Set(state.allItems.map(it => it.id));
    const uniqueNewItems = filteredItems.filter(it => !existingIds.has(it.id));
    
    uniqueNewItems.sort((a, b) => (a.distance || Infinity) - (b.distance || Infinity));
    
    state.allItems.push(...uniqueNewItems);
    render(uniqueNewItems, true);

    state.currentOffset += (API_ITEMS_PER_PAGE * PAGES_TO_COLLECT);
    DOMElements.status.textContent = `Zeige ${state.allItems.length} Angebote in deiner Nähe.`;
    
    if (state.allItems.length === 0) {
        DOMElements.status.textContent = "Keine Angebote für die gewählten Filter gefunden.";
    }
  }
  
  // Liest die Werte aus den Filter-Controls
  function readControls() {
    return {
      radius: Number(DOMElements.radius.value) || 1200,
      avail: DOMElements.avail.value || 'any',
      vehType: DOMElements.vehType.value || 'any',
      pickup: DOMElements.pickup.value || 'any'
    };
  }

  // Wendet clientseitige Filter an
  function applyClientFilters(items, q) {
    let out = items;
    if (q.vehType !== 'any') {
      out = out.filter(it => it.types.includes(q.vehType));
    }
    if (q.pickup !== 'any') {
      out = out.filter(it => (it.pickup_type || '').toLowerCase().includes(q.pickup));
    }
    return out;
  }
  
  // Standortabfrage
  async function geolocate() {
    return new Promise((resolve, reject) => {
      if (!('geolocation' in navigator)) {
        return reject(new Error('Geolocation nicht verfügbar'));
      }
      navigator.geolocation.getCurrentPosition(
        pos => resolve({ lat: pos.coords.latitude, lon: pos.coords.longitude, accuracy: pos.coords.accuracy }),
        err => reject(err),
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
      );
    });
  }

  // Event Listener für Filteränderungen
  ['radius', 'vehType', 'pickup', 'avail'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => run(true));
  });

  // Event Listener für Infinite Scroll
  window.addEventListener('scroll', () => {
    if (state.isLoading || state.allDataLoaded) return;
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - SCROLL_BUFFER) {
      run();
    }
  });

  // Initialisierung der App
  async function init() {
    try {
      const pos = await geolocate();
      state.userPos = { lat: pos.lat, lon: pos.lon };
      DOMElements.status.textContent = `Standort erfasst (Genauigkeit: ${Math.round(pos.accuracy)}m). Lade Angebote...`;
      await run(true);
    } catch (e) {
      state.userPos = null;
      DOMElements.status.textContent = "Standort konnte nicht ermittelt werden. Bitte Freigabe erteilen und Seite neu laden.";
      DOMElements.errorBox.textContent = e.message;
      DOMElements.errorBox.style.display = 'block';
    }
  }

  init();
});
