# Ticket 403: PHPUnit Parse and Import

**Sprint:** 4 — Distribution & Compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

JSON import and pipe parsing bugs have caused silent failures. Smoke tests (204) scan strings; behavioural tests need fixtures (`toplist.json`, `toplist-229.csv`) and PHPUnit bootstrap.

## Goal

PHPUnit covers external JSON decode, pipe parse, and import round-trip on repo fixtures.

## Acceptance criteria

- [ ] `phpunit.xml.dist` and `tests/` directory with bootstrap
- [ ] Test: `toplist_decode_external_toplist_json(toplist.json)` returns expected row count
- [ ] Test: `toplist_items_to_external_json_rows` round-trip preserves key fields
- [ ] Test: CSV import parser matches JSON row count for `toplist-229` fixtures
- [ ] Test: wide pipe rows (20 columns) parse without virtual header truncation
- [ ] `composer test` or `vendor/bin/phpunit` documented in README
- [ ] Tests run on premium source (not lite strip)

## Out of scope

- WordPress integration tests (full WP bootstrap)
- block.js Jest tests
- CI wiring to GitHub Actions (optional nice-to-have)

## Dependencies

- **Blocks:** release confidence
- **Blocked by:** 103 (known-good import behaviour)
- **External:** none

## Approach (optional)

Extract pure parse functions or test via class methods if WP functions required use Brain Monkey or minimal stubs.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
