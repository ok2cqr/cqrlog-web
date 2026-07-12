# Global Backslash Suppression Implementation Plan

> **For agentic workers:** Single-task plan, executed inline per user request.

**Goal:** The `\` key never types a character into any field; the 2×`\` clear shortcut keeps working.

**Spec:** `docs/superpowers/specs/2026-07-12-backslash-suppression-design.md`

### Task 1: Suppress `\` globally

**Files:**
- Modify: `frontend/src/App.tsx` (new `useEffect` near the existing double-shortcut effect at ~line 1071)

- [ ] **Step 1: Add the always-on capture listener**

Insert directly above the existing double-shortcut `useEffect` (line ~1071):

```tsx
  useEffect(() => {
    const suppressBackslash = (event: KeyboardEvent) => {
      if (event.key === '\\' && !event.altKey && !event.ctrlKey && !event.metaKey) {
        event.preventDefault();
      }
    };

    window.addEventListener('keydown', suppressBackslash, true);

    return () => {
      window.removeEventListener('keydown', suppressBackslash, true);
    };
  }, []);
```

- [ ] **Step 2: Build**

Run: `cd /Users/petr/Projects/private/con/frontend && npm run build`
Expected: passes with no TypeScript errors.

- [ ] **Step 3: Manual verification**

In the contest view: single `\` in NR s (and Call) types nothing; 2×`\`
clears the form; 2×ESC still clears; `\` in the edit dialog and settings
types nothing.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/App.tsx
git commit -m "fix: backslash key never types into fields, 2x\\ clear kept"
```
