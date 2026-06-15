# Ticket 402: Lite-to-Premium Migration

**Sprint:** 4 — Distribution & Compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Customers start on WP.org lite and upgrade to portal premium. Both use block name `toplist/rankings` so post content should survive. Plugin slugs differ (`toplist-block-lite` vs `toplist-block`); upgrade is deactivate lite → install premium.

## Goal

Documented and tested upgrade path preserves existing block content and options.

## Acceptance criteria

- [ ] Test post with lite block (local items) renders identically after lite deactivate + premium activate
- [ ] Shared option keys documented (`toplist_*` or equivalent); no collision on switch
- [ ] `docs/upgrade.md` explains steps: deactivate lite, install premium, enter license, optional library migration
- [ ] Premium detects if lite was previously active and shows one-time admin notice (optional, no data loss)
- [ ] No duplicate block registration if both zips accidentally present (activation guard or clear error)

## Out of scope

- Automatic in-plugin upgrade installer
- Migrating lite users' data to library CPT automatically
- WooCommerce integration

## Dependencies

- **Blocks:** none
- **Blocked by:** 299, 302
- **External:** Two test WP installs or one with zip swap

## Approach (optional)

On premium activation, `get_option` migration from lite prefix if different. Block attrs already in post content need no DB migration.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
