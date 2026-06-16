# Ticket 602: Outbound Click Tracking

**Sprint:** 6 — Post-Launch Expansion
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

Affiliate sites need click-through metrics on operator outbound links. Ticket 502 deferred tracking pending product/legal disclosure copy. This ticket delivers a minimal, privacy-conscious tracking layer.

## Goal

Premium can optionally record outbound clicks from toplist rows (aggregate counts per URL/row) with configurable disclosure in the front-end markup. Additionally, it should optionally provide link obfuscation by removing the `href` attribute and relying on a JavaScript `onclick` event to deter scrapers from easily scraping raw affiliate links.

## Acceptance criteria

- [ ] Product/legal disclosure text agreed and shown when tracking is enabled
- [ ] Premium-only redirect or beacon endpoint records clicks without breaking bare links when disabled
- [ ] Admin can enable/disable tracking and view basic counts (per list or export)
- [ ] **Optional Outlink Obfuscation**: Provide a toggle to replace standard `href` attributes on the frontend with JS `onclick` events that trigger the redirect, effectively hiding the raw affiliate URL from hover states and simple crawlers.
- [ ] Absent from lite build and lite readme
- [ ] No PII stored by default (IP hashing or omission documented)

## Out of scope

- Full analytics dashboard / GA4 replacement
- Cross-site tracking
- Real-time reporting UI beyond a simple admin table

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** Legal/product sign-off on disclosure wording

## Notes / decisions log

- 2026-06-15 — Filed from Sprint 5 review / ticket 502 deferral.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. `tickets/overview.md` Sprint 6 item checked.
