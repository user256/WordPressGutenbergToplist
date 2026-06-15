# Sprint 4 review (ticket 499)

**Date:** 2026-06-15  
**Decision:** **Go** — ready for WP.org submission prep and portal sales (operator config pending)

## Tickets closed

| Ticket | Outcome |
|--------|---------|
| 401 | WP.org readme self-audit |
| 402 | Lite→premium upgrade docs + activation guards |
| 403 | PHPUnit parse/import fixtures |
| 404 | GitHub Actions CI |
| 405 | i18n textdomain + `.pot` in lite build |
| 406 | Security audit documented |

## Programme exit criteria (6 items)

| # | Criterion | Status |
|---|-----------|--------|
| 1 | Lite WP.org-ready, no premium residue | Pass — smoke tests |
| 2 | Portal premium + license validation | Pass — Sprint 3 |
| 3 | Lite → premium content survives | Pass — shared block + options |
| 4 | Reproducible build | Pass — `build-lite.php` + CI |
| 5 | Smoke + PHPUnit before release | Pass — `composer test` + `test:build` |
| 6 | Docs match builds | Pass — `free-vs-premium.md`, `upgrade.md` |

## Launch decision

- **WP.org:** Submit lite when operator is ready (account + assets).
- **Portal sales:** Open after `toplist_block_download_path` + Stripe prices configured.
- **Sprint 5:** Proceed — spreadsheet UI + schema markup shipped.

## Known risks

- E2E license test requires live portal + wp-config constants.
- `.pot` regeneration needs WP-CLI `i18n make-pot` on release machines.
- No full WP integration test suite yet (acceptable for v1).
