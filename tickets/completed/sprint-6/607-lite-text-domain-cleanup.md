# Ticket 607: Lite Text Domain Cleanup

**Sprint:** 6 — Post-Launch Expansion
**Status:** Not started
**Owner:** unassigned
**Estimate:** S

---

## Context

Ticket 405 added `load_plugin_textdomain()` and a `.pot` file. The lite build may still use mixed text domains (`toplist` vs `toplist-block-lite`) from historical strings. WP.org prefers a stable domain matching the plugin slug.

## Goal

Lite and premium use consistent, documented text domains; generated lite rewrites or standardizes on `toplist-block-lite` where appropriate.

## Acceptance criteria

- [ ] Audit: `grep __(` across premium and lite build output
- [ ] Lite build script enforces target domain for user-facing strings
- [ ] `.pot` regenerated and referenced in lite package
- [ ] No new `_load_textdomain_just_in_time` notices on activate
- [ ] Documented in `docs/upgrade.md` or i18n section of README

## Out of scope

- Community translations on translate.wordpress.org (operator submits)
- Translating admin copy that is intentionally English-only

## Dependencies

- **Blocks:** none
- **Blocked by:** none
- **External:** none

## Notes / decisions log

- 2026-06-15 — Filed as 405 follow-up.

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. `tickets/overview.md` Sprint 6 item checked.
