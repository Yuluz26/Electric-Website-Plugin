<?php
/**
 * @var array{title:string,description:string,power_range:string,dwell_label:string,best_for:string} $ac
 * @var array{title:string,description:string,power_range:string,dwell_label:string,best_for:string} $dc
 * @var string $mode
 * @var string $accent_treatment
 * @var string $animation_intensity
 * @var string $mobile_mode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$panels = array(
	'ac' => $ac,
	'dc' => $dc,
);
?>
<section
	class="evpx-root alignfull evpx-comparison evpx-comparison--<?php echo esc_attr( $accent_treatment ); ?> evpx-comparison--mobile-<?php echo esc_attr( $mobile_mode ); ?>"
	data-mode="<?php echo esc_attr( $mode ); ?>"
	data-default="ac"
	data-evpx-animate="<?php echo 'off' !== $animation_intensity ? '1' : '0'; ?>"
	data-evpx-intensity="<?php echo esc_attr( $animation_intensity ); ?>"
>
	<div class="evpx-container">
		<?php if ( 'toggle' === $mode ) : ?>
			<div class="evpx-comparison__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Charging type', 'ev-charging-experience' ); ?>">
				<?php foreach ( $panels as $key => $panel ) : ?>
					<button
						type="button"
						class="evpx-comparison__tab evpx-comparison__tab--<?php echo esc_attr( $key ); ?><?php echo 'ac' === $key ? ' evpx-comparison__tab--active' : ''; ?>"
						role="tab"
						data-target="<?php echo esc_attr( $key ); ?>"
						aria-selected="<?php echo 'ac' === $key ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $panel['title'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="evpx-comparison__panels">
			<?php foreach ( $panels as $key => $panel ) : ?>
				<article
					class="evpx-comparison__panel evpx-comparison__panel--<?php echo esc_attr( $key ); ?><?php echo 'ac' === $key ? ' evpx-comparison__panel--active' : ''; ?>"
					role="tabpanel"
				>
					<h3 class="evpx-heading evpx-comparison__title"><?php echo esc_html( $panel['title'] ); ?></h3>

					<?php if ( $panel['description'] ) : ?>
						<div class="evpx-body evpx-comparison__description"><?php echo $panel['description']; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
					<?php endif; ?>

					<dl class="evpx-comparison__data evpx-surface--recessed">
						<div class="evpx-comparison__data-row evpx-comparison__data-row--power">
							<dt><?php esc_html_e( 'Power range', 'ev-charging-experience' ); ?></dt>
							<dd><?php echo esc_html( $panel['power_range'] ); ?></dd>
						</div>
						<div class="evpx-comparison__data-row">
							<dt><?php esc_html_e( 'Dwell time', 'ev-charging-experience' ); ?></dt>
							<dd><?php echo esc_html( $panel['dwell_label'] ); ?></dd>
						</div>
						<div class="evpx-comparison__data-row">
							<dt><?php esc_html_e( 'Best for', 'ev-charging-experience' ); ?></dt>
							<dd><?php echo esc_html( $panel['best_for'] ); ?></dd>
						</div>
					</dl>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
