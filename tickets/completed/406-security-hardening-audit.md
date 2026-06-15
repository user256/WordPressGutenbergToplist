# Ticket 406: Security Hardening Audit

**Sprint:** 4 — Distribution & Compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Before submitting the lite version to the WordPress.org plugin directory, a thorough security audit is necessary to prevent rejection or, worse, shipping vulnerabilities to users. The highest risk areas are the JSON/CSV import endpoints and the rendering of user-inputted row data.

## Goal

Conduct a targeted security pass over the plugin to ensure WP.org security standards are met.

## Acceptance criteria

- [ ] Verify that all AJAX/REST endpoints (like the admin import) have proper authorization (`current_user_can`) and nonce verification.
- [ ] Ensure all imported data (JSON/CSV) is strictly sanitized before saving to the database.
- [ ] Ensure all output in the frontend block render and admin UI is properly escaped (e.g., `esc_html`, `esc_attr`, `wp_kses`).
- [ ] Document the security checks performed.

## Out of scope

- Third-party professional penetration testing.

## Dependencies

- **Blocks:** 499
- **Blocked by:** 103 (Admin import fix)
- **External:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
