<?php
/**
 * @var string $spacing Vertical rhythm: default | compact | none.
 * @var string $eyebrow
 * @var string $heading
 * @var string $figure Optional key figure, set large ("7–22 kW").
 * @var string $figure_label Caption under the figure.
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
<section class="evpx-root alignfull evpx-section evpx-section--<?php echo esc_attr( $layout ); ?><?php echo $media_html ? '' : ' evpx-section--no-media'; ?>" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container evpx-section__grid">
		<div
			class="evpx-section__text evpx-surface--<?php echo esc_attr( $surface ); ?>"
			<?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>
		>
			<div class="evpx-section__head">
				<?php if ( $eyebrow ) : ?>
					<p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( $heading ) : ?>
					<h2 class="evpx-heading evpx-section__heading"><?php echo esc_html( $heading ); ?></h2>
				<?php endif; ?>

				<?php if ( $figure ) : ?>
					<p class="evpx-section__figure">
						<span class="evpx-section__figure-value"><?php echo esc_html( $figure ); ?></span>
						<?php if ( $figure_label ) : ?><span class="evpx-section__figure-label"><?php echo esc_html( $figure_label ); ?></span><?php endif; ?>
					</p>
				<?php endif; ?>
			</div>

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
