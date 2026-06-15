# Security audit (ticket 406)

Target: premium source `toplist-block/` and lite build output. Date: 2026-06-15.

## REST API (`toplist-block/v1/toplists`)

| Check | Status |
|-------|--------|
| `GET /toplists` requires `edit_posts` | Pass |
| `GET /toplists/{id}` requires `edit_post` for that post | Pass |
| `POST /toplists` requires `edit_posts`; name sanitized | Pass |
| Premium-only (not registered without valid license) | Pass |

## Admin-post import/export

| Handler | Capability | Nonce |
|---------|------------|-------|
| `toplist_handle_import_csv` | `edit_post` | `toplist_import_csv_{id}` |
| `toplist_handle_import_json` | `edit_post` | `toplist_import_json_{id}` |
| `toplist_handle_export_csv/json` | `edit_post` | `check_admin_referer` |
| `toplist_handle_import_all_csv` | `manage_options` | `toplist_import_all_csv_nonce` |
| `toplist_handle_export_all_csv` | `edit_posts` | `check_admin_referer` |
| `toplist_save_toplist_raw_content` | `edit_post` | `toplist_save_raw_content` |

## AJAX dismiss notices

| Action | Fix applied |
|--------|-------------|
| `toplist_lite_dismiss_upgrade_notice` | `check_ajax_referer` + `manage_options` |
| `toplist_license_dismiss_notice` | `check_ajax_referer` + `manage_options` |

## Input sanitization

- Import handlers store **raw pipe text** in `post_content` (not HTML) — rendered with `esc_html` / `esc_url` on output.
- Settings CSS: `toplist_sanitize_css()` strips tags before save.
- CSV headers normalized via `toplist_normalize_csv_header()`.
- JSON import uses `toplist_decode_external_toplist_json()` then pipe encoding — no unserialize.

## Output escaping

- Front-end render: `esc_html`, `esc_url`, `esc_attr` on user fields.
- Admin metaboxes: `esc_textarea`, `esc_attr`, `esc_html__`.
- JSON-LD schema: `wp_json_encode` on server-built array (no raw user HTML).

## Lite build

- No license endpoints, no REST library routes, no import handlers in lite tree (verified by smoke tests).

## Residual risks / follow-ups

- Block editor JSON import is client-side only (no server upload) — acceptable.
- Custom CSS in block/global settings is output in `<style>` after `wp_strip_all_tags` — site-owner capability model.
- Professional penetration test not in scope (ticket 406).
