# Ticket 703: PHPUnit Expansion + Testing-as-You-Go Policy

**Sprint:** 6 — Quality
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Unit tests cover parse/import and upload validation. Premium license parsing and API response shaping are untested. Contributors need a clear rule: add or extend tests with every behaviour change.

## Goal

Document the testing policy and add unit tests for license API parsing helpers that run without wp-env.

## Acceptance criteria

- [x] `tests/README.md` defines testing-as-you-go (unit test with every PHP change in tested paths)
- [x] New tests for `Toplist_Block_License::api_success_data()` and related pure helpers
- [x] `composer test` green; test count documented in ticket notes

## Out of scope

- Full wp-env license E2E (603)
- 100% coverage of `toplist-block.php`

## Dependencies

- **Blocked by:** none

---

## Notes / decisions log

- 2026-06-15 — 13 unit tests, 35 assertions after adding LicenseApiParseTest + UtilTest.

## Definition of done

1. Policy section merged.
2. At least one new test class for license parsing.
3. `composer test` passes.
