# Ticket 202: Strip Premium PHP

**Sprint:** 2 — Lite Build Pipeline
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

The lite build must not register `toplist_list` CPT, library REST routes, import/export handlers, or license code. Solar-form deletes entire files and removes premium hooks in `build-free.php` rather than shipping dead `if (false)` gates.

## Goal

After `build-lite.php` runs, the lite tree contains only block render, settings, and local-item editing — no library or import PHP.

## Acceptance criteria

- [ ] Build deletes files listed in `docs/free-vs-premium.md` `PREMIUM_FILES`
- [ ] Lite does not register post type `toplist_list` (`register_post_type` absent or stripped)
- [ ] Lite does not register library REST routes or admin import handlers
- [ ] `toplist_render()` lite path ignores `savedToplistId` / linked mode (local `items` only)
- [ ] Activating lite plugin on WP does not show Toplists admin menu
- [ ] Grep lite tree for `toplist_list`, `toplist_handle_import`, `license` returns zero hits in runtime code (allow upgrade CTA strings only per ticket 205)

## Out of scope

- block.js stripping (ticket 203)
- License class implementation (ticket 301 — file deleted in lite, not created)
- Automated smoke tests (ticket 204)

## Dependencies

- **Blocks:** 204
- **Blocked by:** 102, 201
- **External:** none

## Approach (optional)

Wire `PREMIUM_FILES` from docs into build script. Use regex or AST-light transforms for conditional registration blocks if needed. Prefer deletion over commenting.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
