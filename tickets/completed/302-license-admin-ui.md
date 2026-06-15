# Ticket 302: License Admin UI and Cron

**Sprint:** 3 — Premium Licensing
**Status:** Not started
**Owner:** unassigned
**Estimate:** M

---

## Context

Premium customers enter license keys in WP admin. Solar-form provides a settings section with activate/deactivate, status display, and scheduled recheck from API `recheck_after`.

## Goal

Premium plugin settings include license key management with automatic background recheck.

## Acceptance criteria

- [ ] Settings → Toplist Block (or dedicated submenu) shows license key field, Activate/Deactivate, status badge
- [ ] Successful activation stores cached entitlements via ticket 301 class
- [ ] `wp_schedule_event` (or Action Scheduler) rechecks license when cache expires
- [ ] Library CPT and import features only register when `Toplist_Block_License::is_valid()` (premium runtime, not lite)
- [ ] Admin notices for expired/invalid license are dismissible and non-blocking for local blocks
- [ ] UI absent from lite build (smoke test)

## Out of scope

- Portal customer account page
- Usage telemetry to portal (future ticket)
- Multisite satellite domains (future; document if deferred)

## Dependencies

- **Blocks:** 304
- **Blocked by:** 301
- **External:** none

## Approach (optional)

Hook license section into existing `settings-page.php`. Gate `register_post_type` for `toplist_list` on `is_valid()`.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
