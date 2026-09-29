#!/usr/bin/env bash
# Creates a page designed in Breakdance from docs/demo-article.txt (one Breakdance
# Section per top-level EV shortcode, each holding a Shortcode element) and writes
# its URL to tests/docker/.breakdance-url.
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
#   bash tests/docker/breakdance-page.sh
#   node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)"
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)

"${COMPOSE[@]}" cp tests/docker/breakdance-page.php wordpress:/tmp/breakdance-page.php
"${COMPOSE[@]}" cp docs/demo-article.txt wordpress:/tmp/demo-article.txt
ID="$("${COMPOSE[@]}" exec -T --user www-data wordpress wp eval-file /tmp/breakdance-page.php /tmp/demo-article.txt | tail -1)"

echo "http://localhost:8080/?page_id=${ID}" | tee tests/docker/.breakdance-url
