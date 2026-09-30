#!/usr/bin/env bash
# One-command QA environment: WordPress + MySQL in Docker, plugin activated,
# demo article imported as a real post next to a few sibling posts (so the
# Related Articles widget has something real to list).
#
#   bash tests/docker/setup.sh
#   node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)"
#
# Optional: EVPX_BREAKDANCE_ZIP=/path/to/breakdance-x.y.z.zip installs the real
# Breakdance plugin (you supply the licensed ZIP; it is never committed) and
# activates it next to this plugin. Then run tests/docker/breakdance-real-check.sh.
#
# Optional: EVPX_BREAKDANCE_STUB=1 installs tests/docker/breakdance-stub.php as a
# mu-plugin and runs tests/docker/breakdance-contract-check.php against it.
# (Use one or the other, not both.)
#
# Optional: EVPX_GSAP_DIR=/path/with/gsap.min.js+ScrollTrigger.min.js serves
# GSAP from inside the container via the evpx_gsap_src filters — for networks
# where cdnjs is unreachable. GSAP itself is never committed to this repo.
#
# Tear down: docker compose -f tests/docker/docker-compose.yml down -v
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }

"${COMPOSE[@]}" up -d

echo "Waiting for MySQL..."
for _ in $(seq 1 40); do
	"${COMPOSE[@]}" exec -T db mysqladmin ping -h localhost -uroot -pevpx_root 2>/dev/null | grep -q alive && break
	sleep 2
done

echo "Waiting for WordPress files..."
for _ in $(seq 1 40); do
	"${COMPOSE[@]}" exec -T wordpress test -f /var/www/html/wp-config.php 2>/dev/null && break
	sleep 2
done

# Download on the host and copy in: the container may have no outbound access.
PHAR="${TMPDIR:-/tmp}/evpx-wp-cli.phar"
[ -s "$PHAR" ] || curl -sSL -o "$PHAR" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
WP_CID="$("${COMPOSE[@]}" ps -q wordpress)"
docker cp "$PHAR" "${WP_CID}:/usr/local/bin/wp"
"${COMPOSE[@]}" exec -T wordpress chmod +x /usr/local/bin/wp

if ! wp core is-installed 2>/dev/null; then
	wp core install --url="http://localhost:8080" --title="EVPX QA Site" \
		--admin_user=admin --admin_password=admin --admin_email=qa@example.test --skip-email
fi
wp config set WP_DEBUG_LOG true --raw
wp config set WP_DEBUG_DISPLAY true --raw

# WordPress checks wordpress.org for updates on the first admin page after a theme is added or removed. Where there is
# no route to it (a sandbox behind a proxy) that check takes about 30 s to fail, longer than the browser suites wait for
# a login. Refuse external requests at once instead; set EVPX_ALLOW_EXTERNAL_HTTP=1 to leave them alone.
if [ -z "${EVPX_ALLOW_EXTERNAL_HTTP:-}" ]; then
	wp config set WP_HTTP_BLOCK_EXTERNAL true --raw
fi

# A real, licensed Breakdance ZIP (never committed here). Extracted on the host
# because the WordPress image has no unzip, then copied in and activated.
if [ -n "${EVPX_BREAKDANCE_ZIP:-}" ]; then
	BD_TMP="$(mktemp -d)"
	unzip -q "$EVPX_BREAKDANCE_ZIP" -d "$BD_TMP"
	"${COMPOSE[@]}" exec -T wordpress rm -rf /var/www/html/wp-content/plugins/breakdance
	docker cp "$BD_TMP/breakdance" "${WP_CID}:/var/www/html/wp-content/plugins/breakdance"
	"${COMPOSE[@]}" exec -T wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/breakdance
	rm -rf "$BD_TMP"
	wp plugin activate breakdance
fi

wp plugin activate ev-charging-experience

if [ -n "${EVPX_GSAP_DIR:-}" ]; then
	"${COMPOSE[@]}" exec -T wordpress mkdir -p /var/www/html/wp-content/uploads/evpx-test /var/www/html/wp-content/mu-plugins
	docker cp "${EVPX_GSAP_DIR}/gsap.min.js" "${WP_CID}:/var/www/html/wp-content/uploads/evpx-test/gsap.min.js"
	docker cp "${EVPX_GSAP_DIR}/ScrollTrigger.min.js" "${WP_CID}:/var/www/html/wp-content/uploads/evpx-test/ScrollTrigger.min.js"
	"${COMPOSE[@]}" exec -T wordpress sh -c "cat > /var/www/html/wp-content/mu-plugins/evpx-local-gsap.php <<'PHP'
<?php
add_filter( 'evpx_gsap_src', fn() => content_url( 'uploads/evpx-test/gsap.min.js' ) );
add_filter( 'evpx_scrolltrigger_src', fn() => content_url( 'uploads/evpx-test/ScrollTrigger.min.js' ) );
PHP"
	echo "GSAP served locally via evpx_gsap_src / evpx_scrolltrigger_src."
fi

if [ -n "${EVPX_BREAKDANCE_STUB:-}" ]; then
	"${COMPOSE[@]}" exec -T wordpress mkdir -p /var/www/html/wp-content/mu-plugins
	docker cp tests/docker/breakdance-stub.php "${WP_CID}:/var/www/html/wp-content/mu-plugins/breakdance-stub.php"
	docker cp tests/docker/breakdance-contract-check.php "${WP_CID}:/tmp/breakdance-contract-check.php"
	wp eval-file /tmp/breakdance-contract-check.php
fi

# Demo content: a category, sibling posts, then the demo article itself.
CAT="$(wp term create category "EV Infrastructure" --porcelain 2>/dev/null || wp term list category --name="EV Infrastructure" --field=term_id)"
i=0
for title in "What a utility connection upgrade really involves" "Load management: sharing one supply across many chargers" "How to read a charger's spec sheet without the jargon"; do
	i=$((i + 1))
	wp post create --post_type=post --post_status=publish --post_category="$CAT" \
		--post_title="$title" \
		--post_excerpt="A short, practical look at one decision that shapes how a charging site is planned, costed and operated." \
		--post_content="Placeholder body for QA article ${i}." --porcelain >/dev/null
done

"${COMPOSE[@]}" cp content/demo-article.txt wordpress:/tmp/demo-article.txt
DEMO_ID="$("${COMPOSE[@]}" exec -T --user www-data wordpress sh -c \
	'wp post create --post_type=post --post_status=publish --post_category='"$CAT"' --post_title="Choosing AC or DC Charging for Your Site" --post_content="$(cat /tmp/demo-article.txt)" --porcelain')"

echo "http://localhost:8080/?p=${DEMO_ID}" | tee tests/docker/.demo-url
echo "Ready. Run: node tests/playwright/qa.mjs \"\$(cat tests/docker/.demo-url)\""
