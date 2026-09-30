<?php
/**
 * The path power takes to a charger: a pylon, a transformer, a switchboard, the charger. The last two hops carry
 * a pulse. Light in the neon token, line work in currentColor. Four words are written on it, in the site's language.
 *
 * @var string $uid Unique per piece, for the ids the gradients and filter need.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

$hop_a = 'M280 290H344';
$hop_b = 'M424 290H486';
$bolt  = \EVPX\Support\Icons::path( 'lightning' );

// $u is the escaped uid, and the path data above is this file's own constants: nothing here is user input.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-art evpx-art--grid" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 400" preserveAspectRatio="xMidYMid meet" fill="none" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-body" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#262b33"/>
			<stop offset=".6" stop-color="#151920"/>
			<stop offset="1" stop-color="#0e1116"/>
		</linearGradient>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="420" cy="260" r="320">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".16"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<filter id="<?php echo $u; ?>-halo" filterUnits="userSpaceOnUse" x="0" y="0" width="640" height="400" color-interpolation-filters="sRGB">
			<feGaussianBlur stdDeviation="5"/>
		</filter>
	</defs>

	<rect class="evpx-art__fade" width="640" height="400" fill="url(#<?php echo $u; ?>-aura)"/>
	<path d="M0 344H640" stroke="currentColor" stroke-opacity=".16" stroke-width="1"/>

	<?php // The pylon: two legs, cross arms, and the bracing between them. ?>
	<g stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
		<path class="evpx-art__draw" pathLength="1" style="--i:1" d="M96 64L58 344M96 64L134 344" stroke-opacity=".72" stroke-width="1.5"/>
		<path d="M52 100H140M62 140H130M83 160H109M72 240H120M58 344H134" stroke-opacity=".5" stroke-width="1.3"/>
		<path d="M83 160L120 240M109 160L72 240M72 240L134 344M120 240L58 344" stroke-opacity=".28" stroke-width="1.1"/>
		<path d="M52 100V114M140 100V114M62 140V154M130 140V154" stroke-opacity=".5" stroke-width="1.3"/>
	</g>

	<?php // The line in, off the pylon and down to the transformer's two bushings. ?>
	<g stroke="currentColor" stroke-opacity=".55" stroke-width="1.3" stroke-linecap="round">
		<path class="evpx-art__draw" pathLength="1" style="--i:2" d="M140 114C180 120 204 160 224 208"/>
		<path class="evpx-art__draw" pathLength="1" style="--i:2" d="M130 154C168 158 234 170 256 208"/>
		<path d="M52 114C36 122 18 128 0 130M62 154C40 158 18 160 0 162" stroke-opacity=".3"/>
	</g>

	<?php // The transformer. ?>
	<g stroke-linecap="round">
		<rect x="216" y="204" width="16" height="20" rx="3" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2"/>
		<rect x="248" y="204" width="16" height="20" rx="3" stroke="currentColor" stroke-opacity=".6" stroke-width="1.2"/>
		<rect x="200" y="224" width="80" height="120" rx="8" fill="url(#<?php echo $u; ?>-body)"/>
		<rect class="evpx-art__draw" pathLength="1" style="--i:3" x="200" y="224" width="80" height="120" rx="8" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5"/>
		<circle cx="228" cy="284" r="18" stroke="currentColor" stroke-opacity=".38" stroke-width="1.2"/>
		<circle cx="252" cy="284" r="18" stroke="currentColor" stroke-opacity=".38" stroke-width="1.2"/>
		<path d="M212 246H268" stroke="currentColor" stroke-opacity=".2" stroke-width="1.2"/>
	</g>

	<?php // The switchboard: three breakers, one of them made. ?>
	<g stroke-linecap="round">
		<rect x="344" y="194" width="80" height="150" rx="8" fill="url(#<?php echo $u; ?>-body)"/>
		<rect class="evpx-art__draw" pathLength="1" style="--i:4" x="344" y="194" width="80" height="150" rx="8" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5"/>
		<rect x="356" y="210" width="16" height="30" rx="4" stroke="currentColor" stroke-opacity=".4" stroke-width="1.2"/>
		<rect x="376" y="210" width="16" height="30" rx="4" stroke="currentColor" stroke-opacity=".4" stroke-width="1.2"/>
		<rect x="396" y="210" width="16" height="30" rx="4" stroke="currentColor" stroke-opacity=".4" stroke-width="1.2"/>
		<path d="M364 216v8M384 216v8" stroke="currentColor" stroke-opacity=".5" stroke-width="1.4"/>
		<path d="M404 226v8" class="evpx-art__neon" stroke-width="2"/>
		<path d="M356 262H412M356 276H412" stroke="currentColor" stroke-opacity=".16" stroke-width="1.2"/>
	</g>

	<?php // The charger. ?>
	<g stroke-linecap="round">
		<rect x="486" y="176" width="62" height="168" rx="12" fill="url(#<?php echo $u; ?>-body)"/>
		<rect class="evpx-art__draw" pathLength="1" style="--i:5" x="486" y="176" width="62" height="168" rx="12" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5"/>
		<rect x="498" y="192" width="38" height="30" rx="5" stroke="currentColor" stroke-opacity=".38" stroke-width="1.2"/>
		<path class="evpx-art__dot" d="<?php echo esc_attr( $bolt ); ?>" transform="translate(508 197) scale(.075)"/>
		<path d="M498 244h38M498 258h38" stroke="currentColor" stroke-opacity=".16" stroke-width="1.2"/>
	</g>

	<g class="evpx-art__halo" filter="url(#<?php echo $u; ?>-halo)" stroke-linecap="round">
		<path class="evpx-art__glow evpx-art__draw" pathLength="1" style="--i:4" d="<?php echo $hop_a; ?>"/>
		<path class="evpx-art__glow evpx-art__draw" pathLength="1" style="--i:5" d="<?php echo $hop_b; ?>"/>
		<path class="evpx-art__glow" d="M404 226v8"/>
	</g>
	<g class="evpx-art__lit" stroke-linecap="round">
		<path class="evpx-art__neon evpx-art__draw" pathLength="1" style="--i:4" d="<?php echo $hop_a; ?>" stroke-width="1.8"/>
		<path class="evpx-art__neon evpx-art__draw" pathLength="1" style="--i:5" d="<?php echo $hop_b; ?>" stroke-width="1.8"/>
		<path class="evpx-art__pulse" d="<?php echo $hop_a; ?>" stroke-width="1.8" stroke-dasharray="3 17"/>
		<path class="evpx-art__pulse" d="<?php echo $hop_b; ?>" stroke-width="1.8" stroke-dasharray="3 17"/>
		<circle class="evpx-art__dot" cx="280" cy="290" r="3"/>
		<circle class="evpx-art__dot" cx="344" cy="290" r="3"/>
		<circle class="evpx-art__dot" cx="424" cy="290" r="3"/>
		<circle class="evpx-art__dot" cx="486" cy="290" r="3"/>
	</g>

	<g class="evpx-art__notes">
		<text class="evpx-art__label" x="96" y="376" text-anchor="middle"><?php esc_html_e( 'Grid', 'ev-charging-experience' ); ?></text>
		<text class="evpx-art__label" x="240" y="376" text-anchor="middle"><?php esc_html_e( 'Transformer', 'ev-charging-experience' ); ?></text>
		<text class="evpx-art__label" x="384" y="376" text-anchor="middle"><?php esc_html_e( 'Panel', 'ev-charging-experience' ); ?></text>
		<text class="evpx-art__label" x="517" y="376" text-anchor="middle"><?php esc_html_e( 'Charger', 'ev-charging-experience' ); ?></text>
	</g>
</svg>
<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
