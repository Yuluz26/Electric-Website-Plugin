<?php
/**
 * A road at dusk, seen down its length: the light trails of the traffic converge on the horizon, and a row of
 * chargers stands along the verge, each smaller than the last.
 *
 * @var string $uid Unique per scene.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-scene evpx-scene--highway" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-sky" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#05070c"/>
			<stop offset=".45" stop-color="#16203a"/>
			<stop offset=".78" stop-color="#5b3a3a"/>
			<stop offset="1" stop-color="#b8683a"/>
		</linearGradient>
		<radialGradient id="<?php echo $u; ?>-sun" gradientUnits="userSpaceOnUse" cx="900" cy="500" r="520">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".7"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="<?php echo $u; ?>-road" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#1b1f27"/>
			<stop offset="1" stop-color="#050608"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-trail-w" x1="0" y1="1" x2="0" y2="0">
			<stop offset="0" stop-color="#fff" stop-opacity=".9"/>
			<stop offset="1" stop-color="#fff" stop-opacity="0"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-trail-n" x1="0" y1="1" x2="0" y2="0">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".95"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</linearGradient>
		<filter id="<?php echo $u; ?>-bloom" x="-20%" y="-20%" width="140%" height="140%" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="6"/></filter>
		<?php require EVPX_PATH . 'templates/art/_scene-parts.php'; ?>
	</defs>

	<g class="evpx-scene__layer" data-depth="0">
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-sky)"/>
		<g fill="#fff"><?php echo \EVPX\Support\Scene::stars( 3, 60, 1600, 300 ); ?></g>
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-sun)"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.14">
		<path d="M-60 520C120 470 260 500 420 486S700 440 900 500 1240 470 1400 490 1620 470 1700 500V540H-60z" fill="#0b0f18"/>
		<g fill="#080b12">
			<?php echo \EVPX\Support\Scene::skyline( 5, 1180, 1500, 530, 30, 80, '#080b12', 8 ); ?>
		</g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.3">
		<?php // The far pylons on the left, and their wires. ?>
		<g stroke="#0a0d13" stroke-width="3" fill="none">
			<path d="M180 528L214 340L248 528M196 400H232M190 440H238M200 360H228"/>
			<path d="M420 522L446 380L472 522M432 430H460"/>
			<path d="M-20 350C90 372 160 372 214 344S330 372 446 384 560 400 640 396" stroke-width="1.4" opacity=".7"/>
		</g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.55">
		<?php // The road: a wedge to the vanishing point, lane dashes, and the verge. ?>
		<polygon points="-400,900 2000,900 940,520 860,520" fill="url(#<?php echo $u; ?>-road)"/>
		<polygon points="-400,900 -60,900 860,520 850,520" fill="#0a0c10"/>
		<polygon points="2000,900 1660,900 940,520 950,520" fill="#0a0c10"/>
		<g fill="#fff" fill-opacity=".5">
			<polygon points="895,528 905,528 909,548 891,548"/>
			<polygon points="891,566 909,566 916,600 884,600"/>
			<polygon points="884,632 916,632 928,690 872,690"/>
			<polygon points="872,740 928,740 952,850 848,850"/>
		</g>
		<path d="M860 520L-60 900M940 520L1660 900" stroke="#fff" stroke-opacity=".22" stroke-width="2"/>
		<?php // Light trails: white going away, copper coming. ?>
		<g filter="url(#<?php echo $u; ?>-bloom)" opacity=".9">
			<path d="M880 526L260 900" stroke="url(#<?php echo $u; ?>-trail-w)" stroke-width="9" stroke-linecap="round" fill="none"/>
			<path d="M892 526L560 900" stroke="url(#<?php echo $u; ?>-trail-w)" stroke-width="6" stroke-linecap="round" fill="none"/>
			<path d="M918 526L1250 900" stroke="url(#<?php echo $u; ?>-trail-n)" stroke-width="7" stroke-linecap="round" fill="none"/>
			<path d="M930 526L1500 900" stroke="url(#<?php echo $u; ?>-trail-n)" stroke-width="10" stroke-linecap="round" fill="none"/>
		</g>
		<path d="M880 526L260 900M892 526L560 900" stroke="url(#<?php echo $u; ?>-trail-w)" stroke-width="2.4" fill="none"/>
		<path d="M918 526L1250 900M930 526L1500 900" stroke="url(#<?php echo $u; ?>-trail-n)" stroke-width="2.4" fill="none"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.85">
		<?php // Chargers along the right verge, each smaller than the last. ?>
		<use href="#<?php echo $u; ?>-post" x="1500" y="480" width="112" height="392"/>
		<use href="#<?php echo $u; ?>-post" x="1300" y="470" width="70" height="245"/>
		<use href="#<?php echo $u; ?>-post" x="1170" y="474" width="46" height="161"/>
		<use href="#<?php echo $u; ?>-post" x="1078" y="482" width="30" height="105"/>
		<use href="#<?php echo $u; ?>-post" x="1024" y="490" width="20" height="70"/>
	</g>
</svg>
