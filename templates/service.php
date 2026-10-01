<?php
/**
 * @var string   $uid
 * @var string   $title
 * @var string   $summary
 * @var string[] $points
 * @var string   $link_label
 * @var string   $link_url
 * @var string   $symbol
 * @var string   $media_html
 * @var string   $scene_html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="evpx-service" data-evpx-service>
	<h3 class="evpx-service__head">
		<button type="button" class="evpx-service__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>" data-evpx-service-toggle>
			<span class="evpx-service__index" aria-hidden="true"></span>
			<span class="evpx-service__title"><?php echo esc_html( $title ); ?></span>
			<?php if ( $symbol ) : ?><span class="evpx-service__icon" aria-hidden="true"><?php echo \EVPX\Support\Icons::svg( $symbol ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span><?php endif; ?>
		</button>
	</h3>
	<div class="evpx-service__panel" id="<?php echo esc_attr( $uid ); ?>" role="region" aria-label="<?php echo esc_attr( $title ); ?>">
		<?php if ( $media_html ) : ?>
			<div class="evpx-service__media" aria-hidden="true"><?php echo $media_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?></div>
		<?php elseif ( $scene_html ) : ?>
			<div class="evpx-service__media" aria-hidden="true"><?php echo $scene_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from EVPX\Support\Scene */ ?></div>
		<?php endif; ?>
		<div class="evpx-service__body">
			<?php if ( $summary ) : ?><p class="evpx-service__summary"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
			<?php if ( $points ) : ?>
				<ul class="evpx-service__points" role="list">
					<?php foreach ( $points as $point ) : ?><li><?php echo \EVPX\Support\Icons::svg( 'check', 'evpx-service__tick' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?><?php echo esc_html( $point ); ?></li><?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $link_label && $link_url ) : ?>
				<a class="evpx-button evpx-button--secondary evpx-service__link" href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $link_label ); ?><?php echo \EVPX\Support\Icons::svg( 'arrow-right', 'evpx-button__arrow' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></a>
			<?php endif; ?>
		</div>
	</div>
</article>
