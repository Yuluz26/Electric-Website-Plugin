<?php
/**
 * @var string $spacing
 * @var string $anchor
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro
 * @var bool   $animate
 * @var string $content Rendered Project children.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-projects" <?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> data-evpx-theme="dark" data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container evpx-projects__head" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
		<div>
			<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-projects__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $intro ) : ?><p class="evpx-body"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
		</div>
		<div class="evpx-projects__nav" hidden data-evpx-rail-nav>
			<button type="button" class="evpx-projects__step evpx-projects__step--prev" data-evpx-rail-prev><span class="evpx-visually-hidden"><?php esc_html_e( 'Previous project', 'ev-charging-experience' ); ?></span><?php echo \EVPX\Support\Icons::svg( 'arrow-right' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></button>
			<button type="button" class="evpx-projects__step" data-evpx-rail-next><span class="evpx-visually-hidden"><?php esc_html_e( 'Next project', 'ev-charging-experience' ); ?></span><?php echo \EVPX\Support\Icons::svg( 'arrow-right' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></button>
		</div>
	</div>

	<div class="evpx-projects__rail" role="region" aria-label="<?php echo esc_attr( '' !== $heading ? $heading : __( 'Projects', 'ev-charging-experience' ) ); ?>" tabindex="0" data-evpx-rail>
		<ul class="evpx-projects__track" role="list">
			<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered Project children, already escaped individually */ ?>
		</ul>
	</div>
	<div class="evpx-container"><div class="evpx-projects__progress" aria-hidden="true"><span data-evpx-rail-bar></span></div></div>
</section>
