# QSO Delete — Design

Date: 2026-07-12
Status: approved

## Purpose

Allow deleting a logged QSO from the web UI. Today only profiles support
DELETE; a mistyped QSO cannot be removed without touching the DB directly.

## Design

### Backend

- New endpoint `DELETE /api/logEntries/{id}`:
  - existing entry → delete the `cqrlog_main` row, return `204 No Content`;
  - unknown id → `404` with the standard `not_found` error shape.
- Implemented following the profiles pattern: `LogEntryGateway::delete()`
  (Dibi delete) + a `delete` route/action in `LogEntryController` that
  checks existence first (reuse the detail fetch).
- **No `log_changes` bookkeeping.** The web API already skips it on create
  and update; desktop-driven online-log sync is out of scope.
- Tests in `LogEntryControllerTest`: delete returns 204 and the entry is
  gone from list/detail; deleting an unknown id returns 404.
- Document in `docs/api.md` and `docs/openapi.yaml`.

### Frontend

- `deleteLogEntry(id)` in `api.ts` (mirrors `deleteProfile`).
- Edit QSO dialog gets a **Delete** button, visually separated from
  Save/Cancel (left side, danger styling) so it cannot be hit by mistake
  on a tablet.
- Click → `window.confirm("Delete QSO #<id> (<callsign>)?")` — the same
  confirmation pattern the entry form uses for Clear.
- On success: close the dialog, bump `qsoListReloadKey`, show
  "Deleted QSO #<id>." via the existing `qsoListFeedback` strip.
- On failure: keep the dialog open and show the error in the dialog
  message area (same as save failures).

## Testing

- Backend: `make test` (new PHPUnit cases above).
- Frontend: `npm run build` + manual flow in the browser (delete an entry,
  list refreshes without it; cancel keeps it; 404 path via double delete).
