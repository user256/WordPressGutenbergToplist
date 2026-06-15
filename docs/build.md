# Build

Generate lite and premium distribution trees from canonical source.

## Prerequisites

- PHP CLI
- `zip` command

## Commands

From repo root (`toplist/`):

```bash
php scripts/build-lite.php
php tests/build/run.php
```

## Outputs

| Artifact | Contents |
|----------|----------|
| `toplist-block-lite/` | WP.org plugin (generated) |
| `toplist-block-lite.zip` | Lite upload zip |
| `toplist-block.zip` | Premium upload zip (dev paths excluded) |

## Release checklist

1. Bump `Version:` in `toplist-block/toplist-block.php`
2. `php scripts/build-lite.php`
3. `php tests/build/run.php` — must exit 0
4. Smoke-test lite zip on clean WP (block insert + save)
5. Smoke-test premium zip (library + import)

Never edit `toplist-block-lite/` by hand. Fix `toplist-block/` and rebuild.
