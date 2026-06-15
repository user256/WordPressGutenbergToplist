# Ticket 605: Multisite Satellite Domains

**Sprint:** 6 — Post-Launch Expansion
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Ticket 301 noted future support for multisite networks and satellite domains on one license. Portal already returns `extra_domains` and `allowed_domains` in validate responses.

## Goal

Premium license validation accepts additional domains configured in the portal account without requiring a separate key per subsite.

## Acceptance criteria

- [x] `Toplist_Block_License` treats `allowed_domains` from validate response as authoritative
- [x] Admin UI explains how to add satellite domains in portal account
- [x] Multisite: `domain` sent to validate uses the site’s `home_url` host, not only network primary
- [x] Documented in `docs/upgrade.md` and portal module README
- [ ] Unit or integration test for domain normalization edge cases

## Out of scope

- Automatic portal UI for WordPress multisite network admin
- Wildcard `*.example.com` licenses

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** Portal account extra-domain UX (may already exist)

## Notes / decisions log

- 2026-06-15 — Filed from ticket 301 future work.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. `tickets/overview.md` Sprint 6 item checked.
