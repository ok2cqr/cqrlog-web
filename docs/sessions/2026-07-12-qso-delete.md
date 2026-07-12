# 2026-07-12 — QSO delete

- **Branch:** master
- **Participants:** Petr + Claude (Fable 5)
- **Commits:** `996d8e6` … `95e3b45` (spec, plan, backend, frontend)

## Context

Picked from the improvement backlog after the contest-module session
(2026-07-11 log). QSOs could not be removed from the web UI — DELETE existed
only for profiles. Spec: `docs/superpowers/specs/2026-07-12-qso-delete-design.md`;
plan: `docs/superpowers/plans/2026-07-12-qso-delete.md`. Executed via
subagent-driven development (implementer + reviewer per task, final review).

## What was done

- `DELETE /api/logEntries/{id}` (204 / 404 `not_found`), mirroring the
  profiles delete pattern; TDD, suite 57/57; api.md + openapi.yaml updated.
- Delete button in the Edit QSO dialog: left-aligned `button--danger`,
  `window.confirm` with id + callsign, success closes dialog + reloads list
  + "Deleted QSO #<id>." feedback; failure keeps dialog open with the error.
- Browser-verified (incl. 744 px): cancel keeps the QSO, confirm removes it,
  double-delete → 404.
- Cleanup: Claude's five TEST CONTEST 2026 QSOs deleted via the new endpoint.

## Key decisions

- No `log_changes` bookkeeping — web create/update already skip it; desktop
  online-log sync stays desktop's concern (delete there if sync matters).
- Delete lives only in the edit dialog (not per list row) — deliberate
  tap-safety choice for tablet use.
- Existence check before delete (two round trips) kept — mirrors profiles.

## Open questions

- Petr's own test QSO OK7WA (#75540, TEST CONTEST 2026) still in dev DB —
  he can now delete it in-app.
- Pre-existing doc gap: `DELETE /api/profiles/{id}` was never documented.

## Next steps

- Backlog: QSO list search/filters, ADIF export, App.tsx decomposition,
  HamQTH proxy caching, structured logging, fail-closed auth.

## References

- Prior session: `docs/sessions/2026-07-11-contest-module.md`
- SDD ledger (git-ignored scratch): `.superpowers/sdd/progress.md`
