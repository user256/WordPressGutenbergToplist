# Ticket 501: Admin Row Spreadsheet UI

**Sprint:** 5 — Pro Feature Expansion
**Status:** Done
**Owner:** unassigned
**Estimate:** L

---

## Context

Raw pipe editing in admin metabox is error-prone for non-technical editors. A row/spreadsheet UI would differentiate premium and improve library CPT editing. Deferred until two-plugin launch is complete.

## Goal

Premium admin metabox offers a table/spreadsheet row editor as an alternative to raw pipe textarea.

## Acceptance criteria

- [ ] Toplists edit screen has toggle or tab: Spreadsheet vs Raw pipes
- [ ] Spreadsheet columns match full field set from manifest
- [ ] Edits sync to stored pipe content on save
- [ ] Feature absent from lite build (smoke test 204)
- [ ] Usable with 10+ rows without browser hang

## Out of scope

- Front-end block spreadsheet editing
- Excel file upload
- Real-time collaboration

## Dependencies

- **Blocks:** none
- **Blocked by:** 499
- **External:** none

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
