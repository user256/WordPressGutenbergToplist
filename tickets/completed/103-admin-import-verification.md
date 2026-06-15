# Ticket 103: Admin Import Verification

**Sprint:** 1 — Stabilise & Document
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Admin CSV and JSON import on the Toplists edit screen failed silently because file inputs lived inside `#post` and submit was intercepted. The fix uses `type="button"` triggers and `form.submit()` on hidden footer forms (`toplist_print_import_forms_in_footer`). This has not been end-to-end verified on a live WordPress install.

Premium will keep this feature; lite will strip it. Verification must happen before strip work so we know the premium source is correct.

## Goal

Admin CSV and JSON import on `toplist_list` edit screen update post content reliably on a real WordPress site.

## Acceptance criteria

- [ ] On Toplists → Edit, uploading `toplist-229.csv` replaces row content and saves
- [ ] On Toplists → Edit, uploading `toplist-229.json` (or `toplist.json`) replaces row content and saves
- [ ] Import works when the main Update button has not been clicked first (no disabled-field trap)
- [ ] No duplicate `action` or `post_id` in submitted form data (verify in network tab or server log)
- [ ] Any remaining bug is filed as a new ticket with reproduction steps; this ticket documents pass/fail in Notes

## Out of scope

- Block editor JSON import (separate code path in `block.js`)
- Lite build stripping
- Bulk import across all toplists

## Dependencies

- **Blocks:** none (premium quality gate)
- **Blocked by:** none
- **External:** Local WordPress install (`install-local.sh` or existing dev site)

## Approach (optional)

Use repo fixtures `toplist-229.csv`, `toplist.json`. Edit an existing `toplist_list` post, import, reload, confirm pipe content in metabox matches fixture row count.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main (or no code change needed — document verification result).
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
