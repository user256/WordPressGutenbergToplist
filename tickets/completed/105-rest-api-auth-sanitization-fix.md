# Ticket 105: REST API Privilege Escalation & Sanitization Fix

**Sprint:** 1 — Stabilise & Document (Remedial)
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

During a code review of the `toplist-block.php` implementation, two critical security flaws were found in the REST API endpoint `POST /toplist-block/v1/toplists`:
1. **Privilege Escalation:** The endpoint only checks for the `edit_posts` capability (which Contributors have), but forcefully creates the post with `'post_status' => 'publish'`. This allows Contributors to publish toplists directly, bypassing the standard WordPress publishing workflow (`publish_posts` capability).
2. **Missing Sanitization (XSS):** The `$content` payload from the REST request is inserted directly into the database without passing through `wp_kses_post()` or similar sanitization. Because the user bypasses `publish_posts`, `wp_insert_post` will not enforce the `unfiltered_html` checks properly, allowing malicious users to inject `<script>` tags into the raw pipe content.

## Goal

Patch the REST API endpoint to properly enforce WordPress capabilities and sanitize incoming raw pipe content.

## Acceptance criteria

- [x] Update the `permission_callback` in `POST /toplists` to require `publish_posts` (since the endpoint publishes immediately) or map the `post_status` dynamically based on user capabilities.
- [x] Sanitize the incoming `$content` parameter using `wp_kses_post()` before passing it to `wp_insert_post()`.
- [x] Verify that existing valid pipe data (including URLs and basic HTML like `<b>` or `<a>`) survives the sanitization.

## Dependencies

- **Blocks:** 199
- **Blocked by:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
