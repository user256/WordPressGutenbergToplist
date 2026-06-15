# Ticket 304: Premium Distribution Zip

**Sprint:** 3 — Premium Licensing
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Customers download premium from portal. Solar-form `build-free.php` also emits `solar-lead-capture.zip` excluding dev tooling. Toplist build should produce both zips from one command.

## Goal

`php scripts/build-lite.php` (or sibling target) produces `toplist-block.zip` suitable for portal distribution.

## Acceptance criteria

- [ ] Premium zip contains full `toplist-block/` including license class and library features
- [ ] Premium zip excludes: `tickets/`, `scripts/`, `tests/`, `.git/`, `toplist-block-lite/`, dev markdown
- [ ] Zip version matches plugin header `Version:` in main PHP file
- [ ] `docs/build.md` documents both zip outputs and release checklist
- [ ] Test install: upload premium zip to clean WP, activate, enter test license from ticket 303
- [ ] Lite zip still builds in same run (no regression on 201–204)

## Out of scope

- Automated portal upload
- WordPress update API
- Code signing

## Dependencies

- **Blocks:** 499
- **Blocked by:** 301, 302
- **External:** none

## Approach (optional)

Extend build script with `--premium-only` / `--all` flags or always emit both zips like solar-form.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
