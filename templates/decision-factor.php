<?php
/**
 * @var string $title
 * @var string $description HTML
 * @var string $question
 * @var string $symbol Icon name from EVPX\Support\Icons, or ''.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="evpx-decision__item" role="listitem">
	<h3 class="evpx-decision__title">
		<?php if ( $symbol ) : ?><?php echo \EVPX\Support\Icons::svg( $symbol, 'evpx-decision__icon' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?><?php endif; ?>
		<?php echo esc_html( $title ); ?>
	</h3>

	<?php if ( $description ) : ?>
		<div class="evpx-body evpx-decision__description"><?php echo $description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
	<?php endif; ?>

	<?php if ( $question ) : ?>
		<p class="evpx-decision__question"><span class="evpx-decision__ask"><?php esc_html_e( 'Ask', 'ev-charging-experience' ); ?></span> <?php echo esc_html( $question ); ?></p>
	<?php endif; ?>
</li>
