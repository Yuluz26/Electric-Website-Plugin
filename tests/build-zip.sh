#!/usr/bin/env bash
# Builds dist/ev-charging-experience.zip from the current working tree.
# See docs/PACKAGING.md for exactly what is included/excluded.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="ev-charging-experience"
WORK_DIR="$(mktemp -d)"
STAGE_DIR="${WORK_DIR}/${SLUG}"
DIST_DIR="${ROOT_DIR}/dist"

cd "$ROOT_DIR"
mkdir -p "$DIST_DIR" "$STAGE_DIR"

# Plain `cp` (portable — no rsync dependency), then prune the staged COPY.
# Nothing here touches the actual repository.
cp -r . "$STAGE_DIR"

rm -rf \
	"${STAGE_DIR}/.git" \
	"${STAGE_DIR}/.github" \
	"${STAGE_DIR}/.claude" \
	"${STAGE_DIR}/.agents" \
	"${STAGE_DIR}/skills-lock.json" \
	"${STAGE_DIR}/phpcs.xml.dist" \
	"${STAGE_DIR}/composer.lock" \
	"${STAGE_DIR}/tests" \
	"${STAGE_DIR}/dist" \
	"${STAGE_DIR}/node_modules" \
	"${STAGE_DIR}/vendor"

find "$STAGE_DIR" -name '.DS_Store' -delete
find "$STAGE_DIR" -name '*.log' -delete

rm -f "${DIST_DIR}/${SLUG}.zip"
(cd "$WORK_DIR" && zip -rq "${DIST_DIR}/${SLUG}.zip" "${SLUG}")
rm -rf "$WORK_DIR"

echo "Built: ${DIST_DIR}/${SLUG}.zip"
unzip -l "${DIST_DIR}/${SLUG}.zip" | tail -5
