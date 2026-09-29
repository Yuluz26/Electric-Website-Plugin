#!/usr/bin/env bash
# Creates two pages designed in Breakdance from docs/demo-article.txt — one Breakdance Section per
# EV widget — and writes their URLs:
#   tests/docker/.breakdance-url          each widget in Breakdance's Shortcode element
#   tests/docker/.breakdance-native-url   each widget as the plugin's own native element
#   tests/docker/.breakdance-full-url     the same, in full-width Sections without padding (the recommended setup)
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
#   bash tests/docker/breakdance-page.sh
#   node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)"
#   node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-native-url)"
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }

"${COMPOSE[@]}" cp tests/docker/native-helpers.php wordpress:/tmp/native-helpers.php
"${COMPOSE[@]}" cp tests/docker/breakdance-page.php wordpress:/tmp/breakdance-page.php
"${COMPOSE[@]}" cp docs/demo-article.txt wordpress:/tmp/demo-article.txt

SHORTCODE_ID="$(wp eval-file /tmp/breakdance-page.php /tmp/demo-article.txt shortcode | tail -1)"
NATIVE_ID="$(wp eval-file /tmp/breakdance-page.php /tmp/demo-article.txt native | tail -1)"
FULL_ID="$(wp eval-file /tmp/breakdance-page.php /tmp/demo-article.txt full | tail -1)"

echo "http://localhost:8080/?page_id=${SHORTCODE_ID}" | tee tests/docker/.breakdance-url
echo "http://localhost:8080/?page_id=${NATIVE_ID}" | tee tests/docker/.breakdance-native-url
echo "http://localhost:8080/?page_id=${FULL_ID}" | tee tests/docker/.breakdance-full-url
