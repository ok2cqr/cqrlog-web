# 2026-07-25 — Project audit + 8-WP improvement batch

- **Branch:** master (merged from `improvements/2026-07`, fast-forward)
- **Participants:** Petr + Claude (Fable 5)
- **Commits:** `e904595` … `ddf68ed` (17 commits, base `9d4b86f`)

## Context

Petr asked for a full-project audit with improvement proposals; his one
concrete wish was a collapsible left sidebar. Four parallel audits
(frontend, backend, tests/QA, infra/docs) fed an approved 8-work-package
plan, executed via subagent-driven development (per-task review + final
whole-branch review). Plan archive: `~/.claude/plans/recursive-swinging-pike.md`;
per-task ledger: `.superpowers/sdd/progress.md`.

## What was done

- **Security hotfix**: `.dockerignore` now excludes `.env*`/`*.sql`/docs
  (prod creds were baked into the image); Dockerfile `.env` stub; session
  id rotation on login; auth fails closed (503 `auth_not_configured`);
  DEPLOY.md secret-rotation checklist (`e904595`…`5cfd9ef`).
- **UTC clock**: entry-form date/time formatters → `getUTC*` + UTC labels;
  no data migration (Petr's call) (`107f060`).
- **QA infra**: GitHub Actions (backend MariaDB job w/ hosts-alias for
  forced `DB_HOST=db`; frontend lint/typecheck/build), PHPStan L6
  (+baseline), ESLint flat config (`d86eeb3`, `897d472`, `5fab714`).
- **Collapsible sidebar** (hide completely, persisted, aria, ≤700px
  untouched) (`a4393d7`).
- **Perf**: clock + pollers gated on viewMode/visibility; setForm identity
  bail-out (1 re-render/min vs 1/s) (`2d844aa`).
- **Persistence**: viewMode + entry draft (TTL 24 h, defensive parse)
  (`9035dc7`).
- **ConfirmDialog + toasts** replacing `window.confirm`/inline feedback
  (`f847d3b`, `0dc2f08`).
- **QSO list**: debounced callsign filter, date range, sortable headers,
  ARIA table roles (`af47682`, `2e14445`).
- Final review caught 3 cross-task bugs (auth-ungated effects w/ persisted
  viewMode; mount-time RST clobber of restored drafts; missing mariadb
  client in CI) → fixed (`1b22d22`, `ddf68ed`).

## Key decisions

- QSO times are UTC **going forward**; historic rows left as-is.
- Auth is fail-closed; DEPLOY.md no longer documents LOGIN-less deploys.
- Sidebar collapse = full hide (reclaim 60 px), not labeled drawer.
- Backend dedup refactor (~25 % of src/), HamQTH caching, monolog, docs
  drift deliberately deferred — recorded in plan's "Mimo rozsah" section.

## Open questions / follow-ups (triaged non-blocking)

- `status()` still reports `authRequired:false` when auth unconfigured;
  frontend treats 503 as generic error (plan-mandated deferral).
- Toast host should be permanently mounted (aria-live reliability);
  `**/*.sql` in dockerignore; CI runs 2× on PR branches; UTC hint on Date
  field. Full list in `.superpowers/sdd/progress.md`.
- Any `FormState` shape change must bump `cqrlog.entryDraft.v1` → `.v2`.

## Next steps

1. **Push master** — first push runs the new CI for the first time; watch it.
2. **Operator checklist (manual, important)**: rotate prod DB password +
   `LOGIN_PASSWORD`, generate real `APP_SECRET`, purge all previously
   built images (they contain `.env.prod` in layers) — see DEPLOY.md
   "Security checklist".
3. Next batch candidates: backend dedup + HamQTH cache + monolog.
