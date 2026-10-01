<?php
/**
 * The plug in close-up: a round port, its pins ringed in light, on a dark metal ground. No place, just the moment
 * of connection, for the page that ends in "get in touch".
 *
 * @var string $uid Unique per scene.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-scene evpx-scene--plug" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
	<defs>
		<radialGradient id="<?php echo $u; ?>-ground" gradientUnits="userSpaceOnUse" cx="1080" cy="450" r="900">
			<stop offset="0" stop-color="#1c232d"/>
			<stop offset=".6" stop-color="#0a0d12"/>
			<stop offset="1" stop-color="#030406"/>
		</radialGradient>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="1080" cy="450" r="520">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".5"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="<?php echo $u; ?>-metal" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0" stop-color="#4b5563"/>
			<stop offset=".4" stop-color="#1b212a"/>
			<stop offset="1" stop-color="#0a0d12"/>
		</linearGradient>
		<filter id="<?php echo $u; ?>-bloom" x="-30%" y="-30%" width="160%" height="160%" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="10"/></filter>
	</defs>

	<g class="evpx-scene__layer" data-depth="0">
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-ground)"/>
		<g stroke="#fff" stroke-opacity=".05" stroke-width="1"><?php for ( $gx = -100; $gx < 1720; $gx += 60 ) { printf( '<path d="M%d -80V980"/>', $gx ); } for ( $gy = -60; $gy < 980; $gy += 60 ) { printf( '<path d="M-120 %dH1720"/>', $gy ); } ?></g>
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-aura)"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.3">
		<circle cx="1080" cy="450" r="330" fill="none" stroke="#fff" stroke-opacity=".08" stroke-width="1.5"/>
		<circle cx="1080" cy="450" r="410" fill="none" stroke="#fff" stroke-opacity=".05" stroke-width="1.5" stroke-dasharray="2 10"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.7">
		<g filter="url(#<?php echo $u; ?>-bloom)"><circle class="evpx-scene__neon-line" cx="1080" cy="450" r="272" stroke-width="8" opacity=".8"/></g>
		<circle cx="1080" cy="450" r="286" fill="url(#<?php echo $u; ?>-metal)"/>
		<circle cx="1080" cy="450" r="286" fill="none" stroke="#fff" stroke-opacity=".32" stroke-width="1.5"/>
		<circle cx="1080" cy="450" r="248" fill="#06080b"/>
		<circle class="evpx-scene__neon-line" cx="1080" cy="450" r="248" fill="none" stroke-width="2.2" opacity=".85"/>
		<circle cx="1080" cy="450" r="214" fill="#0c1016" stroke="#fff" stroke-opacity=".12"/>
		<?php // Five contacts: two small at the top, two power pins, one earth. ?>
		<g>
			<circle cx="1020" cy="358" r="30" fill="#05070a" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>
			<circle cx="1140" cy="358" r="30" fill="#05070a" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>
			<circle cx="1000" cy="470" r="50" fill="#05070a" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>
			<circle cx="1160" cy="470" r="50" fill="#05070a" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>
			<circle cx="1080" cy="380" r="22" fill="#05070a" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>
			<g class="evpx-scene__neon" opacity=".95"><circle cx="1020" cy="358" r="9"/><circle cx="1140" cy="358" r="9"/><circle cx="1000" cy="470" r="17"/><circle cx="1160" cy="470" r="17"/><circle cx="1080" cy="380" r="7"/></g>
			<path d="M1050 520h60l-10 40h-40z" fill="#05070a" stroke="#fff" stroke-opacity=".3" stroke-width="2"/>
		</g>
		<path d="M1080 164v-24M1080 736v24M794 450h-24M1366 450h24" stroke="#fff" stroke-opacity=".28" stroke-width="2" stroke-linecap="round"/>
	</g>
</svg>
