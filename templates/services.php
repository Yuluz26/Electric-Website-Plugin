<?php
/**
 * @var string $spacing
 * @var string $anchor
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro
 * @var bool   $animate
 * @var string $content Rendered Service children.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-services" <?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> data-evpx-theme="dark" data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container">
		<?php if ( $eyebrow || $heading || $intro ) : ?>
			<div class="evpx-services__head" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
				<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-services__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
				<?php if ( $intro ) : ?><p class="evpx-body evpx-services__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="evpx-services__strip" data-evpx-services>
		<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered Service children, already escaped individually */ ?>
	</div>
</section>
