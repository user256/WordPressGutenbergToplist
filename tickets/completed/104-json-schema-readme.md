# Ticket 104: JSON Schema and README

**Sprint:** 1 — Stabilise & Document
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

`README.md` still documents the legacy 8-field pipe format (`operator|product|offer|link|logo|year|button|terms|bullets`). The plugin and block editor now support a wider pipe schema and external JSON (`name`, `bonus`, `visit_link`, `image_url`, `features[]`, etc.) with fixtures in `toplist.json` and `toplist_updated.json`.

Customers and WP.org reviewers need accurate docs before the two-plugin split ships.

## Goal

`README.md` documents the external JSON schema, the full pipe field set, and the planned lite vs premium distribution model.

## Acceptance criteria

- [ ] README includes JSON field mapping table (JSON key → internal field), matching `block.js` / `toplist_decode_external_toplist_json`
- [ ] README documents pipe format with all current columns (not just 8 legacy fields)
- [ ] README explains two-plugin model: lite on WP.org, premium from portal; mutually exclusive installs
- [ ] README links to `docs/free-vs-premium.md` (after ticket 102)
- [ ] Example snippets reference `toplist.json` or `toplist_updated.json` from the repo

## Out of scope

- WP.org `readme.txt` for lite (ticket 401)
- User-facing marketing copy on portal
- Changing parser behaviour

## Dependencies

- **Blocks:** 401
- **Blocked by:** 102 (for free-vs-premium link)
- **External:** none

## Approach (optional)

Extract mapping from `toplist_decode_external_toplist_json` in `toplist-block.php` and `jsonRowToItem` in `block.js`. Keep README concise; detail lives in `docs/` if needed.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
