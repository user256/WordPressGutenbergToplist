# Ticket 612: Visual Drag & Drop Card Builder

**Sprint:** 6 — Post-Launch Expansion
**Status:** Proposed
**Owner:** unassigned
**Estimate:** XL

---

## Context

Currently, the Toplist block relies on predefined CSS and global setting toggles to dictate the shape and layout of the cards. To offer a truly premium experience, users should have the ability to visually drag, drop, resize, and reorder the fields (logo, rating, CTA, terms) on the cards.

## Goal

Build a drag-and-drop visual card editor that allows the user to design the specific look, layout, and size of the Toplist cards. The resulting layout should be saved as a CSS/layout override and applied to the frontend rendering.

## Acceptance criteria

- [ ] Build a visual UI in the Toplist editor that renders a "dummy" card and allows users to click-and-drag fields (e.g., Move Logo to the top, Move CTA below terms).
- [ ] Allow users to resize elements or columns on the card.
- [ ] Provide functionality to save this custom layout configuration as an override (likely as custom CSS Grid rules or Flexbox order rules) on the specific Toplist.
- [ ] Ensure the generated layout is responsive and degrades gracefully on mobile devices.

## Dependencies

- **Blocks:** 699
- **Blocked by:** 610 (Editor UX Enhancements) - This visual builder should integrate with the per-list overrides UI.

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
