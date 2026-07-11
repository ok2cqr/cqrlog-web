# Contest Module — Design

Date: 2026-07-11
Status: approved

## Purpose

A contest (závody) logging view modeled on the desktop CQRLOG Contest window, reduced
to the parts actually used. Fast QSO entry during a contest: callsign, RST, serial
numbers, exchange — with minimal typing between QSOs.

## Scope

Included:

- Contest name (free text, persisted in `localStorage`), saved to `contestName`.
- Fast-entry row: Call, RST s, NR s, MSG s, RST r, NR r, MSG r.
  - NR s → `stx`, NR r → `srx`, MSG s → `stxString`, MSG r → `srxString`.
- NR s has an **auto +1** toggle: on = increments after each saved QSO (zero-padded,
  `001` → `002`); off = stays constant (contests exchanging a fixed number, e.g.
  WAZ/ITU zone). Always manually editable. Survives page reload.
- MSG s is sticky between QSOs (fixed exchange). After save only Call, RST r,
  NR r and MSG r are cleared.
- Manual dupe check: after leaving the Call field, recent QSOs with that callsign
  are shown below the form (reuses the existing `callsignContext` endpoint). No
  programmatic dupe detection — the operator decides visually. This also covers
  multi-stage contests where dupes reset per stage.
- List of QSOs logged in the current contest (filtered by `contestName`),
  newest first — the desktop "Status" area equivalent.
- Band/mode/frequency/power are shown read-only and follow the main entry form
  state (including radio sync).

Excluded (owner's decision):

- CQ automation (CQ period / repeats / start — needs a keyer, not applicable to web).
- Programmatic dupe checking, q/10 and q/60 rate counters.
- Desktop checkboxes: SPACE is TAB, Tru, Inc, Qsp, S&P, No, MSG is Grid.

## Architecture

- **UI**: new `Contest` view in the sidebar (`viewMode: 'contest'`), a stripped
  single-form view inside `frontend/src/App.tsx`, following existing state and
  effect conventions (status-discriminated unions, debounce + keyRef race guards).
- **Save**: existing `POST /api/logEntries` — the backend already validates and
  maps `stx`/`srx`/`stxString`/`srxString`/`contestName` to legacy columns.
  Desktop CQRLOG reads the same `cqrlog_main` table, so contest QSOs stay fully
  compatible.
- **Backend change (only one)**: `contestName` exact-match filter on
  `GET /api/logEntries` (`LogEntryListQuery` + `LogEntryGateway::buildListFilters`),
  used to populate the contest QSO list. Covered by PHPUnit tests, documented in
  `docs/api.md` and `docs/openapi.yaml`.
- **Persistence**: `localStorage` key `cqrlog.contest` holding
  `{ contestName, serialSent, autoIncrement }`.

## Error handling

Same as the main form: submit errors surface as an inline message; the contest
QSO list and callsign lookup have their own `idle/loading/ready/error` states.

## Testing

- Backend: PHPUnit tests for the `contestName` filter (match, AND-composition with
  `callsign`, 422 on >40 chars) via `make test` (Docker, `cqrlog002_test`).
- Frontend: `npm run build` (type-level), plus a manual end-to-end flow
  (save → sticky/auto-increment behavior → reload persistence → list filter).
