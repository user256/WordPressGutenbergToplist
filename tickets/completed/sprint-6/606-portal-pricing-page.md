# Ticket 606: Portal Pricing Page (Toplist Pro)

**Sprint:** 6 — Post-Launch Expansion
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Toplist Block Pro plans are seeded in the portal (`toplist-block-pro*`) but may not appear on the public marketing index. Customers need a clear path from lite upgrade CTAs to checkout.

## Goal

Portal surfaces Toplist Block Pro plans on a public page linked from lite upgrade CTAs.

## Acceptance criteria

- [ ] Public page lists monthly/yearly/lifetime plans seeded by `api/bin/seed-toplist-plans.php`
- [ ] Stripe price IDs mapped in admin (or documented operator step)
- [ ] Lite `upgrade_url` filter / build constant points to this page
- [ ] Smoke: anonymous visitor can start checkout (test mode)

## Out of scope

- Full marketing site redesign
- Custom whitelabel themes beyond existing portal theming

## Dependencies

- **Blocks:** none
- **Blocked by:** Portal Stripe configuration (operator)
- **External:** Stripe test/live keys

## Notes / decisions log

- 2026-06-15 — Filed from launch backlog.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main (portal repo) and lite upgrade URL updated in toplist repo.
3. `tickets/overview.md` Sprint 6 item checked.
