# Ticket 201: build-lite.php Scaffold

**Sprint:** 2 — Lite Build Pipeline
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

Solar-form generates `solar-lead-capture-lite/` from premium source via `scripts/build-free.php`: copy, delete premium files, rename slug/text domain, transform PHP/JS, zip. Toplist needs the same entry point before feature-specific stripping tickets land.

## Goal

`php scripts/build-lite.php` from repo root copies `toplist-block/` → `toplist-block-lite/`, renames the main plugin file/bootstrap, and produces `toplist-block-lite.zip`.

## Acceptance criteria

- [ ] `scripts/build-lite.php` exists and runs from CLI only
- [ ] Script copies `toplist-block/` to `toplist-block-lite/` (clean destination each run)
- [ ] Main plugin file renamed to `toplist-block-lite.php` with updated plugin header (name, text domain, slug)
- [ ] Output zip `toplist-block-lite.zip` excludes dev paths (`tickets/`, `scripts/`, `tests/`, `.git/`)
- [ ] Script fails loudly if run from wrong directory (no `toplist-block/` sibling)
- [ ] README or `docs/build.md` documents the command

## Out of scope

- Deleting premium PHP/JS (tickets 202, 203) — scaffold may copy full tree first; stripping added incrementally
- Smoke tests (ticket 204)
- Premium zip (ticket 304)

## Dependencies

- **Blocks:** 202, 203, 204
- **Blocked by:** 101, 102
- **External:** Reference `/home/user256/GitRepos/solar-form/scripts/build-free.php`

## Approach (optional)

Start with phases 1–2 of solar build script (clean/copy/rename). Add delete/transform hooks as empty arrays filled by ticket 202/203.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
