<?php
/**
 * @var string $eyebrow
 * @var string $heading
 * @var string $content Rendered FaqItem children.
 * @var string $schema  JSON-LD <script> tag or empty string.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-faq">
	<div class="evpx-container evpx-faq__container">
		<div class="evpx-faq__intro" data-evpx-reveal>
			<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-faq__heading-main"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		</div>

		<div class="evpx-faq__list">
			<?php echo $content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered FaqItem children, already escaped individually */ ?>
		</div>
	</div>

	<?php echo $schema; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() output inside a fixed <script type="application/ld+json"> wrapper built in Faq::buildFaqSchema() */ ?>
</section>
