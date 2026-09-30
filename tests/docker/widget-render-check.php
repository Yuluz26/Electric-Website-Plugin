<?php
/**
 * What the widgets print, checked in WordPress without a browser: the comparison's power scale, the section's
 * key figure, the hero's captions, and that anything typed into them is escaped. Run through
 * tests/docker/widget-render-check.sh. Exits non-zero on any failure.
 */

$failures = 0;
$check    = static function ( $label, $ok, $detail = '' ) use ( &$failures ) {
	echo ( $ok ? 'PASS' : 'FAIL' ) . ' — ' . $label . ( ! $ok && $detail ? " ($detail)" : '' ) . "\n";
	if ( ! $ok ) {
		++$failures;
	}
};

$comparison = static function ( string $ac, string $dc ): string {
	return do_shortcode( '[evpx_comparison ac_power_range="' . $ac . '" dc_power_range="' . $dc . '"]' );
};
$rulers     = static function ( string $html ): int {
	return substr_count( $html, 'class="evpx-ruler"' );
};

// ------------------------------------------------------------------ the power scale
$html = $comparison( '7–22 kW', '50–350+ kW' );
$check( 'comparison: two kilowatt ranges give each panel a ruler', 2 === $rulers( $html ), 'rulers=' . $rulers( $html ) );
$check( 'comparison: the scale tops out at the larger range, labelled', str_contains( $html, 'data-max="350 kW"' ) );
$check(
	'comparison: the AC bar is 7 to 22 of 350, the DC range is outlined beside it',
	str_contains( $html, '--evpx-from:2.00%;--evpx-to:6.29%;--evpx-other-from:14.29%;--evpx-other-to:100.00%' ),
	preg_match( '/--evpx-from:[^"]*/', $html, $m ) ? $m[0] : 'no style'
);
$check(
	'comparison: the DC panel gets the same scale the other way round',
	str_contains( $html, '--evpx-from:14.29%;--evpx-to:100.00%;--evpx-other-from:2.00%;--evpx-other-to:6.29%' )
);
$check( 'comparison: the ruler and its legend are decoration, hidden from assistive technology, and the figure is still text', 2 === preg_match_all( '/<span\s+class="evpx-ruler"\s+aria-hidden="true"/', $html ) && 2 === preg_match_all( '/<span class="evpx-ruler__key" aria-hidden="true">/', $html ) && str_contains( $html, '<span class="evpx-comparison__figure">7–22 kW</span>' ) );
$check( 'comparison: the legend names what the bar and the outline are (this panel, the other)', 2 === substr_count( $html, 'evpx-ruler__swatch--other' ) && str_contains( $html, 'AC Charging</span>' ) && str_contains( $html, 'DC Fast Charging</span>' ) );
$check( 'comparison: each tab carries an icon, drawn inline and hidden from assistive technology', 1 === preg_match( '/<button[^>]*evpx-comparison__tab--ac[^>]*>\s*<svg class="evpx-icon evpx-icon--wave-sine"[^>]*aria-hidden="true"[^>]*focusable="false"/', $html ) && str_contains( $html, 'evpx-icon--lightning' ) );

$check( 'comparison: one figure is a point on the scale, not a range from zero', 2 === $rulers( $comparison( '22 kW', '350 kW' ) ) && str_contains( $comparison( '22 kW', '350 kW' ), '--evpx-from:6.29%;--evpx-to:6.29%' ) );
$check( 'comparison: thousands separators are read (1,000 kW)', str_contains( $comparison( '7–22 kW', '50–1,000 kW' ), 'data-max="1,000 kW"' ) );
$check( 'comparison: a decimal range is read (7.4-22 kW)', 2 === $rulers( $comparison( '7.4-22 kW', '50–350 kW' ) ) );

foreach (
	array(
		'more than two numbers is ambiguous ("CCS2 50-350 kW")' => array( '7–22 kW', 'CCS2 50-350 kW' ),
		'a voltage beside the range ("230 V, 7-22 kW")'       => array( '230 V, 7-22 kW', '50–350 kW' ),
		'a unit that is not kW ("2 MW")'                      => array( '7–22 kW', '2 MW' ),
		'no number at all'                                    => array( 'Fast', '50–350 kW' ),
		'a range that runs backwards ("22-7 kW")'             => array( '22-7 kW', '50–350 kW' ),
		'an empty range'                                      => array( '', '50–350 kW' ),
	) as $why => $pair
) {
	$check( 'comparison: no ruler when a panel\'s range is not plainly kilowatts: ' . $why, 0 === $rulers( $comparison( $pair[0], $pair[1] ) ) );
}

$check( 'comparison: the plate keeps the copy and both data rows whether or not there is a ruler', 2 === substr_count( $comparison( 'Fast', 'Faster' ), 'class="evpx-comparison__figure"' ) && 4 === substr_count( $comparison( 'Fast', 'Faster' ), 'class="evpx-comparison__data-row"' ) );

// ------------------------------------------------------------------ the section
$section = do_shortcode( '[evpx_section heading="How AC charging works" figure="7–22 kW" figure_label="Typical AC power" body="One.' . "\n\n" . 'Two." layout="media-left"]' );
$check( 'section: a key figure is printed with its caption', str_contains( $section, '<span class="evpx-section__figure-value">7–22 kW</span>' ) && str_contains( $section, '<span class="evpx-section__figure-label">Typical AC power</span>' ) );
$check( 'section: without a picture it says so, so the stylesheet can make it a split', str_contains( $section, 'evpx-section--no-media' ) );
$check( 'section: no figure, no figure markup', ! str_contains( do_shortcode( '[evpx_section heading="A"]' ), 'evpx-section__figure' ) );
$check( 'section: a caption without a figure prints nothing', ! str_contains( do_shortcode( '[evpx_section heading="A" figure_label="Orphan caption"]' ), 'Orphan caption' ) );
$check( 'section: what is typed into the figure is escaped', ! str_contains( do_shortcode( '[evpx_section heading="A" figure="<script>alert(1)</script>"]' ), '<script>' ) );

// ------------------------------------------------------------------ the hero
$hero = do_shortcode( '[evpx_hero title="T" author="A. Writer" date="September 2026" reading_time="7 min read" progress_bar="false"]' );
$byline = static function ( string $caption, string $value ) use ( $hero ): bool {
	return 1 === preg_match( '/data-label="' . preg_quote( $caption, '/' ) . '">\s*<svg class="evpx-icon[^>]*aria-hidden="true"[^>]*>.*?<\/svg><span class="evpx-hero__meta-value">' . preg_quote( $value, '/' ) . '<\/span><\/span>/s', $hero );
};
$check( 'hero: each byline item carries its caption in data-label and an icon, and its text is only the value', $byline( 'Author', 'A. Writer' ) && $byline( 'Published', 'September 2026' ) && $byline( 'Reading time', '7 min read' ) );
$check( 'hero: without a picture it carries the blueprint grid and the charge line', str_contains( $hero, 'evpx-hero__grid' ) && str_contains( $hero, 'evpx-hero__line' ) && ! str_contains( $hero, 'evpx-hero--media' ) );
$check( 'hero: the content sits in a column inside the container, so it aligns with the sections below', 1 === preg_match( '/class="evpx-container evpx-hero__inner">\s*<div class="evpx-hero__content">/', $hero ) );

// ------------------------------------------------------------------ nothing typed by hand is a glyph arrow any more
$arrows = do_shortcode( '[evpx_hero title="T" cta_label="Go" cta_url="#x" progress_bar="false"][evpx_cta title="C" button_label="Go" button_url="#y"]' );
$check( 'buttons: the arrow is an icon from the family (inline SVG, hidden from assistive technology), not a typed glyph', ! str_contains( $arrows, '&rarr;' ) && ! str_contains( $arrows, '→' ) && 2 === preg_match_all( '/<svg class="evpx-icon evpx-icon--arrow-right evpx-button__arrow"[^>]*aria-hidden="true"[^>]*focusable="false"/', $arrows ) );

// ------------------------------------------------------------------ icons
$icon = \EVPX\Support\Icons::svg( 'lightning' );
$check( 'icons: one is inline SVG in currentColor, decorative, and not focusable', 1 === preg_match( '/^<svg class="evpx-icon evpx-icon--lightning" [^>]*viewBox="0 0 256 256"[^>]*fill="currentColor" aria-hidden="true" focusable="false"><path d="[^"]+"\/><\/svg>$/', $icon ) );
$check( 'icons: a name that is not in the family renders as nothing, not a broken box', '' === \EVPX\Support\Icons::svg( 'no-such-icon' ) && '' === \EVPX\Support\Icons::svg( '"><script>' ) );
$check( 'icons: an extra class is kept and sanitised', str_contains( \EVPX\Support\Icons::svg( 'plus', 'a b"c' ), 'class="evpx-icon evpx-icon--plus a bc"' ) );
$missing = array_filter( array_keys( \EVPX\Support\Icons::choices() ), static function ( $name ) {
	return ! \EVPX\Support\Icons::has( $name );
} );
$check( 'icons: every icon an editor is offered exists in the family', empty( $missing ), implode( ',', $missing ) );
$check(
	'icons: the words of the demo article find their icons',
	'clock' === \EVPX\Support\Icons::guess( 'Dwell time' ) && 'circuitry' === \EVPX\Support\Icons::guess( 'Site Infrastructure' ) && 'gauge' === \EVPX\Support\Icons::guess( 'Site capacity' ) && 'coins' === \EVPX\Support\Icons::guess( 'Operating model' ) && 'road-horizon' === \EVPX\Support\Icons::guess( 'Highway corridor' ) && 'bed' === \EVPX\Support\Icons::guess( 'Hotel' ) && 'lightning' === \EVPX\Support\Icons::guess( 'Grid' )
);
$check( 'icons: words it does not know fall back to what the caller says, or to nothing', 'x' === \EVPX\Support\Icons::guess( 'Zebra', 'x' ) && '' === \EVPX\Support\Icons::guess( 'Zebra' ) );
$check( 'icons: "none" is nothing, "auto" is a guess, a name is itself, a stale value is nothing', '' === \EVPX\Support\Icons::resolve( 'none', 'Hotel' ) && 'bed' === \EVPX\Support\Icons::resolve( 'auto', 'Hotel' ) && 'truck' === \EVPX\Support\Icons::resolve( 'truck', 'Hotel' ) && '' === \EVPX\Support\Icons::resolve( 'gone', 'Hotel' ) );

$card = static function ( string $atts ): string {
	return do_shortcode( '[evpx_scenarios][evpx_scenario_card ' . $atts . ' title="T"][/evpx_scenarios]' );
};
$check( 'scenario card: the icon is chosen from the scenario\'s words', str_contains( $card( 'scenario="Workplace"' ), 'evpx-icon--buildings' ) && str_contains( $card( 'scenario="Fleet depot"' ), 'evpx-icon--truck' ) );
$check( 'scenario card: unknown words get the charging-station icon, "none" gets no chip, a chosen icon wins', str_contains( $card( 'scenario="Zebra"' ), 'evpx-icon--charging-station' ) && ! str_contains( $card( 'scenario="Workplace" symbol="none"' ), 'evpx-iconchip' ) && str_contains( $card( 'scenario="Workplace" symbol="leaf"' ), 'evpx-icon--leaf' ) );
$check( 'scenario card: a stale icon value is the automatic choice, not an error and not a broken box', str_contains( $card( 'scenario="Workplace" symbol="bogus"' ), 'evpx-icon--buildings' ) );

$flow = static function ( string $atts ): string {
	return do_shortcode( '[evpx_flow ' . $atts . ']' );
};
$check( 'flow: five steps that all name something get an icon each, and the number becomes a caption', 5 === substr_count( $flow( '' ), 'class="evpx-flow__num"' ) && str_contains( $flow( '' ), 'evpx-icon--charging-station' ) );
$check( 'flow: if one step\'s words are not known, none has an icon and every node is its number', ! str_contains( $flow( 'step3_label="Permits"' ), 'evpx-icon' ) && 5 === preg_match_all( '/class="evpx-flow__node" aria-hidden="true">0[1-5]<\/span>/', $flow( 'step3_label="Permits"' ) ) );
$check( 'flow: icons can be switched off', ! str_contains( $flow( 'icons="false"' ), 'evpx-icon' ) );
$check( 'flow: dark is the default look (the dark tokens are set on the widget), light leaves them off, and a stale value is the default', str_contains( $flow( '' ), 'data-evpx-theme="dark"' ) && ! str_contains( $flow( 'variant="light"' ), 'data-evpx-theme' ) && str_contains( $flow( 'variant="neon"' ), 'data-evpx-theme="dark"' ) );

$factor = do_shortcode( '[evpx_decision_factors][evpx_decision_factor title="Dwell time"][evpx_decision_factor title="Dwell time" symbol="none"][/evpx_decision_factors]' );
$check( 'decision factor: the icon is chosen from the title, and can be switched off', 1 === substr_count( $factor, 'evpx-decision__icon' ) && str_contains( $factor, 'evpx-icon--clock' ) );

// ------------------------------------------------------------------ artwork
$art = \EVPX\Support\Art::render( 'charge-curve' ) . \EVPX\Support\Art::render( 'charge-curve' );
preg_match_all( '/id="(evpx-art-\d+)-under"/', $art, $ids );
$check( 'artwork: it is decoration (aria-hidden, not focusable), and two on a page do not share ids', 2 === count( $ids[1] ) && $ids[1][0] !== $ids[1][1] && 2 === substr_count( $art, 'aria-hidden="true" focusable="false"' ) );
$check( 'artwork: a key that is not there renders nothing; so does "none"', '' === \EVPX\Support\Art::render( 'no-such-art' ) && '' === \EVPX\Support\Art::render( '../../wp-config' ) && '' === \EVPX\Support\Art::panel( 'none' ) && '' === \EVPX\Support\Art::panel( 'hero-schematic' ) );
$missing = array_filter( array_keys( \EVPX\Support\Art::options() ), static function ( $key ) {
	return 'none' !== $key && ! \EVPX\Support\Art::has( $key );
} );
$check( 'artwork: every drawing a control offers exists', empty( $missing ), implode( ',', $missing ) );

$drawn = do_shortcode( '[evpx_section heading="H" body="B" layout="media-right" artwork="wallbox"]' );
$check( 'section: a chosen drawing is shown on its panel, and the section is no longer a text-only split', str_contains( $drawn, 'class="evpx-artpanel"' ) && str_contains( $drawn, 'evpx-art--wallbox' ) && ! str_contains( $drawn, 'evpx-section--no-media' ) );
$check( 'section: text-only ignores the drawing; a stale one is nothing', ! str_contains( do_shortcode( '[evpx_section heading="H" layout="text-only" artwork="wallbox"]' ), 'evpx-art' ) && str_contains( do_shortcode( '[evpx_section heading="H" artwork="gone"]' ), 'evpx-section--no-media' ) );
$check( 'hero: without a picture it carries its drawing; "none" removes it; a picture replaces it', str_contains( $hero, 'evpx-hero--art' ) && str_contains( $hero, 'evpx-art--schematic' ) && ! str_contains( do_shortcode( '[evpx_hero title="T" artwork="none" progress_bar="false"]' ), 'evpx-art' ) );
$check( 'hero: the drawing is hidden from assistive technology, and its words are in the site\'s language', str_contains( $hero, '<div class="evpx-hero__visual" aria-hidden="true">' ) && str_contains( $hero, 'State of charge' ) );

// ------------------------------------------------------------------ the CTA
$check( 'cta: the default look is the dark one', str_contains( do_shortcode( '[evpx_cta title="C"]' ), 'evpx-cta--dark' ) && str_contains( do_shortcode( '[evpx_cta title="C" variant="accent"]' ), 'evpx-cta--accent' ) );

// ------------------------------------------------------------------ related: a drawing for an article with no picture
$made = array();
foreach ( array( 'One', 'Two', 'Three' ) as $title ) {
	$made[] = wp_insert_post( array( 'post_title' => 'Art check ' . $title, 'post_status' => 'publish', 'post_content' => 'x' ) );
}
$related = do_shortcode( '[evpx_related source="manual" post_ids="' . implode( ',', $made ) . '" count="3"]' );
$check( 'related: articles with no picture are given a drawing each, and the drawings differ', 3 === substr_count( $related, 'evpx-related__media evpx-artpanel' ) && str_contains( $related, 'evpx-art--curve' ) && str_contains( $related, 'evpx-art--wallbox' ) && str_contains( $related, 'evpx-art--cabinet' ) );
$check( 'related: with the toggle off, a row of articles with no pictures shows none', ! str_contains( do_shortcode( '[evpx_related source="manual" post_ids="' . implode( ',', $made ) . '" count="3" art="false"]' ), 'evpx-related__media' ) );
foreach ( $made as $post_id ) {
	wp_delete_post( $post_id, true );
}

// ------------------------------------------------------------------ the explorer
$explorer = do_shortcode( '[evpx_explorer]' );
preg_match( '/data-evpx-explorer="([^"]*)"/', $explorer, $cfg_match );
$cfg = json_decode( html_entity_decode( $cfg_match[1] ?? '' ), true );
$check( 'explorer: it prints its model\'s numbers and the starting state for the script, as JSON', is_array( $cfg ) && 60.0 === (float) $cfg['battery'] && 0.2 === (float) $cfg['start'] && 120 === $cfg['dwell'] && 'ac-22' === $cfg['charger'] && 'km' === $cfg['unit'], wp_json_encode( $cfg ) );
$check( 'explorer: the controls are rendered hidden (a page without a script has none), the comparison is not', str_contains( $explorer, 'evpx-explorer__controls" hidden' ) && 5 === substr_count( $explorer, 'class="evpx-explorer__row' ) );
$check( 'explorer: the default answer is worked out on the server: 22 kWh, 122 km, and the car\'s limit named', str_contains( $explorer, 'data-out="energy">22<' ) && str_contains( $explorer, 'data-out="range">122<' ) && str_contains( $explorer, 'takes at most 11' ) );
$check( 'explorer: the sentence keeps a figure and its unit on one line', str_contains( $explorer, "122\u{00A0}km" ) && str_contains( $explorer, "22\u{00A0}kW" ) );
$check( 'explorer: it is a dark panel of its own', str_contains( $explorer, 'data-evpx-theme="dark"' ) );
$wild = do_shortcode( '[evpx_explorer battery="99999" start_soc="-40" ac_limit="0" dc_peak="abc" consumption="0" charger="bogus" dwell="9" unit="furlongs"]' );
preg_match( '/data-evpx-explorer="([^"]*)"/', $wild, $wild_match );
$wild_cfg = json_decode( html_entity_decode( $wild_match[1] ?? '' ), true );
$check( 'explorer: numbers out of range are held inside it; an unknown charger, dwell or unit is the default', is_array( $wild_cfg ) && 150.0 === (float) $wild_cfg['battery'] && 0.0 === (float) $wild_cfg['start'] && 3.0 === (float) $wild_cfg['acLimit'] && 150.0 === (float) $wild_cfg['dcPeak'] && 10.0 === (float) $wild_cfg['consumption'] && 'ac-22' === $wild_cfg['charger'] && 120 === $wild_cfg['dwell'] && 'km' === $wild_cfg['unit'], wp_json_encode( $wild_cfg ) );
$check( 'explorer: what is typed into it is escaped', ! str_contains( do_shortcode( '[evpx_explorer heading="<script>alert(1)</script>" eyebrow="<b>x</b>"]' ), '<script>' ) );
$miles = do_shortcode( '[evpx_explorer unit="mi"]' );
$check( 'explorer: miles are miles (122 km is 76 mi)', str_contains( $miles, 'data-out="range">76<' ) && str_contains( $miles, 'data-out="distance">mi<' ) );

echo $failures ? "\n{$failures} check(s) failed.\n" : "\nAll render checks passed.\n";
exit( $failures ? 1 : 0 );
