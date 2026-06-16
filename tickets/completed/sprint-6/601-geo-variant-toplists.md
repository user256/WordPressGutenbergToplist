# Ticket 601: Geo-Variant Toplists

**Sprint:** 6 — Post-Launch Expansion
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

iGaming affiliates often serve different operator rankings per visitor country. Ticket 502 deferred geo variants in favour of ItemList schema. This is the first premium differentiator in the Sprint 6 backlog.

## Goal

Premium sites can attach country/region codes to toplist rows or lists and render the matching variant on the front end without duplicating entire pages.

## Acceptance criteria

- [ ] Block or library supports at least one geo selector (e.g. ISO country code on rows or per-list variant groups)
- [ ] Front-end render picks the best match for the visitor (configurable default when no match)
- [ ] Feature is premium-only, license-gated, and absent from lite build
- [ ] Documented in README premium section and `docs/free-vs-premium.md`
- [ ] Existing blocks without geo attributes render unchanged

## Out of scope

- IP geolocation database hosting (use WordPress-friendly hook or optional MaxMind integration ticket)
- Compliance copy for jurisdiction-specific gambling ads
- Full analytics per geo variant

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** Product decision on default geo detection (server header vs JS vs none)

## Notes / decisions log

- 2026-06-15 — Filed from Sprint 5 review / ticket 502 deferral.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. `tickets/overview.md` Sprint 6 item checked.
4. Follow-ups filed as new tickets if needed.
