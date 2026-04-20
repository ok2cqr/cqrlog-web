# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CQRLOG Web - a full-stack web application providing a REST API backend and React frontend for amateur radio (ham radio) QSO logging. The project wraps a legacy MariaDB database (from the desktop CQRLOG application) with a clean, modern API and tablet-optimized UI.

## Stack

- **Backend**: PHP 8.2+ / Symfony 7.4, Dibi 5.1.1 (database abstraction)
- **Frontend**: React 18 + TypeScript + Vite
- **Database**: MariaDB 11.4 (legacy CQRLOG schema)
- **Testing**: PHPUnit 12.5
- **Deployment**: Docker Compose (dev and production)

## Key Architectural Decisions

- **No Doctrine ORM** - Dibi with explicit SQL queries and custom mapping. The legacy schema is messy and Doctrine would add unnecessary complexity.
- **Contract-first API** - JSON uses camelCase, unified `id` fields, readable field names. Legacy column names stay inside the persistence layer only.
- **Per-resource layering** - Each resource has: Input DTO, Output DTO (View), Gateway (SQL), Mapper (DB row to DTO), Controller.
- **Frontend and API share origin** in production (no CORS needed).

## Directory Structure

```
backend root (repo root):
  src/
    Api/                          # Resource modules
      CallsignContext/            # Read-only callsign lookup/autofill
      Dxcc/                       # DXCC proxy to HamQTH
      LogEntry/                   # QSO log entries (main writable resource)
      Note/                       # Short notes per callsign
      LongNote/                   # Long notes
      Profile/                    # Radio profiles
      SolarData/                  # Solar data proxy
      Exception/                  # ApiException, ValidationException, etc.
      Http/                       # JsonRequestDecoder
    Controller/
      Api/                        # One controller per resource
      ApiController.php           # Base controller with shared helpers
    EventSubscriber/              # ApiExceptionSubscriber (error formatting)
    Support/                      # CallsignIdResolver (callsign normalization)
  config/
    services.yaml                 # Dibi connection config (env-based)
    routes.yaml                   # Route definitions
  tests/
    Api/                          # PHPUnit controller tests
    Support/UsesTestDatabase.php  # Test DB isolation trait

frontend/:
  src/
    App.tsx                       # Main React component (large, single-file app)
    api.ts                        # API client methods
    types.ts                      # TypeScript type definitions
    styles.css                    # All styling including dark mode
```

## Development Commands

### Backend + Database

```bash
make dev              # Start Symfony app + MariaDB (http://localhost:4000)
make dev-down         # Stop development stack
make dev-logs         # Tail development logs
make test             # Reset test DB + run PHPUnit tests
```

### Frontend (separate terminal)

```bash
cd frontend
npm install
npm run dev           # Vite dev server at http://localhost:5173
npm run build         # Production build
```

### Production

```bash
cp .env.prod.example .env.prod   # Configure first
make prod             # Build and start production container
make prod-down        # Stop production
make prod-logs        # Tail production logs
```

## Testing

- Tests run inside the `symfony-app` Docker container, NOT with local host PHP
- Test database: `cqrlog002_test` (isolated from live data)
- The `UsesTestDatabase` trait prevents destructive operations on non-`_test` databases
- `phpunit.dist.xml` forces test DB environment variables
- Run all tests: `make test` (resets test DB and runs PHPUnit)

Test files:
- `tests/Api/LogEntryControllerTest.php`
- `tests/Api/NoteControllerTest.php`
- `tests/Api/LongNoteControllerTest.php`
- `tests/Api/ProfileControllerTest.php`
- `tests/Api/CallsignContextControllerTest.php`

## API Resources

### Writable Resources

| Resource | Table | Endpoints |
|---|---|---|
| `logEntries` | `cqrlog_main` | GET list/detail, POST, PATCH |
| `notes` | `notes` | GET list/detail, POST, PATCH |
| `longNotes` | `long_note` | GET list/detail, POST, PATCH |
| `profiles` | `profiles` | GET list/detail, POST, PATCH |

### Read-only Resources

| Resource | Source | Purpose |
|---|---|---|
| `callsignContext` | local DB | Autofill helper - note, clubs, recent QSOs |
| `dxcc` | HamQTH proxy | DXCC metadata lookup |
| `solarData` | HamQTH proxy | Solar data for DX Cluster view |
| `frontendConfig` | env vars | Radio sync defaults |
| `health` | internal | Health check |

## Important Field Mappings

Legacy column names must NOT appear in API responses. Key mappings:

- `id_cqrlog_main` / `id_notes` / etc. -> `id`
- `freq` -> `frequency`
- `loc` -> `grid`
- `pwr` -> `power`
- `my_loc` -> `myLocator`
- `qso_dxcc` -> `qsoDxcc`
- `profile` -> `profileId`
- `idcall` -> `idCall`
- `cont` -> `continent`
- `longremarks` -> `remarks` (notes)
- `nr` -> `number` (profiles)

Full mapping reference: `docs/api.md` and `SPEC.md`

## Per-Resource Pattern (follow for new resources)

For each resource, create:

1. **Input DTO** (`src/Api/{Resource}/{Resource}Input.php`) - validation rules
2. **View DTO** (`src/Api/{Resource}/{Resource}View.php`) - output shape
3. **Gateway** (`src/Api/{Resource}/{Resource}Gateway.php`) - Dibi SQL queries
4. **Mapper** (`src/Api/{Resource}/{Resource}Mapper.php`) - DB row <-> DTO
5. **Controller** (`src/Controller/Api/{Resource}Controller.php`) - HTTP endpoints

Additional per-resource files as needed:
- `*Query.php` - query parameter DTOs
- `*ListView.php` - list-specific view DTOs

## API Behavior Rules

- All responses are JSON
- camelCase field names, unified `id` on every resource
- Unknown input fields -> `422 Unprocessable Entity`
- Invalid JSON -> `400 Bad Request`
- PATCH requires at least one field
- Empty strings are normalized to `null` in responses
- Certain fields are uppercased before storing (`callsign`, `grid`, `state`, `iota`, `idCall`, etc.)
- Zero values for `qsoDxcc`, `profileId`, `adif`, `itu`, `waz` are returned as `null`
- Error format: `{ "error": { "code": "...", "message": "...", "details": { "fields": {...} } } }`

## Frontend Architecture

- Single large React component (`App.tsx`) manages the entire UI
- Plain React state (no React Query or form library)
- Tablet-optimized layout (iPad Mini portrait as reference)
- Dark mode via `data-theme="dark"` on document root
- `localStorage` persists: band, mode, frequency, power, settings
- Czech keyboard transliteration on selected fields
- Vite proxies `/api/*` to backend on port 4000 during development

### Frontend Features

- QSO entry form with callsign autofill from history
- DXCC lookup via HamQTH
- QSO list with pagination and edit modal
- Radio sync (JSON endpoint polling, configurable URL/interval)
- DX Cluster view (HamQTH CSV feed, auto-refresh)
- Solar data display (A, K, SFI, SSN, GF)
- Settings: dark mode, QTH profile, radio sync config
- Sidebar navigation: QSO entry (+), QSO list, DX Cluster, Settings

## External Dependencies

- **HamQTH** (`www.hamqth.com`): DXCC lookups, DX Cluster CSV, solar data
  - DX Cluster CSV is fetched directly from browser (has CORS)
  - Solar data is proxied through backend (no CORS from HamQTH)
  - If HamQTH is down, `/api/dxcc` returns `502`
- **pico-radio-gateway**: Optional JSON API for radio frequency/mode sync

## Environment Variables

Database connection (all required):
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_CHARSET`

Application:
- `APP_SECRET` - Symfony secret
- `APP_ENV` - `dev` / `prod` / `test`
- `DEFAULT_URI` - public base URL for production
- `LOGIN_USERNAME` / `LOGIN_PASSWORD` - application login credentials (defaults in `.env`, override in `.env.prod`)

Optional:
- `SESSION_IDLE_TIMEOUT_SECONDS` - server-side idle timeout in seconds (0 = no timeout). Session expires after this many seconds of inactivity.
- `FRONTEND_RADIO_SYNC_DEFAULT_URL` - default radio sync endpoint
- `FRONTEND_RADIO_SYNC_DEFAULT_POLL_INTERVAL_SECONDS` - default poll interval

## What NOT to Do

- Do NOT use Doctrine ORM
- Do NOT expose raw database column names in API responses
- Do NOT build generic CRUD over the entire database
- Do NOT mix HTTP logic and SQL queries (use the layered pattern)
- Do NOT run PHPUnit on the host - always inside the Docker container
- Do NOT run destructive test operations against a DB that doesn't end with `_test`
- Do NOT select `dxcc_ref` directly from `cqrlog_main` (it doesn't exist there - use subquery to `dxcc_id`)

## Documentation

- `README.md` - setup and usage
- `SPEC.md` - API design specification and principles
- `docs/HANDOFF.md` - implementation status and decisions
- `docs/DEPLOY.md` - production deployment guide
- `docs/api.md` - human-readable API documentation
- `docs/openapi.yaml` - machine-readable OpenAPI spec

## Useful Legacy References

Old implementation in `_old/` directory:
- `_old/assets/app.js` - original JS behavior
- `_old/src/Controller/FrontendController.php` - old save flow
- `_old/src/Dao/QsoDao.php` - old recent QSO queries
- `_old/src/Dao/MembershipDao.php` - old club lookup
- `_old/src/Traits/CallsignExceptions.php` - old callsign normalization

## Known Limitations

- `dxcc_id` in local DB may be empty, so DXCC is resolved from HamQTH, not locally
- RBN is still a UI placeholder
- Frontend uses plain React state, no form library or React Query
- QSO list has pagination only, no client-side filters/search yet
- Frontend is a single large component (`App.tsx`) - no component extraction yet
