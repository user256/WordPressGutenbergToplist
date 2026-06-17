# Plugin Audit — Toplist Block Lite (2026-06-16)

Audit target: generated shipping tree `toplist-block-lite/`

Audit basis: `/home/user256/GitRepos/solar-form/WordPressAudit.md`

## Summary

The lite build looks clean on the major wp.org rejection classes for trialware, hidden premium handlers, direct input handling, REST permissions, and direct file access guards. The concrete issues found are concentrated in shipped inline asset output and one dead upgrade URL.

## 🔴 Blocking / likely rejection items

- [readme URL] [toplist-block-lite.php](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/toplist-block-lite.php:1241) ships an upgrade URL of `https://example.com/toplist-pricing.php`, which currently returns `404`.
  Fix: replace the placeholder with the real live public pricing/upgrade URL before any submission.

- [Enqueue] [toplist-block-lite.php](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/toplist-block-lite.php:1247) prints a raw inline `<script>` tag for dismissing the upgrade notice.
  Fix: move this into an enqueued admin script or `wp_add_inline_script()` attached to a registered admin script handle.

- [Enqueue] [settings-page.php](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/settings-page.php:223) prints raw inline `<style>` markup in the settings screen.
  Fix: move the CSS into an enqueued admin stylesheet or `wp_add_inline_style()`.

- [Enqueue] [settings-page.php](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/settings-page.php:558) prints raw inline `<script>` markup in the settings screen.
  Fix: move the JavaScript into an enqueued admin script or `wp_add_inline_script()`.

- [Enqueue] [toplist-block-lite.php](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/toplist-block-lite.php:954), [toplist-block-lite.php](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/toplist-block-lite.php:957), and [toplist-block-lite.php](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/toplist-block-lite.php:960) print raw `<style>` tags on the front end for global/custom/card CSS.
  Fix: register/enqueue a stylesheet and attach user CSS via `wp_add_inline_style()` instead of echoing `<style>` directly during render.

## 🟠 Should fix / review closely

- [Name/Distinctiveness] `Toplist Block Lite` / `toplist-block-lite`
  Quick wp.org search did not show an obvious exact slug collision, but the name is descriptive and somewhat generic. This is a manual-review risk rather than a confirmed failure.
  Fix: optionally consider a more distinctive branded name before submission if you want to reduce pend risk.

- [Example URL in sample content] [block.js](/home/user256/GitRepos/Casino-project/toplist/toplist-block-lite/block.js:475) contains `https://via.placeholder.com/...` and `https://example.com` in sample row text.
  This does not appear to be a remotely loaded runtime asset in the lite build, but reviewers may still notice it during a broad scan.
  Fix: replace demo/example URLs with clearly documented placeholders or local example values if you want a cleaner audit surface.

## 🟢 Explicit passes

- [Trialware / locked features] No lite-tree evidence of hidden premium handlers, `if (false)` dead UI gates, or shipped license enforcement in the generated build.
- [Serviceware] No outbound API/service processing found in the lite PHP tree.
- [REST permissions] No shipped REST routes found in the lite build.
- [Input handling] No direct `$_GET` / `$_POST` / `$_REQUEST` usage found in the lite build.
- [Nonce / capability checks] Lite settings submission and upgrade-notice dismissal include nonce/cap checks.
- [Direct file access guards] Main shipped PHP files include `ABSPATH` guards.
- [Contributors] `Contributors: user256` resolves on wp.org profiles.
- [License URI] `https://www.gnu.org/licenses/gpl-2.0.html` resolves successfully.

## Recommended next pass

1. Replace the placeholder upgrade URL with the real live public page.
2. Remove all raw `<script>` and `<style>` output from the lite build in favor of enqueue APIs.
3. Rebuild `toplist-block-lite/` and rerun the same audit sweep against the generated tree.
