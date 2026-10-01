#!/usr/bin/env bash
# What the site widgets print (page hero, header and search, footer, stats, services, process, projects, quotes,
# the contact form and its token, the full-width template), checked inside WordPress without a browser.
# Needs the QA environment (bash tests/docker/setup.sh).
#
#   bash tests/docker/site-render-check.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)

"${COMPOSE[@]}" cp tests/docker/site-render-check.php wordpress:/tmp/site-render-check.php
"${COMPOSE[@]}" exec -T --user www-data wordpress wp eval-file /tmp/site-render-check.php
