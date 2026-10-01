<?php
/**
 * @var string $name
 * @var string $place
 * @var string $sector
 * @var string $summary
 * @var array  $metrics    [ [value, label], … ]
 * @var string $url
 * @var string $media_html
 * @var string $scene_html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrap = $url ? 'a' : 'div';
?>
<li class="evpx-project">
	<<?php echo $wrap; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 'a' or 'div' */ ?> class="evpx-project__card"<?php echo $url ? ' href="' . esc_url( $url ) . '"' : ''; ?> data-evpx-spot>
		<span class="evpx-project__visual" aria-hidden="true">
			<?php if ( $media_html ) : ?>
				<?php echo $media_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?>
			<?php elseif ( $scene_html ) : ?>
				<?php echo $scene_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from EVPX\Support\Scene */ ?>
			<?php endif; ?>
			<?php if ( $sector ) : ?><span class="evpx-project__sector"><?php echo esc_html( $sector ); ?></span><?php endif; ?>
		</span>
		<span class="evpx-project__body">
			<?php if ( $place ) : ?><span class="evpx-project__place"><?php echo \EVPX\Support\Icons::svg( 'map-pin' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?><?php echo esc_html( $place ); ?></span><?php endif; ?>
			<span class="evpx-project__name"><?php echo esc_html( $name ); ?></span>
			<?php if ( $summary ) : ?><span class="evpx-project__summary"><?php echo esc_html( $summary ); ?></span><?php endif; ?>
			<?php if ( $metrics ) : ?>
				<span class="evpx-project__metrics">
					<?php foreach ( $metrics as $metric ) : ?>
						<span class="evpx-project__metric"><span class="evpx-project__figure"><?php echo esc_html( $metric[0] ); ?></span><span class="evpx-project__caption"><?php echo esc_html( $metric[1] ); ?></span></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</span>
	</<?php echo $wrap; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 'a' or 'div' */ ?>>
</li>
