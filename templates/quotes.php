<?php
/**
 * @var string $spacing
 * @var string $anchor
 * @var string $eyebrow
 * @var bool   $auto
 * @var bool   $animate
 * @var string $content Rendered Quote children.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-quotes" <?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> data-evpx-theme="dark" data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>" data-evpx-auto="<?php echo $auto ? '1' : '0'; ?>" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>" data-evpx-quotes>
	<div class="evpx-container">
		<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
		<div class="evpx-quotes__stage" data-evpx-quotes-stage>
			<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered Quote children, already escaped individually */ ?>
		</div>
		<div class="evpx-quotes__nav" hidden data-evpx-quotes-nav>
			<button type="button" class="evpx-projects__step evpx-projects__step--prev" data-evpx-quotes-prev><span class="evpx-visually-hidden"><?php esc_html_e( 'Previous quotation', 'ev-charging-experience' ); ?></span><?php echo \EVPX\Support\Icons::svg( 'arrow-right' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></button>
			<span class="evpx-quotes__dots" data-evpx-quotes-dots></span>
			<button type="button" class="evpx-projects__step" data-evpx-quotes-next><span class="evpx-visually-hidden"><?php esc_html_e( 'Next quotation', 'ev-charging-experience' ); ?></span><?php echo \EVPX\Support\Icons::svg( 'arrow-right' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></button>
		</div>
	</div>
</section>
