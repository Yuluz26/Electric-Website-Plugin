<?php
/**
 * Direct current: a level that stays where it is, above a line that is nought. A point of light runs along it,
 * always the same way. Light in the neon token, line work in currentColor. Only a name and a frequency are
 * written on it, so it has nothing to translate.
 *
 * @var string $uid Unique per piece, for the ids the gradients and filter need.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

$level = 'M50 130H590';

// $u is the escaped uid, and the path data above is this file's own constants: nothing here is user input.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-art evpx-art--wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 400" preserveAspectRatio="xMidYMid meet" fill="none" aria-hidden="true" focusable="false">
	<defs>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="320" cy="150" r="330">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".16"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="<?php echo $u; ?>-under" x1="0" y1="0" x2="0" y2="1">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".22"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</linearGradient>
		<filter id="<?php echo $u; ?>-halo" filterUnits="userSpaceOnUse" x="0" y="0" width="640" height="400" color-interpolation-filters="sRGB">
			<feGaussianBlur stdDeviation="5"/>
		</filter>
	</defs>

	<rect class="evpx-art__fade" width="640" height="400" fill="url(#<?php echo $u; ?>-aura)"/>
	<g class="evpx-art__floor" stroke="currentColor" stroke-opacity=".08" stroke-width="1">
		<path d="M50 60V340M140 60V340M230 60V340M320 60V340M410 60V340M500 60V340M590 60V340M50 120H590M50 280H590"/>
	</g>
	<path class="evpx-art__fade" d="M50 130H590V270H50Z" fill="url(#<?php echo $u; ?>-under)" opacity=".7"/>
	<path class="evpx-art__fade" d="M50 270H590" stroke="currentColor" stroke-opacity=".4" stroke-width="1.2" stroke-dasharray="3 6"/>

	<g class="evpx-art__halo" filter="url(#<?php echo $u; ?>-halo)" stroke-linecap="round">
		<path class="evpx-art__glow evpx-art__draw" pathLength="1" style="--i:2" d="<?php echo $level; ?>"/>
	</g>
	<path class="evpx-art__neon evpx-art__draw" pathLength="1" style="--i:2" d="<?php echo $level; ?>" stroke-width="2.4" stroke-linecap="round"/>
	<path class="evpx-art__pulse" d="<?php echo $level; ?>" stroke-width="2" stroke-dasharray="3 27" stroke-linecap="round"/>

	<g class="evpx-art__lit">
		<path d="M320 130V270" stroke="currentColor" stroke-opacity=".4" stroke-width="1" stroke-dasharray="2 4"/>
		<circle class="evpx-art__dot" cx="320" cy="130" r="5"/>
	</g>

	<g class="evpx-art__notes">
		<text class="evpx-art__label" x="50" y="44">DC</text>
		<text class="evpx-art__label evpx-art__tick" x="590" y="44" text-anchor="end">0 <tspan class="evpx-art__unit">Hz</tspan></text>
		<text class="evpx-art__label evpx-art__tick" x="40" y="136" text-anchor="end">+</text>
		<text class="evpx-art__label evpx-art__tick" x="40" y="276" text-anchor="end">0</text>
	</g>
</svg>
<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
