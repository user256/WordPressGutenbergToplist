#!/usr/bin/env bash
# Deploy the generated lite build to WordPress.org SVN (ticket 604).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
LITE_DIR="${ROOT}/toplist-block-lite"
DRY_RUN=0
TAG=""

usage() {
  cat <<'EOF'
Usage: bash scripts/deploy-wporg.sh [--dry-run] [--tag VERSION]

Builds toplist-block-lite.zip, then syncs toplist-block-lite/ into the WP.org
SVN trunk and optionally tags a release.

Environment (required for real deploy):
  WPORG_SVN_USER     WordPress.org username
  WPORG_SVN_PASSWORD Application password (not your login password)

Options:
  --dry-run   Print actions without touching SVN
  --tag VER   Create tags/VER after trunk deploy (defaults to Version: header)
  -h, --help  Show this help
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1 ;;
    --tag) TAG="${2:-}"; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
  esac
  shift
done

if ! command -v svn >/dev/null 2>&1; then
  echo "svn command is required." >&2
  exit 1
fi

echo "Building lite distribution ..."
php "${ROOT}/scripts/build-lite.php"

if [[ ! -d "${LITE_DIR}" ]]; then
  echo "Missing ${LITE_DIR} after build." >&2
  exit 1
fi

MAIN_FILE="${LITE_DIR}/toplist-block-lite.php"
if [[ ! -f "${MAIN_FILE}" ]]; then
  echo "Missing ${MAIN_FILE}" >&2
  exit 1
fi

if [[ -z "${TAG}" ]]; then
  TAG="$(php -r '
    $lines = file($argv[1]);
    foreach ($lines as $line) {
      if (preg_match("/^\\s*Version:\\s*(.+)$/i", $line, $m)) {
        echo trim($m[1]);
        exit(0);
      }
    }
    exit(1);
  ' "${MAIN_FILE}")" || {
    echo "Could not read Version: from ${MAIN_FILE}" >&2
    exit 1
  }
fi

SVN_USER="${WPORG_SVN_USER:-}"
SVN_PASS="${WPORG_SVN_PASSWORD:-}"
SVN_URL="https://plugins.svn.wordpress.org/toplist-block-lite"

if [[ "${DRY_RUN}" -eq 1 ]]; then
  echo "[dry-run] Would svn checkout ${SVN_URL} and rsync:"
  find "${LITE_DIR}" -type f | sed "s#${LITE_DIR}/#  #" | head -30
  echo "  ..."
  echo "[dry-run] Would svn commit trunk and copy to tags/${TAG}"
  exit 0
fi

if [[ -z "${SVN_USER}" || -z "${SVN_PASS}" ]]; then
  echo "Set WPORG_SVN_USER and WPORG_SVN_PASSWORD for deploy." >&2
  exit 1
fi

WORKDIR="$(mktemp -d)"
trap 'rm -rf "${WORKDIR}"' EXIT

export SVN_USERNAME="${SVN_USER}"
export SVN_PASSWORD="${SVN_PASS}"

svn checkout "${SVN_URL}" "${WORKDIR}/svn" --quiet
rsync -a --delete \
  --exclude '.git/' \
  --exclude 'node_modules/' \
  "${LITE_DIR}/" "${WORKDIR}/svn/trunk/"

cd "${WORKDIR}/svn"
svn add --force trunk/* 2>/dev/null || true
svn status
svn commit -m "Deploy Toplist Block Lite ${TAG} to trunk."

if [[ -d "tags/${TAG}" ]]; then
  echo "Tag tags/${TAG} already exists; skipping tag copy." >&2
else
  svn copy trunk "tags/${TAG}" -m "Tag version ${TAG}."
fi

echo "Deployed ${TAG} to ${SVN_URL}"
