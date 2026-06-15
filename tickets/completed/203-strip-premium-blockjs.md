# Ticket 203: Strip Premium block.js

**Sprint:** 2 — Lite Build Pipeline
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

The block editor includes Saved Toplist library UI, JSON import/export buttons, and linked-list mode that depend on premium PHP/REST. Lite must ship a simpler inspector: local pipe editing only, with no disabled premium controls (WP.org guideline 5).

## Goal

Lite `block.js` exposes block + local items editing only; library panel and JSON import/export are removed at build time, not hidden.

## Acceptance criteria

- [ ] Build removes or excludes Saved Toplist panel, Import JSON, Export JSON UI from lite `block.js`
- [ ] Lite block does not call library REST endpoints (`apiFetch` to toplist routes absent)
- [ ] Lite block attributes for linked mode are not exposed in UI (defaults safe for render)
- [ ] Block still registers as `toplist/rankings` (content compatibility with premium)
- [ ] Premium source `block.js` unchanged in behaviour — stripping only affects lite output
- [ ] Manual smoke: insert block in lite, edit pipes, save post, front-end renders

## Out of scope

- PHP stripping (ticket 202)
- Row spreadsheet UI (ticket 501)
- Rebuilding block with `@wordpress/scripts` if not already used

## Dependencies

- **Blocks:** 204
- **Blocked by:** 102, 201
- **External:** none

## Approach (optional)

Add `LITE_STRIP_MARKERS` or file-section deletes in build script mirroring solar-form JS transforms. Consider separate `block-lite.js` source if transforms become fragile.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
