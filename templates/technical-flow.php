<?php
/**
 * @var string $spacing Vertical rhythm: default | compact | none.
 * @var string $anchor HTML id for links to this section, or ''.
 * @var string   $heading
 * @var string[] $steps
 * @var string[] $symbols One icon name per step, or all ''.
 * @var string   $direction
 * @var string   $variant dark | light.
 * @var bool     $compact
 * @var bool     $animate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section
	class="evpx-root alignfull evpx-flow evpx-flow--<?php echo esc_attr( $direction ); ?><?php echo $compact ? ' evpx-flow--compact' : ''; ?>"
	<?php echo 'dark' === $variant ? 'data-evpx-theme="dark"' : ''; ?>
	data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>"
	<?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>"
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
					<span class="evpx-flow__node" aria-hidden="true"><?php echo $symbols[ $index ] ? \EVPX\Support\Icons::svg( $symbols[ $index ] ) : esc_html( sprintf( '%02d', $index + 1 ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span>
					<span class="evpx-flow__text">
						<?php if ( $symbols[ $index ] ) : ?><span class="evpx-flow__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span><?php endif; ?>
						<span class="evpx-flow__label"><?php echo esc_html( $label ); ?></span>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
