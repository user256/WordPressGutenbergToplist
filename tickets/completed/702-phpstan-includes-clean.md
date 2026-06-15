# Ticket 702: PHPStan Level 9 — Premium Includes

**Sprint:** 6 — Quality
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Ticket 701 adds PHPStan level 9 with a baseline. The premium classes in `toplist-block/includes/` are the smallest typed surface and should pass without baseline entries first.

## Goal

`toplist-block/includes/` passes PHPStan level 9 with zero baseline ignores.

## Acceptance criteria

- [x] No `ignoreErrors` entries for paths under `toplist-block/includes/`
- [x] Type-safe helpers for mixed cache/API values (no blind `(string)` casts)
- [x] `composer phpstan` still exits 0

## Out of scope

- `toplist-block.php` main file (future ticket)
- `settings-page.php` (future ticket)

## Dependencies

- **Blocked by:** 701

---

## Definition of done

1. `vendor/bin/phpstan analyse toplist-block/includes` at level 9 with no baseline — exit 0.
2. Baseline regenerated and smaller than after 701.
