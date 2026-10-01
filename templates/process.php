<?php
/**
 * @var string $spacing
 * @var string $anchor
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro
 * @var bool   $animate
 * @var string $content Rendered ProcessStep children.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-process" <?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>" data-evpx-process>
	<div class="evpx-container evpx-process__grid">
		<div class="evpx-process__intro" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
			<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-process__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $intro ) : ?><p class="evpx-body"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
			<p class="evpx-process__counter" aria-hidden="true"><span class="evpx-process__now">01</span><span class="evpx-process__of"></span></p>
		</div>
		<ol class="evpx-process__steps" role="list">
			<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered ProcessStep children, already escaped individually */ ?>
		</ol>
	</div>
</section>
