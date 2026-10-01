#!/usr/bin/env bash
# The builder round-trip on native elements. On a scratch copy of the demo article (as native elements):
# choose a dropdown option, edit a text field, press Save, check the front end, reopen the builder. On a
# scratch page with one empty Section: add an element from the Add panel, save, check the front end.
# Both pages are created here and deleted again — also when a check fails.
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
#   bash tests/docker/builder-save-check.sh
#
# Needs the same Playwright setup as tests/playwright/qa.mjs. Exit status is non-zero if a check fails.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }

"${COMPOSE[@]}" cp tests/docker/native-helpers.php wordpress:/tmp/native-helpers.php
"${COMPOSE[@]}" cp tests/docker/breakdance-page.php wordpress:/tmp/breakdance-page.php
"${COMPOSE[@]}" cp content/demo-article.txt wordpress:/tmp/demo-article.txt

PAGE_ID="$(wp eval-file /tmp/breakdance-page.php /tmp/demo-article.txt native | tail -1)"
EMPTY_ID="$(wp eval-file /tmp/breakdance-page.php /tmp/demo-article.txt empty | tail -1)"
trap 'wp post delete "$PAGE_ID" "$EMPTY_ID" --force >/dev/null 2>&1' EXIT

node tests/playwright/builder-save-qa.mjs "http://localhost:8080/?page_id=${PAGE_ID}" "http://localhost:8080/?page_id=${EMPTY_ID}"
