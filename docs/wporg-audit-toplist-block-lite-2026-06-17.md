# Plugin Audit (re-sweep) — Toplist Block Lite (2026-06-17)

Audit target: regenerated shipping tree `toplist-block-lite/`

Audit basis: `/home/user256/GitRepos/solar-form/WordPressAudit.md`; follows up the
[2026-06-16 audit](wporg-audit-toplist-block-lite-2026-06-16.md) and Sprint 7
(tickets 710–714).

## Summary

All 🔴 blocking items from the 2026-06-16 audit are resolved. The lite build no
longer echoes raw `<script>` / `<style>`, no longer ships a live external image
host in sample content, and can no longer ship the dead placeholder upgrade URL
(the build now refuses it unless explicitly overridden). No new blocking issues
found.

## Status of prior findings

| # | Prior 🔴 item | Status | Resolution |
|---|---|---|---|
| 1 | Dead upgrade URL `example.com/toplist-pricing.php` (404) | ✅ Resolved | Ticket 710 — `scripts/build-lite.php` refuses a build that resolves to the placeholder (exit 1) unless `TOPLIST_LITE_ALLOW_PLACEHOLDER_URL=1`. Release builds pass the real URL via `TOPLIST_LITE_UPGRADE_URL`. |
| 2 | Upgrade-notice inline `<script>` | ✅ Resolved | Ticket 713 — moved to `wp_add_inline_script()` on a registered admin handle (`toplist-lite-upgrade-notice`, dep `jquery`). |
| 3 | Settings-page inline `<style>` | ✅ Resolved | Ticket 712 — extracted to `assets/admin-settings.css`, enqueued on the settings hook only. |
| 4 | Settings-page inline `<script>` | ✅ Resolved | Ticket 712 — extracted to `assets/admin-settings.js`, same gating. |
| 5 | Front-end raw `<style>` ×3 (global/custom/card CSS) | ✅ Resolved | Ticket 711 — `toplist_add_render_inline_css()` attaches CSS to the block's `toplist-style` handle via `wp_add_inline_style()`. |

### 🟠 prior items

| Item | Status | Note |
|---|---|---|
| External image host in sample content (`via.placeholder.com`) | ✅ Resolved | Ticket 714 — replaced with reserved `example.com/logo.png`. |
| Generic name / distinctiveness | ⚠️ Unchanged | Manual-review risk only; no code change. Operator decision. |
| `example.com` in sample rows | ↺ Intentionally kept | IANA-reserved documentation domain; conventional for samples. |

## Re-sweep results (regenerated tree)

- Raw `<script>` / `<style>` echoes in shipped PHP: **none**.
- `via.placeholder.com` (live host) in shipped code: **none**.
- `ABSPATH` guards present in main shipped files: **yes**.
- Lite smoke checks (no premium residue, text domain): **pass**.
- New `assets/admin-settings.{css,js}` ship in the lite tree (not premium-deleted): **yes**.
- Placeholder upgrade URL present in this tree: **yes — expected**; this is a
  smoke build using `TOPLIST_LITE_ALLOW_PLACEHOLDER_URL=1`. A release build with a
  real `TOPLIST_LITE_UPGRADE_URL` does not contain it, and a build without either
  refuses to run.

## Remaining operator actions before submission

1. Run the production lite build with the real public pricing page:
   `TOPLIST_LITE_UPGRADE_URL=https://… php scripts/build-lite.php`, and confirm
   that page returns `200` (ticket 710).
2. Optionally reconsider the plugin name/slug distinctiveness (🟠, manual-review risk).
