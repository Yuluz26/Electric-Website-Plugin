<?php
/**
 * @var string $scenario
 * @var string $title
 * @var string $description HTML
 * @var string $icon_html Own image, or ''.
 * @var string $symbol Icon name from EVPX\Support\Icons, or ''.
 * @var string $requirement
 * @var string $recommendation
 * @var string $cta_label
 * @var string $cta_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="evpx-scenario-card evpx-surface--raised-sm" data-evpx-reveal data-evpx-spot>
	<?php if ( $icon_html || $symbol || $scenario ) : ?>
		<header class="evpx-scenario-card__head">
			<?php if ( $icon_html ) : ?>
				<span class="evpx-iconchip evpx-scenario-card__icon"><?php echo $icon_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?></span>
			<?php elseif ( $symbol ) : ?>
				<span class="evpx-iconchip evpx-scenario-card__icon"><?php echo \EVPX\Support\Icons::svg( $symbol ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span>
			<?php endif; ?>
			<?php if ( $scenario ) : ?>
				<p class="evpx-eyebrow evpx-scenario-card__label"><?php echo esc_html( $scenario ); ?></p>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<h3 class="evpx-scenario-card__title"><?php echo esc_html( $title ); ?></h3>

	<?php if ( $description ) : ?>
		<div class="evpx-body evpx-scenario-card__description"><?php echo $description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
	<?php endif; ?>

	<?php if ( $requirement || $recommendation ) : ?>
		<dl class="evpx-scenario-card__facts evpx-surface--recessed">
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
			<?php echo \EVPX\Support\Icons::svg( 'arrow-right', 'evpx-button__arrow' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
		</a>
	<?php endif; ?>
</article>
