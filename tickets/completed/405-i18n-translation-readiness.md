# Ticket 405: i18n Translation Readiness

**Sprint:** 4 — Distribution & Compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

WordPress.org guidelines require all plugins to be translation-ready. Currently, there is no verified process for ensuring all strings in Toplist Block use the correct text domains and that a `.pot` template file is generated for translators.

## Goal

Audit the codebase for translatable strings, ensure a consistent text domain, and automate the generation of the `.pot` file.

## Acceptance criteria

- [ ] All user-facing strings in PHP use `__()`, `_e()`, etc., with the correct `toplist-block` text domain.
- [ ] All user-facing strings in JavaScript use `@wordpress/i18n` functions.
- [ ] A `.pot` file is generated and placed in a `languages/` directory.
- [ ] The lite build pipeline includes the `.pot` file in the generated `toplist-block-lite/` directory.

## Out of scope

- Actually translating the plugin into other languages.

## Dependencies

- **Blocks:** 499
- **Blocked by:** none
- **External:** WP CLI (for `wp i18n make-pot`)

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
