# Contest MSG s / MSG r Uppercase — Design

Date: 2026-07-12
Status: approved

## Purpose

Contest exchange messages are conventionally uppercase and often contain
numbers (serials, district codes). MSG s and MSG r should uppercase as
you type and transliterate the Czech number row (ě→2, š→3, …) so the
operator never switches keyboard layout mid-contest.

## Design

- Both contest inputs MSG s and MSG r change their `onChange` to the
  established pattern used by Grid/QSL Via/IOTA:
  `updateContestField(field, normalizeCzechNumberRow(event.target.value).toUpperCase())`.
- No other field or view changes; backend unchanged (values arrive
  already uppercase).

## Testing

- `npm run build` + manual: lowercase + Czech number row typed into
  MSG s / MSG r appears uppercase with digits.
