# Ticket 101: Monorepo Structure

**Sprint:** 1 — Stabilise & Document
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

Today the plugin lives in a flat `toplist-block/` folder with no `scripts/`, `tests/`, or `docs/` layout. The solar-form project treats `solar-lead-capture/` as premium canonical source and generates `solar-lead-capture-lite/` via `scripts/build-free.php`. Toplist needs the same skeleton before any stripping work begins.

Without this structure, build and test tooling has nowhere to live and agents will edit the wrong tree.

## Goal

The repo root matches the solar-form monorepo layout with `toplist-block/` as the premium canonical edit target.

## Acceptance criteria

- [ ] Directories exist: `scripts/`, `tests/build/`, `docs/`, `tickets/completed/`
- [ ] `toplist-block-lite/` is listed in `.gitignore` (generated output — never commit hand edits)
- [ ] `process_tickets.py` runs from repo root: `python process_tickets.py` (dry run, exit 0)
- [ ] `CLAUDE.md` at repo root documents the two-plugin model and points to `tickets/overview.md`
- [ ] `docs/wporg-vs-premium.md` stub exists with a one-paragraph summary and link to ticket 102

## Out of scope

- Implementing `build-lite.php` (ticket 201)
- Moving or renaming plugin PHP files beyond directory scaffolding
- Portal or license work

## Dependencies

- **Blocks:** 102, 201
- **Blocked by:** none
- **External:** none

## Approach (optional)

Copy directory layout from `/home/user256/GitRepos/solar-form` without copying solar-specific code. Add `.gitignore` entries for `toplist-block-lite/`, `*.zip`, `dist/`.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
