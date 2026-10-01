<?php
/**
 * @var string $spacing
 * @var string $brand
 * @var string $blurb
 * @var array  $links   [ [label, url], … ]
 * @var array  $contact [ [text, href], … ]
 * @var string $legal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="evpx-root alignfull evpx-footer" data-evpx-theme="dark" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container evpx-footer__grid">
		<div class="evpx-footer__about">
			<p class="evpx-footer__brand"><span class="evpx-header__mark" aria-hidden="true"><?php echo \EVPX\Support\Icons::svg( 'lightning' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span><?php echo esc_html( $brand ); ?></p>
			<?php if ( $blurb ) : ?><p class="evpx-footer__blurb"><?php echo esc_html( $blurb ); ?></p><?php endif; ?>
		</div>

		<?php if ( $links ) : ?>
			<nav class="evpx-footer__col" aria-label="<?php echo esc_attr_x( 'Footer', 'navigation label', 'ev-charging-experience' ); ?>">
				<p class="evpx-footer__head"><?php esc_html_e( 'Explore', 'ev-charging-experience' ); ?></p>
				<ul role="list">
					<?php foreach ( $links as $link ) : ?><li><a class="evpx-footer__link" href="<?php echo esc_url( $link[1] ); ?>"><?php echo esc_html( $link[0] ); ?></a></li><?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<?php if ( $contact ) : ?>
			<div class="evpx-footer__col">
				<p class="evpx-footer__head"><?php esc_html_e( 'Contact', 'ev-charging-experience' ); ?></p>
				<ul role="list">
					<?php foreach ( $contact as $line ) : ?>
						<li>
							<?php if ( $line[1] ) : ?>
								<a class="evpx-footer__link" href="<?php echo esc_url( $line[1], array( 'mailto', 'tel' ) ); ?>"><?php echo esc_html( $line[0] ); ?></a>
							<?php else : ?>
								<span class="evpx-footer__text"><?php echo esc_html( $line[0] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>

	<div class="evpx-container evpx-footer__base">
		<p class="evpx-footer__legal"><?php echo esc_html( $legal ); ?></p>
		<a class="evpx-footer__top" href="#"><?php esc_html_e( 'Back to top', 'ev-charging-experience' ); ?><?php echo \EVPX\Support\Icons::svg( 'arrow-up-right', 'evpx-footer__top-icon' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></a>
	</div>
</footer>
