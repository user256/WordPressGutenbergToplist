#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/toplist-block/languages/toplist.pot"
mkdir -p "$(dirname "$OUT")"

if command -v wp >/dev/null 2>&1; then
  wp i18n make-pot "$ROOT/toplist-block" "$OUT" \
    --domain=toplist \
    --exclude=node_modules,vendor \
    --headers='{"Report-Msgid-Bugs-To":"https://github.com/user256/toplist-block"}'
  echo "Wrote $OUT"
  exit 0
fi

cat > "$OUT" <<'EOF'
# Copyright (C) 2026 Toplist Block
msgid ""
msgstr ""
"Project-Id-Version: Toplist Block 0.1.2\n"
"Report-Msgid-Bugs-To: https://github.com/user256/toplist-block\n"
"Language-Team: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Generator: scripts/make-pot.sh (fallback)\n"
"X-Domain: toplist\n"

#. Run with WP-CLI installed to regenerate: bash scripts/make-pot.sh
EOF
echo "WP-CLI not found — wrote minimal stub $OUT (install wp-cli for full extraction)"
