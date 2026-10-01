<?php
/**
 * @var string $title
 * @var string $description
 * @var string $duration
 * @var string $symbol
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="evpx-step" data-evpx-step>
	<span class="evpx-step__node" aria-hidden="true"><?php echo \EVPX\Support\Icons::svg( $symbol ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span>
	<div class="evpx-step__body">
		<?php if ( $duration ) : ?><p class="evpx-step__duration"><?php echo esc_html( $duration ); ?></p><?php endif; ?>
		<h3 class="evpx-step__title"><?php echo esc_html( $title ); ?></h3>
		<?php if ( $description ) : ?><p class="evpx-step__text"><?php echo esc_html( $description ); ?></p><?php endif; ?>
	</div>
</li>
