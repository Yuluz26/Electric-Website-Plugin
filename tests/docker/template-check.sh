#!/usr/bin/env bash
# EV elements that live in a Breakdance *template* — a footer, a Single Post template — not in the
# page, under three kinds of theme:
#
#   twentytwentyfive  a block theme: the whole body renders before <head>
#   breakdance-zero   Breakdance's own bundled theme, the usual pairing: its template simulator renders
#                     the header, the page and the footer before <head> as well
#   evpx-classic      a bare classic theme (tests/docker/classic-theme): <head> first, then the body
#
# A template applies site-wide, so this creates each one, checks pages against it, removes it again,
# and puts the original theme back (also when a check fails or you interrupt it).
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
#   bash tests/docker/breakdance-page.sh           # the pages checked below
#   bash tests/docker/template-check.sh
#
# Needs the same Playwright setup as tests/playwright/qa.mjs. Exit status is non-zero if any check fails.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }
status=0

# shellcheck source=tests/docker/themes.sh
source tests/docker/themes.sh
remember_theme
"${COMPOSE[@]}" cp tests/docker/template-fixture.php wordpress:/tmp/template-fixture.php
cleanup() {
	wp eval-file /tmp/template-fixture.php none >/dev/null 2>&1
	restore_themes
}
trap cleanup EXIT
install_test_themes

ORIGIN="http://localhost:8080"
PLAIN="${ORIGIN}/?page_id=2"                          # WordPress's Sample Page: no EV widget of its own
POST="${ORIGIN}/?p=1"                                 # Hello world: an ordinary post
DEMO="$(cat tests/docker/.demo-url)"                  # the demo article: thirteen widgets as shortcodes in a post
NATIVE="$(cat tests/docker/.breakdance-native-url)"   # the same thirteen as native elements
QA=(node tests/playwright/template-qa.mjs)

run() { # <mode> <label> <widgets> <urls…>
	local mode="$1" label="$2" widgets="$3"
	shift 3
	echo "== ${THEME}: ${label}"
	wp eval-file /tmp/template-fixture.php "$mode" >/dev/null || { echo "FAIL — could not create the ${mode} fixture"; status=1; return; }
	"${QA[@]}" ${FLAGS:+"$FLAGS"} "${THEME}: ${label}" "$widgets" "$@" || status=1
}

for THEME in twentytwentyfive breakdance-zero evpx-classic; do
	wp theme activate "$THEME" >/dev/null || { echo "FAIL — could not activate ${THEME}"; status=1; continue; }
	FLAGS=""
	[ "$THEME" = "evpx-classic" ] && FLAGS="--classic"

	# No template: the widgets in the page's own content (shortcodes) and in its Breakdance tree (native).
	run none "13 shortcode widgets in a post" 13 "$DEMO"
	run none "13 native widgets on a Breakdance page" 13 "$NATIVE"

	# A native element in the footer.
	run footer-native "native CTA in a Breakdance footer" 1 "$PLAIN"
	run footer-native "native CTA in a Breakdance footer, page has 13 shortcode widgets" 14 "$DEMO"
	run footer-native "native CTA in a Breakdance footer, page has 13 native widgets" 14 "$NATIVE"

	# The same, in a Shortcode element.
	run footer-shortcode "Shortcode-element CTA in a Breakdance footer" 1 "$PLAIN"
	run footer-shortcode "Shortcode-element CTA in a Breakdance footer, page has 13 shortcode widgets" 14 "$DEMO"

	# Native elements in a Single Post template.
	run post-template "native Hero and FAQ in a Single Post template" 2 "$POST" "$DEMO"
done

exit "$status"
