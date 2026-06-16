# Sprint 6 Review

**Date:** 2026-06-15  
**Status:** Complete

## Shipped

| Ticket | Summary |
|--------|---------|
| 601 | Geo-variant rows (`geo` pipe column), visitor detection (CF-IPCountry / filter), per-list defaults |
| 602 | Outbound click tracking (redirect endpoint, JSON stats, disclosure, optional obfuscation) |
| 603 | wp-env integration test scaffold (prior sprint) |
| 604 | WP.org deploy docs + lite safety guards |
| 605 | Multisite satellite domain normalization |
| 606 | Portal `toplist-pricing.php` public pricing page |
| 607 | Lite text domain `toplist-block-lite` rewrite |
| 610 | Live preview metabox + per-list CSS/toggle overrides |
| 611 | REST sync endpoint + remote source cron |
| 612 | Card layout builder (drag-reorder regions → flex `order` CSS) |
| 613 | Premium code in `includes/pro/` (library extracted; minimal markers remain in main/settings/block.js) |
| 701–704 | PHPStan level 9 + CI quality gate (prior sprint) |

## Quality

- `composer check` green: 22 unit tests, PHPStan level 9, lite build smoke
- Integration tests: `composer test:integration` (requires Docker/wp-env) — documented in `tests/README.md`

## Operator follow-ups (not code)

1. WP.org: submit lite zip + banner/icon assets (`docs/wporg-deploy.md`)
2. Portal: Stripe sync for `toplist-block-pro*` plans; verify `/toplist-pricing.php` checkout in test mode
3. Set `TOPLIST_LITE_UPGRADE_URL` when building lite for production portal host

## Carry-over / Sprint 7 candidates

- Full removal of inline `@toplist-premium` markers from `settings-page.php` and `block.js` (613 follow-up)
- MaxMind / server-side geo database integration (601 extension)
- Rich analytics UI for click stats beyond JSON export (602 extension)
- 612: column resize + responsive breakpoint editor

## Programme state

Sprint 6 backlog closed. Sprints 1–6 complete; programme is launch-ready pending operator checklist in `tickets/overview.md`.
