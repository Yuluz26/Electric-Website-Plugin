<?php
/**
 * The grid at dusk: pylons and their catenary wires marching to a low sun, a substation, and a small charger
 * at the foot of the frame that is where all of it ends up.
 *
 * @var string $uid Unique per scene.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

/** One lattice pylon, drawn from its foot. Numbers only. */
$pylon = static function ( float $x, float $y, float $s ) {
	$w = 60 * $s;
	$h = 300 * $s;
	$d = sprintf(
		'M%1$.1f %2$.1fL%3$.1f %4$.1fL%5$.1f %2$.1f M%6$.1f %7$.1fH%8$.1f M%9$.1f %10$.1fH%11$.1f M%12$.1f %13$.1fH%14$.1f M%15$.1f %16$.1fL%17$.1f %18$.1f M%19$.1f %16$.1fL%17$.1f %18$.1f M%20$.1f %13$.1fH%21$.1f M%22$.1f %23$.1fH%24$.1f',
		$x - $w / 2,
		$y,
		$x,
		$y - $h,
		$x + $w / 2,
		$x - $w * 0.33,
		$y - $h * 0.3,
		$x + $w * 0.33,
		$x - $w * 0.22,
		$y - $h * 0.55,
		$x + $w * 0.22,
		$x - $w * 0.13,
		$y - $h * 0.8,
		$x + $w * 0.13,
		$x - $w * 0.33,
		$y - $h * 0.3,
		$x,
		$y - $h * 0.55,
		$x + $w * 0.33,
		$x - $w * 0.7,
		$x + $w * 0.7,
		$x - $w * 0.55,
		$y - $h * 0.86,
		$x + $w * 0.55
	);

	return '<path d="' . $d . '"/>';
};

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-scene evpx-scene--grid" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo esc_attr( $uid ); ?>-sky" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#04060a"/>
			<stop offset=".5" stop-color="#141b30"/>
			<stop offset=".82" stop-color="#4a2e34"/>
			<stop offset="1" stop-color="#c7743b"/>
		</linearGradient>
		<radialGradient id="<?php echo esc_attr( $uid ); ?>-sun" gradientUnits="userSpaceOnUse" cx="1040" cy="640" r="560">
			<stop class="evpx-scene__stop-neon" offset="0" stop-opacity=".85"/>
			<stop class="evpx-scene__stop-neon" offset=".35" stop-opacity=".25"/>
			<stop class="evpx-scene__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="<?php echo esc_attr( $uid ); ?>-ground" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#0b0e14"/>
			<stop offset="1" stop-color="#030406"/>
		</linearGradient>
		<filter id="<?php echo esc_attr( $uid ); ?>-bloom" x="-30%" y="-30%" width="160%" height="160%" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="9"/></filter>
		<?php
		$u = esc_attr( $uid );
		require EVPX_PATH . 'templates/art/_scene-parts.php';
		?>
	</defs>

	<g class="evpx-scene__layer" data-depth="0">
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-sky)"/>
		<g fill="#fff"><?php echo \EVPX\Support\Scene::stars( 21, 80, 1600, 360 ); ?></g>
		<rect x="-120" y="-80" width="1840" height="1060" fill="url(#<?php echo $u; ?>-sun)"/>
		<circle class="evpx-scene__neon" cx="1040" cy="650" r="64" opacity=".95" filter="url(#<?php echo $u; ?>-bloom)"/>
		<circle cx="1040" cy="650" r="46" fill="#ffd9b0" opacity=".95"/>
	</g>

	<g class="evpx-scene__layer" data-depth="0.12">
		<path d="M-60 660C160 610 320 640 520 622S860 590 1060 650 1360 610 1700 640V720H-60z" fill="#0a0d14"/>
		<g stroke="#07090e" stroke-width="2.4" fill="none" stroke-linecap="round">
			<?php
			echo $pylon( 260, 640, 0.6 );
			echo $pylon( 470, 634, 0.7 );
			echo $pylon( 720, 640, 0.8 );
			?>
			<path d="M226 470C300 490 420 490 470 462S620 486 720 452" stroke-width="1.2"/>
			<path d="M240 486C320 506 430 504 470 478S630 502 720 468" stroke-width="1.2"/>
		</g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.34">
		<g stroke="#05070a" stroke-width="4" fill="none" stroke-linecap="round">
			<?php
			echo $pylon( 1180, 690, 1.15 );
			echo $pylon( 1560, 700, 1.5 );
			?>
			<path d="M1180 400C1300 470 1440 470 1560 372S1700 396 1760 380" stroke-width="2"/>
			<path d="M1150 424C1290 494 1430 494 1560 396" stroke-width="2"/>
			<path d="M-60 300C180 402 380 402 620 330S960 404 1180 400" stroke-width="2" opacity=".85"/>
		</g>
		<g class="evpx-scene__neon" opacity=".9"><circle cx="1180" cy="400" r="3.5"/><circle cx="1560" cy="372" r="3.5"/></g>
	</g>

	<g class="evpx-scene__layer" data-depth="0.7">
		<rect x="-120" y="690" width="1840" height="300" fill="url(#<?php echo $u; ?>-ground)"/>
		<path d="M-120 690H1720" stroke="#fff" stroke-opacity=".12" stroke-width="1.5"/>
		<g fill="#0a0d13" stroke="#fff" stroke-opacity=".14">
			<rect x="60" y="612" width="210" height="120" rx="3"/>
			<rect x="300" y="640" width="120" height="92" rx="3"/>
		</g>
		<g stroke="#fff" stroke-opacity=".22" stroke-width="2" fill="none"><path d="M100 612v-30M140 612v-42M180 612v-30"/></g>
		<g class="evpx-scene__neon"><circle cx="140" cy="570" r="3"/></g>
		<use href="#<?php echo $u; ?>-post" x="900" y="600" width="86" height="300"/>
		<use href="#<?php echo $u; ?>-car" x="620" y="770" width="290" height="87"/>
	</g>
</svg>
