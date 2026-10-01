<?php
/**
 * A DC fast charger: the cabinet that does the converting, its heavy cable and CCS plug, and the symbol for direct
 * current (a steady line over a dashed one). Light in the neon token, line work in currentColor.
 *
 * @var string $uid Unique per piece, for the ids the gradients and filter need.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

$cable = 'M326 196C388 196 388 312 440 312';
$bolt  = \EVPX\Support\Icons::path( 'lightning' );

// $u is the escaped uid, and the path data above is this file's own constants: nothing here is user input.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-art evpx-art--cabinet" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 400" preserveAspectRatio="xMidYMid meet" fill="none" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-body" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#262b33"/>
			<stop offset=".6" stop-color="#151920"/>
			<stop offset="1" stop-color="#0e1116"/>
		</linearGradient>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="300" cy="190" r="320">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".18"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<filter id="<?php echo $u; ?>-halo" filterUnits="userSpaceOnUse" x="0" y="0" width="640" height="400" color-interpolation-filters="sRGB">
			<feGaussianBlur stdDeviation="5"/>
		</filter>
	</defs>

	<rect class="evpx-art__fade" width="640" height="400" fill="url(#<?php echo $u; ?>-aura)"/>
	<path d="M40 356H600" stroke="currentColor" stroke-opacity=".16" stroke-width="1"/>

	<?php // The symbol for direct current: steady above, dashed below. ?>
	<g class="evpx-art__notes" stroke="currentColor" stroke-linecap="round">
		<path d="M500 96H580M500 112H508M521 112H529M542 112H550M563 112H571" stroke-opacity=".5" stroke-width="2"/>
	</g>

	<g class="evpx-art__charger" stroke-linecap="round">
		<rect x="150" y="44" width="176" height="300" rx="16" fill="url(#<?php echo $u; ?>-body)"/>
		<rect class="evpx-art__draw" pathLength="1" style="--i:1" x="150" y="44" width="176" height="300" rx="16" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5"/>
		<rect x="176" y="84" width="124" height="88" rx="9" fill="#0b0d11" stroke="currentColor" stroke-opacity=".38" stroke-width="1.2"/>
		<path class="evpx-art__dot" d="<?php echo esc_attr( $bolt ); ?>" transform="translate(190 100) scale(.15)"/>
		<text class="evpx-art__value" x="238" y="134" font-size="28">150</text>
		<text class="evpx-art__label" x="238" y="160"><tspan class="evpx-art__unit">kW</tspan></text>
		<path d="M180 216H296M180 234H296M180 252H296M180 270H296M180 288H296" stroke="currentColor" stroke-opacity=".16" stroke-width="1.4"/>
		<path d="M144 344H332" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5"/>
	</g>

	<g class="evpx-art__halo" filter="url(#<?php echo $u; ?>-halo)" stroke-linecap="round">
		<path class="evpx-art__glow" d="M176 62H300"/>
		<circle class="evpx-art__glow" cx="474" cy="342" r="7"/>
		<circle class="evpx-art__glow" cx="498" cy="342" r="7"/>
	</g>
	<g class="evpx-art__lit" stroke-linecap="round">
		<path class="evpx-art__neon" d="M176 62H300" stroke-width="2"/>
	</g>

	<?php // The cable and its CCS head, seen from the front: the round Type 2 half above, the two DC pins below. ?>
	<path class="evpx-art__draw" pathLength="1" style="--i:3" d="<?php echo $cable; ?>" stroke="currentColor" stroke-opacity=".62" stroke-width="7" stroke-linecap="round"/>
	<path class="evpx-art__pulse" d="<?php echo $cable; ?>" stroke-width="1.8" stroke-dasharray="3 17" stroke-linecap="round"/>
	<g stroke-linecap="round">
		<rect x="438" y="268" width="72" height="94" rx="16" fill="url(#<?php echo $u; ?>-body)" stroke="currentColor" stroke-opacity=".78" stroke-width="1.5"/>
		<circle cx="474" cy="298" r="20" stroke="currentColor" stroke-opacity=".34" stroke-width="1.2"/>
		<circle cx="474" cy="298" r="3" stroke="currentColor" stroke-opacity=".5" stroke-width="1.2"/>
		<circle cx="465" cy="290" r="3.2" stroke="currentColor" stroke-opacity=".5" stroke-width="1.1"/>
		<circle cx="483" cy="290" r="3.2" stroke="currentColor" stroke-opacity=".5" stroke-width="1.1"/>
		<circle cx="465" cy="306" r="3.2" stroke="currentColor" stroke-opacity=".5" stroke-width="1.1"/>
		<circle cx="483" cy="306" r="3.2" stroke="currentColor" stroke-opacity=".5" stroke-width="1.1"/>
		<circle class="evpx-art__neon" cx="474" cy="342" r="7" stroke-width="1.6"/>
		<circle class="evpx-art__neon" cx="498" cy="342" r="7" stroke-width="1.6"/>
	</g>

	<g class="evpx-art__notes">
		<text class="evpx-art__label" x="500" y="76">DC</text>
		<text class="evpx-art__label" x="474" y="258" text-anchor="middle">CCS2</text>
		<text class="evpx-art__label evpx-art__tick" x="500" y="140">50–350 <tspan class="evpx-art__unit">kW</tspan></text>
	</g>
</svg>
<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
