# Ticket 704: CI Quality Gate (PHPStan + PHPUnit)

**Sprint:** 6 — Quality
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

CI runs PHPUnit and build smoke tests but not PHPStan. Local dev should have one command that matches CI.

## Goal

Every push/PR runs `composer check` (lint + unit tests + phpstan + build smoke).

## Acceptance criteria

- [x] `composer check` runs `composer test`, `composer phpstan`, `composer test:build`
- [x] `.github/workflows/ci.yml` includes PHPStan step
- [x] Documented in `tests/README.md`

## Out of scope

- Integration tests in default CI (optional workflow exists)
- PHPCS

## Dependencies

- **Blocked by:** 701

---

## Definition of done

1. CI green with phpstan step.
2. `composer check` documented as pre-push command.
