# Ticket 204: Lite Build Smoke Tests

**Sprint:** 2 — Lite Build Pipeline
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Solar-form ticket 12 requires automated checks that the lite zip contains zero premium residue: no license UI, no `if (false)` artifacts, no internal dev paths. Toplist needs equivalent gates before any WP.org submission.

## Goal

`php tests/build/run.php` (or documented equivalent) fails CI locally if the lite build contains premium strings, files, or registration code.

## Acceptance criteria

- [ ] Test runner executes after `php scripts/build-lite.php` in CI or documented pre-release command
- [ ] Asserts zero matches for: `toplist_list`, `class-toplist-block-license`, `toplist_handle_import`, `recheck_license`, `if (false)`
- [ ] Asserts lite zip does not contain `tickets/`, `scripts/`, `tests/`, `CLAUDE.md`
- [ ] Asserts main plugin header text domain is `toplist-block-lite`
- [ ] Test failure message names the offending file and needle
- [ ] Documented in `docs/build.md` or README

## Out of scope

- PHPUnit behavioural tests (ticket 403)
- WP.org plugin check plugin (manual in ticket 401)
- Premium zip smoke tests (add in 304 if needed)

## Dependencies

- **Blocks:** 299, 401
- **Blocked by:** 202, 203
- **External:** none

## Approach (optional)

Port minimal checks from solar-form `tests/build/`. Keep tests fast (grep/scan, no WP bootstrap required).

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
