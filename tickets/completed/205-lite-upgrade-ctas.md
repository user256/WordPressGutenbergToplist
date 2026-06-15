# Ticket 205: Lite Upgrade CTAs

**Sprint:** 2 — Lite Build Pipeline
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

WordPress.org allows pointing users to a separate premium product. Solar-form lite shows upgrade notices without disabled features or license gates. Toplist lite should do the same: informative CTAs only.

## Goal

Lite plugin shows WP.org-compliant upgrade messaging with no locked controls or license checks.

## Acceptance criteria

- [ ] Lite admin shows optional notice: Pro adds library, bulk import, live-linked lists (wording TBD)
- [ ] Notice links to portal product URL (placeholder OK until ticket 303)
- [ ] No disabled buttons, no "upgrade to unlock" on greyed-out UI elements
- [ ] No outbound license validation calls from lite
- [ ] Smoke test (204) does not flag CTA strings as premium residue violations
- [ ] Settings page does not reference Toplists library in lite

## Out of scope

- Portal product page content (ticket 303)
- In-plugin purchase flow
- Email marketing

## Dependencies

- **Blocks:** 401
- **Blocked by:** 202, 203
- **External:** Final product URL from portal

## Approach (optional)

Single `admin_notices` hook in lite-only bootstrap or injected by build script. Dismissible transient option.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
