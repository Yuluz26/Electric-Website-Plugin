<?php
/**
 * @var string $eyebrow
 * @var string $title
 * @var string $body HTML
 * @var string $button_label
 * @var string $button_url
 * @var string $media_html
 * @var string $variant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-cta evpx-cta--<?php echo esc_attr( $variant ); ?>">
	<?php if ( $media_html ) : ?>
		<div class="evpx-cta__media"><?php echo $media_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?></div>
		<div class="evpx-cta__scrim" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="evpx-container evpx-cta__content" data-evpx-reveal>
		<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>

		<h2 class="evpx-heading evpx-cta__title"><?php echo esc_html( $title ); ?></h2>

		<?php if ( $body ) : ?>
			<div class="evpx-body evpx-cta__body"><?php echo $body; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
		<?php endif; ?>

		<?php if ( $button_label && $button_url ) : ?>
			<a class="evpx-button evpx-button--<?php echo 'accent' === $variant ? 'secondary' : 'primary'; ?> evpx-cta__button" href="<?php echo esc_url( $button_url ); ?>">
				<?php echo esc_html( $button_label ); ?>
				<span class="evpx-button__arrow" aria-hidden="true">&rarr;</span>
			</a>
		<?php endif; ?>
	</div>
</section>
