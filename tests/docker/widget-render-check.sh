#!/usr/bin/env bash
# What the widgets print (the comparison's power scale, the section's key figure, the hero's captions, escaping),
# checked inside WordPress without a browser. Needs the QA environment (bash tests/docker/setup.sh).
#
#   bash tests/docker/widget-render-check.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)

"${COMPOSE[@]}" cp tests/docker/widget-render-check.php wordpress:/tmp/widget-render-check.php
"${COMPOSE[@]}" exec -T --user www-data wordpress wp eval-file /tmp/widget-render-check.php
