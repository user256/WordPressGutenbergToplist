# Tests & static analysis

## Testing as you go

When you change PHP behaviour in the plugin, add or extend tests in the same change:

| Area changed | Minimum test action |
|--------------|---------------------|
| Parse/import/upload helpers | Unit test in `tests/unit/` |
| Premium `includes/` classes | Unit test for pure methods (no wp-env required) |
| REST/admin flows | Integration test when wp-env is available |
| Build/lite strip rules | `composer test:build` must pass |

Run `composer check` before pushing — it matches CI.

## Unit tests

```bash
composer install
composer test
```

Runs PHPUnit against stubs in `tests/bootstrap.php` (no WordPress required).

## PHPStan (level 9)

```bash
composer phpstan
```

- Config: `phpstan.neon.dist` (level 9, WordPress stubs)
- Baseline: `phpstan-baseline.neon` — shrink file-by-file; `toplist-block/includes/` is baseline-free
- Bootstrap: `tests/phpstan-bootstrap.php`

## Build smoke tests

```bash
composer test:build
```

## Full local check (CI parity)

```bash
composer check
```

Runs unit tests, PHPStan, and lite build smoke tests.

## Integration tests (wp-env)

Requires Node.js, Docker, and npm dependencies:

```bash
npm install
npm run test:integration:setup
composer test:integration
```

Integration tests skip automatically when wp-env is not running or Docker is unavailable.

CI: `.github/workflows/integration.yml` runs on `workflow_dispatch` and PRs touching plugin/integration paths.

Optional environment variables:

| Variable | Default | Purpose |
|----------|---------|---------|
| `TOPLIST_WP_ENV_URL` | `http://localhost:8888` | WordPress URL inside wp-env |
| `TOPLIST_WP_ENV_BIN` | `npx wp-env` | wp-env executable |

For live portal license validation against a local API, use `bash scripts/setup-local-license.sh` and verify in wp-admin.
