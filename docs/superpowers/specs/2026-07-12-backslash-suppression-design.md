# Global Backslash Suppression — Design

Date: 2026-07-12
Status: approved

## Purpose

The 2×`\` clear-form shortcut only calls `preventDefault()` on the second
press, so the first `\` is typed into the focused field. In NR s the
character even survives the clear (NR s is sticky by design). `\` never
legitimately appears in any field of this app, so the key should be
ignored everywhere.

## Design

- New always-mounted `useEffect` in `App.tsx`: a window `keydown`
  listener in the capture phase that calls `event.preventDefault()` when
  `event.key === '\\'` and no Ctrl/Alt/Meta modifier is active.
- Effect: `\` can never be typed into any input anywhere in the app
  (entry, contest, dialogs, settings, login).
- `preventDefault()` does not stop propagation, so the existing 2×`\`
  clear shortcut (`App.tsx:1103`) keeps working unchanged.
- Paste containing `\` is out of scope (not a real input path here).

## Testing

- `npm run build` + manual: press `\` once in NR s (and other fields) →
  nothing typed; press 2×`\` → form clears as before; 2×ESC unchanged.
