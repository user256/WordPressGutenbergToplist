# Ticket 610: Toplist Editor UX Enhancements (Live Preview & Overrides)

**Sprint:** 6 — Post-Launch Expansion
**Status:** Proposed
**Owner:** unassigned
**Estimate:** M

---

## Context

Currently, editing a Toplist in the backend (`wp-admin/post.php?post=ID&action=edit`) only provides a raw pipe/spreadsheet editor and CSV/JSON tools. The user experience can be significantly improved by adding a live preview of the block, allowing editors to see exactly how their data will render without having to embed the shortcode on a frontend page first. 

Additionally, the powerful theming and field-toggle UI currently available only on the global Settings page should be replicated as a "per-list override" metabox directly on the edit screen.

## Goal

Improve the Toplist edit screen UX by adding a collapsible live preview and a per-list theme/toggle override metabox.

## Acceptance criteria

- [ ] **Live Preview Metabox:** Add a new collapsible metabox to the `toplist_list` edit screen that renders a live preview of the toplist.
- [ ] The preview should re-render dynamically via AJAX or Alpine/React when the spreadsheet data changes or when override settings are modified.
- [ ] **Per-List Overrides Metabox:** Replicate the "Global CSS / Theme Builder" and "Visibility Toggles" UI from `settings-page.php` into a new metabox on the edit screen.
- [ ] If a user configures overrides on a specific toplist, these settings should be saved as post meta and override the global settings when that specific toplist is rendered on the frontend.
- [ ] The override metabox should clearly indicate when a setting is inheriting the global default vs. actively overriding it.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
