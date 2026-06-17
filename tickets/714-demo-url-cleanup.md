# Ticket 714: Replace demo `example.com` / placeholder URLs in sample content

**Sprint:** 7 — WP.org submission compliance
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

The WP.org audit ([`docs/wporg-audit-toplist-block-lite-2026-06-16.md`](../docs/wporg-audit-toplist-block-lite-2026-06-16.md)) noted (🟠, non-blocking) that sample row text contains `https://via.placeholder.com/...` and `https://example.com` — in the lite build at `block.js:475`, with equivalents in premium source (e.g. [`library.php:1151`](../toplist-block/includes/pro/library.php#L1151)). These aren't runtime remote loads, but reviewers may flag them during a broad scan.

## Goal

Sample/demo content uses clearly-labelled placeholder values that don't read as live external URLs.

## Acceptance criteria

- [ ] Demo `example.com` / `via.placeholder.com` URLs in `block.js` sample rows and premium sample data replaced with documented placeholders or local example values.
- [ ] No behaviour change to real (non-sample) rendering.
- [ ] `composer check` stays green; rebuilt lite reflects the change.

## Out of scope

- `placeholder=` attribute hints in admin inputs (those are legitimate UI hints, not shipped sample data).
- Any blocking enqueue work (710–713).

## Dependencies

- **Blocks:** 799
- **Blocked by:** none

## Notes / decisions log

- 2026-06-17 — Filed from WP.org audit (2026-06-16), 🟠 should-fix item. Optional polish to reduce manual-review pend risk.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-up work filed as new tickets, not absorbed.
