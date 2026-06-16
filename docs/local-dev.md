# Local development (WordPress + portal license E2E)

Use this when the premium plugin shows **“Toplist Block Pro features are inactive until you enter a valid license key”** and license verification fails because WordPress cannot reach the portal API.

## Prerequisites

- WordPress at `/var/www/html` (site URL typically `http://127.0.0.1`)
- Portal checkout at `../portal` with `api/config.local.php` configured:
  - `toplist_block_api_key`
  - `toplist_block_download_path` (optional, for OTA smoke tests)
- PHP CLI 8.x

## One-command setup

From the `toplist/` repo:

```bash
bash scripts/setup-local-license.sh
```

This will:

1. Start the portal API on `http://127.0.0.1:9080` (PHP built-in server + router)
2. Issue (or reuse) a lifetime dev license for your WordPress site domain

Then sync the plugin and activate:

```bash
cd ..
bash install-local.sh --plugin-only
```

In wp-admin: **Settings → Toplist Block**:

1. **License API URL** — `http://127.0.0.1:9080/api/v1/toplist-block/validate`
2. **Module API key** — from portal `toplist_block_api_key` in `api/config.local.php`
3. **License key** — from the setup script output (or portal account)

Click **Save & verify**.

Optional: `scripts/setup-local-license.sh` can still install a mu-plugin with constants; the settings UI is the normal path and does not require wp-config or mu-plugins.

## Portal API options

### PHP built-in server (default)

Started automatically by `setup-local-license.sh`. Logs: `.local-portal-dev.log`, pid: `.local-portal-dev.pid`.

Stop manually:

```bash
kill "$(cat .local-portal-dev.pid)" && rm -f .local-portal-dev.pid
```

### nginx + php-fpm (optional)

If you prefer nginx over the built-in server, use `scripts/dev/nginx-portal-local.conf` (listens on `127.0.0.1:9080`). Update `root` if your portal path differs, then:

```bash
sudo cp scripts/dev/nginx-portal-local.conf /etc/nginx/sites-available/toplist-portal-dev
sudo ln -sf /etc/nginx/sites-available/toplist-portal-dev /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
bash scripts/setup-local-license.sh --no-server
```

## Manual smoke test

```bash
curl -sS -X POST 'http://127.0.0.1:9080/api/v1/toplist-block/validate' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -d '{"domain":"127.0.0.1","license_key":"YOUR_LICENSE_KEY"}'
```

Expect JSON with `"success": true` and `"valid": true`.

## Environment variables

| Variable | Default | Purpose |
|----------|---------|---------|
| `PORTAL_ROOT` | `../portal` | Portal checkout path |
| `WP_ROOT` | `/var/www/html` | WordPress install |
| `PORTAL_PORT` | `9080` | Local portal API port |

## Related docs

- `docs/portal-setup.md` — production portal configuration
- `../install-local.sh` — sync theme/plugin into system WordPress
