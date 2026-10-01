<?php
/**
 * @var string $quote
 * @var string $name
 * @var string $role
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<figure class="evpx-quote" data-evpx-quote>
	<blockquote class="evpx-quote__text"><p><?php echo esc_html( $quote ); ?></p></blockquote>
	<?php if ( $name || $role ) : ?>
		<figcaption class="evpx-quote__by">
			<?php if ( $name ) : ?><span class="evpx-quote__name"><?php echo esc_html( $name ); ?></span><?php endif; ?>
			<?php if ( $role ) : ?><span class="evpx-quote__role"><?php echo esc_html( $role ); ?></span><?php endif; ?>
		</figcaption>
	<?php endif; ?>
</figure>
