# Toplist Block Roadmap

This roadmap tracks the two-plugin commercialisation programme for Toplist Block: a **premium canonical source** (`toplist-block/`) and a **generated lite build** (`toplist-block-lite/`) for WordPress.org, following the solar-form pattern.

**Programme status:** Sprints 1–7 complete (launch-ready).

See `tickets/completed/overview.md` for archived Sprints 1–7.

---

# Programme Status

| Area | State |
|------|--------|
| **Sprint 1** — Stabilise & document | Done |
| **Sprint 2** — Lite build pipeline | Done |
| **Sprint 3** — Premium licensing | Done |
| **Sprint 4** — Distribution & compliance | Done |
| **Sprint 5** — Pro feature expansion | Done |
| **Sprint 6** — Post-launch expansion | Done |
| **Sprint 7** — WP.org submission compliance | Done |

---

# Remedial (post-review)

- [x] [105](completed/105-rest-api-auth-sanitization-fix.md) — REST API capability + sanitization fix
- [x] [106](completed/106-import-file-validation.md) — Import upload extension/MIME validation

---

# Sprint 6 — Post-Launch Expansion (archived)

See [Sprint 6 review](../docs/sprint-6-review.md) and `tickets/completed/sprint-6/`.

- [x] 601 — Geo-variant toplists
- [x] 602 — Outbound click tracking
- [x] 603 — WordPress integration tests
- [x] 604 — WP.org SVN deploy
- [x] 605 — Multisite satellite domains
- [x] 606 — Portal pricing page
- [x] 607 — Lite text domain cleanup
- [x] 610 — Editor UX (live preview & overrides)
- [x] 611 — API population options
- [x] 612 — Visual card layout builder
- [x] 613 — Premium bootstrap refactor
- [x] 699 — Sprint 6 review

---

# Launch checklist (operator)

1. `composer check` — green CI (unit tests + PHPStan + lite build smoke)
2. Local license E2E: `bash scripts/setup-local-license.sh` — see `docs/local-dev.md`
3. Portal: seed plans, configure `toplist_block_*` keys, upload premium zip
4. WP.org: build lite with the real upgrade URL — `TOPLIST_LITE_UPGRADE_URL=https://… php scripts/build-lite.php` (a bare build refuses the `example.com` placeholder; verify the page returns 200), then submit `toplist-block-lite.zip` + assets
5. Portal: open Stripe checkout on `/toplist-pricing.php` for `toplist-block-pro*` plans

---

# Reference

- Build: `php scripts/build-lite.php`
- Tests: `composer test`, `composer test:build`, `composer test:integration` (wp-env), `composer phpstan`, `composer check`
- Docs: `docs/free-vs-premium.md`, `docs/upgrade.md`, `docs/portal-setup.md`, `docs/local-dev.md`, `docs/sprint-6-review.md`
- Reviews: `docs/sprint-3-review.md`, `docs/sprint-4-review.md`, `docs/sprint-5-review.md`
