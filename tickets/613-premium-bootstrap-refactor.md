# Ticket 613: Premium Bootstrap Architecture Refactor

**Sprint:** 6 — Post-Launch Expansion
**Status:** Proposed
**Owner:** unassigned
**Estimate:** M

---

## Context

Currently, the Toplist Block uses a "Solar Pattern" where Premium and Lite code coexist in core files like `toplist-block.php` and `settings-page.php`. The Lite build script (`build-lite.php`) uses regex to strip code between `// @toplist-premium-start` and `// @toplist-premium-end` markers. Relying heavily on inline regex stripping for complex logic is prone to human error, which could risk leaking premium features into the WP.org Lite distribution. 

To improve maintainability and strictness, we need to minimize inline comment markers and move all premium-specific feature logic into dedicated files that the build script can simply delete entirely.

## Goal

Refactor the codebase to use a strict "Bootstrap Pattern", moving premium logic (like CSV/JSON import, premium REST API routes, and advanced metaboxes) into a dedicated `includes/pro/` directory, and loading it via a single inline marker block.

## Acceptance criteria

- [ ] Create an `includes/pro/` directory to house premium-only logic.
- [ ] Move premium functionality out of `toplist-block.php` and `settings-page.php` into structured classes/files inside `includes/pro/`.
- [ ] Update `toplist-block.php` to include a single bootstrap marker:
      ```php
      // @toplist-premium-start
      require_once TOPLIST_BLOCK_PATH . '/includes/pro/bootstrap.php';
      // @toplist-premium-end
      ```
- [ ] Update `scripts/build-lite.php` to add `includes/pro/` to the `TOPLIST_PREMIUM_DELETE_FILES` array to ensure the entire folder is physically deleted during the Lite build.
- [ ] Run `php scripts/build-lite.php` and verify the Lite build smoke checks pass.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
