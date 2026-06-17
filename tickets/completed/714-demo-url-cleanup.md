# Ticket 714: Replace demo `example.com` / placeholder URLs in sample content

**Sprint:** 7 — WP.org submission compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

The WP.org audit ([`docs/wporg-audit-toplist-block-lite-2026-06-16.md`](../docs/wporg-audit-toplist-block-lite-2026-06-16.md)) noted (🟠, non-blocking) that sample row text contains `https://via.placeholder.com/...` and `https://example.com` — in the lite build at `block.js:475`, with equivalents in premium source (e.g. [`library.php:1151`](../toplist-block/includes/pro/library.php#L1151)). These aren't runtime remote loads, but reviewers may flag them during a broad scan.

## Goal

Sample/demo content uses clearly-labelled placeholder values that don't read as live external URLs.

## Acceptance criteria

- [x] Demo `example.com` / `via.placeholder.com` URLs in `block.js` sample rows and premium sample data replaced with documented placeholders or local example values. — *Replaced the only live external host (`via.placeholder.com` logo) with `https://example.com/logo.png`, matching `library.php` sample data. Kept `example.com` URLs: IANA-reserved, conventional for samples, and a `data:` URI was rejected because the logo render path runs `esc_url()` which strips the `data:` protocol.*
- [x] No behaviour change to real (non-sample) rendering. — *Only the editor's "add example row" demo string changed.*
- [x] `composer check` stays green; rebuilt lite reflects the change. — *Rebuilt lite has no `placeholder.com` URL (comment aside).*

## Out of scope

- `placeholder=` attribute hints in admin inputs (those are legitimate UI hints, not shipped sample data).
- Any blocking enqueue work (710–713).

## Dependencies

- **Blocks:** 799
- **Blocked by:** none

## Notes / decisions log

- 2026-06-17 — Filed from WP.org audit (2026-06-16), 🟠 should-fix item. Optional polish to reduce manual-review pend risk.
- 2026-06-17 — Tried a self-contained SVG `data:` URI for the demo logo but
  reverted: the logo render path escapes via `esc_url()`/`esc_url_raw()`, which
  drops the `data:` protocol, so the demo logo would render empty. Allowing
  `data:` globally is an unnecessary security surface for a cosmetic sample, so
  used the reserved `example.com/logo.png` instead. The remaining `example.com`
  references are intentionally kept (reserved documentation domain).

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-up work filed as new tickets, not absorbed.
