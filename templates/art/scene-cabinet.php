<?php
/**
 * A DC fast charger at dusk, big in the frame, its cable across to a car that is half out of it, with the
 * substation it draws from behind.
 *
 * @var string $uid Unique per scene.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-scene evpx-scene--cabinet" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-sky" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#05070b"/>
			<stop offset=".55" stop-color="#121a28"/>
			<stop offset="1" stop-color="#3b2a26"/>
		</linearGradient>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="1100" cy="520" r="620">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".38"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="<?php echo $u; ?>-body" x1="0" y1="0" x2="1" y2="0">
			<stop offset="0" stop-color="#1a2029"/>
			<stop offset=".5" stop-color="#2b333f"/>
			<stop offset="1" stop-color="#10141a"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-ground" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#141921"/>
			<stop offset="1" stop-color="#040507"/>
		</linearGradient>
		<filter id="<?php echo $u; ?>-bloom" x="-30%" y="-30%" width="160%" height="160%" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="8"/></filter>
		<?php require EVPX_PATH . 'templates/art/_scene-parts.php'; ?>
	</defs>

	<g class="evpx-scene__layer" data-depth="0">
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-sky)"/>
		<g fill="#fff"><?php echo \EVPX\Support\Scene::stars( 13, 70, 1600, 380 ); ?></g>
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-aura)"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.16">
		<?php // A substation behind the fence: transformers, bushings, a gantry. ?>
		<g fill="#0a0e15" stroke="#fff" stroke-opacity=".12">
			<rect x="120" y="470" width="180" height="170" rx="4"/>
			<rect x="340" y="500" width="130" height="140" rx="4"/>
			<rect x="520" y="440" width="220" height="200" rx="4"/>
			<path d="M70 640V400h1560v240" fill="none"/>
		</g>
		<g stroke="#fff" stroke-opacity=".2" stroke-width="2" fill="none">
			<path d="M170 470v-40M200 470v-52M230 470v-40M580 440v-50M620 440v-62M660 440v-50M700 440v-40"/>
			<path d="M60 400L1620 380" stroke-opacity=".12"/>
		</g>
		<g class="evpx-scene__neon" opacity=".8"><circle cx="200" cy="412" r="3"/><circle cx="620" cy="374" r="3"/><circle cx="420" cy="486" r="3"/></g>
		<g stroke="#fff" stroke-opacity=".07" stroke-width="1"><?php for ( $fx = 0; $fx < 1700; $fx += 26 ) { printf( '<path d="M%d 640V560"/>', $fx ); } ?></g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.5">
		<rect x="-120" y="640" width="1840" height="300" fill="url(#<?php echo $u; ?>-ground)"/>
		<path d="M-120 640H1720" stroke="#fff" stroke-opacity=".14" stroke-width="1.5"/>
		<?php // The fast charger: a cabinet with a screen, a light bar, a holster and a cable. ?>
		<g>
			<rect x="1010" y="230" width="250" height="420" rx="34" fill="url(#<?php echo $u; ?>-body)"/>
			<rect x="1010.5" y="230.5" width="249" height="419" rx="33.5" fill="none" stroke="#fff" stroke-opacity=".3"/>
			<rect x="1046" y="280" width="178" height="120" rx="14" fill="#05070a"/>
			<rect x="1046" y="280" width="178" height="120" rx="14" fill="none" stroke="#fff" stroke-opacity=".18"/>
			<g class="evpx-scene__neon"><rect x="1064" y="352" width="96" height="6" rx="3" opacity=".9"/><rect x="1064" y="368" width="60" height="4" rx="2" opacity=".5"/></g>
			<text x="1064" y="334" fill="#ff8a3d" font-family="ui-monospace, Menlo, monospace" font-size="42" font-weight="500">150</text>
			<text x="1164" y="334" fill="#fff" fill-opacity=".6" font-family="ui-monospace, Menlo, monospace" font-size="18">kW</text>
			<g filter="url(#<?php echo $u; ?>-bloom)"><rect class="evpx-scene__neon evpx-scene__pulse" x="1236" y="260" width="8" height="340" rx="4"/></g>
			<rect class="evpx-scene__neon" x="1236" y="260" width="8" height="340" rx="4"/>
			<rect x="1060" y="440" width="70" height="120" rx="14" fill="#07090c" stroke="#fff" stroke-opacity=".2"/>
			<path class="evpx-scene__neon-line" d="M1094 560C1090 640 980 640 900 668S740 690 700 740" fill="none" stroke-width="3" stroke-linecap="round"/>
			<path d="M1094 560C1090 640 980 640 900 668S740 690 700 740" fill="none" stroke="#000" stroke-opacity=".55" stroke-width="12" stroke-linecap="round" transform="translate(0 -3)" opacity=".6"/>
		</g>
		<ellipse class="evpx-scene__neon" cx="1135" cy="650" rx="230" ry="14" opacity=".35" filter="url(#<?php echo $u; ?>-bloom)"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.9">
		<use href="#<?php echo $u; ?>-car" x="140" y="660" width="640" height="192"/>
	</g>
</svg>
