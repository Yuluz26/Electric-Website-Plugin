<?php
/**
 * @var string $eyebrow
 * @var string $heading
 * @var string $body HTML
 * @var string $media_html
 * @var string $layout
 * @var string $surface
 * @var bool   $animate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-section evpx-section--<?php echo esc_attr( $layout ); ?>">
	<div class="evpx-container evpx-section__grid">
		<div
			class="evpx-section__text evpx-surface--<?php echo esc_attr( $surface ); ?>"
			<?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>
		>
			<?php if ( $eyebrow ) : ?>
				<p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<?php if ( $heading ) : ?>
				<h2 class="evpx-heading evpx-section__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>

			<?php if ( $body ) : ?>
				<div class="evpx-body evpx-section__body"><?php echo $body; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
			<?php endif; ?>
		</div>

		<?php if ( $media_html ) : ?>
			<div class="evpx-section__media" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
				<?php echo $media_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?>
			</div>
		<?php endif; ?>
	</div>
</section>
