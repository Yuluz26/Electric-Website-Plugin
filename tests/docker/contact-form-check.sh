#!/usr/bin/env bash
# The contact form, end to end, over HTTP as a visitor would use it: what it refuses (too fast, a trap field filled,
# a changed recipient, a bad address, an off-site return address, more than five in an hour), what it sends, and to
# whom. Mail is caught by a must-use plugin that is removed again; nothing leaves the QA site.
#
#   bash tests/docker/contact-form-check.sh
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f tests/docker/docker-compose.yml)
wp() { "${COMPOSE[@]}" exec -T --user www-data wordpress wp "$@"; }
ORIGIN="http://localhost:8080"
MU=/var/www/html/wp-content/mu-plugins/evpx-mail-stub.php
MAIL=/tmp/evpx-mail.json
status=0
check() { if [ "$2" = 1 ]; then echo "PASS — $1"; else echo "FAIL — $1${3:+ ($3)}"; status=1; fi; }
ok() { [ "$1" = "$2" ] && echo 1 || echo 0; }

reset_limit() { wp eval 'global $wpdb; $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE \"%evpx_contact_%\"" );' >/dev/null 2>&1; }
cleanup() {
	"${COMPOSE[@]}" exec -T wordpress rm -f "$MU" "$MAIL" >/dev/null 2>&1
	[ -n "${PAGE:-}" ] && wp post delete "$PAGE" --force >/dev/null 2>&1
	reset_limit
}
trap cleanup EXIT

"${COMPOSE[@]}" exec -T wordpress sh -c "mkdir -p /var/www/html/wp-content/mu-plugins && cat > $MU" <<'PHP'
<?php
// Test only: catch every wp_mail() call instead of sending it.
add_filter( 'pre_wp_mail', function ( $null, $atts ) { file_put_contents( '/tmp/evpx-mail.json', wp_json_encode( $atts ) . "\n", FILE_APPEND ); return true; }, 10, 2 );
PHP
"${COMPOSE[@]}" exec -T wordpress rm -f "$MAIL"
reset_limit

PAGE="$(wp post create --post_type=page --post_status=publish --post_title='Contact form check' --post_content='[evpx_contact to_email="owner@example.com"]' --porcelain 2>/dev/null | tail -1)"
URL="$ORIGIN/?page_id=$PAGE"
html="$(curl -s "$URL")"
token="$(sed -n 's/.*name="evpx_token" value="\([^"]*\)".*/\1/p' <<<"$html" | head -1)"
back="$(sed -n 's/.*name="evpx_return" value="\([^"]*\)".*/\1/p' <<<"$html" | head -1 | sed 's/&#038;/\&/g; s/&amp;/\&/g')"
check "the page prints a token and a return address" "$([ -n "$token" ] && [ -n "$back" ] && echo 1 || echo 0)" "token=$token back=$back"

post() { # post <extra curl args...>: prints the Location header
	curl -s -i -o - -X POST "$ORIGIN/wp-admin/admin-post.php" --data-urlencode 'action=evpx_contact' --data-urlencode "evpx_return=$back" "$@" | tr -d '\r' | sed -n 's/^[Ll]ocation: //p'
}
valid=(--data-urlencode "evpx_token=$token" --data-urlencode 'evpx_name=Ada Lovelace' --data-urlencode 'evpx_email=ada@example.com' --data-urlencode 'evpx_phone=+44 20 7946 0000' --data-urlencode 'evpx_topic=A new site' --data-urlencode 'evpx_message=We have a car park with forty bays.')

loc="$(post "${valid[@]}")"
check "sent at once, it is refused: a person takes longer than three seconds to fill a form" "$([[ "$loc" == *evpx_sent=expired* ]] && echo 1 || echo 0)" "$loc"
check "and nothing was mailed" "$("${COMPOSE[@]}" exec -T wordpress sh -c "[ ! -s $MAIL ] && echo 1 || echo 0" | tr -d '\r')"

sleep 4
loc="$(post "${valid[@]}")"
check "a while later it is accepted, and the visitor is sent back to the form's own page with the outcome" "$([[ "$loc" == *evpx_sent=1* && "$loc" == *"page_id=$PAGE"* && "$loc" == *"#evpx-contact" ]] && echo 1 || echo 0)" "$loc"
mail="$("${COMPOSE[@]}" exec -T wordpress cat "$MAIL" 2>/dev/null | head -1)"
field() { php -r '$m = json_decode($argv[1], true); $v = $m[$argv[2]] ?? ""; echo is_array($v) ? implode("|", $v) : $v;' -- "$mail" "$1"; }
check "it was mailed to the recipient the widget was given, not the site's admin" "$(ok "$(field to)" "owner@example.com")" "$(field to)"
check "the reply goes to the person who wrote" "$([[ "$(field headers)" == *"Reply-To: Ada Lovelace <ada@example.com>"* ]] && echo 1 || echo 0)" "$(field headers)"
check "the subject names the site, the topic and the sender; the body carries the message and the phone number" "$([[ "$(field subject)" == *"A new site: Ada Lovelace"* && "$(field message)" == *"forty bays"* && "$(field message)" == *"+44 20 7946 0000"* ]] && echo 1 || echo 0)" "$(field subject)"

"${COMPOSE[@]}" exec -T wordpress rm -f "$MAIL"
loc="$(post "${valid[@]}" --data-urlencode 'evpx_website=http://spam.example')"
check "a filled trap field is told it worked, and nothing is mailed" "$([[ "$loc" == *evpx_sent=1* ]] && "${COMPOSE[@]}" exec -T wordpress sh -c "[ ! -s $MAIL ]" && echo 1 || echo 0)" "$loc"

loc="$(post --data-urlencode "evpx_token=$token" --data-urlencode 'evpx_name=Ada' --data-urlencode 'evpx_email=not-an-email' --data-urlencode 'evpx_message=Long enough message')"
check "a bad address is refused as invalid" "$([[ "$loc" == *evpx_sent=invalid* ]] && echo 1 || echo 0)" "$loc"
loc="$(post --data-urlencode "evpx_token=$token" --data-urlencode 'evpx_name=' --data-urlencode 'evpx_email=a@b.co' --data-urlencode 'evpx_message=Long enough message')"
check "no name is refused as invalid" "$([[ "$loc" == *evpx_sent=invalid* ]] && echo 1 || echo 0)" "$loc"
loc="$(post --data-urlencode "evpx_token=$token" --data-urlencode 'evpx_name=A' --data-urlencode 'evpx_email=a@b.co' --data-urlencode 'evpx_message=hi')"
check "a message of two letters is refused as invalid" "$([[ "$loc" == *evpx_sent=invalid* ]] && echo 1 || echo 0)" "$loc"

forged="$(php -r '$p = explode(".", $argv[1]); echo $p[0] . "." . rtrim(strtr(base64_encode("evil@example.com"), "+/", "-_"), "=") . "." . $p[2];' -- "$token")"
loc="$(post "${valid[@]:2}" --data-urlencode "evpx_token=$forged")"
check "a token whose recipient was changed is refused, so the form cannot be pointed at someone else" "$([[ "$loc" == *evpx_sent=expired* ]] && echo 1 || echo 0)" "$loc"
loc="$(post "${valid[@]:2}" --data-urlencode 'evpx_token=garbage')"
check "so is garbage" "$([[ "$loc" == *evpx_sent=expired* ]] && echo 1 || echo 0)" "$loc"

loc="$(curl -s -i -o - -X POST "$ORIGIN/wp-admin/admin-post.php" --data-urlencode 'action=evpx_contact' --data-urlencode 'evpx_return=https://evil.example/phish' "${valid[@]}" | tr -d '\r' | sed -n 's/^[Ll]ocation: //p')"
check "a return address on another site is not followed" "$([[ "$loc" != *evil.example* && "$loc" == "$ORIGIN"* ]] && echo 1 || echo 0)" "$loc"

reset_limit
"${COMPOSE[@]}" exec -T wordpress rm -f "$MAIL"
last=""
for i in 1 2 3 4 5 6; do last="$(post "${valid[@]}")"; done
check "the sixth message within the hour is refused" "$([[ "$last" == *evpx_sent=limit* ]] && echo 1 || echo 0)" "$last"
sent="$("${COMPOSE[@]}" exec -T wordpress sh -c "wc -l < $MAIL" | tr -d '\r ')"
check "and five were mailed" "$(ok "$sent" 5)" "$sent"

for state in 1 invalid expired limit failed; do
	page="$(curl -s "$URL&evpx_sent=$state")"
	check "the outcome '$state' is shown on the form" "$(grep -q 'class="evpx-contact__notice' <<<"$page" && echo 1 || echo 0)"
done
check "a made-up outcome shows nothing, and markup in it is not printed" "$(! curl -s "$URL&evpx_sent=%3Cscript%3Ealert(1)%3C/script%3E" | grep -q '<script>alert(1)' && ! curl -s "$URL&evpx_sent=zzz" | grep -q 'evpx-contact__notice' && echo 1 || echo 0)"

echo
[ "$status" = 0 ] && echo "All contact form checks passed." || echo "Some contact form checks failed."
exit "$status"
