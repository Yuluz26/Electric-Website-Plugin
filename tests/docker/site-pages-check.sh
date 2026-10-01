#!/usr/bin/env bash
# The six site pages the plugin makes (Home, About, Services, Projects, Contact, Search), end to end, in both
# ways they can be made:
#
#   A. Breakdance active: native elements on the "EV full-width page" template
#   B. Breakdance not active: shortcodes on the same template
#
# Each is made the way activation makes it, published, and driven in a browser (tests/playwright/site-qa.mjs); the
# notice's publish buttons are followed; the contact form is checked over HTTP (contact-form-check.sh). It puts the
# site back as it found it, Breakdance and the plugin active, also when a check fails.
#
#   bash tests/docker/site-pages-check.sh
#   EVPX_PLUGIN_DIR=evpx-zip bash tests/docker/site-pages-check.sh
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@" 2>/dev/null; } # a PHP warning from WordPress.org being out of reach would land in the output
ORIGIN="http://localhost:8080"
PLUGIN="${EVPX_PLUGIN_DIR:-ev-charging-experience}"
OUT="${EVPX_SITE_OUT:-qa-site-output}"
status=0
check() { if [ "$2" = 1 ]; then echo "PASS — $1"; else echo "FAIL — $1${3:+ ($3)}"; status=1; fi; }

SLUGS='"home", "about", "services", "projects", "contact", "search"'
forget_site() {
	wp eval 'global $wpdb; foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = \"page\" AND post_name REGEXP \"^(home|about|services|projects|contact|search)(-[0-9]+)?$\"" ) as $id ) { wp_delete_post( (int) $id, true ); } $o = (array) get_option( "evpx_examples" ); foreach ( array( "article", "breakdance" ) as $k ) { if ( ! empty( $o[ $k ] ) ) { wp_delete_post( (int) $o[ $k ], true ); } } delete_option( "evpx_examples" ); delete_transient( "evpx_examples_notice" ); update_option( "show_on_front", "posts" ); update_option( "page_on_front", 0 );' >/dev/null 2>&1
}
make_site() { # prints slug=id,…
	wp eval 'wp_set_current_user( 1 ); add_option( "evpx_examples", array( "pending" => true ) ); ( new EVPX\Setup\ExamplePages() )->createPending(); $o = get_option( "evpx_examples" ); echo implode( ",", array_map( function ( $k, $v ) { return "$k=$v"; }, array_keys( (array) ( $o["site_pages"] ?? array() ) ), (array) ( $o["site_pages"] ?? array() ) ) );' 2>/dev/null | tail -1
}
cleanup() {
	wp config set WP_DEBUG_DISPLAY true --raw >/dev/null 2>&1
	forget_site
	wp plugin activate breakdance >/dev/null 2>&1
	wp plugin activate "$PLUGIN" >/dev/null 2>&1
}
trap cleanup EXIT

run_mode() { # run_mode <label> <expects native: 1|0>
	local label="$1" native="$2" ids urls="" slug id
	echo; echo "## $label"
	forget_site
	ids="$(make_site)"
	check "$label: six pages were made, all drafts" "$([ "$(tr ',' '\n' <<<"$ids" | grep -c .)" = 6 ] && [ "$(wp post list --post_type=page --post_status=draft --name=about --format=count)" = 1 ] && echo 1 || echo 0)" "$ids"
	for pair in ${ids//,/ }; do slug="${pair%%=*}"; id="${pair##*=}"
		check "$label: $slug is a page on the full-width template" "$([ "$(wp post meta get "$id" _wp_page_template)" = evpx-canvas ] && [ "$(wp post get "$id" --field=post_name)" = "$slug" ] && echo 1 || echo 0)"
		if [ "$native" = 1 ]; then
			check "$label: $slug holds a Breakdance tree and no content of its own" "$([ -z "$(wp post get "$id" --field=post_content)" ] && [ -n "$(wp post meta get "$id" _breakdance_data 2>/dev/null)" ] && echo 1 || echo 0)"
		else
			check "$label: $slug holds shortcodes, and every one of them starts with the header and ends with the footer" "$(wp post get "$id" --field=post_content | php -r '$c = trim(stream_get_contents(STDIN)); echo (str_starts_with($c, "[evpx_header") && str_ends_with($c, "[evpx_footer]") && !str_contains($c, "{{")) ? 1 : 0;')"
		fi
	done
	wp post list --post_type=page --post_status=draft --name=home --format=ids | xargs -r -I{} true
	for pair in ${ids//,/ }; do id="${pair##*=}"; wp post update "$id" --post_status=publish >/dev/null 2>&1; urls+="${pair%%=*}=$ORIGIN/?page_id=$id "; done

	out="$(node tests/playwright/site-qa.mjs "$OUT/$label" $urls)" || status=1
	echo "$out" | grep -v '^$'

	home_html="$(curl -s "$ORIGIN/?page_id=$(sed 's/.*home=\([0-9]*\).*/\1/' <<<"$ids")")"
	check "$label: the page is a bare document: the theme's own header, title and footer are not on it" "$(grep -q 'id="evpx-main"' <<<"$home_html" && ! grep -qE 'class="wp-site-blocks|wp-block-template-part|Designed with' <<<"$home_html" && echo 1 || echo 0)"
}

# ---------------------------------------------------------------- A. Breakdance
wp plugin activate breakdance >/dev/null 2>&1
wp plugin activate "$PLUGIN" >/dev/null 2>&1
run_mode "breakdance" 1

# ---------------------------------------------------------------- B. no Breakdance
wp plugin deactivate breakdance >/dev/null 2>&1
run_mode "shortcodes" 0

# ---------------------------------------------------------------- the publish buttons
echo; echo "## the notice's publish buttons"
wp plugin activate breakdance >/dev/null 2>&1
for which in all front; do
	forget_site
	wp eval 'wp_set_current_user( 1 ); add_option( "evpx_examples", array( "pending" => true ) ); ( new EVPX\Setup\ExamplePages() )->createPending();' >/dev/null 2>&1
	node tests/playwright/site-publish-qa.mjs "$ORIGIN" "$which" | grep -v '^$' || status=1
	published="$(wp post list --post_type=page --post_status=publish --format=csv --fields=post_name 2>/dev/null | grep -cE '^(home|about|services|projects|contact|search)$')"
	check "publish ($which): all six site pages are published" "$([ "$published" = 6 ] && echo 1 || echo 0)" "$published"
	if [ "$which" = front ]; then
		check "publish (front): Home is the front page" "$([ "$(wp option get show_on_front)" = page ] && [ "$(wp option get page_on_front)" = "$(wp post list --post_type=page --name=home --format=ids)" ] && echo 1 || echo 0)"
	else
		check "publish (all): the front page setting was not touched" "$([ "$(wp option get show_on_front)" = posts ] && echo 1 || echo 0)"
	fi
done

echo; echo "## the contact form"
bash tests/docker/contact-form-check.sh | grep -v '^$' || status=1

echo
[ "$status" = 0 ] && echo "All site page checks passed." || echo "Some site page checks failed."
exit "$status"
