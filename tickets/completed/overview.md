# Sprint 1: Stabilise & Document

**Status:** Closed

**Theme:** Treat `toplist-block/` as premium canonical source, document the split, verify recent import fixes, and bring README in line with reality.

**Tickets:**
- [x] [Ticket 101: Monorepo Structure](./101-monorepo-structure.md)
- [x] [Ticket 102: Free vs Premium Manifest](./102-free-premium-manifest.md)
- [x] [Ticket 103: Admin Import Verification](./103-admin-import-verification.md)
- [x] [Ticket 104: JSON Schema and README](./104-json-schema-readme.md)
- [x] [Ticket 199: Sprint 1 Review and Go/No-Go](./199-sprint-1-review.md)

**Exit criteria:**
- Repo layout matches solar-form pattern (`toplist-block/`, `scripts/`, `tests/`, `docs/`)
- `docs/free-vs-premium.md` lists every feature and which build includes it
- Admin CSV/JSON import verified on a real WP install
- README documents JSON schema and the upcoming two-plugin model
- Team decides whether to continue to Sprint 2

**Explicitly out of scope:** license class, portal module, WP.org submission, lite zip generation.

---

# Sprint 2: Lite Build Pipeline

**Status:** Closed

**Theme:** Generate `toplist-block-lite/` from premium source via `scripts/build-lite.php`; lite must contain zero premium residue.

**Tickets:**
- [x] [Ticket 201: build-lite.php Scaffold](./201-build-lite-scaffold.md)
- [x] [Ticket 202: Strip Premium PHP](./202-strip-premium-php.md)
- [x] [Ticket 203: Strip Premium block.js](./203-strip-premium-blockjs.md)
- [x] [Ticket 204: Lite Build Smoke Tests](./204-lite-build-smoke-tests.md)
- [x] [Ticket 205: Lite Upgrade CTAs](./205-lite-upgrade-ctas.md)
- [x] [Ticket 299: Sprint 2 Review and Go/No-Go](./299-sprint-2-review.md)

**Exit criteria:**
- `php scripts/build-lite.php` produces `toplist-block-lite/` and `toplist-block-lite.zip`
- Lite has no CPT library, no import handlers, no license code, no linked-mode UI
- Smoke tests pass (`php tests/build/run.php` or equivalent)
- Lite shows WP.org-compliant upgrade notices only (no disabled premium controls)
- Team decides whether to continue to Sprint 3

**Explicitly out of scope:** portal API calls, Stripe checkout, WP.org plugin review submission.

---

# Sprint 3: Premium Licensing

**Tickets:**
- [x] [Ticket 304: Premium Distribution Zip](./304-premium-distribution-zip.md)
- [x] [Ticket 301: Toplist Block License Class](./301-license-class.md)
- [x] [Ticket 302: License Admin UI and Cron](./302-license-admin-ui.md)
- [x] [Ticket 303: Portal Product and Module](./303-portal-product-module.md)
- [x] [Ticket 305: Premium OTA Plugin Updates](./305-premium-ota-updates.md)
- [x] [Ticket 399: Sprint 3 Review and Go/No-Go](./399-sprint-3-review.md)

**Status:** Closed — see `docs/sprint-3-review.md`

---

# Sprint 4: Distribution & Compliance

**Tickets:**
- [x] [Ticket 401: WP.org readme.txt Audit](./401-wporg-readme-audit.md)
- [x] [Ticket 402: Lite-to-Premium Migration](./402-lite-premium-migration.md)
- [x] [Ticket 403: PHPUnit Parse and Import](./403-phpunit-parse-import.md)
- [x] [Ticket 404: CI / Automated Build Pipeline](./404-ci-build-automation.md)
- [x] [Ticket 405: i18n Translation Readiness](./405-i18n-translation-readiness.md)
- [x] [Ticket 406: Security Hardening Audit](./406-security-hardening-audit.md)
- [x] [Ticket 499: Sprint 4 Review and Go/No-Go](./499-sprint-4-review.md)

**Status:** Closed — see `docs/sprint-4-review.md`

---

# Sprint 5: Pro Feature Expansion

**Tickets:**
- [x] [Ticket 501: Admin Row Spreadsheet UI](./501-row-spreadsheet-ui.md)
- [x] [Ticket 502: Geo Variants and Tracking](./502-geo-tracking-schema.md) — shipped ItemList JSON-LD schema first
- [x] [Ticket 599: Sprint 5 Review and Go/No-Go](./599-sprint-5-review.md)

**Status:** Closed — see `docs/sprint-5-review.md`

