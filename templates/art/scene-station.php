<?php
/**
 * A night forecourt: a skyline, a canopy lit from underneath, three charging posts and a car on wet asphalt.
 * Layers (data-depth) slide against each other as the pointer or the page moves.
 *
 * @var string $uid Unique per scene.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-scene evpx-scene--station" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-sky" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#04060a"/>
			<stop offset=".5" stop-color="#0d1523"/>
			<stop offset=".82" stop-color="#2a2027"/>
			<stop offset="1" stop-color="#5a3421"/>
		</linearGradient>
		<radialGradient id="<?php echo $u; ?>-glow" gradientUnits="userSpaceOnUse" cx="1080" cy="610" r="640">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".42"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="<?php echo $u; ?>-haze" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#5a3421" stop-opacity="0"/>
			<stop offset="1" stop-color="#5a3421" stop-opacity=".55"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-cone" x1="0" y1="0" x2="0" y2="1">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".5"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-ground" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#151a22"/>
			<stop offset=".5" stop-color="#0a0d12"/>
			<stop offset="1" stop-color="#040507"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-refl" x1="0" y1="0" x2="0" y2="1">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".55"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</linearGradient>
		<filter id="<?php echo $u; ?>-bloom" x="-20%" y="-20%" width="140%" height="140%" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="7"/></filter>
		<filter id="<?php echo $u; ?>-soft" x="-20%" y="-20%" width="140%" height="140%" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="22"/></filter>
		<?php require EVPX_PATH . 'templates/art/_scene-parts.php'; ?>
	</defs>

	<g class="evpx-scene__layer" data-depth="0">
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-sky)"/>
		<g fill="#fff"><?php echo \EVPX\Support\Scene::stars( 7, 90, 1600, 420 ); ?></g>
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-glow)"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.12">
		<g transform="translate(0 -120)">
		<?php echo \EVPX\Support\Scene::skyline( 11, -60, 1700, 640, 90, 250, '#0a0f17', 14 ); ?>
		<rect x="-60" y="480" width="1800" height="170" fill="url(#<?php echo $u; ?>-haze)"/>
			</g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.26">
		<g transform="translate(0 -120)">
		<?php echo \EVPX\Support\Scene::skyline( 29, -60, 1700, 664, 40, 130, '#0d121b', 6 ); ?>
			</g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.45">
		<g transform="translate(170 -120)">
		<?php // The canopy: a slab, its columns, and the light it throws down. ?>
		<polygon points="520,214 1720,190 1720,226 520,250" fill="#0f141c"/>
		<polyline points="520,250 1720,226" fill="none" stroke="#fff" stroke-opacity=".28" stroke-width="1.4"/>
		<g fill="#0b0f15"><rect x="690" y="246" width="16" height="416"/><rect x="1130" y="240" width="16" height="422"/><rect x="1570" y="234" width="16" height="428"/></g>
		<g filter="url(#<?php echo $u; ?>-bloom)"><polyline class="evpx-scene__neon-line" points="540,254 1700,230" fill="none" stroke-width="7" stroke-linecap="round"/></g>
		<polyline class="evpx-scene__neon-line" points="540,254 1700,230" fill="none" stroke-width="2.6" stroke-linecap="round"/>
		<polygon points="700,254 1140,246 1240,668 560,668" fill="url(#<?php echo $u; ?>-cone)" opacity=".5"/>
		<polygon points="1140,246 1580,238 1700,668 1240,668" fill="url(#<?php echo $u; ?>-cone)" opacity=".4"/>
		<?php // The posts. ?>
		<use href="#<?php echo $u; ?>-post" x="800" y="460" width="60" height="210"/>
		<use href="#<?php echo $u; ?>-post" x="1000" y="460" width="60" height="210"/>
		<use href="#<?php echo $u; ?>-post" x="1290" y="460" width="60" height="210"/>
			</g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.75">
		<g transform="translate(170 -120)">
		<rect x="-500" y="662" width="2400" height="560" fill="url(#<?php echo $u; ?>-ground)"/>
		<path d="M-500 662H1900" stroke="#fff" stroke-opacity=".16" stroke-width="1.5"/>
		<?php // Wet asphalt: the posts and the canopy line, stretched and softened underneath. ?>
		<g opacity=".5" filter="url(#<?php echo $u; ?>-soft)">
			<rect class="evpx-scene__neon" x="826" y="676" width="10" height="150" rx="5" opacity=".5"/>
			<rect class="evpx-scene__neon" x="1026" y="676" width="10" height="150" rx="5" opacity=".5"/>
			<rect class="evpx-scene__neon" x="1316" y="676" width="10" height="150" rx="5" opacity=".5"/>
			<rect class="evpx-scene__neon" x="640" y="690" width="900" height="8" rx="4" opacity=".35"/>
		</g>
		<g stroke="#fff" stroke-opacity=".1" stroke-width="2"><path d="M760 760l-90 140M1080 760l-30 140M1400 760l60 140"/></g>
		<?php // The car, and the light under it. ?>
		<ellipse class="evpx-scene__neon" cx="960" cy="748" rx="230" ry="12" opacity=".35" filter="url(#<?php echo $u; ?>-bloom)"/>
		<use href="#<?php echo $u; ?>-car" x="760" y="640" width="400" height="120"/>
		<path class="evpx-scene__neon-line" d="M872 724C900 780 980 790 1030 752" fill="none" stroke-width="2.2" stroke-linecap="round" opacity=".9"/>
			</g>
	</g>
</svg>
