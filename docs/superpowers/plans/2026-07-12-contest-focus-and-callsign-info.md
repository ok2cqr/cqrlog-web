# Contest Focus Skip + Callsign Info Strip Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** In the contest view, keyboard navigation from Call lands directly in NR r (skipping the sent fields), and a slim strip under the entry row shows the operator's name and DXCC info after the callsign is entered.

**Architecture:** Frontend-only change inside the single-component app (`frontend/src/App.tsx`) plus one CSS class. Nav change edits the existing `CONTEST_ARROW_NAV_ORDER` constant and adds `tabIndex={-1}` to two inputs. The info strip renders from the existing `contestLookup` state (populated on Call blur) — no new fetches, no backend changes.

**Tech Stack:** React 18 + TypeScript + Vite. No frontend test framework exists in this project — verification is `npm run build` (runs `tsc -b`) plus manual browser checks, matching established project practice.

**Spec:** `docs/superpowers/specs/2026-07-12-contest-focus-and-callsign-info-design.md`

## Global Constraints

- All commands run from `frontend/`: `cd /Users/petr/Projects/private/con/frontend`.
- Verify layout on iPad Mini portrait (744×1133) before claiming done — compact single-row contest entry must not break.
- Dev stack for manual checks: backend `make dev` (port 4000), frontend `npm run dev` (port 5173).

---

### Task 1: Keyboard navigation skips NR s and MSG s

**Files:**
- Modify: `frontend/src/App.tsx:296-301` (`CONTEST_ARROW_NAV_ORDER`)
- Modify: `frontend/src/App.tsx:3426-3446` (NR s and MSG s inputs)

**Interfaces:**
- Consumes: existing `ContestArrowField` type and `handleContestArrowNavigation` (unchanged).
- Produces: nothing new — behavior change only.

- [ ] **Step 1: Remove `serialSent` from the arrow order**

In `frontend/src/App.tsx`, change:

```tsx
const CONTEST_ARROW_NAV_ORDER: ContestArrowField[] = [
  'callsign',
  'serialSent',
  'serialReceived',
  'msgReceived',
];
```

to:

```tsx
const CONTEST_ARROW_NAV_ORDER: ContestArrowField[] = [
  'callsign',
  'serialReceived',
  'msgReceived',
];
```

Do NOT change the `ContestArrowField` type — `serialSent` stays a valid ref key (`handleContestArrowNavigation('serialSent')` returns early via `indexOf === -1`, which is fine).

- [ ] **Step 2: Take NR s and MSG s out of the tab order**

In the contest entry section (`grid--contest-entry`), add `tabIndex={-1}` to both sent-field inputs:

```tsx
            <label className="field">
              <span>NR s</span>
              <input
                ref={(element) => setContestArrowFieldRef('serialSent', element)}
                onKeyDown={handleContestArrowNavigation('serialSent')}
                value={contestForm.serialSent}
                onChange={(event) => updateContestField('serialSent', normalizeCzechNumberRow(event.target.value))}
                maxLength={6}
                tabIndex={-1}
              />
            </label>

            <label className="field">
              <span>MSG s</span>
              <input
                ref={(element) => setContestArrowFieldRef('msgSent', element)}
                onKeyDown={handleContestArrowNavigation('msgSent')}
                value={contestForm.msgSent}
                onChange={(event) => updateContestField('msgSent', event.target.value)}
                maxLength={50}
                tabIndex={-1}
              />
            </label>
```

Only the `tabIndex={-1}` lines are new; everything else stays as-is.

- [ ] **Step 3: Type-check and build**

Run: `cd /Users/petr/Projects/private/con/frontend && npm run build`
Expected: build succeeds with no TypeScript errors.

- [ ] **Step 4: Manual verification in the browser**

With `make dev` and `npm run dev` running, open http://localhost:5173, switch to the Contest view, and confirm:
1. Focus in Call, press ↓ → focus lands in NR r (content selected).
2. In NR r, press ↑ → focus returns to Call.
3. Focus in Call, press Tab → focus lands in NR r; Shift+Tab from NR r → back to Call.
4. Tab from NR r → MSG r.
5. Click/tap into NR s and MSG s → still editable.

- [ ] **Step 5: Commit**

```bash
cd /Users/petr/Projects/private/con
git add frontend/src/App.tsx
git commit -m "feat: contest keyboard nav skips NR s and MSG s"
```

---

### Task 2: Name + DXCC info strip under the entry row

**Files:**
- Modify: `frontend/src/App.tsx` (derived value near `isContestView` at ~line 2700; JSX after the `grid--contest-entry` section closing tag at ~line 3469)
- Modify: `frontend/src/styles.css` (new `.contest-callsign-info` class + dark-mode selector at ~line 1124)

**Interfaces:**
- Consumes: `contestLookup: ContestLookupState` — uses `autofill.name` (`string | null`) and `dxccData` (`DxccData | null` with `details`, `name`, `continent`, `waz`, `itu`).
- Produces: derived `const contestCallsignInfo: string` (empty string = render nothing).

- [ ] **Step 1: Build the info string**

In `frontend/src/App.tsx`, directly below `const isContestView = viewMode === 'contest';` (~line 2700), add:

```tsx
  const contestDxccParts: string[] = [];

  if (contestLookup.dxccData) {
    const country = contestLookup.dxccData.details ?? contestLookup.dxccData.name;

    if (country) {
      contestDxccParts.push(
        contestLookup.dxccData.continent ? `${country} (${contestLookup.dxccData.continent})` : country,
      );
    }

    if (contestLookup.dxccData.waz) {
      contestDxccParts.push(`WAZ ${contestLookup.dxccData.waz}`);
    }

    if (contestLookup.dxccData.itu) {
      contestDxccParts.push(`ITU ${contestLookup.dxccData.itu}`);
    }
  }

  const contestCallsignInfo = [contestLookup.autofill?.name ?? '', contestDxccParts.join(' | ')]
    .filter((part) => part !== '')
    .join(' — ');
```

- [ ] **Step 2: Render the strip**

In the contest form JSX, immediately after the closing `</section>` of the `grid--contest-entry` section (before the `contestSubmitState.message` paragraph), add:

```tsx
          {contestCallsignInfo !== '' ? (
            <p className="contest-callsign-info">{contestCallsignInfo}</p>
          ) : null}
```

Rendering rules this satisfies (from the spec): name only from history, DXCC only from HamQTH, either alone is fine, neither → no element at all (no placeholder, no loading state).

- [ ] **Step 3: Style the strip**

In `frontend/src/styles.css`, after the `.topbar__subtle` rule (~line 188), add:

```css
.contest-callsign-info {
  margin: 0.1rem 0 0;
  color: #6a8098;
  font-size: 0.88rem;
}
```

Then add the class to the existing dark-mode muted-text selector group (~line 1124), keeping alphabetical-ish placement unimportant — just append one selector line:

```css
:root[data-theme='dark'] .topbar__subtle,
:root[data-theme='dark'] .contest-callsign-info,
:root[data-theme='dark'] .field span,
```

(The rest of that selector group stays unchanged.)

- [ ] **Step 4: Type-check and build**

Run: `cd /Users/petr/Projects/private/con/frontend && npm run build`
Expected: build succeeds with no TypeScript errors.

- [ ] **Step 5: Manual verification in the browser**

In the Contest view at http://localhost:5173:
1. Type a callsign with log history (pick one from the QSO list), leave the field (↓ or Tab) → within ~1 s the strip shows `Name — Country (Continent) | WAZ n | ITU n`.
2. Type a callsign with no history but valid DXCC → strip shows only the DXCC part.
3. Clear/edit the callsign → strip disappears immediately.
4. Dark mode (Settings) → strip text is muted, readable.
5. iPad Mini portrait 744×1133 (Chrome DevTools device emulation) → entry row still a single row, strip doesn't break layout.

- [ ] **Step 6: Commit**

```bash
cd /Users/petr/Projects/private/con
git add frontend/src/App.tsx frontend/src/styles.css
git commit -m "feat: name and DXCC info strip in contest view"
```
