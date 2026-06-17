# Ticket 705: WordPress Coding Standards (WPCS) Integration

**Sprint:** 6 — Quality (PHPStan + testing)
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

While PHPStan handles static typing and error detection, we have lacked a proper style and standards linter. Introducing `PHP_CodeSniffer` with the `WordPress Coding Standards (WPCS)` ruleset ensures the plugin adheres precisely to WP.org directory expectations regarding escaping, formatting, and standard WP functions.

We have successfully installed `wpcs` via Composer, established a baseline `phpcs.xml.dist`, and run `phpcbf` to automatically resolve over 7,000 formatting violations. However, some manual fixes (escaping, complex spacing) remain.

## Goal

Integrate WPCS into the automated CI pipeline and clear the remaining manual formatting and standard violations across the codebase.

## Acceptance criteria

- [x] Install `wp-coding-standards/wpcs` and `dealerdirect/phpcodesniffer-composer-installer` via Composer.
- [x] Create `phpcs.xml.dist` configured with the `WordPress` ruleset, targeting the `toplist-block/` directory and excluding `vendor/`, `tests/`, and the `lite` build.
- [x] Run `vendor/bin/phpcbf` to auto-fix the low-hanging fruit.
- [x] Add a `composer phpcs` script to `composer.json` and include it in the `composer check` quality gate.
- [x] Manually review and fix the remaining ~480 `phpcs` violations (e.g. escaping output properly, Yoda conditions, etc).

## Dependencies

- **Blocks:** 699
- **Blocked by:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.

## Decisions / closing notes

- `composer phpcs` wired into the `composer check` gate alongside `test`, `phpstan`, `test:build`.
- All remaining manual violations cleared: `vendor/bin/phpcs --report=summary` reports **0 errors** across the 8 scanned premium source files.
- `composer check` is fully green (22 unit tests / 50 assertions, PHPCS clean, PHPStan level 9 clean, lite build smoke tests pass).
- Premium bootstrap refactored in passing: `includes/pro/bootstrap.php` → `includes/pro/class-toplist-block-pro-bootstrap.php`; stale PHPStan baselines removed now that the tree is clean.
