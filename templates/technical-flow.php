<?php
/**
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
>
	<div class="evpx-container">
		<?php if ( $heading ) : ?>
			<h2 class="evpx-heading evpx-flow__heading" <?php echo $animate ? 'data-evpx-reveal' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo esc_html( $heading ); ?>
			</h2>
		<?php endif; ?>

		<ol class="evpx-flow__steps evpx-surface--recessed">
			<?php foreach ( $steps as $index => $label ) : ?>
				<li class="evpx-flow__step">
					<span class="evpx-flow__node" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
					<span class="evpx-flow__label"><?php echo esc_html( $label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
