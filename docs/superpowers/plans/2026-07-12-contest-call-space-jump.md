# Contest Call Space Jump Implementation Plan

> **For agentic workers:** Single-task plan, executed inline per user request.

**Goal:** Space in the contest Call field jumps to NR r (like arrow ↓/Tab) without typing a space.

**Spec:** `docs/superpowers/specs/2026-07-12-contest-call-space-jump-design.md`

### Task 1: Accept Space as "move down" from callsign

**Files:**
- Modify: `frontend/src/App.tsx` (`handleContestArrowNavigation`, ~line 1976)

- [ ] **Step 1: Extend the key filter and direction logic**

Change:

```tsx
      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
        return;
      }
```

to:

```tsx
      const isCallsignSpace = event.key === ' ' && field === 'callsign';

      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && !isCallsignSpace) {
        return;
      }
```

and change:

```tsx
      const nextIndex = event.key === 'ArrowDown' ? currentIndex + 1 : currentIndex - 1;
```

to:

```tsx
      const nextIndex = event.key === 'ArrowUp' ? currentIndex - 1 : currentIndex + 1;
```

(Space falls into the "move down" branch; the existing `preventDefault()` + focus/select code path is reused unchanged.)

- [ ] **Step 2: Build**

Run: `cd /Users/petr/Projects/private/con/frontend && npm run build`
Expected: passes with no TypeScript errors.

- [ ] **Step 3: Manual verification**

Contest view: Space in Call → focus in NR r, no space in Call; Space in
MSG r types a space; ↓/↑/Tab/Shift+Tab unchanged.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/App.tsx
git commit -m "feat: space in contest Call jumps to NR r"
```
