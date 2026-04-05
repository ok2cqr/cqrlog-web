# Handoff Notes

Updated: 2026-03-22

## Current state

The repository now contains:

- Symfony REST API in the repo root
- React + TypeScript frontend in `/Users/petr/Projects/private/con/frontend`
- a new read-only `callsignContext` helper endpoint
- a new DXCC proxy endpoint using HamQTH

## What was implemented

### Backend

Added `GET /api/callsignContext`:

- controller: `/Users/petr/Projects/private/con/src/Controller/Api/CallsignContextController.php`
- gateway: `/Users/petr/Projects/private/con/src/Api/CallsignContext/CallsignContextGateway.php`
- query/view DTOs in `/Users/petr/Projects/private/con/src/Api/CallsignContext/`

This endpoint now returns:

- `callsign`
- `idCall`
- `note`
- `clubs`
- `recentQsoCount`
- `recentQsos`
- `autofill`

Behavior:

- `idCall` normalization is implemented in `/Users/petr/Projects/private/con/src/Support/CallsignIdResolver.php`
- club lookup uses `club1..club5`
- club names are parsed from `cqrlog_config.config_file`
- note lookup uses `notes`
- recent QSOs are matched by normalized `idCall`
- recent QSOs are looked up only by stored `idcall`
- autofill uses first non-empty values from recent QSOs, ordered by `qsodate DESC, time_on DESC`
- `QTH` and `Grid` are special cases:
  - they are autofilled only from rows where stored `callsign` exactly matches the currently entered callsign
  - other autofill fields still use the `idCall`-based history

Added `GET /api/dxcc`:

- controller: `/Users/petr/Projects/private/con/src/Controller/Api/DxccController.php`
- gateway: `/Users/petr/Projects/private/con/src/Api/Dxcc/DxccGateway.php`

Behavior:

- proxies `https://www.hamqth.com/dxcc_json.php?callsign=...`
- returns normalized JSON for frontend use
- returns `502` on upstream failure

### Frontend

Frontend app lives in:

- `/Users/petr/Projects/private/con/frontend`

Main files:

- app: `/Users/petr/Projects/private/con/frontend/src/App.tsx`
- API client: `/Users/petr/Projects/private/con/frontend/src/api.ts`
- shared types: `/Users/petr/Projects/private/con/frontend/src/types.ts`
- styles: `/Users/petr/Projects/private/con/frontend/src/styles.css`
- local run instructions: `/Users/petr/Projects/private/con/frontend/README.md`

Implemented frontend behavior:

- QSO form matching the provided tablet-oriented layout
- top row order is `Freq / Band / Mode / Pwr(W)`
- top row stays on one line on iPad Mini portrait
- `Callsign / His RST / My RST / Name` stay on one line on iPad Mini portrait
- `QTH / Grid / State / County / QS / QR` stay on one line on iPad Mini portrait
- `Award / Comment to QSO / QSL Via` stay on one line on iPad Mini portrait
- `WAZ / ITU / IOTA` stay on one line on iPad Mini portrait
- `Date / Time on / Time off / Offline` stay on one line on iPad Mini portrait
- top heading was removed; top bar now shows DXCC summary on the left and compact `Save/Clear` icon buttons on the right
- after successful save, entry fields are cleared and focus returns to `Callsign`
- `Offline` checkbox stops auto-sync from local clock
- auto-sync behavior is closer to `_old/assets/app.js`
- `band`, `mode`, `frequency`, `power` persist in `localStorage`
- persisted `band/mode/frequency/power` are read during initial state creation, so refresh no longer overwrites them with defaults
- `frequency -> band` mapping implemented
- default RST changes with mode:
  - `CW` -> `599`
  - other modes -> `59`
- `Callsign` gets focus after refresh / initial load
- callsign lookup loads:
  - note to callsign
  - club memberships
  - recent QSO history
  - autofill values from recent QSOs
- DXCC lookup loads:
  - `details`
  - `continent`
  - `waz`
  - `itu`
  - `adif`
- QSO save uses `POST /api/logEntries`
- callsign note create/update uses `POST/PATCH /api/notes`
- `Clear` now asks for confirmation before wiping the form
- selected technical fields transliterate Czech keyboard number row on input:
  - `Callsign`
  - `His RST`
  - `My RST`
  - `Freq`
  - `Pwr`
  - `Grid`
  - `QSL Via`
  - `WAZ`
  - `ITU`
  - `IOTA`

Additional frontend work completed after initial handoff:

- left sidebar now switches views:
  - `+` opens QSO entry form
  - lower button opens paginated QSO list
- QSO list is implemented in React and uses `GET /api/logEntries`
- list view shows:
  - `QSO count`
  - pagination
  - columns `QSO Date`, `Time on/off`, `Callsign`, `RST_S`, `RST_R`, `Band`, `Freq`, `Mode`, `Name`, `QTH`, `Award`, `Pfx`
- `Pfx` is backed by `dxcc_ref`
- `Callsign` label now shows `(QSO nr. XXX)` after callsign blur / context lookup
- callsign autofill from previous QSOs now runs only after leaving the `Callsign` field
- `Callsign context` side card was removed
- club names now resolve from the real CQRLOG `[Clubs]` config stored in `cqrlog_config.config_file`
- club membership lookup now returns only one active record per slot for the given date
- `QSO takes ...` moved from a side card next to the `Date` label
- `QSO takes ...` is hidden when `Offline` is checked
- `Name` field includes attributes to discourage 1Password / password-manager autofill
- `Club` side card was widened after removing the old `Session` card
- QSO list rows now support editing via modal dialog
- edit dialog uses roughly the same field layout as the new-QSO form
- editing a callsign triggers live DXCC refresh in the dialog, updating `Pfx`, `WAZ`, and `ITU`
- editing a callsign also refreshes club memberships / club numbers before save
- edit dialog saves both the QSO itself and `Comment to callsign` note data
- `QS` / `QR` were moved from the geo row to the `WAZ / ITU / IOTA` row in both new and edit forms
- freed space from moving `QS` / `QR` was used to widen `QTH` and `County`
- fixed a responsive regression on iPad Mini portrait:
  - mobile one-column layout was previously triggered at `max-width: 760px`
  - iPad Mini portrait width is about `744px`, so sidebar moved to the top and form rows stacked vertically
  - mobile breakpoint in `/Users/petr/Projects/private/con/frontend/src/styles.css` is now `max-width: 700px`
- fixed an additional iPad Safari layout issue in the `Date / Time on / Time off / Offline` row:
  - the remaining overlap was caused by native iOS Safari rendering of `input[type="date"]` and `input[type="time"]`
  - grid column tweaks alone were not sufficient because the native controls visually overflowed into neighboring columns
  - `/Users/petr/Projects/private/con/frontend/src/styles.css` now applies a Safari-specific compact treatment in `.grid--time`:
    - `overflow: hidden` on the field wrapper
    - explicit `width/max-width`
    - `appearance: none` / `-webkit-appearance: none`
    - compact padding and font sizing
    - constrained `::-webkit-date-and-time-value`
- sidebar now includes a third `Settings` section
- current `Settings` implementation is intentionally limited to real frontend preferences:
  - `Dark mode` toggle via frontend-only persisted setting
  - default `QTH profile` selection loaded from `GET /api/profiles`
- selected QTH profile is stored in frontend local settings and sent for new QSOs as `profileId`
- when a new QSO is saved with `profileId` and no explicit `myLocator`, backend copies `profiles.locator` into `cqrlog_main.my_loc`
- dark mode is implemented in `/Users/petr/Projects/private/con/frontend/src/styles.css` via `data-theme="dark"` on the document root
- DXCC lookup for the new-QSO form now runs only after leaving the `Callsign` field
- clicking `+` in the sidebar focuses the `Callsign` field after switching back to the entry form
- settings now also include radio sync options stored in frontend local settings:
  - `JSON URL`
  - `Refresh interval`
- radio sync defaults are no longer frontend-only constants:
  - backend now exposes `GET /api/frontendConfig`
  - current payload includes:
    - `radioSyncDefaultUrl`
    - `radioSyncDefaultPollIntervalSeconds`
  - frontend uses these values as defaults for a new browser/profile that has no existing local settings
  - existing localStorage settings still win and are not overwritten
  - `Reset defaults` in frontend settings now resets to backend-provided defaults, not just hardcoded frontend fallback
- current backend env vars for this are:
  - `FRONTEND_RADIO_SYNC_DEFAULT_URL`
  - `FRONTEND_RADIO_SYNC_DEFAULT_POLL_INTERVAL_SECONDS`
- production now uses the same variable names in `.env.prod`
- frontend can poll an external radio JSON endpoint and map returned values into the entry form:
  - `freq` is expected in `kHz` and is converted to `MHz`
  - if converted `freq > 0`, it updates `Freq`
  - `Band` is re-derived from the updated frequency
  - `mode` is normalized to existing frontend modes, including common variants like `USB/LSB -> SSB`
- radio sync status is shown as a small dot next to the `Freq` label:
  - green when the endpoint is considered online
  - gray when the endpoint is configured but considered offline
  - hidden when radio sync is disabled by clearing the URL
- radio online/offline state is not based only on fetch success:
  - endpoint currently returns `last_seen`
  - frontend accepts both `last_seen` and `lastseen`
  - radio is considered online only when `last_seen` is newer than `2 x Refresh interval`
- radio polling no longer overwrites manual `Freq` / `Mode` edits while the endpoint is stale/offline
- `Pwr(W)` now accepts digits only in both the new-QSO form and the edit dialog
- sidebar now also includes a dedicated `DX Cluster` section with a globe icon
- `DX Cluster` is no longer a placeholder inside the entry form; it has its own full view
- frontend DX Cluster view now:
  - loads `https://www.hamqth.com/dxc_csv.php?limit=10`
  - parses rows separated by newlines and fields separated by `^`
  - auto-refreshes every 20 seconds
  - supports immediate manual reload via `Reload`
  - shows columns in this order:
    `Spotter`, `Freq`, `DX`, `Info`, `Spotdate`, `Country`, `Continent`
- DX Cluster header now also shows solar data summary parsed from HamQTH solar feed:
  - `A`
  - `K`
  - `SFI`
  - `SSN`
  - `GF`
  - update timestamp from the feed
- important implementation detail:
  - HamQTH `dxc_csv.php` currently sends CORS headers and can be fetched directly from the browser
  - HamQTH `solar_data1.dat` does not send usable CORS headers for frontend fetches
  - because of that, solar data is proxied through backend endpoint `/api/solarData`
  - backend implementation:
    - `/Users/petr/Projects/private/con/src/Api/SolarData/SolarDataGateway.php`
    - `/Users/petr/Projects/private/con/src/Controller/Api/SolarDataController.php`

UI still intentionally placeholder-only for:

- `RBN`
- `DX cluster`

## Old implementation references

Useful legacy sources in `_old`:

- old JS behavior: `/Users/petr/Projects/private/con/_old/assets/app.js`
- old save flow: `/Users/petr/Projects/private/con/_old/src/Controller/FrontendController.php`
- old recent QSO DAO: `/Users/petr/Projects/private/con/_old/src/Dao/QsoDao.php`
- old club lookup DAO: `/Users/petr/Projects/private/con/_old/src/Dao/MembershipDao.php`
- old callsign normalization: `/Users/petr/Projects/private/con/_old/src/Traits/CallsignExceptions.php`

Important note:

- old DXCC also used HamQTH externally
- old club lookup only hardcoded `club1 => FOC member`, `club2 => CWOPS member`
- new implementation is more generic and reads names from config
- old QSO list lived in `_old/templates/frontend/qsoList.html.twig`
- the current React list view is not server-rendered; it consumes `/api/logEntries`

## Additional backend changes

For the QSO list / `Pfx` column:

- `GET /api/logEntries` now also returns `dxccRef`
- implementation touches:
  - `/Users/petr/Projects/private/con/src/Api/LogEntry/LogEntryGateway.php`
  - `/Users/petr/Projects/private/con/src/Api/LogEntry/LogEntryMapper.php`
  - `/Users/petr/Projects/private/con/src/Api/LogEntry/LogEntryView.php`

Implementation detail:

- `dxccRef` is resolved via a subquery to `dxcc_id` by matching `adif`
- do not select `dxcc_ref` directly from `cqrlog_main`; that caused `500` errors in tests because the column does not exist there
- `/api/dxcc` now also returns `dxccRef`, resolved from local `dxcc_id` by `adif`, so the edit dialog can preview `Pfx` before save
- `POST /api/logEntries` and `PATCH /api/logEntries/{id}` now auto-fill `idcall` from `callsign` when client code does not send it explicitly
- `POST /api/logEntries` now also derives `my_loc` from the selected profile locator when `profileId` is provided and `myLocator` is omitted

## Test isolation

Important recent fix:

- API tests previously ran against the same DB as the app (`cqrlog002`) and test setup truncation wiped real data
- test environment is now isolated to `cqrlog002_test`
- `phpunit.dist.xml` forces test DB env vars
- `/.env.test` also points to `cqrlog002_test`
- API tests now use `/Users/petr/Projects/private/con/tests/Support/UsesTestDatabase.php`
- this trait throws immediately if destructive tests are pointed at a DB whose name does not end with `_test`

Operational note:

- `cqrlog002_test` was created as a copy of the live DB schema/data inside Docker MariaDB
- if the DB container is recreated from scratch, the test DB may need to be recreated again

## Verified commands

These were run successfully:

```bash
docker compose up -d db app
docker exec symfony-app php bin/phpunit tests/Api/CallsignContextControllerTest.php
docker exec symfony-app php bin/phpunit tests/Api/LogEntryControllerTest.php tests/Api/NoteControllerTest.php
docker exec symfony-app php bin/phpunit tests/Api/LogEntryControllerTest.php
docker exec symfony-app php bin/phpunit tests/Api
docker exec symfony-app php bin/phpunit tests/Api/CallsignContextControllerTest.php
docker exec symfony-app php bin/phpunit tests/Api/LogEntryControllerTest.php
curl -sS 'http://localhost:4000/api/dxcc?callsign=XX9W'
cd frontend && npm install
cd frontend && npm run build
```

## How to run locally

Backend:

```bash
cd /Users/petr/Projects/private/con
docker compose up --build
```

Frontend:

```bash
cd /Users/petr/Projects/private/con/frontend
npm install
npm run dev
```

Open:

- `http://localhost:5173` for frontend
- `http://localhost:4000` for backend

## Open work / next likely tasks

Most likely next steps:

1. Implement real backend endpoints for `RBN`
2. Implement real backend endpoints for `DX cluster`
3. Add exact original keyboard/focus behavior from `_old/assets/app.js`
4. Expand tests for `GET /api/dxcc`
5. Consider caching or rate-limiting for HamQTH DXCC lookups
6. Add tests for frontend-only radio sync behavior if a frontend test setup is introduced
7. Consider moving DX Cluster fetch behind backend too if HamQTH changes CORS behavior for `dxc_csv.php`

## Known limitations

- DXCC depends on external HamQTH availability
- `dxcc_id` in local DB is empty in current local data, so DXCC is not resolved from local DB
- `RBN` and `DX cluster` are still UI placeholders
- frontend currently uses plain React state, not React Query or form library
- QSO list currently supports pagination only; no client filters/search UI yet
- backend PHPUnit in this repo should be run inside `symfony-app` container, not with local host PHP
