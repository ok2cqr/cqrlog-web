# Contest Call: Space Jumps to NR r — Design

Date: 2026-07-12
Status: approved

## Purpose

Contest operators expect Space in the callsign field to advance to the
exchange (N1MM-style). Space never appears in a callsign, so in the
contest Call field it should behave exactly like Tab / arrow ↓: jump to
NR r without typing anything.

## Design

- Extend `handleContestArrowNavigation` in `App.tsx`: an unmodified
  Space press is accepted only for the `callsign` field and treated as
  "move down" — `preventDefault()` (no space is typed), focus + select
  the next field in `CONTEST_ARROW_NAV_ORDER` (NR r).
- All other contest fields keep Space as a normal character (MSG r/MSG s
  may legitimately contain spaces).
- The regular QSO entry view is unchanged.

## Testing

- `npm run build` + manual: Space in contest Call → focus lands in NR r,
  no space typed; Space in MSG r still types a space; arrows/Tab flows
  from the previous change unchanged.
