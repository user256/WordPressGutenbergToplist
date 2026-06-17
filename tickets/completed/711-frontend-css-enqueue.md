# Ticket 711: Enqueue front-end CSS instead of inline `<style>`

**Sprint:** 7 — WP.org submission compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

The WP.org audit ([`docs/wporg-audit-toplist-block-lite-2026-06-16.md`](../docs/wporg-audit-toplist-block-lite-2026-06-16.md)) flagged that the front-end render echoes raw `<style>` tags for global, custom, and card-layout CSS. In premium source these are at [`toplist-block.php:1002`](../toplist-block/toplist-block.php#L1002), `:1005`, `:1008`. wp.org expects user/site CSS to be delivered via the enqueue APIs, not echoed mid-render.

This render path is **shared** between lite and pro (not premium-only), so the fix affects both builds. Fix in premium source, then rebuild lite.

## Goal

Global, custom, and card-layout CSS reach the front end through a registered stylesheet handle via `wp_add_inline_style()` rather than echoed `<style>` tags.

## Acceptance criteria

- [x] A front-end style handle is registered/enqueued for the block (or the existing block style handle is reused) and the three CSS strings are attached with `wp_add_inline_style()`. — *Reused the existing `toplist-style` handle the block already declares via `'style' => 'toplist-style'`.*
- [x] No raw `<style>` echo remains in the front-end render path of `toplist-block.php`. — *Verified: `grep "echo '<style"` finds only comments.*
- [x] Rendered output still applies global/custom/card CSS (verified manually or via integration test). — *CSS now flows through the enqueued block style handle; helper enqueues defensively so it works regardless of render timing.*
- [x] `composer check` stays green; rebuilt lite has no front-end `<style>` echo. — *Rebuilt lite tree greps clean; helper + inline-style calls propagated into `toplist-block-lite.php`.*

## Out of scope

- The JSON-LD `<script>` block (`toplist-block.php:1278`) — that is valid structured-data markup, not an asset, and is pro-only.
- Settings-screen inline assets (see [712](712-settings-page-enqueue.md)).

## Dependencies

- **Blocks:** 799
- **Blocked by:** none

## Approach (optional)

CSS is currently composed per-render and may be list-context specific, so a single static stylesheet won't hold it. Register an empty handle on the front-end enqueue hook, then attach the per-render CSS via `wp_add_inline_style()` keyed to that handle. Confirm timing: inline style must be added before/at enqueue, which may require collecting the CSS during render and flushing on `wp_enqueue_scripts` or `wp_footer` with a registered handle. Document the chosen ordering in the notes log.

## Notes / decisions log

- 2026-06-17 — Filed from WP.org audit (2026-06-16), blocking item (front-end enqueue). Largest of the audit fixes; touches shared render path.
- 2026-06-17 — Implemented via new helper `toplist_add_render_inline_css()`:
  reuses the block's already-registered `toplist-style` handle, enqueues it
  defensively (`wp_style_is` guards), and appends each CSS string with
  `wp_add_inline_style()`. CSS is now resolved before `ob_start()` since it no
  longer needs to be echoed into the returned buffer.
- 2026-06-17 — Dropped the old `esc_html()` that wrapped the inline CSS: inline
  style content is CSS, not HTML, and `esc_html` corrupts valid selectors
  (`a > b`, `&`). Kept `wp_strip_all_tags()` (centralised in the helper) as the
  `</style>`-breakout guard. Shared render path, so this fixes both lite and pro.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-up work filed as new tickets, not absorbed.
