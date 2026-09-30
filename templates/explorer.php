<?php
/**
 * @var string $spacing Vertical rhythm: default | compact | none.
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro HTML
 * @var bool   $animate
 * @var string $id Unique per instance; ties each label to its control.
 * @var array<string,mixed> $config The model's numbers and the starting state, for the script.
 * @var array<string,string> $strings The wording the script needs (the verdict's sentences, the units).
 * @var array<int,array<string,mixed>> $chargers One per charger: key, type, rating, energy (as text), ratio, selected.
 * @var array<int,string> $dwells Minutes => label, for the presets.
 * @var int    $dwell Starting dwell, minutes.
 * @var int    $dwell_pos Where that is on the slider, 0 to 100.
 * @var string $dwell_text
 * @var string $energy Energy added, as text.
 * @var string $range Range added, as text.
 * @var string $distance km | mi
 * @var int    $soc_from
 * @var int    $soc_to
 * @var string $verdict
 * @var string $note
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section
	class="evpx-root alignfull evpx-explorer"
	data-evpx-theme="dark"
	data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>"
	data-evpx-explorer="<?php echo esc_attr( wp_json_encode( $config ) ); ?>"
	data-evpx-strings="<?php echo esc_attr( wp_json_encode( $strings ) ); ?>"
>
	<div class="evpx-container">
		<div class="evpx-explorer__intro" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
			<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-explorer__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $intro ) : ?>
				<div class="evpx-body evpx-explorer__lede"><?php echo $intro; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
			<?php endif; ?>
		</div>

		<div class="evpx-explorer__panel" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
			<div class="evpx-explorer__side">
				<?php // Rendered hidden: without a script the controls would do nothing, so evpx.js shows them when it takes over. ?>
				<div class="evpx-explorer__controls" hidden>
					<div class="evpx-explorer__field">
						<div class="evpx-explorer__legend">
							<label for="<?php echo esc_attr( $id ); ?>-dwell"><?php esc_html_e( 'How long the car stays', 'ev-charging-experience' ); ?></label>
							<output class="evpx-explorer__dwell" for="<?php echo esc_attr( $id ); ?>-dwell"><?php echo esc_html( $dwell_text ); ?></output>
						</div>
						<input class="evpx-explorer__range" id="<?php echo esc_attr( $id ); ?>-dwell" type="range" min="0" max="100" step="1" value="<?php echo (int) $dwell_pos; ?>" style="--evpx-pos:<?php echo (int) $dwell_pos; ?>">
						<div class="evpx-explorer__presets">
							<?php foreach ( $dwells as $minutes => $label ) : ?>
								<button type="button" class="evpx-explorer__preset" data-minutes="<?php echo (int) $minutes; ?>"<?php echo (int) $minutes === (int) $dwell ? ' aria-pressed="true"' : ' aria-pressed="false"'; ?>><?php echo esc_html( $label ); ?></button>
							<?php endforeach; ?>
						</div>
					</div>

					<fieldset class="evpx-explorer__field">
						<legend class="evpx-explorer__legend"><span><?php esc_html_e( 'Charger', 'ev-charging-experience' ); ?></span></legend>
						<div class="evpx-explorer__segments">
							<?php foreach ( $chargers as $charger ) : ?>
								<input class="evpx-explorer__radio" type="radio" name="<?php echo esc_attr( $id ); ?>-charger" id="<?php echo esc_attr( $id . '-c-' . $charger['key'] ); ?>" value="<?php echo esc_attr( $charger['key'] ); ?>"<?php echo $charger['selected'] ? ' checked' : ''; ?>>
								<label class="evpx-explorer__segment" for="<?php echo esc_attr( $id . '-c-' . $charger['key'] ); ?>">
									<span class="evpx-explorer__type"><?php echo \EVPX\Support\Icons::svg( 'AC' === $charger['type'] ? 'wave-sine' : 'lightning' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?><?php echo esc_html( $charger['type'] ); ?></span>
									<span class="evpx-explorer__kw"><?php echo (int) $charger['rating']; ?> kW</span>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				</div>

				<?php // The comparison the numbers are for: the same dwell time on every charger. It is also what a page without a script shows. ?>
				<ol class="evpx-explorer__compare" aria-label="<?php esc_attr_e( 'Energy added in this time, by charger', 'ev-charging-experience' ); ?>">
					<?php foreach ( $chargers as $charger ) : ?>
						<li class="evpx-explorer__row<?php echo $charger['selected'] ? ' evpx-explorer__row--selected' : ''; ?>" data-charger="<?php echo esc_attr( $charger['key'] ); ?>">
							<span class="evpx-explorer__name"><?php echo esc_html( $charger['type'] ); ?> <?php echo (int) $charger['rating']; ?> kW</span>
							<span class="evpx-explorer__bar" aria-hidden="true"><span class="evpx-explorer__bar-fill" style="--evpx-fill:<?php echo esc_attr( round( $charger['ratio'], 3 ) ); ?>"></span></span>
							<span class="evpx-explorer__amount"><span class="evpx-explorer__amount-value"><?php echo esc_html( $charger['energy'] ); ?></span> kWh</span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>

			<div class="evpx-explorer__readout">
				<div class="evpx-explorer__figures">
					<p class="evpx-explorer__figure">
						<span class="evpx-explorer__label"><?php esc_html_e( 'Energy', 'ev-charging-experience' ); ?></span>
						<span class="evpx-explorer__number"><span class="evpx-explorer__value" data-out="energy"><?php echo esc_html( $energy ); ?></span><span class="evpx-explorer__unit">kWh</span></span>
					</p>
					<p class="evpx-explorer__figure">
						<span class="evpx-explorer__label"><?php esc_html_e( 'Range', 'ev-charging-experience' ); ?></span>
						<span class="evpx-explorer__number"><span class="evpx-explorer__value" data-out="range"><?php echo esc_html( $range ); ?></span><span class="evpx-explorer__unit" data-out="distance"><?php echo esc_html( $distance ); ?></span></span>
					</p>
				</div>

				<div class="evpx-explorer__soc">
					<span class="evpx-explorer__label"><?php esc_html_e( 'Battery', 'ev-charging-experience' ); ?></span>
					<span class="evpx-explorer__track" aria-hidden="true">
						<span class="evpx-explorer__track-start" style="--evpx-fill:<?php echo esc_attr( $soc_from / 100 ); ?>"></span>
						<span class="evpx-explorer__track-fill" style="--evpx-from:<?php echo esc_attr( $soc_from / 100 ); ?>;--evpx-fill:<?php echo esc_attr( $soc_to / 100 ); ?>"></span>
					</span>
					<span class="evpx-explorer__percent"><span data-out="soc-from"><?php echo (int) $soc_from; ?></span>% → <span data-out="soc-to"><?php echo (int) $soc_to; ?></span>%</span>
				</div>

				<?php // Drawn by the script (power against time on the chosen charger); a page without one has the figures and the comparison. ?>
				<div class="evpx-explorer__chartbox"><svg class="evpx-explorer__chart" viewBox="0 0 640 260" preserveAspectRatio="xMidYMid meet" fill="none" aria-hidden="true" focusable="false" hidden></svg></div>

				<p class="evpx-explorer__verdict" data-out="verdict" aria-live="polite"><?php echo esc_html( $verdict ); ?></p>
			</div>
		</div>

		<p class="evpx-explorer__note"><?php echo esc_html( $note ); ?></p>
	</div>
</section>
