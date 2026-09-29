<?php
/**
 * @var string $spacing Vertical rhythm: default | compact | none.
 * @var string   $heading
 * @var string[] $steps
 * @var string   $direction
 * @var bool     $compact
 * @var bool     $animate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section
	class="evpx-root alignfull evpx-flow evpx-flow--<?php echo esc_attr( $direction ); ?><?php echo $compact ? ' evpx-flow--compact' : ''; ?>"
	data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>"
	data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>"
>
	<div class="evpx-container">
		<?php if ( $heading ) : ?>
			<h2 class="evpx-heading evpx-flow__heading" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
				<?php echo esc_html( $heading ); ?>
			</h2>
		<?php endif; ?>

		<ol class="evpx-flow__steps">
			<?php foreach ( $steps as $index => $label ) : ?>
				<li class="evpx-flow__step" style="--evpx-step:<?php echo (int) $index; ?>">
					<span class="evpx-flow__node" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
					<span class="evpx-flow__label"><?php echo esc_html( $label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
