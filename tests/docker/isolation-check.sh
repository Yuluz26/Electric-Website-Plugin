#!/usr/bin/env bash
# The PRD's first definition of done: activating the plugin does not visually alter unrelated pages.
# For each of three pages with no EV element on them — WordPress's Sample Page, an ordinary post, and a
# page designed in Breakdance — this screenshots the page with the plugin active and again with it
# deactivated, and asserts the two are identical pixel for pixel and that the active page carries nothing
# from the plugin (no class, style or script). Repeated under a block theme and Breakdance's Zero theme.
# The plugin is switched back on, and the theme restored, at the end (also when a check fails).
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
#   bash tests/docker/isolation-check.sh
#
# EVPX_PLUGIN_DIR: the plugin's folder name under wp-content/plugins (default: the bind mount from
# docker-compose.yml), for running this against an installed ZIP.
#
# Needs the same Playwright setup as tests/playwright/qa.mjs. Exit status is non-zero if any check fails.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }
status=0
PLUGIN="${EVPX_PLUGIN_DIR:-ev-charging-experience}"

# shellcheck source=tests/docker/themes.sh
source tests/docker/themes.sh
remember_theme
"${COMPOSE[@]}" cp tests/docker/isolation-fixture.php wordpress:/tmp/isolation-fixture.php
BREAKDANCE_PAGE=""
cleanup() {
	wp plugin activate "$PLUGIN" >/dev/null 2>&1
	[ -n "$BREAKDANCE_PAGE" ] && wp post delete "$BREAKDANCE_PAGE" --force >/dev/null 2>&1
	restore_themes
}
trap cleanup EXIT
install_test_themes

ORIGIN="http://localhost:8080"
BREAKDANCE_PAGE="$(wp eval-file /tmp/isolation-fixture.php | tail -1)"
PAGES=("${ORIGIN}/?page_id=2" "${ORIGIN}/?p=1" "${ORIGIN}/?page_id=${BREAKDANCE_PAGE}")
OUT="${EVPX_OUT_DIR:-./qa-isolation-output}"
QA=(node tests/playwright/isolation-qa.mjs)

for THEME in twentytwentyfive breakdance-zero; do
	wp theme activate "$THEME" >/dev/null || { echo "FAIL — could not activate ${THEME}"; status=1; continue; }
	echo "== ${THEME}"
	rm -rf "${OUT}/${THEME}"
	wp plugin activate "$PLUGIN" >/dev/null
	"${QA[@]}" shoot "${OUT}/${THEME}" active "${PAGES[@]}" || status=1
	wp plugin deactivate "$PLUGIN" >/dev/null
	"${QA[@]}" shoot "${OUT}/${THEME}" inactive "${PAGES[@]}" >/dev/null || status=1
	"${QA[@]}" compare "${OUT}/${THEME}" "${#PAGES[@]}" || status=1
done

exit "$status"
