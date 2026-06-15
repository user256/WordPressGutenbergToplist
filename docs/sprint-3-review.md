# Sprint 3 review (ticket 399)

**Date:** 2026-06-15  
**Decision:** **Go** — proceed to Sprint 4

## Tickets closed

| Ticket | Outcome |
|--------|---------|
| 301 | License class |
| 302 | License admin UI |
| 303 | Portal product module |
| 304 | Premium distribution zip |
| 305 | OTA updates |

## Exit criteria

| Criterion | Status |
|-----------|--------|
| Premium validates keys against portal | Pass — `POST /api/v1/toplist-block/validate` |
| Invalid keys degrade gracefully | Pass — local blocks render; library gated |
| Portal product + plan + feature flag | Pass — `toplist_block_pro`, seeded plans |
| Premium zip excludes dev tooling | Pass — build script + smoke tests |
| OTA updates from portal | Pass — update-check + download-package |
| Team Go/No-Go | **Go** |

## E2E test (documented)

1. `php api/bin/seed-toplist-plans.php` (portal)
2. `php api/bin/test-toplist-block-validate.php` (portal smoke)
3. Build premium: `php scripts/build-lite.php`
4. WordPress: set `TOPLIST_BLOCK_LICENSE_API_URL` + `TOPLIST_BLOCK_LICENSE_API_KEY`
5. Portal account → issue license → paste in Settings → Toplist Block
6. Confirm **Toplists** CPT appears when license valid

## Next

Sprint 4 — WP.org readme, upgrade path, PHPUnit, CI, i18n, security audit.
