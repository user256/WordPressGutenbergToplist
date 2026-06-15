# Ticket 305: Premium OTA Plugin Updates

**Sprint:** 3 — Premium Licensing
**Status:** Done
**Owner:** unassigned
**Estimate:** M

---

## Context

Premium customers will need to receive updates to the Toplist Block Pro plugin directly within their WordPress dashboard. Currently, there is no mechanism specified for delivering these Over-The-Air (OTA) updates, which means users would have to manually download new versions from the portal and upload them to their site.

## Goal

Implement an update checker mechanism within the premium plugin that securely fetches update information and download packages from the portal using the valid license key.

## Acceptance criteria

- [x] Hook into `pre_set_site_transient_update_plugins` to check the portal API for newer versions of the premium plugin.
- [x] Portal API provides the new version number, changelog, and a secure download link (gated by a valid license key).
- [x] Hook into `plugins_api` to show the update details/changelog in the WP admin "View version details" modal.
- [x] The update mechanism is completely stripped from the lite build.

## Out of scope

- Auto-updating (handled by WP core if enabled by the user).
- Delivering updates to expired licenses (portal API should reject the download request).

## Dependencies

- **Blocks:** 399
- **Blocked by:** 301, 303
- **External:** Portal API support for plugin update payloads

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
