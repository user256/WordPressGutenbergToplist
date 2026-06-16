# Ticket 604: WordPress.org SVN Deploy Automation

**Sprint:** 6 — Post-Launch Expansion
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

After initial WP.org submission, lite releases need repeatable deploys from `php scripts/build-lite.php` output to the plugin SVN trunk/tags. Manual SVN steps are error-prone.

## Goal

A documented script (or CI workflow) publishes `toplist-block-lite/` to WP.org SVN trunk and tags a version from the lite plugin header.

## Acceptance criteria

- [x] `scripts/deploy-wporg.sh` (or workflow) documents required secrets (`WPORG_SVN_USER`, app password)
- [x] Dry-run mode lists files that would be committed
- [x] Tag created from `Version:` in lite `toplist-block-lite.php`
- [ ] `assets/` banner/icon copy steps documented if not automated
- [ ] Does not publish premium `toplist-block/` sources

## Out of scope

- Plugin review team interactions
- Automated readme.txt translation

## Dependencies

- **Blocks:** none
- **Blocked by:** WP.org plugin approval (operator)
- **External:** WordPress.org SVN access

## Notes / decisions log

- 2026-06-15 — Filed from launch backlog.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. `tickets/overview.md` Sprint 6 item checked.
