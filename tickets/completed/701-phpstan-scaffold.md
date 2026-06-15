# Ticket 701: PHPStan Level 9 Scaffold

**Sprint:** 6 — Quality
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

The plugin has PHPUnit unit tests but no static analysis. Solar-form uses PHPStan with WordPress stubs. We want level 9 from the start with a baseline for legacy procedural code, ratcheted down file-by-file.

## Goal

`composer phpstan` runs PHPStan level 9 against the premium plugin tree with WordPress stubs and a committed baseline.

## Acceptance criteria

- [x] `phpstan/phpstan` and `szepeviktor/phpstan-wordpress` in `require-dev`
- [x] `phpstan.neon.dist` at level 9 with bootstrap for plugin constants
- [x] `phpstan-baseline.neon` generated and committed
- [x] `composer phpstan` script documented in `tests/README.md`

## Out of scope

- Clearing the baseline (ticket 702+)
- PHPCS

## Dependencies

- **Blocked by:** none

---

## Definition of done

1. `composer phpstan` exits 0 locally.
2. Ticket archived; overview updated.
