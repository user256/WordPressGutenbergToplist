# Portal setup (Toplist Block Pro)

Ticket 303 — portal module `toplist-block` at `/home/user256/GitRepos/portal/modules/toplist-block/`.

## 1. Portal config (`api/config.local.php`)

```php
'toplist_block_api_key' => 'your-long-random-api-key',
'toplist_block_download_path' => '/var/www/downloads/toplist-block.zip',
// or: 'toplist_block_download_url' => 'https://cdn.example.com/toplist-block.zip',
```

Ensure `license_key_pepper` and `bearer_secret` are set (core licensing).

## 2. Seed plans

```bash
cd /home/user256/GitRepos/portal
php api/bin/seed-toplist-plans.php
```

Creates plans with `plan_features.value = toplist_block_pro`:

| Slug | Billing |
|------|---------|
| `toplist-block-pro` | monthly |
| `toplist-block-pro-yearly` | yearly |
| `toplist-block-pro-lifetime` | lifetime |

Map Stripe price IDs in admin before live checkout.

## 3. WordPress premium plugin (Settings → Toplist Block)

Enter on the **Toplist Block** settings page (premium plugin):

| Field | Example |
|-------|---------|
| **License API URL** | `https://YOUR-PORTAL-HOST/api/v1/toplist-block/validate` |
| **Module API key** | Value of `toplist_block_api_key` from `api/config.local.php` |
| **License key** | From portal account after purchase |

Optional: `wp-config.php` constants `TOPLIST_BLOCK_LICENSE_API_URL` and `TOPLIST_BLOCK_LICENSE_API_KEY` override the settings UI (for hosts that lock config in code).

## 4. Customer flow

1. Buy Toplist Block Pro (Stripe checkout on plan with `toplist_block_pro`)
2. Account → **Toplist Pro** (`/account/toplist-block/licenses.php`)
3. Enter primary domain → **Generate license**
4. **Download plugin zip** → install on WordPress
5. Settings → Toplist Block → paste license → **Save & verify**

## 5. Lite plugin upgrade CTA

Set when building lite zip (or override in `scripts/build-lite.php`):

```
https://YOUR-PORTAL-HOST/account/toplist-block/licenses.php
```

## 6. Smoke test

```bash
php api/bin/test-toplist-block-validate.php
```

## API reference

- Validate: `POST /api/v1/toplist-block/validate`
- Update check: `POST /api/v1/toplist-block/update-check` (same auth + body as validate, plus `plugin_version`)
- Download package: `GET /api/v1/toplist-block/download-package?token=…` (signed URL from update-check; license-gated)

Body for validate/update-check: `{ "domain": "example.com", "license_key": "…" }`

Success validate: `{ "success": true, "data": { "status": "active", "recheck_after": "…", … } }`

Success update-check (update available): `{ "success": true, "data": { "update_available": true, "new_version": "…", "package": "…", "sections": { "changelog": "…" } } }`

### OTA metadata (optional in `config.local.php`)

- `toplist_block_plugin_version` — override version read from zip header
- `toplist_block_plugin_changelog` — HTML for WP “View version details”
- `toplist_block_changelog_path` — path to plain-text/HTML changelog file
- `toplist_block_plugin_tested` / `toplist_block_plugin_requires` / `toplist_block_plugin_requires_php`

WordPress derives update-check URL from validate URL (`/validate` → `/update-check`) or set `TOPLIST_BLOCK_UPDATE_API_URL` in `wp-config.php`.

See also: `portal/docs/extension-toplist-block.md`.
