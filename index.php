<?php /* index.php – Shared Mobility Finder (Modern & Mobile-First) */ ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Shared Mobility Finder CH</title>
  <meta name="description" content="Finde Autos, E-Scooter, Velos und mehr in deiner Nähe. Alle Sharing-Anbieter der Schweiz auf einen Blick.">
  <meta name="theme-color" content="#0ea5e9">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/app.js" defer></script>
</head>
<body>

<header>
  <div class="title-container">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 17h16M6 17l1-9h10l1 9M8 17v2a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.6"/><path d="M10 6h4a2 2 0 0 1 2 2H8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6"/></svg>
    <div>
      <h1>Shared Mobility Finder</h1>
      <p class="subtitle">Autos, Scooter, Velos in deiner Nähe</p>
    </div>
  </div>
</header>

<main>
  <section class="controls-wrapper">
    <div class="controls">
      <div>
        <label for="radius">Radius (m)</label>
        <input type="number" id="radius" value="1200" min="100" step="100">
      </div>
      <div>
        <label for="vehType">Fahrzeugtyp</label>
        <select id="vehType">
          <option value="any" selected>Alle</option>
          <option value="e_scooter">E-Scooter</option>
          <option value="bike">Bike</option>
          <option value="e_bike">E-Bike</option>
          <option value="e_cargobike">E-CargoBike</option>
          <option value="car">Car</option>
          <option value="e_car">E-Car</option>
          <option value="moped">Moped</option>
        </select>
      </div>
      <div>
        <label for="pickup">Pickup-Typ</label>
        <select id="pickup">
          <option value="any" selected>Alle</option>
          <option value="station_based">Stationsbasiert</option>
          <option value="free_floating">Free-Floating</option>
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
    </div>
  </section>

  <div class="status-wrapper">
    <div id="status" class="status">Standort wird erfragt…</div>
    <div id="errorBox" class="status err" style="display:none"></div>
    <div id="suggestBox" class="status suggest" style="display:none"></div>
  </div>

  <div id="results-container" class="results-grid" aria-live="polite">
    </div>

  <div id="loader" class="loader-bar" style="display:none;"></div>
</main>

<footer>
  <p>
    Webseite von <a href="https://rueetschli.com" target="_blank" rel="noopener">Michael Rüetschli</a>.
    Verbesserungsvorschläge? Mach mit auf <a href="https://github.com/rueetschli/Shared-Mobility-Finder-CH" target="_blank" rel="noopener">GitHub</a>!
  </p>
</footer>

</body>
</html>
