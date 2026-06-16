# Ticket 614: Fix Dead Code (Missing Hook Initialization in Bootstrap)

**Sprint:** 6 — Post-Launch Expansion (Bugfix)
**Status:** Proposed
**Owner:** unassigned
**Estimate:** XS

---

## Context

During the partial rollout of Ticket 613 (Premium Bootstrap Architecture Refactor), `editor-ux.php` and `api-sync.php` were moved into `includes/pro/` and successfully required inside `Toplist_Block_Pro_Bootstrap::init()`.

However, the initialization functions (`toplist_register_editor_ux_hooks()` and `toplist_register_api_sync_hooks()`) inside those files are **never actually called**. Because these hooks are never registered, all of the features delivered in Ticket 610 (Live Preview) and Ticket 611 (API Population) are currently dead code and will not execute in the admin or REST API.

## Goal

Add the missing initialization calls to `Toplist_Block_Pro_Bootstrap::init()` so the newly moved premium modules actually execute.

## Acceptance criteria

- [ ] In `includes/pro/bootstrap.php`, add `toplist_register_editor_ux_hooks();` after `require_once __DIR__ . '/editor-ux.php';`.
- [ ] Add `toplist_register_api_sync_hooks();` after `require_once __DIR__ . '/api-sync.php';`.
- [ ] Verify that the Live Preview metabox and the `POST /toplist-block/v1/sync/{id}` endpoints actually work after the fix.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
