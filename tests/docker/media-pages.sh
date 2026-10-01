#!/usr/bin/env bash
# Image checks need images, and none ship with the plugin. This generates photo-like test files,
# imports them, and creates four pages — writing their URLs:
#   tests/docker/.media-url          the demo article as shortcodes, with pictures (attachment ids)
#   tests/docker/.media-native-url   the same as native Breakdance elements (needs real Breakdance)
#   tests/docker/.media-mixed-url    a Related Articles row where one article has no featured image
#   tests/docker/.media-bright-url   a hero and a CTA over a bright picture
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
#   bash tests/docker/media-pages.sh
#   node tests/playwright/media-qa.mjs "$(cat tests/docker/.media-url)" "$(cat tests/docker/.media-native-url)" \
#        "$(cat tests/docker/.media-mixed-url)" "$(cat tests/docker/.media-bright-url)"
#   node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.media-native-url)"   # the builder, with pictures
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }

"${COMPOSE[@]}" cp tests/docker/native-helpers.php wordpress:/tmp/native-helpers.php
"${COMPOSE[@]}" cp tests/docker/media-pages.php wordpress:/tmp/media-pages.php
"${COMPOSE[@]}" cp content/demo-article.txt wordpress:/tmp/demo-article.txt

read -r POST_ID NATIVE_ID MIXED_ID BRIGHT_ID < <(wp eval-file /tmp/media-pages.php /tmp/demo-article.txt | tail -1)

echo "http://localhost:8080/?p=${POST_ID}" | tee tests/docker/.media-url
echo "http://localhost:8080/?page_id=${NATIVE_ID}" | tee tests/docker/.media-native-url
echo "http://localhost:8080/?page_id=${MIXED_ID}" | tee tests/docker/.media-mixed-url
echo "http://localhost:8080/?page_id=${BRIGHT_ID}" | tee tests/docker/.media-bright-url
