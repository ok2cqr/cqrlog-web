# 2026-07-11 — Contest module

- **Branch:** master
- **Participants:** Petr (OK2CQR-style workflow) + Claude (Fable 5)
- **Commits:** `b73967d` … `dd8d470` (11 commits)

## Context

Project review requested; user picked a new feature: contest logging view
modeled on the desktop CQRLOG Contest window (screenshot reviewed), reduced
to what he actually uses. Primary device: **iPad Mini portrait (744 px)** —
always verify layout there first. Design spec:
`docs/superpowers/specs/2026-07-11-contest-module-design.md`.

## What was done

- Backend: `contestName` exact-match filter on `GET /api/logEntries`
  (TDD, 2 new PHPUnit tests, docs/api.md + openapi.yaml) — the only backend
  change; contest fields (`stx`/`srx`/`stxString`/`srxString`/`contestName`)
  already existed end-to-end.
- New Contest sidebar view in `App.tsx`: contest name (localStorage
  `cqrlog.contest`), fast row Call · NR s · MSG s · NR r · MSG r, NR s
  auto-increment toggle, sticky MSG s, Enter-to-save, list of the contest's
  QSOs, previous-QSOs panel as *manual* dupe check (reuses callsignContext).
- Editable Freq/Band/Mode/Pwr row shared with the entry form state
  (radio sync works, manual fallback when it doesn't).
- Keyboard parity: double-ESC / double-`\` clears per-QSO fields (keeps
  contest name, NR s, MSG s), arrows walk Call → NR s → NR r → MSG r.
- Saved contest QSOs are fully enriched (name/QTH/grid/state/county/award/
  QSL via/IOTA from callsignContext, WAZ/ITU/ADIF/continent from DXCC,
  club slots 1–5) — indistinguishable from normal QSOs in the log.
- Optional club-memberships card in contest view — Settings → Contest
  toggle, default off.
- Layout fixes: `min-width: 0` containment for the contest table
  (iPad blow-up), compact fixed-width fields, history-table empty state.

## Key decisions

- No programmatic dupe check — user checks visually via previous-QSOs
  panel (also covers his two-stage contest where dupes reset per stage).
- No RST fields in contest UI; values auto-filled (599 CW / 59 voice).
- Excluded: CQ automation, q/10–q/60 rate counters, desktop checkboxes.
- Enrichment loads on Call blur; save falls back to inline fetch
  (`Promise.allSettled`) — HamQTH failure must never block saving.
- Per-flow payload construction kept (no shared builder) — codebase
  convention.

## Open questions

- Test QSOs left in dev DB: `DELETE FROM cqrlog_main WHERE contestname =
  'TEST CONTEST 2026';` (IDs 75537–75543) — Claude may not delete from
  non-`_test` DBs.
- HANDOFF.md not yet updated with the contest module.

## Next steps

- User verifies on the physical iPad; possible polish follow-ups.
- Improvement backlog from the project review: QSO list search/filters,
  QSO delete, ADIF export, App.tsx decomposition, HamQTH proxy caching,
  structured logging, fail-closed auth.

## References

- Spec: `docs/superpowers/specs/2026-07-11-contest-module-design.md`
- Env quirk: `.env.local` overrides logins (`ok2cqr`/`ok2cqr`) and
  session idle timeout (now 3600 s); port 5173 is often taken by an
  unrelated container → Vite lands on 5174.
