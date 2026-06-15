# Toplist Block Roadmap

This roadmap tracks the two-plugin commercialisation programme for Toplist Block: a **premium canonical source** (`toplist-block/`) and a **generated lite build** (`toplist-block-lite/`) for WordPress.org, following the solar-form pattern.

**Programme status:** Sprints 1–5 complete (launch-ready). **Sprint 6** is the active backlog.

See `tickets/completed/overview.md` for archived Sprints 1–5.

---

# Programme Status

| Area | State |
|------|--------|
| **Sprint 1** — Stabilise & document | Done |
| **Sprint 2** — Lite build pipeline | Done |
| **Sprint 3** — Premium licensing | Done |
| **Sprint 4** — Distribution & compliance | Done |
| **Sprint 5** — Pro feature expansion | Done |
| **Sprint 6** — Post-launch expansion | In progress |

---

# Remedial (post-review)

- [x] [105](completed/105-rest-api-auth-sanitization-fix.md) — REST API capability + sanitization fix
- [x] [106](completed/106-import-file-validation.md) — Import upload extension/MIME validation

---

# Sprint 6 — Post-Launch Expansion

- [ ] [601](601-geo-variant-toplists.md) — Geo-variant toplists (premium)
- [ ] [602](602-outbound-click-tracking.md) — Outbound click tracking (premium)
- [x] [603](completed/completed/603-wp-integration-tests.md) — WordPress integration tests (wp-env)
- [ ] [604](604-wporg-svn-deploy.md) — WP.org SVN deploy automation (`scripts/deploy-wporg.sh` scaffolded)
- [ ] [605](605-multisite-satellite-domains.md) — Multisite satellite domains (runtime + docs; admin copy pending)
- [ ] [606](606-portal-pricing-page.md) — Portal pricing page (Toplist Pro)
- [ ] [607](607-lite-text-domain-cleanup.md) — Lite text domain cleanup
- [ ] [608](608-rest-api-auth-sanitization-fix.md) — Remedial: REST API privilege & sanitization fix
- [ ] [609](609-import-file-validation.md) — Remedial: Import file upload validation
- [ ] [699](699-sprint-6-review.md) — Sprint 6 review

---

# Launch checklist (operator)

1. `composer test && composer test:build` — green CI
2. Local license E2E: `bash scripts/setup-local-license.sh` — see `docs/local-dev.md`
3. Portal: seed plans, configure `toplist_block_*` keys, upload premium zip
4. WP.org: submit `toplist-block-lite.zip` + assets
5. Portal: open Stripe checkout for `toplist-block-pro*` plans

---

# Reference

- Build: `php scripts/build-lite.php`
- Tests: `composer test`, `composer test:build`, `composer test:integration` (wp-env)
- Docs: `docs/free-vs-premium.md`, `docs/upgrade.md`, `docs/portal-setup.md`, `docs/local-dev.md`
- Reviews: `docs/sprint-3-review.md`, `docs/sprint-4-review.md`, `docs/sprint-5-review.md`
