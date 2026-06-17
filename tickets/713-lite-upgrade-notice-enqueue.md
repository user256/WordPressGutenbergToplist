# Ticket 713: Move lite upgrade-notice `<script>` to an enqueued/inline handle

**Sprint:** 7 — WP.org submission compliance
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

The WP.org audit ([`docs/wporg-audit-toplist-block-lite-2026-06-16.md`](../docs/wporg-audit-toplist-block-lite-2026-06-16.md)) flagged a raw inline `<script>` for dismissing the upgrade notice in the lite build. This notice is **lite-only** — it is emitted by the build script (`scripts/build-lite.php`, `toplist_lite_upgrade_notice_php()` around line 431), so the fix lives in the build script's generated PHP, not in premium source.

## Goal

The lite upgrade-notice dismissal JS is delivered via `wp_add_inline_script()` on a registered admin handle rather than a raw `<script>` echo.

## Acceptance criteria

- [ ] The generated upgrade-notice PHP attaches its dismissal JS via `wp_add_inline_script()` (admin handle), no raw `<script>` echo.
- [ ] `php scripts/build-lite.php` produces a lite tree whose upgrade notice still dismisses correctly (manual check) and contains no inline `<script>` for the notice.
- [ ] Existing nonce/capability checks on dismissal are preserved.
- [ ] `composer test:build` and `composer check` stay green.

## Out of scope

- The upgrade URL itself (see [710](710-lite-upgrade-url.md)).
- Premium-source inline assets (see [711](711-frontend-css-enqueue.md), [712](712-settings-page-enqueue.md)).

## Dependencies

- **Blocks:** 799
- **Blocked by:** none

## Approach (optional)

Edit the heredoc/string in `toplist_lite_upgrade_notice_php()` so the emitted PHP registers an admin handle and uses `wp_add_inline_script()`. Keep the AJAX/nonce dismissal flow identical.

## Notes / decisions log

- 2026-06-17 — Filed from WP.org audit (2026-06-16), blocking item (upgrade-notice inline script). Build-script change, not premium source.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-up work filed as new tickets, not absorbed.
