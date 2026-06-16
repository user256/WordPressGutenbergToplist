# WordPress.org SVN deploy (lite only)

Ticket 604 — publish `toplist-block-lite/` to the plugin SVN repository.

## Prerequisites

- WP.org plugin slug approved (expected: `toplist-block-lite`)
- [WordPress.org SVN credentials](https://wordpress.org/plugins/developers/) — username + application password (not your login password)

## Build

```bash
php scripts/build-lite.php
```

Produces `toplist-block-lite/` and `toplist-block-lite.zip`. The deploy script only uploads the **lite** directory — never `toplist-block/` (premium).

## Dry run

```bash
bash scripts/deploy-wporg.sh --dry-run
```

Lists files that would sync to SVN trunk.

## Deploy

```bash
export WPORG_SVN_USER='your-wporg-username'
export WPORG_SVN_PASSWORD='your-application-password'
bash scripts/deploy-wporg.sh
```

Creates `tags/{Version}` from the `Version:` header in `toplist-block-lite.php`.

## Plugin assets (banner / icon)

WordPress.org plugin assets live in the SVN `assets/` folder (sibling to `trunk/`), not inside the plugin zip:

```
svn/wp-plugins/toplist-block-lite/
  assets/          ← banner-772x250.png, icon-256x256.png, etc.
  trunk/           ← plugin files from build
  tags/1.0.0/
```

After first SVN checkout, copy your images manually:

```bash
svn co https://plugins.svn.wordpress.org/toplist-block-lite /tmp/wporg-toplist-lite
cp path/to/banner-772x250.png /tmp/wporg-toplist-lite/assets/
cp path/to/icon-256x256.png /tmp/wporg-toplist-lite/assets/
cd /tmp/wporg-toplist-lite && svn add assets/* && svn ci -m 'Add plugin assets'
```

Required sizes: see [Plugin Assets handbook](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).

## Safety checks

- `deploy-wporg.sh` builds lite from `scripts/build-lite.php` before upload
- Premium source `toplist-block/` is never referenced by the deploy script
- Run `composer test:build` before deploying to confirm lite smoke tests pass
