<?php
/**
 * A network: sites as nodes on a faint grid, the links between them, and a pulse of light that runs along each.
 * Abstract on purpose: it says "a network of sites", not where.
 *
 * @var string $uid Unique per piece.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u     = esc_attr( $uid );
$nodes = array( array( 120, 250 ), array( 250, 130 ), array( 360, 230 ), array( 470, 110 ), array( 540, 270 ), array( 300, 320 ) );
$links = array( array( 0, 1 ), array( 1, 2 ), array( 2, 3 ), array( 3, 4 ), array( 2, 4 ), array( 0, 5 ), array( 5, 2 ) );

// The coordinates above are this file's own numbers: nothing here is user input.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-art evpx-art--network" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 400" preserveAspectRatio="xMidYMid meet" fill="none" aria-hidden="true" focusable="false">
	<defs>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="320" cy="200" r="300">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".16"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<filter id="<?php echo $u; ?>-halo" filterUnits="userSpaceOnUse" x="0" y="0" width="640" height="400" color-interpolation-filters="sRGB"><feGaussianBlur stdDeviation="5"/></filter>
	</defs>
	<rect class="evpx-art__fade" width="640" height="400" fill="url(#<?php echo $u; ?>-aura)"/>
	<g stroke="currentColor" stroke-opacity=".07"><?php for ( $x = 40; $x < 640; $x += 40 ) { printf( '<path d="M%d 20V380"/>', $x ); } for ( $y = 40; $y < 400; $y += 40 ) { printf( '<path d="M20 %dH620"/>', $y ); } ?></g>
	<?php foreach ( $links as $i => $edge ) : ?>
		<?php
		$a = $nodes[ $edge[0] ];
		$b = $nodes[ $edge[1] ];
		?>
		<path class="evpx-art__draw" pathLength="1" style="--i:<?php echo (int) $i; ?>" d="M<?php echo (int) $a[0]; ?> <?php echo (int) $a[1]; ?>L<?php echo (int) $b[0]; ?> <?php echo (int) $b[1]; ?>" stroke="currentColor" stroke-opacity=".4" stroke-width="1.4"/>
		<path class="evpx-art__pulse" d="M<?php echo (int) $a[0]; ?> <?php echo (int) $a[1]; ?>L<?php echo (int) $b[0]; ?> <?php echo (int) $b[1]; ?>" stroke-width="2.4" stroke-linecap="round" pathLength="100" style="--i:<?php echo (int) $i; ?>"/>
	<?php endforeach; ?>
	<g class="evpx-art__halo" filter="url(#<?php echo $u; ?>-halo)"><?php foreach ( $nodes as $n ) { printf( '<circle class="evpx-art__glow" cx="%d" cy="%d" r="9"/>', $n[0], $n[1] ); } ?></g>
	<?php foreach ( $nodes as $n ) : ?>
		<circle cx="<?php echo (int) $n[0]; ?>" cy="<?php echo (int) $n[1]; ?>" r="11" fill="#0f1216" stroke="currentColor" stroke-opacity=".6" stroke-width="1.4"/>
		<circle class="evpx-art__dot" cx="<?php echo (int) $n[0]; ?>" cy="<?php echo (int) $n[1]; ?>" r="4"/>
	<?php endforeach; ?>
</svg>
