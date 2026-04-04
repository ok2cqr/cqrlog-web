# SPEC: REST API over the legacy CQRLOG database

## Goal
Build a REST API on top of the existing MariaDB schema from `schema.sql`.

This API will serve as the backend for a future JavaScript application. The database schema is legacy and must not be exposed directly. The API contract must be clean, consistent, and separated from the internal table and column names.

## Technology decision
- Backend framework: Symfony
- Database access: Dibi
- Do not use Doctrine ORM

## Database configuration
The database must be configurable.

Requirements:
- Do not hardcode database host, port, database name, username, or password
- Database connection must be configured through environment variables
- The application must support at least:
  - local Docker-based MariaDB
  - an externally provided MariaDB instance
- The import source must be configurable
- Local development should default to `schema.sql`
- Tests should be able to run against a dedicated configurable database

### Reasoning
The database structure is inconsistent and messy. Doctrine would add unnecessary complexity to mapping and maintenance. Dibi with explicit SQL queries and a custom mapping layer is a better fit.

Node.js is not excluded, but for this use case it does not provide a major advantage. The main problem is not the framework, but designing a clean API contract over a legacy database. Symfony is fully suitable for this layer.

## Core design principles
- The API must be contract-first, not database-first
- JSON input and output must use:
  - `camelCase`
  - a unified `id`
  - readable field names
- Legacy column names must stay inside the persistence layer only
- The API must not be a generic CRUD wrapper over all tables
- Write operations are allowed only for selected resources
- All other tables are read-only

## Writable tables
- `cqrlog_main`
- `long_note`
- `notes`
- `profiles`

## Read-only scope
All other tables and views are read-only and should be exposed only when they are actually needed by the frontend.

## Architecture
For each resource, create separate layers:

1. `Input DTO`
2. `Output DTO`
3. `Gateway` over Dibi with explicit SQL
4. `Mapper` between DB rows and DTOs
5. `Controller`

Example structure:

- `src/Api/LogEntry/LogEntryInput.php`
- `src/Api/LogEntry/LogEntryView.php`
- `src/Api/LogEntry/LogEntryGateway.php`
- `src/Api/LogEntry/LogEntryMapper.php`
- `src/Controller/Api/LogEntryController.php`

## Resource naming
The API must not mirror table names 1:1. Resources should be named clearly for frontend consumers.

Proposed resource names:
- `cqrlog_main` -> `logEntries`
- `notes` -> `notes`
- `long_note` -> `longNotes`
- `profiles` -> `profiles`

## Proposed endpoints

### Log entries
- `GET /api/logEntries`
- `GET /api/logEntries/{id}`
- `POST /api/logEntries`
- `PATCH /api/logEntries/{id}`

### Notes
- `GET /api/notes`
- `GET /api/notes/{id}`
- `POST /api/notes`
- `PATCH /api/notes/{id}`

### Long notes
- `GET /api/longNotes`
- `GET /api/longNotes/{id}`
- `POST /api/longNotes`
- `PATCH /api/longNotes/{id}`

### Profiles
- `GET /api/profiles`
- `GET /api/profiles/{id}`
- `POST /api/profiles`
- `PATCH /api/profiles/{id}`

## Field naming and mapping
Output API fields must be mapped explicitly. Do not use a blind `snake_case -> camelCase` conversion, because some legacy names are also domain-wise unclear or unsuitable.

### Example mappings
- `id_cqrlog_main` -> `id`
- `id_notes` -> `id`
- `id_long_note` -> `id`
- `id_profiles` -> `id`
- `time_on` -> `timeOn`
- `time_off` -> `timeOff`
- `qsodate` -> `qsoDate`
- `rst_s` -> `rstSent`
- `rst_r` -> `rstReceived`
- `qsl_s` -> `qslSent`
- `qsl_r` -> `qslReceived`
- `qsl_via` -> `qslVia`
- `my_loc` -> `myLocator`
- `qso_dxcc` -> `qsoDxcc`
- `club_nr1` -> `clubNumber1`
- `club_nr2` -> `clubNumber2`
- `club_nr3` -> `clubNumber3`
- `club_nr4` -> `clubNumber4`
- `club_nr5` -> `clubNumber5`
- `eqsl_qsl_sent` -> `eqslSent`
- `eqsl_qsl_rcvd` -> `eqslReceived`

Note: for `cqrlog_main`, the final mapping should be confirmed based on what the frontend really needs.

## Database notes

### `cqrlog_main`
Main log table. It is wide and contains many legacy fields. It should not be exposed in full before the API contract is properly defined.

### `notes`
Columns:
- `id_notes`
- `callsign`
- `longremarks`

### `long_note`
Columns:
- `id_long_note`
- `note`

### `profiles`
Columns:
- `id_profiles`
- `nr`
- `locator`
- `qth`
- `rig`
- `remarks`
- `visible`

## DTO rules
- Separate input and output DTOs
- Do not work with raw DB rows directly in controllers
- Perform input validation at the DTO/request layer
- Normalize nullable and default values before persisting

## Persistence rules
- Write SQL explicitly
- Do not create a generic repository layer
- Use a dedicated gateway per resource
- Allow inserts and updates only for selected fields
- Read-only tables must not expose write methods

## API behavior
- JSON responses
- Consistent error responses
- `id` on all resources
- The structure should feel similar to a well-designed API Platform API, but without depending on Doctrine

## Documentation requirements
- Every newly added endpoint must be documented immediately
- Every newly added endpoint must be added to both:
  - the human-readable API documentation
  - the machine-readable OpenAPI specification

## Testing requirements
- Write automated tests for API endpoints
- Cover both successful and error scenarios
- At minimum, each writable resource must have tests for:
  - list/detail read endpoints
  - create endpoint
  - update endpoint
  - validation failures
  - not found responses
- Read-only resources must have endpoint tests for the exposed read operations
- Tests should verify the public API contract, especially:
  - `camelCase` field names
  - unified `id`
  - correct status codes
  - request/response payload shape
- Endpoint tests must not assert raw legacy database column names in API responses

## Recommended implementation order
1. Add MariaDB to `docker-compose`
2. Make database connection fully configurable through environment variables
3. Support importing `schema.sql`
4. Connect Symfony to the database through Dibi
5. Implement the first reference resource:
   - recommended: `profiles` or `notes`
6. Establish the standard pattern for DTO, gateway, mapper, and controller
7. Implement `longNotes`
8. Implement `logEntries`
9. Add endpoint tests for each implemented resource
10. Add read-only endpoints for other tables only when the frontend actually needs them

## What not to do
- Do not expose raw database column names directly in the API
- Do not build a generic CRUD API over the entire database
- Do not use Doctrine ORM
- Do not mix HTTP logic and SQL queries
- Do not start implementation with `cqrlog_main` before the API contract is confirmed

## Immediate next step
Define the concrete JSON contract for these four writable resources:
- `profiles`
- `notes`
- `longNotes`
- `logEntries`

After the contract is approved, implement the first resource end-to-end as the reference pattern.
