<?php
/**
 * @var string $spacing Vertical rhythm: default | compact | none.
 * @var string $eyebrow
 * @var string $heading
 * @var string $columns
 * @var bool   $animate
 * @var string $content Rendered ScenarioCard children.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-scenarios" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container">
		<?php if ( $eyebrow || $heading ) : ?>
			<div class="evpx-scenarios__intro" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
				<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-scenarios__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="evpx-scenarios__grid evpx-scenarios__grid--cols-<?php echo esc_attr( $columns ); ?>">
			<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered child elements, already escaped individually */ ?>
		</div>
	</div>
</section>
