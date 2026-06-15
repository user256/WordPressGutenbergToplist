# Ticket 102: Free vs Premium Manifest

**Sprint:** 1 — Stabilise & Document
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

WordPress.org guideline 5 forbids locked/trialware in the org plugin. The solar-form approach deletes premium code from the lite build rather than gating it with `if (is_premium())`. Before writing `build-lite.php`, every feature in the current plugin must be classified as lite, premium-only (deleted in build), or shared.

The current plugin mixes block editing, library CPT, linked lists, JSON/CSV import, and admin tooling in one tree.

## Goal

`docs/free-vs-premium.md` is the authoritative manifest listing every feature, file, hook, and REST route with its lite/premium disposition and the `PREMIUM_FILES` delete list for the build script.

## Acceptance criteria

- [ ] `docs/free-vs-premium.md` contains a feature matrix table (lite vs premium columns)
- [ ] Document lists `PREMIUM_FILES` paths to delete in lite build (minimum: license class stub path, CPT registration, import handlers, library REST)
- [ ] Document lists `block.js` sections to strip (Saved Toplist panel, Import JSON, linked mode attrs)
- [ ] Document states mutual exclusion: only one of lite or premium may be active (same block name `toplist/rankings` for content compatibility)
- [ ] Ticket 201 can implement delete/strip lists without further product decisions

## Out of scope

- Writing the build script itself
- Portal pricing or plan names
- Implementing stripped code

## Dependencies

- **Blocks:** 201, 202, 203
- **Blocked by:** 101
- **External:** WordPress.org guideline 5/6 (reference solar-form ticket 12 notes if available)

## Approach (optional)

Audit `toplist-block.php`, `block.js`, `settings-page.php`, `admin-diagnostics.php` for premium surfaces. Cross-check conversation map: lite = block + local pipe edit + basic toggles/CSS; premium = library CPT, linked mode, bulk import/export, license.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
