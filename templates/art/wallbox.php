<?php
/**
 * An AC wall box, its cable and Type 2 plug, and the alternating current it carries: a wave that goes both ways.
 * Light in the neon token, line work in currentColor; nothing on it needs translating.
 *
 * @var string $uid Unique per piece, for the ids the gradients and filter need.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

$cable = 'M266 272C266 326 330 334 386 318C426 306 452 302 476 302';
$wave  = 'M372 132q22.5-44 45 0t45 0t45 0t45 0t45 0';
$bolt  = \EVPX\Support\Icons::path( 'lightning' );

// $u is the escaped uid, and the path data above is this file's own constants: nothing here is user input.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-art evpx-art--wallbox" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 400" preserveAspectRatio="xMidYMid meet" fill="none" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-body" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#262b33"/>
			<stop offset=".6" stop-color="#151920"/>
			<stop offset="1" stop-color="#0e1116"/>
		</linearGradient>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="340" cy="200" r="300">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".16"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<filter id="<?php echo $u; ?>-halo" filterUnits="userSpaceOnUse" x="0" y="0" width="640" height="400" color-interpolation-filters="sRGB">
			<feGaussianBlur stdDeviation="5"/>
		</filter>
	</defs>

	<rect class="evpx-art__fade" width="640" height="400" fill="url(#<?php echo $u; ?>-aura)"/>
	<path d="M40 344H600" stroke="currentColor" stroke-opacity=".16" stroke-width="1"/>

	<?php // The wave the wall box carries, and the line that ties it to the unit. ?>
	<path class="evpx-art__fade" d="M300 132H372" stroke="currentColor" stroke-opacity=".3" stroke-width="1" stroke-dasharray="2 5"/>
	<path class="evpx-art__fade" d="M372 132H597" stroke="currentColor" stroke-opacity=".16" stroke-width="1"/>
	<g class="evpx-art__halo" filter="url(#<?php echo $u; ?>-halo)" stroke-linecap="round" stroke-linejoin="round">
		<path class="evpx-art__glow evpx-art__draw" pathLength="1" style="--i:3" d="<?php echo $wave; ?>"/>
		<path class="evpx-art__glow" d="M266 158a30 30 0 0 1 0 0" opacity="0"/>
		<circle class="evpx-art__glow evpx-art__arc" cx="266" cy="128" r="30" pathLength="100" stroke-dasharray="68 100" transform="rotate(-90 266 128)"/>
	</g>
	<path class="evpx-art__neon evpx-art__draw" pathLength="1" style="--i:3" d="<?php echo $wave; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>

	<?php // The unit. ?>
	<g class="evpx-art__charger" stroke-linecap="round">
		<rect x="200" y="64" width="132" height="208" rx="24" fill="url(#<?php echo $u; ?>-body)"/>
		<rect class="evpx-art__draw" pathLength="1" style="--i:1" x="200" y="64" width="132" height="208" rx="24" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5"/>
		<circle cx="266" cy="128" r="30" stroke="currentColor" stroke-opacity=".18" stroke-width="2"/>
		<circle class="evpx-art__neon evpx-art__arc" cx="266" cy="128" r="30" pathLength="100" stroke-width="2.5" stroke-dasharray="68 100" transform="rotate(-90 266 128)"/>
		<path class="evpx-art__dot" d="<?php echo esc_attr( $bolt ); ?>" transform="translate(254 116) scale(.094)"/>
		<rect x="234" y="180" width="64" height="9" rx="4.5" stroke="currentColor" stroke-opacity=".32" stroke-width="1.2"/>
		<rect x="234" y="180" width="38" height="9" rx="4.5" class="evpx-art__dot"/>
		<path d="M244 214h44M244 228h44" stroke="currentColor" stroke-opacity=".18" stroke-width="1.2"/>
		<rect x="254" y="268" width="24" height="10" rx="3" fill="#0b0d11" stroke="currentColor" stroke-opacity=".5" stroke-width="1.2"/>
	</g>

	<?php // The cable, and the plug on the end of it. ?>
	<path class="evpx-art__draw" pathLength="1" style="--i:4" d="<?php echo $cable; ?>" stroke="currentColor" stroke-opacity=".62" stroke-width="5" stroke-linecap="round"/>
	<path class="evpx-art__pulse" d="<?php echo $cable; ?>" stroke-width="1.6" stroke-dasharray="3 17" stroke-linecap="round"/>
	<g stroke-linecap="round">
		<circle cx="514" cy="302" r="38" fill="url(#<?php echo $u; ?>-body)" stroke="currentColor" stroke-opacity=".78" stroke-width="1.5"/>
		<circle cx="514" cy="302" r="29" stroke="currentColor" stroke-opacity=".3" stroke-width="1.2"/>
		<circle cx="500" cy="288" r="4.5" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2"/>
		<circle cx="528" cy="288" r="4.5" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2"/>
		<circle cx="493" cy="308" r="4.5" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2"/>
		<circle cx="535" cy="308" r="4.5" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2"/>
		<circle cx="514" cy="322" r="5.5" class="evpx-art__neon" stroke-width="1.5"/>
		<circle cx="514" cy="302" r="3" stroke="currentColor" stroke-opacity=".5" stroke-width="1.2"/>
	</g>

	<g class="evpx-art__notes">
		<text class="evpx-art__label" x="372" y="84">AC · 7–22 <tspan class="evpx-art__unit">kW</tspan></text>
		<text class="evpx-art__label evpx-art__tick" x="372" y="186">50 / 60 <tspan class="evpx-art__unit">Hz</tspan></text>
		<text class="evpx-art__label" x="514" y="372" text-anchor="middle">Type 2</text>
	</g>
</svg>
<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
