# Shared Mobility Finder

Mobile-first Webapp zum Finden von Autos, Scootern, Velos und weiteren Shared-Mobility-Angeboten in deiner Nähe.  
Die App fragt beim Laden den Standort ab, ruft die offizielle Shared-Mobility-API via PHP-Proxy auf, sortiert nach Distanz und zeigt alles in einer übersichtlichen Tabelle mit Piktogrammen, Detail-Accordion und Google-Maps-Links.

<img width="1535" height="990" alt="image" src="https://github.com/user-attachments/assets/f891e44e-f7c1-427b-8c35-6f1c745aa423" />


## Features

- **Super mobilfähig**: responsive UI, grosse Touch-Ziele, schnelle Interaktionen
- **Standortabfrage**: Browser-Geolocation, Distanzberechnung via Haversine
- **Filter**:
  - **Typ**: Auto, Scooter, Velo, Moped, Station basiert, Free Floating
  - **Verfügbarkeit**: Ja, Nein, Egal
  - **Offset** für Paging in 50er-Schritten
- **Robuste API-Auswertung**:
  - Unterstützt beide API-Antwortformen: Objekt mit `geoJsonSearchInformations` oder nacktes Feature-Array
  - Normalisiert `vehicle_type` (String oder Array)
- **Details pro Zeile**: Anbieter, Station, Status, Anzahl freie Fahrzeuge, optional Akkustand, App-Links
- **Stabiles Backend**:
  - PHP-Proxy mit Fallback-Strategie, wenn Filter zu strikt sind
  - Request- und Error-Logging (`logs/sharedmobility.log`) inkl. Treffermenge

## Anforderungen

- PHP **7.4+** oder **8.x**
- PHP-cURL Extension aktiviert
- Webserver mit HTTPS (Geolocation braucht HTTPS)
- Schreibrechte für `logs/` (oder Log-Pfad anpassen)

## Installation

1. Repository auf deinen PHP-fähigen Server deployen.
2. Ordner `logs/` anlegen und Schreibrechte setzen:
   ```bash
   mkdir -p logs && chmod 775 logs
Seite unter index.php aufrufen, Standortfreigabe erlauben.

## Credits
Datenquelle und API: Shared Mobility API von sharedmobility.ch bzw. Bundesamt für Energie (BFE)
Zugriff: https://api.sharedmobility.ch/ mit Base-Pfad /v1/sharedmobility
Kartenlink: Google Maps „Search“ Linkformat https://www.google.com/maps/search/?api=1&query=LAT,LON.
Ohne diese offenen Daten wäre dieses Projekt nicht möglich. Danke an das BFE und sharedmobility.ch.

