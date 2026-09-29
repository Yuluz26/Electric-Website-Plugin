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
$check( 'comparison: the ruler is decoration, hidden from assistive technology, and the figure is still text', substr_count( $html, 'class="evpx-ruler"' ) === substr_count( $html, 'aria-hidden="true"' ) - substr_count( $html, 'evpx-button__arrow' ) && str_contains( $html, '<span class="evpx-comparison__figure">7–22 kW</span>' ) );

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
$check( 'hero: each byline item carries its caption in data-label and its text is only the value', 1 === preg_match( '/data-label="Author">A\. Writer<\/span>/', $hero ) && 1 === preg_match( '/data-label="Published">September 2026<\/span>/', $hero ) && 1 === preg_match( '/data-label="Reading time">7 min read<\/span>/', $hero ) );
$check( 'hero: without a picture it carries the blueprint grid and the charge line', str_contains( $hero, 'evpx-hero__grid' ) && str_contains( $hero, 'evpx-hero__line' ) && ! str_contains( $hero, 'evpx-hero--media' ) );
$check( 'hero: the content sits in a column inside the container, so it aligns with the sections below', 1 === preg_match( '/class="evpx-container evpx-hero__inner">\s*<div class="evpx-hero__content">/', $hero ) );

// ------------------------------------------------------------------ nothing typed by hand is a glyph arrow any more
$arrows = do_shortcode( '[evpx_hero title="T" cta_label="Go" cta_url="#x" progress_bar="false"][evpx_cta title="C" button_label="Go" button_url="#y"]' );
$check( 'buttons: the arrow is drawn by CSS, not typed (no glyph, no per-font fallback)', ! str_contains( $arrows, '&rarr;' ) && ! str_contains( $arrows, '→' ) && 2 === substr_count( $arrows, 'class="evpx-button__arrow"' ) );

echo $failures ? "\n{$failures} check(s) failed.\n" : "\nAll render checks passed.\n";
exit( $failures ? 1 : 0 );
