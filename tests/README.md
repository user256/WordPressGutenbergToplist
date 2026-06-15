# Tests

## Unit tests

```bash
composer install
composer test
```

Runs PHPUnit against stubs in `tests/bootstrap.php` (no WordPress required).

## Build smoke tests

```bash
composer test:build
```

## Integration tests (wp-env)

Requires Node.js, Docker, and npm dependencies:

```bash
npm install
npm run test:integration:setup
composer test:integration
```

Integration tests skip automatically when wp-env is not running or Docker is unavailable.

Optional environment variables:

| Variable | Default | Purpose |
|----------|---------|---------|
| `TOPLIST_WP_ENV_URL` | `http://localhost:8888` | WordPress URL inside wp-env |
| `TOPLIST_WP_ENV_BIN` | `npx wp-env` | wp-env executable |

For live portal license validation against a local API, use `bash scripts/setup-local-license.sh` and verify in wp-admin; automated portal E2E is out of scope for this scaffold.
