# Contest MSG Uppercase Implementation Plan

> **For agentic workers:** Single-task plan, executed inline per user request.

**Goal:** MSG s and MSG r uppercase as you type, with Czech number-row transliteration.

**Spec:** `docs/superpowers/specs/2026-07-12-contest-msg-uppercase-design.md`

### Task 1: Uppercase + transliterate both MSG inputs

**Files:**
- Modify: `frontend/src/App.tsx` (MSG s input ~line 3477, MSG r input ~line 3500)

- [ ] **Step 1: Change both onChange handlers**

MSG s:

```tsx
onChange={(event) => updateContestField('msgSent', normalizeCzechNumberRow(event.target.value).toUpperCase())}
```

MSG r:

```tsx
onChange={(event) => updateContestField('msgReceived', normalizeCzechNumberRow(event.target.value).toUpperCase())}
```

- [ ] **Step 2: Build**

Run: `cd /Users/petr/Projects/private/con/frontend && npm run build`
Expected: passes with no TypeScript errors.

- [ ] **Step 3: Manual verification**

Contest view: type lowercase + Czech number-row characters into MSG s
and MSG r → uppercase letters and digits appear.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/App.tsx
git commit -m "feat: contest MSG fields uppercase with Czech number-row input"
```
