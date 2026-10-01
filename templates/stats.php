<?php
/**
 * @var string $spacing
 * @var string $anchor
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro
 * @var string $variant dark | light
 * @var bool   $animate
 * @var string $content Rendered Stat children.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-stats" <?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> <?php echo 'dark' === $variant ? 'data-evpx-theme="dark"' : ''; ?> data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container">
		<?php if ( $eyebrow || $heading || $intro ) : ?>
			<div class="evpx-stats__head" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
				<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-stats__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
				<?php if ( $intro ) : ?><p class="evpx-body evpx-stats__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>

		<ul class="evpx-stats__grid" role="list">
			<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered Stat children, already escaped individually */ ?>
		</ul>
	</div>
</section>
