# Ticket 603: WordPress Integration Tests

**Sprint:** 6 — Post-Launch Expansion
**Status:** In progress
**Owner:** unassigned
**Estimate:** M

---

## Context

Sprint 4 added PHPUnit unit tests with stubs. Solar-form uses `@wordpress/env` for integration tests against a real WordPress runtime. Toplist needs the same scaffold to catch activation, REST, and license regressions.

## Goal

`composer test:integration` runs a wp-env-backed suite that activates the premium plugin and asserts core behaviours.

## Acceptance criteria

- [ ] `.wp-env.json` mounts `toplist-block/` (and optionally lite after build)
- [ ] `tests/IntegrationTestCase.php` skips cleanly when Docker/wp-env unavailable
- [ ] At least one smoke test: plugin activates and license class is loadable
- [ ] Documented in `tests/README.md` or `docs/local-dev.md`
- [ ] CI job documented (optional separate workflow step; may stay manual until runners have Docker)

## Out of scope

- Full browser E2E (Playwright)
- Portal API tests (covered by portal `api/bin/test-toplist-block-validate.php`)
- Lite edition matrix in CI (follow-up)

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** Docker for local/CI runs

## Notes / decisions log

- 2026-06-15 — Scaffold started in same programme batch as ticket filing.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. `tickets/overview.md` Sprint 6 item checked.
