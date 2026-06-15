# Ticket 303: Portal Product and Module

**Sprint:** 3 — Premium Licensing
**Status:** Done
**Owner:** unassigned
**Estimate:** L

---

## Context

Portal at `/home/user256/GitRepos/portal` issues license keys via `licence-manager` and Stripe-synced plans. Toplist Pro needs a product record, plan feature flag, and customer-facing download/license page before premium sales.

## Goal

Portal can issue Toplist Pro license keys that ticket 301 validates successfully end-to-end.

## Acceptance criteria

- [x] Product/plan exists in portal DB or config with `product_slug` matching license class (e.g. `toplist-block-pro`)
- [x] `plan_features` or equivalent grants Toplist Pro entitlement after Stripe checkout
- [x] Customer account shows license key + premium zip download link
- [x] `POST /api/v1/toplist-block/validate` returns success for issued test key on configured domain
- [x] Ticket 205 CTA URL updated to real product page
- [x] Document portal setup steps in `docs/portal-setup.md`

## Out of scope

- WordPress.org listing
- Automated zip upload to portal (manual OK for v1)
- Affiliate/revenue share logic

## Dependencies

- **Blocks:** 304, 205 (CTA URL)
- **Blocked by:** 301 (need product_slug)
- **External:** Portal repo access, Stripe test mode, domain config

## Approach (optional)

Reuse existing `licence-manager` module; clone solar-form product setup if a reference exists in portal.

## Notes / decisions log

- Dedicated `portal/modules/toplist-block/` module (solar-capture pattern), not licence-manager product UI.
- Plans seeded via `portal/api/bin/seed-toplist-plans.php`; smoke test `test-toplist-block-validate.php`.
- WP plugin sends `Authorization: Bearer` when `TOPLIST_BLOCK_LICENSE_API_KEY` is set.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged in portal and/or toplist docs as appropriate.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
