<?php
/**
 * @var string $scenario
 * @var string $title
 * @var string $description HTML
 * @var string $icon_html
 * @var string $requirement
 * @var string $recommendation
 * @var string $cta_label
 * @var string $cta_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="evpx-scenario-card evpx-surface--raised-sm" data-evpx-reveal>
	<?php if ( $icon_html ) : ?>
		<div class="evpx-scenario-card__icon"><?php echo $icon_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?></div>
	<?php endif; ?>

	<?php if ( $scenario ) : ?>
		<p class="evpx-eyebrow evpx-scenario-card__label"><?php echo esc_html( $scenario ); ?></p>
	<?php endif; ?>

	<h3 class="evpx-scenario-card__title"><?php echo esc_html( $title ); ?></h3>

	<?php if ( $description ) : ?>
		<div class="evpx-body evpx-scenario-card__description"><?php echo $description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
	<?php endif; ?>

	<?php if ( $requirement || $recommendation ) : ?>
		<dl class="evpx-scenario-card__facts">
			<?php if ( $requirement ) : ?>
				<div><dt><?php esc_html_e( 'Key requirement', 'ev-charging-experience' ); ?></dt><dd><?php echo esc_html( $requirement ); ?></dd></div>
			<?php endif; ?>
			<?php if ( $recommendation ) : ?>
				<div><dt><?php esc_html_e( 'Recommended approach', 'ev-charging-experience' ); ?></dt><dd><?php echo esc_html( $recommendation ); ?></dd></div>
			<?php endif; ?>
		</dl>
	<?php endif; ?>

	<?php if ( $cta_label && $cta_url ) : ?>
		<a class="evpx-scenario-card__cta" href="<?php echo esc_url( $cta_url ); ?>">
			<?php echo esc_html( $cta_label ); ?>
			<span class="evpx-button__arrow" aria-hidden="true">&rarr;</span>
		</a>
	<?php endif; ?>
</article>
