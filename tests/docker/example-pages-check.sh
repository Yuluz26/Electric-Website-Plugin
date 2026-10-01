#!/usr/bin/env bash
# The example articles the plugin makes when it is activated (src/Setup/ExamplePages.php), end to end:
#
#   1. the logic, in WordPress          example-pages-check.php
#   2. activation, then the first admin request in a browser (the notice, both pages inspected)
#   3. the same through the Plugins screen's own Activate link
#   4. a second request and a deleted example: nothing is made again
#   5. Breakdance arriving after the plugin: the Breakdance page is added then, not before
#
# It creates and removes its own pages and options, and leaves Breakdance and the plugin active, also when a
# check fails or you interrupt it.
#
#   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
#   bash tests/docker/example-pages-check.sh
#   EVPX_PLUGIN_DIR=evpx-zip bash tests/docker/example-pages-check.sh    # the built ZIP, installed as a plugin of its own
#
# Needs the same Playwright setup as tests/playwright/qa.mjs. Exit status is non-zero if a check fails.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }
ORIGIN="http://localhost:8080"
PLUGIN="${EVPX_PLUGIN_DIR:-ev-charging-experience}"   # the plugin directory under test; export it for the browser step too
export EVPX_PLUGIN_DIR="$PLUGIN"
OUT="${EVPX_EXAMPLES_OUT:-qa-examples-output}"
status=0

check() { # check <label> <ok: 0|1> [detail]
	if [ "$2" = 1 ]; then echo "PASS — $1"; else echo "FAIL — $1${3:+ ($3)}"; status=1; fi
}

# A page of the admin, as an administrator asks for it: log in, load Plugins, print the HTML.
admin_visit() {
	local jar
	jar="$(mktemp)"
	curl -s -c "$jar" -b 'wordpress_test_cookie=WP%20Cookie%20check' --data-urlencode 'log=admin' --data-urlencode 'pwd=admin' \
		--data 'wp-submit=Log+In&testcookie=1' "${ORIGIN}/wp-login.php" -o /dev/null
	curl -s -b "$jar" "${ORIGIN}/wp-admin/plugins.php"
	rm -f "$jar"
}

# Every id the plugin recorded, then the record itself.
forget_examples() {
	wp eval '$o = (array) get_option( "evpx_examples" ); foreach ( array_merge( $o, (array) ( $o["site_pages"] ?? array() ) ) as $v ) { if ( is_int( $v ) && $v ) { wp_delete_post( $v, true ); } } delete_option( "evpx_examples" ); delete_transient( "evpx_examples_notice" );' >/dev/null 2>&1
}
reactivate() { wp plugin deactivate "$PLUGIN" >/dev/null 2>&1; wp plugin activate "$PLUGIN" >/dev/null 2>&1; }
count_posts() { wp post list --post_type=post,page --post_status=publish,draft,private,pending,future,trash --format=count; }
option() { wp option get evpx_examples --format=json 2>/dev/null; }
field() { php -r '$o = json_decode($argv[1], true); $v = $o[$argv[2]] ?? "-"; echo is_bool($v) ? ($v ? "true" : "false") : $v;' -- "$1" "$2" 2>/dev/null || echo "-"; }

cleanup() {
	wp config set WP_DEBUG_DISPLAY true --raw >/dev/null 2>&1
	forget_examples
	wp plugin activate breakdance >/dev/null 2>&1
	wp plugin activate "$PLUGIN" >/dev/null 2>&1
}
trap cleanup EXIT
forget_examples
rm -rf "$OUT"

# ------------------------------------------------------------------ 1. the logic
echo "## the logic, in WordPress"
"${COMPOSE[@]}" cp tests/docker/example-pages-check.php wordpress:/tmp/example-pages-check.php
"${COMPOSE[@]}" exec -T --user www-data wordpress wp eval-file /tmp/example-pages-check.php || status=1

# ------------------------------------------------------------------ 2. activation, then a real admin request
echo; echo "## activation, then the first admin request"
before="$(count_posts)"
reactivate
check "activating queues the examples and makes nothing yet" "$([ "$(option)" = '{"pending":true}' ] && [ "$(count_posts)" = "$before" ] && echo 1 || echo 0)" "$(option) / posts $before → $(count_posts)"

visit="$(node tests/playwright/examples-qa.mjs "$ORIGIN" landing)" || status=1
echo "$visit"
ids="$(sed -n 's/^ids //p' <<<"$visit" | tail -1)"
article_id="${ids%% *}"
page_id="${ids##* }"
state="$(option)"
check "the ids the notice links to are the ones the plugin recorded" "$([ -n "$article_id" ] && [ "$(field "$state" article)" = "$article_id" ] && [ "$(field "$state" breakdance)" = "$page_id" ] && [ "$(field "$state" pending)" = false ] && echo 1 || echo 0)" "$ids vs $state"
check "eight pages were added (the two examples and the six site pages), no more" "$([ "$(count_posts)" = "$((before + 8))" ] && echo 1 || echo 0)" "$before → $(count_posts)"

if [ -n "$article_id" ] && [ -n "$page_id" ]; then
	node tests/playwright/examples-qa.mjs "$ORIGIN" inspect "$article_id" "$page_id" "$OUT" || status=1
else
	check "both examples can be inspected" 0 "no ids in the notice"
fi

# ------------------------------------------------------------------ 3. the Activate link
echo; echo "## the Activate link on the Plugins screen"
forget_examples
wp plugin deactivate "$PLUGIN" >/dev/null 2>&1
before="$(count_posts)"
# While it activates a plugin WordPress tries wordpress.org, and where there is no route to it (this QA site) it prints a
# warning. A warning printed first stops the redirect that follows an activation, so for this step it is logged instead.
wp config set WP_DEBUG_DISPLAY false --raw >/dev/null 2>&1
activated="$(node tests/playwright/examples-qa.mjs "$ORIGIN" activate)" || status=1
grep -v '^ids ' <<<"$activated"
check "pressing Activate made the two examples and the site" "$([ "$(count_posts)" = "$((before + 8))" ] && echo 1 || echo 0)" "$before → $(count_posts)"
wp config set WP_DEBUG_DISPLAY true --raw >/dev/null 2>&1
forget_examples

# ------------------------------------------------------------------ 4. once
echo; echo "## a second request, and an example that was deleted"
reactivate
admin_visit >/dev/null
after="$(count_posts)"
article_id="$(field "$(option)" article)"
reactivate
admin_visit >/dev/null
check "activating again and visiting the admin makes nothing" "$([ "$(count_posts)" = "$after" ] && echo 1 || echo 0)" "$after → $(count_posts)"
wp post delete "$article_id" >/dev/null 2>&1
admin_visit >/dev/null
check "a trashed example is not made again" "$([ "$(count_posts)" = "$after" ] && [ "$(field "$(option)" article)" = "$article_id" ] && echo 1 || echo 0)" "$after → $(count_posts)"

# ------------------------------------------------------------------ 5. Breakdance later
echo; echo "## Breakdance arriving after the plugin"
forget_examples
wp plugin deactivate breakdance >/dev/null 2>&1
reactivate
before="$(count_posts)"
html="$(admin_visit)"
state="$(option)"
check "without Breakdance the article is made and the Breakdance page waits" "$([ "$(field "$state" article)" != "-" ] && [ "$(field "$state" article)" != 0 ] && [ "$(field "$state" breakdance)" = "-" ] && [ "$(field "$state" pending)" = true ] && [ "$(count_posts)" = "$((before + 7))" ] && echo 1 || echo 0)" "$state, posts $before → $(count_posts)"
check "the notice says so" "$(grep -q "Once Breakdance is active" <<<"$html" && echo 1 || echo 0)"
check "and it is a plain edit link, not a builder link, while there is no Breakdance" "$(grep -q "breakdance=builder" <<<"$html" && echo 0 || echo 1)"

wp plugin activate breakdance >/dev/null 2>&1
html="$(admin_visit)"
state="$(option)"
check "when Breakdance is active the next admin request adds the Breakdance page" "$([ "$(field "$state" breakdance)" != "-" ] && [ "$(field "$state" breakdance)" != 0 ] && [ "$(field "$state" pending)" = false ] && [ "$(count_posts)" = "$((before + 8))" ] && echo 1 || echo 0)" "$state, posts $before → $(count_posts)"
check "its notice says it found Breakdance, and links to the builder" "$(grep -q "found Breakdance" <<<"$html" && grep -q "breakdance=builder" <<<"$html" && echo 1 || echo 0)"

echo
if [ "$status" = 0 ]; then echo "All example-page checks passed."; else echo "Some example-page checks failed."; fi
exit "$status"
