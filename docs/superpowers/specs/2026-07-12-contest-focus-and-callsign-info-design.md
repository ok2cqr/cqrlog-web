# Contest: Call → NR r Focus Skip + Callsign Name/DXCC Strip — Design

Date: 2026-07-12
Status: approved

## Purpose

Two contest-view refinements from live contest testing:

1. Leaving the Call field must land directly in **NR r**. The sent fields
   (**NR s** — auto-incremented serial, **MSG s** — sticky message) almost
   never need manual edits mid-contest, so they should be skipped by
   keyboard navigation.
2. After entering a callsign and leaving the field, show the operator's
   name (if known from log history) and DXCC info — currently the data is
   fetched but never displayed in the contest view.

## Design

### 1. Keyboard navigation skips NR s and MSG s

- `CONTEST_ARROW_NAV_ORDER` drops `serialSent` → order becomes
  `callsign → serialReceived → msgReceived`. Arrow ↓ from Call lands in
  NR r; arrow ↑ from NR r returns to Call.
- The NR s and MSG s inputs get `tabIndex={-1}` — they leave the tab
  order in both directions (Tab from Call → NR r, Shift+Tab from
  NR r → Call). Both stay fully editable by tap/click.
- No behavior change to NR s auto-increment or sticky MSG s.

### 2. Name + DXCC strip under the entry row

- New slim info strip rendered directly below the Call/NR/MSG entry row
  (`grid--contest-entry` section).
- Data source: existing `contestLookup` state, populated on Call blur
  with a 250 ms debounce — `autofill.name` (log history) and `dxccData`
  (HamQTH). No new fetches.
- Format matches the entry-view topbar:
  `Petr — Czech Republic (EU) | WAZ 17 | ITU 28`
  (`dxccData.details ?? dxccData.name`, continent, WAZ, ITU).
- No name in history → DXCC part only. Neither available → strip is not
  rendered at all (no empty space, no loading placeholder).
- Callsign edits already reset the lookup state, which hides the strip.

## Testing

- `npm run build` + manual flow in the browser: type a callsign, leave
  the field with ↓/Tab, confirm focus lands in NR r and the strip shows
  name + DXCC; Shift+Tab returns to Call; NR s / MSG s editable by click.
- Verify iPad Mini portrait (744×1133) — the strip must not break the
  compact single-row entry layout.
