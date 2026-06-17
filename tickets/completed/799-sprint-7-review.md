# Ticket 799: Sprint 7 review — rebuild & re-audit

**Sprint:** 7 — WP.org submission compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

Sprint 7 closes the WP.org submission-compliance gaps found in the 2026-06-16 audit ([`docs/wporg-audit-toplist-block-lite-2026-06-16.md`](../docs/wporg-audit-toplist-block-lite-2026-06-16.md)). Per the audit's "recommended next pass", once the fixes land we must rebuild the lite tree and re-run the same audit sweep to confirm the blocking items are gone.

## Goal

A rebuilt lite tree passes a fresh audit sweep with all 🔴 blocking items resolved, and the sprint outcome is recorded.

## Acceptance criteria

- [x] 710–713 complete (714 complete or explicitly deferred). — *710–714 all done.*
- [x] `php scripts/build-lite.php` rebuilds `toplist-block-lite/`; `composer check` green.
- [x] Re-run the audit sweep against the regenerated tree; record results in a dated `docs/wporg-audit-toplist-block-lite-<date>.md`. — *[docs/wporg-audit-toplist-block-lite-2026-06-17.md](../docs/wporg-audit-toplist-block-lite-2026-06-17.md).*
- [x] No remaining 🔴 blocking items; any new findings filed as tickets. — *All 5 blocking items resolved; no new findings. Two operator actions noted (real upgrade URL; optional name review).*
- [x] Programme-status table / launch checklist in `tickets/overview.md` updated.

## Dependencies

- **Blocks:** none
- **Blocked by:** 710, 711, 712, 713

## Notes / decisions log

- 2026-06-17 — Filed alongside Sprint 7 from the WP.org audit (2026-06-16).

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-up work filed as new tickets, not absorbed.
