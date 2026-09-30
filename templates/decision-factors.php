<?php
/**
 * @var string $spacing Vertical rhythm: default | compact | none.
 * @var string $anchor HTML id for links to this section, or ''.
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro HTML
 * @var bool   $animate
 * @var string $content Rendered DecisionFactor children.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-decision" <?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container evpx-decision__grid">
		<div class="evpx-decision__intro" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
			<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-decision__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $intro ) : ?>
				<div class="evpx-body evpx-decision__lede"><?php echo $intro; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
			<?php endif; ?>
		</div>

		<?php // role="list": list-style:none strips list semantics in Safari/VoiceOver. ?>
		<ol class="evpx-decision__list" role="list">
			<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered DecisionFactor children, already escaped individually */ ?>
		</ol>
	</div>
</section>
