<?php
/**
 * @var string $label
 * @var string $note
 * @var string $value
 * @var float  $fill   0..100
 * @var string $symbol Icon name, or ''.
 * @var string $prefix
 * @var string $number
 * @var string $suffix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A ring of radius 46 in a 100 box; the arc is drawn with pathLength 100, so the fill is a plain percentage.
?>
<li class="evpx-stat" data-evpx-reveal>
	<?php if ( $fill > 0 ) : ?>
		<svg class="evpx-stat__ring" viewBox="0 0 100 100" aria-hidden="true" focusable="false" style="--evpx-fill:<?php echo esc_attr( (string) $fill ); ?>">
			<circle class="evpx-stat__track" cx="50" cy="50" r="46" pathLength="100"/>
			<circle class="evpx-stat__arc" cx="50" cy="50" r="46" pathLength="100" transform="rotate(-90 50 50)"/>
		</svg>
	<?php elseif ( $symbol ) : ?>
		<span class="evpx-iconchip evpx-stat__icon"><?php echo \EVPX\Support\Icons::svg( $symbol ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span>
	<?php endif; ?>
	<p class="evpx-stat__value"<?php echo '' !== $number ? ' data-count="' . esc_attr( $number ) . '"' : ''; ?>><?php echo '' !== trim( $prefix ) ? '<span class="evpx-stat__unit">' . esc_html( trim( $prefix ) ) . '</span>' : ''; ?><span class="evpx-stat__num"><?php echo esc_html( '' !== $number ? $number : $value ); ?></span><?php echo '' !== trim( $suffix ) ? '<span class="evpx-stat__unit">' . esc_html( trim( $suffix ) ) . '</span>' : ''; ?></p>
	<?php if ( $label ) : ?><p class="evpx-stat__label"><?php echo esc_html( $label ); ?></p><?php endif; ?>
	<?php if ( $note ) : ?><p class="evpx-stat__note"><?php echo esc_html( $note ); ?></p><?php endif; ?>
</li>
