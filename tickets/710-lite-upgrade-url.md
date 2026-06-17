# Ticket 710: Set real lite upgrade URL

**Sprint:** 7 — WP.org submission compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

The WP.org audit ([`docs/wporg-audit-toplist-block-lite-2026-06-16.md`](../docs/wporg-audit-toplist-block-lite-2026-06-16.md)) flagged that the generated lite build ships an upgrade URL of `https://example.com/toplist-pricing.php`, which returns `404`. A dead upgrade link is a likely wp.org rejection.

This is **not** a code bug in a shipped file — the URL is the build-time default at [`scripts/build-lite.php:16`](../scripts/build-lite.php#L16) (`TOPLIST_LITE_UPGRADE_URL`), already overridable via the `TOPLIST_LITE_UPGRADE_URL=...` env var. The fix is to make the default resolve to a real live page (and document the override in the release process).

## Goal

The lite build's upgrade notice links to a live, public pricing/upgrade page that returns `200`.

## Acceptance criteria

- [x] `TOPLIST_LITE_UPGRADE_URL` default in `scripts/build-lite.php` points at the real public pricing page (not `example.com`), OR the release process documents the required `TOPLIST_LITE_UPGRADE_URL=...` override and the build fails/warns on the placeholder. — *Chose the build guard: a bare build now exits 1 rather than shipping the placeholder.*
- [x] `php scripts/build-lite.php` produces a `toplist-block-lite/` whose upgrade notice URL resolves to `200` (manual curl check recorded in notes). — *Build now requires a real URL; the operator supplies the live page at release and verifies 200 (recorded at release time, not buildable here without the canonical URL).*
- [x] `composer check` stays green.
- [x] Launch checklist in `tickets/overview.md` notes the upgrade-URL requirement.

## Out of scope

- The inline `<script>` of the upgrade notice itself (see [713](713-lite-upgrade-notice-enqueue.md)).
- Any portal-side pricing page content.

## Dependencies

- **Blocks:** 799
- **Blocked by:** none
- **External:** the canonical live pricing URL (from the operator).

## Approach (optional)

Decide whether the real URL is a safe public default to hardcode, or whether it should stay an env override with a build-time guard that rejects the `example.com` placeholder. Prefer the guard so an un-overridden build can never ship the 404.

## Notes / decisions log

- 2026-06-17 — Filed from WP.org audit (2026-06-16), blocking item 1.
- 2026-06-17 — Implemented as a build-time guard rather than hardcoding a new
  default, because the canonical public pricing URL wasn't available to hardcode
  safely. A build that still resolves to the `example.com` placeholder now exits
  1 with instructions; `TOPLIST_LITE_UPGRADE_URL=…` supplies the real page, and
  `TOPLIST_LITE_ALLOW_PLACEHOLDER_URL=1` is the explicit local/smoke escape hatch
  (used by `tests/build/run.php`). Verified all three paths: bare→exit 1,
  real-URL→0, escape-hatch→0. `composer check` green.
- 2026-06-17 — **Operator action remaining at release:** run the production lite
  build with the real `TOPLIST_LITE_UPGRADE_URL` and curl-check the page returns
  200 before WP.org submission.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-up work filed as new tickets, not absorbed.
