<?php
/**
 * What the site widgets print, checked in WordPress without a browser: the page hero, the header and its search,
 * the search page, the footer, stats, services, process, projects, quotes, the contact form and its signed token,
 * the full-width template and the six site pages. Run through tests/docker/site-render-check.sh. Exits non-zero
 * on any failure.
 */

use EVPX\Support\ContactForm;
use EVPX\Support\Lines;
use EVPX\Support\Scene;

$failures = 0;
$check    = static function ( $label, $ok, $detail = '' ) use ( &$failures ) {
	echo ( $ok ? 'PASS' : 'FAIL' ) . ' — ' . $label . ( ! $ok && $detail ? " ($detail)" : '' ) . "\n";
	if ( ! $ok ) {
		++$failures;
	}
};
$count    = static function ( string $needle, string $html ): int {
	return substr_count( $html, $needle );
};

// ------------------------------------------------------------------ lines
$check( 'lines: a multi-line attribute that wpautop has given <br /> is read as lines', array( array( 'A', 'x' ), array( 'B', 'y' ) ) === Lines::pairs( "A | x<br />\nB | y", 5 ) );
$check( 'lines: pairs stop at the limit, and blank lines are skipped', 2 === count( Lines::pairs( "A|1\n\nB|2\nC|3", 2 ) ) );
$check( 'lines: an address on this site stays on this site, an outside one is kept, a script is dropped', str_starts_with( Lines::url( '/about/' ), home_url() ) && 'https://example.com/x' === Lines::url( 'https://example.com/x' ) && '' === Lines::url( 'javascript:alert(1)' ), Lines::url( '/about/' ) . ' | ' . Lines::url( 'javascript:alert(1)' ) );
$check( 'lines: a figure splits into what comes before, the number, and what follows', array( '£', '1,250', 'k' ) === array_values( Lines::figure( '£1,250k' ) ) && array( '', '150', ' kW' ) === array_values( Lines::figure( '150 kW' ) ) );
$check( 'lines: "24/7" is a phrase, not a number that can count up', '' === Lines::figure( '24/7' )['number'] );

// ------------------------------------------------------------------ scenes
$check( 'scene: every scene a control offers exists, and "none" renders nothing', 5 === count( array_filter( array_keys( Scene::options() ), array( Scene::class, 'has' ) ) ) && '' === Scene::render( 'none' ) && '' === Scene::render( '../x' ) );
$two = Scene::render( 'station' ) . Scene::render( 'station' );
preg_match_all( '/\sid="([^"]+)"/', $two, $ids );
$check( 'scene: two on a page share no ids (a shared id would make one draw with the other\'s gradients)', $ids[1] && count( $ids[1] ) === count( array_unique( $ids[1] ) ) );
$check( 'scene: it is decoration, hidden from assistive technology, and every layer says how far it moves', str_contains( Scene::render( 'grid' ), 'aria-hidden="true"' ) && str_contains( Scene::render( 'grid' ), 'data-depth=' ) );

// ------------------------------------------------------------------ the page hero
$stage = do_shortcode( '[evpx_stage scene="station" title="Power that *arrives* first" eyebrow="A" lede="B" cta_label="Go" cta_url="/contact/" facts="24/7 | Monitored' . "\n" . '150 kW | Per bay' . "\n" . '1,250 | Bays' . "\n" . 'A | b' . "\n" . 'C | d"]' );
$check( 'stage: the title is one h1, and *word* is set in the accent as <em>', 1 === $count( '<h1', $stage ) && str_contains( $stage, '<em>arrives</em>' ) );
$check( 'stage: it is a full-width section with its scene as decoration', str_contains( $stage, 'alignfull' ) && str_contains( $stage, 'evpx-scene--station' ) && str_contains( $stage, 'aria-hidden="true"' ) );
$check( 'stage: no more than four facts, the numbers count up and a phrase does not', 4 === $count( 'class="evpx-stage__fact"', $stage ) && 2 === $count( 'data-count=', $stage ), (string) $count( 'data-count=', $stage ) );
$check( 'stage: what is typed into it is escaped', ! str_contains( do_shortcode( '[evpx_stage title="<script>x</script>" lede="<img src=x onerror=1>"]' ), '<script>' ) && ! str_contains( do_shortcode( '[evpx_stage title="<script>x</script>" lede="<img src=x onerror=1>"]' ), '<img' ) );
$check( 'stage: the title can be an h2 (a page whose theme prints the h1), and a stale tag is an h1', str_contains( do_shortcode( '[evpx_stage title="T" title_tag="h2"]' ), '<h2' ) && str_contains( do_shortcode( '[evpx_stage title="T" title_tag="script"]' ), '<h1' ) );
$check( 'stage: no scene means the blueprint grid, and a stale scene is the default one, not a broken box', ! str_contains( do_shortcode( '[evpx_stage title="T" scene="none"]' ), 'class="evpx-scene' ) && str_contains( do_shortcode( '[evpx_stage title="T" scene="none"]' ), 'evpx-stage__grid' ) && str_contains( do_shortcode( '[evpx_stage title="T" scene="nope"]' ), 'evpx-scene--station' ) );
$check( 'stage: the height is a class, and a stale one is the default (tall)', str_contains( do_shortcode( '[evpx_stage title="T" height="compact"]' ), 'evpx-stage--compact' ) && str_contains( do_shortcode( '[evpx_stage title="T" height="full"]' ), 'evpx-stage--full' ) && str_contains( do_shortcode( '[evpx_stage title="T" height="huge"]' ), 'evpx-stage--tall' ) );
$check( 'stage: with its animation off it says so, so nothing on it moves', str_contains( do_shortcode( '[evpx_stage title="T" animate="false"]' ), 'data-evpx-animate="0"' ) );

// ------------------------------------------------------------------ the header
$header = do_shortcode( '[evpx_header brand="Volt & Co" links="Home | /' . "\n" . 'About | /about/" cta_label="Talk" cta_url="/contact/"]' );
$check( 'header: the brand is escaped text, the links are a labelled navigation, the button goes where it is told', str_contains( $header, 'Volt &amp; Co' ) && str_contains( $header, 'aria-label="Primary"' ) && str_contains( $header, '>Talk<' ) );
$check( 'header: the search is a dialog with a labelled field, a live status, and a way out', str_contains( $header, '<dialog' ) && str_contains( $header, 'role="search"' ) && str_contains( $header, 'aria-live="polite"' ) && str_contains( $header, 'data-evpx-search-close' ) );
$check( 'header: search off means no dialog and no button for it', ! str_contains( do_shortcode( '[evpx_header search="false"]' ), '<dialog' ) && ! str_contains( do_shortcode( '[evpx_header search="false"]' ), 'data-evpx-search-open' ) );
$check( 'header: sticky is on by default and can be turned off', str_contains( $header, 'data-evpx-sticky="1"' ) && str_contains( do_shortcode( '[evpx_header sticky="false"]' ), 'data-evpx-sticky="0"' ) );
$check( 'header: an address that is a script is not a link', ! str_contains( do_shortcode( '[evpx_header links="X | javascript:alert(1)"]' ), 'javascript:' ) );
$check( 'header: with no brand given it is the site\'s name', str_contains( do_shortcode( '[evpx_header]' ), esc_html( get_bloginfo( 'name' ) ) ) );

// ------------------------------------------------------------------ the search page
$author = (int) get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];
$made   = array();
foreach ( array( 'Zyxwv charging guide' => 'post', 'Zyxwv about page' => 'page' ) as $title => $type ) {
	$made[] = wp_insert_post( array( 'post_title' => $title, 'post_content' => 'zyxwv', 'post_status' => 'publish', 'post_type' => $type, 'post_author' => $author ) );
}
$_GET['q'] = 'zyxwv';
$found     = do_shortcode( '[evpx_search]' );
$check( 'search: a query lists the published pages and posts it matches, with a count', 2 === $count( 'class="evpx-search__hit"', $found ) && str_contains( $found, 'class="evpx-search__count"' ), (string) $count( 'class="evpx-search__hit"', $found ) );
$_GET['type'] = 'page';
$check( 'search: a filter narrows it to one kind', 1 === $count( 'class="evpx-search__hit"', do_shortcode( '[evpx_search]' ) ) );
unset( $_GET['type'] );
$_GET['q'] = '<script>alert(1)</script>';
$check( 'search: what was searched for is printed escaped', ! str_contains( do_shortcode( '[evpx_search]' ), '<script>alert' ) );
$_GET['q'] = 'qqqqnomatch';
$check( 'search: no match says so and offers the way on', str_contains( do_shortcode( '[evpx_search suggestions="Talk | /contact/"]' ), 'Talk' ) && 0 === $count( 'class="evpx-search__hit"', do_shortcode( '[evpx_search]' ) ) );
unset( $_GET['q'] );
$check( 'search: with nothing typed it shows the suggestions', str_contains( do_shortcode( '[evpx_search suggestions="Talk | /contact/"]' ), 'evpx-search__hit--quick' ) );
foreach ( $made as $id ) {
	wp_delete_post( $id, true );
}

// ------------------------------------------------------------------ the footer
$footer = do_shortcode( '[evpx_footer brand="X" contact="a@b.co' . "\n" . '+44 20 7946 0000' . "\n" . '12 Road" legal="© X"]' );
$footer_text = html_entity_decode( $footer );
$check( 'footer: an email and a phone number are links, an address is text', str_contains( $footer_text, 'href="mailto:a@b.co"' ) && str_contains( $footer_text, 'href="tel:+442079460000"' ) && str_contains( $footer, '>12 Road<' ) && ! str_contains( $footer_text, 'href="12 Road"' ) );

// ------------------------------------------------------------------ stats
$stats = do_shortcode( '[evpx_stats heading="H" variant="light"][evpx_stat value="99.5%" label="Up" fill="99.5"][evpx_stat value="150 kW" label="Bay" symbol="lightning"][evpx_stat value="24/7" label="Watch"][/evpx_stats]' );
$check( 'stats: one item per stat, in a list', 3 === $count( 'class="evpx-stat"', $stats ) && str_contains( $stats, '<ul class="evpx-stats__grid" role="list">' ) );
$check( 'stats: a fill draws a ring to that value; a symbol draws a chip; neither is announced', str_contains( $stats, '--evpx-fill:99.5' ) && 1 === $count( 'class="evpx-stat__ring"', str_replace( 'class="evpx-stat__ring"', 'class="evpx-stat__ring"', $stats ) ) && str_contains( $stats, 'evpx-icon--lightning' ) && str_contains( $stats, 'aria-hidden="true" focusable="false" style="--evpx-fill' ) );
$check( 'stats: a number counts up and its unit is set apart; a phrase does not count', str_contains( $stats, 'data-count="99.5"' ) && str_contains( $stats, '<span class="evpx-stat__unit">%</span>' ) && str_contains( $stats, '<span class="evpx-stat__unit">kW</span>' ) && 2 === $count( 'data-count=', $stats ) && str_contains( $stats, '<span class="evpx-stat__num">24/7</span>' ) );
$check( 'stats: light leaves the dark tokens off, dark sets them, a stale value is dark', ! str_contains( $stats, 'data-evpx-theme="dark"' ) && str_contains( do_shortcode( '[evpx_stats variant="dark"]' ), 'data-evpx-theme="dark"' ) && str_contains( do_shortcode( '[evpx_stats variant="x"]' ), 'data-evpx-theme="dark"' ) );
$check( 'stats: a fill out of range is held to 0 to 100', str_contains( do_shortcode( '[evpx_stats][evpx_stat value="1" label="a" fill="900"][/evpx_stats]' ), '--evpx-fill:100' ) );

// ------------------------------------------------------------------ services
$services = do_shortcode( '[evpx_services heading="S"][evpx_service title="One" summary="a" points="p1' . "\n" . 'p2" scene="grid"][evpx_service title="Two" scene="cabinet" link_label="Go" link_url="/contact/"][/evpx_services]' );
preg_match_all( '/aria-controls="([^"]+)"/', $services, $controls );
preg_match_all( '/<div class="evpx-service__panel" id="([^"]+)"/', $services, $panels );
$check( 'services: every panel has a heading with a button that names it, and the ids are unique', 2 === count( $controls[1] ) && $controls[1] === $panels[1] && 2 === count( array_unique( $panels[1] ) ) );
$check( 'services: the strip starts as finished, static content (the script marks it ready)', ! str_contains( $services, 'data-evpx-ready' ) && 2 === $count( 'aria-expanded="false"', $services ) );
$check( 'services: each panel has a scene behind it, decoration only, and a picture replaces it', 2 === $count( 'evpx-scene--', $services ) && str_contains( $services, 'class="evpx-service__media" aria-hidden="true"' ) );
$check( 'services: points become a list, the link goes where it is told, a panel with no link has none', 2 === $count( '<li>', $services ) && 1 === $count( 'evpx-service__link', $services ) );

// ------------------------------------------------------------------ process
$process = do_shortcode( '[evpx_process heading="P"][evpx_process_step title="A" duration="Week 1" symbol="map-pin"][evpx_process_step title="B"][/evpx_process]' );
$check( 'process: the steps are an ordered list with the counter the script drives', str_contains( $process, '<ol class="evpx-process__steps" role="list">' ) && 2 === $count( 'data-evpx-step', $process ) && str_contains( $process, 'evpx-process__now' ) );

// ------------------------------------------------------------------ projects
$projects = do_shortcode( '[evpx_projects heading="W"][evpx_project name="One" url="/x/" metrics="1 | a' . "\n" . '2 | b' . "\n" . '3 | c' . "\n" . '4 | d"][evpx_project name="Two"][/evpx_projects]' );
$check( 'projects: a project with an address is a link, one without is not', 1 === $count( '<a class="evpx-project__card"', $projects ) && 1 === $count( '<div class="evpx-project__card"', $projects ) );
$check( 'projects: at most three figures a project', 3 === $count( 'class="evpx-project__metric"', $projects ) );
$check( 'projects: the rail is a labelled, focusable region (so a keyboard can scroll it) and its buttons wait for the script', str_contains( $projects, 'role="region"' ) && str_contains( $projects, 'tabindex="0"' ) && str_contains( $projects, 'data-evpx-rail-nav' ) && 1 === preg_match( '/<div class="evpx-projects__nav" hidden/', $projects ) );

// ------------------------------------------------------------------ quotes
$quotes = do_shortcode( '[evpx_quotes auto="true"][evpx_quote quote="Q1" name="N" role="R"][evpx_quote quote="Q2"][/evpx_quotes]' );
$check( 'quotes: each is a figure with a blockquote and, when there is one, a caption', 2 === $count( '<figure class="evpx-quote"', $quotes ) && 2 === $count( '<blockquote', $quotes ) && 1 === $count( '<figcaption', $quotes ) );
$check( 'quotes: the rotation is asked for, the buttons wait for the script', str_contains( $quotes, 'data-evpx-auto="1"' ) && 1 === preg_match( '/<div class="evpx-quotes__nav" hidden/', $quotes ) && str_contains( do_shortcode( '[evpx_quotes][evpx_quote quote="a"][/evpx_quotes]' ), 'data-evpx-auto="0"' ) );

// ------------------------------------------------------------------ contact and its token
$contact = do_shortcode( '[evpx_contact details="Email | hello@example.com' . "\n" . 'Phone | +44 20 7946 0000" to_email="me@example.com"]' );
$check( 'contact: the form posts to admin-post.php with its action, its return address and a signed token', str_contains( $contact, 'admin-post.php' ) && str_contains( $contact, 'name="action" value="evpx_contact"' ) && str_contains( $contact, 'name="evpx_token"' ) && str_contains( $contact, 'name="evpx_return"' ) );
$check( 'contact: every field has a label, and the required ones say so', 4 <= $count( '<label for="evpx-c-', $contact ) && str_contains( $contact, 'name="evpx_email" required' ) && str_contains( $contact, 'name="evpx_message" rows="6" required' ) );
$check( 'contact: the trap field is there, off the page and out of the tab order', str_contains( $contact, 'name="evpx_website" tabindex="-1"' ) && str_contains( $contact, 'class="evpx-contact__trap" aria-hidden="true"' ) );
$check( 'contact: an email and a phone number in the details are links (the address is obscured from harvesters)', str_contains( html_entity_decode( $contact ), 'href="mailto:hello@example.com"' ) && str_contains( $contact, 'href="tel:' ) );
$check( 'contact: the recipient is not in the page as text (only inside the signed token)', ! str_contains( $contact, 'me@example.com' ) );
$check( 'contact: with the map off there is no map', ! str_contains( do_shortcode( '[evpx_contact map="false"]' ), 'evpx-contact__map' ) );

preg_match( '/name="evpx_token" value="([^"]+)"/', $contact, $tok );
$token  = $tok[1] ?? '';
$parsed = ContactForm::verify( $token );
$check( 'token: a token from the form verifies, carries its recipient and is new', $parsed['ok'] && 'me@example.com' === $parsed['to'] && $parsed['age'] < 5 );
$parts    = explode( '.', $token );
$tampered = $parts[0] . '.' . rtrim( strtr( base64_encode( 'evil@example.com' ), '+/', '-_' ), '=' ) . '.' . $parts[2]; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
$check( 'token: changing the recipient breaks it, so a form can only ever mail the address it was given', ! ContactForm::verify( $tampered )['ok'] );
$check( 'token: garbage and an empty token do not verify', ! ContactForm::verify( 'x' )['ok'] && ! ContactForm::verify( '' )['ok'] && ! ContactForm::verify( '1.-.abc' )['ok'] );
$default = ContactForm::verify( ContactForm::token() );
$check( 'token: with no recipient it is the site\'s address (nothing in it names one)', $default['ok'] && '' === $default['to'] );

// ------------------------------------------------------------------ the anchor, for the new sections
$loose = array();
foreach ( array( 'stats', 'services', 'process', 'projects', 'quotes' ) as $tag ) {
	$set = do_shortcode( '[evpx_' . $tag . ' anchor="jump-here"]' );
	if ( 1 !== preg_match( '/<section\b[^>]*\sid="jump-here"[^>]*>/', $set ) ) {
		$loose[] = $tag;
	}
}
$check( 'anchor: the new sections print it as the id of their root', ! $loose, implode( ',', $loose ) );

// ------------------------------------------------------------------ the full-width template
$templates = apply_filters( 'theme_page_templates', array(), wp_get_theme(), null, 'page' );
$check( 'canvas: the "EV full-width page" template is offered for pages, and not for posts', isset( $templates['evpx-canvas'] ) && ! isset( apply_filters( 'theme_page_templates', array(), wp_get_theme(), null, 'post' )['evpx-canvas'] ) );
$check( 'canvas: its template file is there and prints only the content', is_readable( EVPX_PATH . 'templates/canvas.php' ) && str_contains( (string) file_get_contents( EVPX_PATH . 'templates/canvas.php' ), 'the_content()' ) && ! str_contains( (string) file_get_contents( EVPX_PATH . 'templates/canvas.php' ), 'get_header' ) );

echo $failures ? "\n{$failures} check(s) failed.\n" : "\nAll site render checks passed.\n";
exit( $failures ? 1 : 0 );
