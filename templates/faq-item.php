<?php
/**
 * @var string $question
 * @var string $answer_html
 * @var string $answer_plain Used only for the JSON-LD data attribute.
 * @var bool   $default_open
 * @var string $id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$question_id = $id . '-q';
$panel_id    = $id . '-a';
?>
<div class="evpx-faq__item<?php echo $default_open ? ' evpx-faq__item--open' : ''; ?>"
	data-evpx-question="<?php echo esc_attr( $question ); ?>"
	data-evpx-answer="<?php echo esc_attr( $answer_plain ); ?>"
>
	<h3 class="evpx-faq__heading">
		<button
			type="button"
			class="evpx-faq__question"
			id="<?php echo esc_attr( $question_id ); ?>"
			aria-expanded="<?php echo $default_open ? 'true' : 'false'; ?>"
			aria-controls="<?php echo esc_attr( $panel_id ); ?>"
		>
			<span><?php echo esc_html( $question ); ?></span>
			<?php // The knob holds a plus and a minus, one over the other; the stylesheet shows the one that fits the state. ?>
			<span class="evpx-faq__icon" aria-hidden="true"><?php echo \EVPX\Support\Icons::svg( 'plus' ) . \EVPX\Support\Icons::svg( 'minus' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span>
		</button>
	</h3>
	<div
		class="evpx-faq__answer evpx-body"
		id="<?php echo esc_attr( $panel_id ); ?>"
		role="region"
		aria-labelledby="<?php echo esc_attr( $question_id ); ?>"
		<?php echo $default_open ? '' : 'hidden'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	>
		<?php echo $answer_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?>
	</div>
</div>
