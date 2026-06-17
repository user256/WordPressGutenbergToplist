# Ticket 712: Enqueue settings-page inline `<style>` / `<script>`

**Sprint:** 7 — WP.org submission compliance
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

The WP.org audit ([`docs/wporg-audit-toplist-block-lite-2026-06-16.md`](../docs/wporg-audit-toplist-block-lite-2026-06-16.md)) flagged raw inline `<style>` and `<script>` markup printed on the settings screen — in premium source at [`settings-page.php:290`](../toplist-block/settings-page.php#L290) (style) and [`settings-page.php:625`](../toplist-block/settings-page.php#L625) (script). wp.org expects admin assets to be enqueued, not echoed.

## Goal

The settings-page CSS and JS are delivered through `wp_add_inline_style()` / `wp_add_inline_script()` attached to a handle enqueued only on the Toplist Block settings screen.

## Acceptance criteria

- [ ] Settings-page CSS attached via `wp_add_inline_style()` to an admin style handle enqueued on the settings hook (`admin_enqueue_scripts`, gated to the Toplist settings screen).
- [ ] Settings-page JS attached via `wp_add_inline_script()` to an admin script handle, same gating.
- [ ] No raw `<style>` / `<script>` echo remains in `settings-page.php`.
- [ ] Settings screen behaviour unchanged (manual check); `composer check` stays green.

## Out of scope

- Front-end render CSS (see [711](711-frontend-css-enqueue.md)).
- The lite upgrade-notice script (see [713](713-lite-upgrade-notice-enqueue.md)).

## Dependencies

- **Blocks:** 799
- **Blocked by:** none

## Approach (optional)

Register the handles on `admin_enqueue_scripts` and bail unless `$hook` matches the Toplist settings page so the assets don't load globally. Move the literal CSS/JS bodies into the inline-asset calls verbatim first; tidy afterwards.

## Notes / decisions log

- 2026-06-17 — Filed from WP.org audit (2026-06-16), blocking items (settings inline style + script).

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-up work filed as new tickets, not absorbed.
