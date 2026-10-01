<?php
/**
 * Power against time for the same battery on AC and on DC: a low line that never rises, and a curve that rises at
 * once, holds, and tapers as the battery fills. The figures are illustrative (an 11 kW AC point, a 150 kW DC one),
 * the same the explorer uses. Only units and acronyms are written on it, so it has nothing to translate.
 *
 * @var string $uid Unique per piece, for the ids the gradients and filter need.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

$dc = 'M72 316C86 316 92 84 112 78H229C270 78 300 100 334 140C378 192 420 226 465 240C510 252 556 260 596 268';

// $u is the escaped uid, and the path data above is this file's own constants: nothing here is user input.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-art evpx-art--curve" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 400" preserveAspectRatio="xMidYMid meet" fill="none" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-under" x1="0" y1="0" x2="0" y2="1">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".3"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</linearGradient>
		<filter id="<?php echo $u; ?>-halo" filterUnits="userSpaceOnUse" x="0" y="0" width="640" height="400" color-interpolation-filters="sRGB">
			<feGaussianBlur stdDeviation="5"/>
		</filter>
	</defs>

	<g class="evpx-art__floor" stroke="currentColor" stroke-opacity=".09" stroke-width="1">
		<path d="M72 60H596M72 124H596M72 188H596M72 252H596M203 60V316M334 60V316M465 60V316M596 60V316"/>
	</g>
	<path d="M72 316H596M72 60V316" stroke="currentColor" stroke-opacity=".34" stroke-width="1.2"/>

	<path class="evpx-art__fade" d="<?php echo $dc; ?>V316H72Z" fill="url(#<?php echo $u; ?>-under)"/>

	<path class="evpx-art__draw" pathLength="1" style="--i:1" d="M72 298H596" stroke="currentColor" stroke-opacity=".78" stroke-width="1.6" stroke-linecap="round"/>

	<g class="evpx-art__halo" filter="url(#<?php echo $u; ?>-halo)" stroke-linecap="round" stroke-linejoin="round">
		<path class="evpx-art__glow evpx-art__draw" pathLength="1" style="--i:2" d="<?php echo $dc; ?>"/>
	</g>
	<path class="evpx-art__neon evpx-art__draw" pathLength="1" style="--i:2" d="<?php echo $dc; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

	<g class="evpx-art__lit">
		<path d="M334 316V140" stroke="currentColor" stroke-opacity=".4" stroke-width="1" stroke-dasharray="3 5"/>
		<circle class="evpx-art__dot" cx="334" cy="140" r="4.5"/>
		<circle cx="334" cy="298" r="3.5" fill="currentColor"/>
	</g>

	<g class="evpx-art__notes">
		<text class="evpx-art__label" x="72" y="42"><tspan class="evpx-art__unit">kW</tspan></text>
		<text class="evpx-art__label" x="596" y="360" text-anchor="end"><tspan class="evpx-art__unit">min</tspan></text>
		<text class="evpx-art__label evpx-art__tick" x="62" y="64" text-anchor="end">160</text>
		<text class="evpx-art__label evpx-art__tick" x="62" y="128" text-anchor="end">120</text>
		<text class="evpx-art__label evpx-art__tick" x="62" y="192" text-anchor="end">80</text>
		<text class="evpx-art__label evpx-art__tick" x="62" y="256" text-anchor="end">40</text>
		<text class="evpx-art__label evpx-art__tick" x="62" y="320" text-anchor="end">0</text>
		<text class="evpx-art__label evpx-art__tick" x="72" y="340" text-anchor="middle">0</text>
		<text class="evpx-art__label evpx-art__tick" x="203" y="340" text-anchor="middle">15</text>
		<text class="evpx-art__label evpx-art__tick" x="334" y="340" text-anchor="middle">30</text>
		<text class="evpx-art__label evpx-art__tick" x="465" y="340" text-anchor="middle">45</text>
		<text class="evpx-art__label evpx-art__tick" x="596" y="340" text-anchor="middle">60</text>
		<text class="evpx-art__label evpx-art__series" x="122" y="60">DC · 150 <tspan class="evpx-art__unit">kW</tspan></text>
		<text class="evpx-art__label evpx-art__series" x="200" y="284">AC · 11 <tspan class="evpx-art__unit">kW</tspan></text>
	</g>
</svg>
<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
