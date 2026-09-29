#!/usr/bin/env bash
# Checks this plugin against a REAL Breakdance install.
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance-x.y.z.zip bash tests/docker/setup.sh
#   bash tests/docker/breakdance-real-check.sh
#
# 1. Structural: breakdance-real-check.php (save locations, Dynamic Data) via wp eval-file.
# 2. Behavioural: drops a throwaway element file into this plugin's Element Studio
#    folder and asks a *fresh* PHP process whether Breakdance loaded it. Breakdance
#    reads save locations once, at priority 10 on `breakdance_loaded`, so this only
#    passes if the plugin registered its location in time, whatever the plugin load order.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }
status=0

echo "== structure"
"${COMPOSE[@]}" cp tests/docker/breakdance-real-check.php wordpress:/tmp/breakdance-real-check.php
wp eval-file /tmp/breakdance-real-check.php || status=1

echo
echo "== behaviour: does Breakdance load an element saved in this plugin's folder?"
# EVPX_PLUGIN_DIR: the plugin's folder name under wp-content/plugins (default: the bind mount from docker-compose.yml).
PROBE="/var/www/html/wp-content/plugins/${EVPX_PLUGIN_DIR:-ev-charging-experience}/element-studio/elements/evpx-probe"
cleanup() { "${COMPOSE[@]}" exec -T wordpress rm -rf "$PROBE"; }
trap cleanup EXIT
# Root creates it (the plugin directory is a bind mount); www-data only needs to read it.
"${COMPOSE[@]}" exec -T wordpress sh -c "mkdir -p $PROBE && echo '<?php define( \"EVPX_PROBE_LOADED\", true );' > $PROBE/evpx-probe.php && chmod -R a+rX $PROBE"
RESULT="$(wp eval 'echo defined( "EVPX_PROBE_LOADED" ) ? "loaded" : "missing";' 2>&1)"
if [ "$RESULT" = "loaded" ]; then
	echo "PASS — Breakdance required the element file from this plugin's save location"
else
	echo "FAIL — Breakdance did not load it (got: $RESULT)"
	status=1
fi

exit "$status"
