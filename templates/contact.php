<?php
/**
 * @var string   $spacing
 * @var string   $anchor
 * @var string   $eyebrow
 * @var string   $heading
 * @var string   $intro
 * @var array    $details      [ [label, value, href], … ]
 * @var string[] $topics
 * @var string   $button_label
 * @var string   $map_html
 * @var bool     $animate
 * @var string   $action       admin-post.php
 * @var string   $return       This page's address.
 * @var string   $token        Signed timestamp and recipient (Support\ContactForm).
 * @var string   $sent         '' | 1 | invalid | expired | limit | failed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$messages = array(
	'1'       => __( 'Thank you. Your message is on its way, and we will reply within a working day.', 'ev-charging-experience' ),
	'invalid' => __( 'Something is missing: a name, a valid email address and a few words about the site.', 'ev-charging-experience' ),
	'expired' => __( 'That form was open for too long, or was sent too quickly. Please try again.', 'ev-charging-experience' ),
	'limit'   => __( 'That is a lot of messages from one place. Please try again in an hour.', 'ev-charging-experience' ),
	'failed'  => __( 'The message could not be sent from here. Please email us instead.', 'ev-charging-experience' ),
);
?>
<section class="evpx-root alignfull evpx-contact" id="<?php echo esc_attr( '' !== $anchor ? $anchor : 'evpx-contact' ); ?>" data-evpx-theme="dark" data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container evpx-contact__grid">
		<div class="evpx-contact__info" <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
			<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-contact__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $intro ) : ?><p class="evpx-body"><?php echo esc_html( $intro ); ?></p><?php endif; ?>

			<?php if ( $details ) : ?>
				<dl class="evpx-contact__details">
					<?php foreach ( $details as $detail ) : ?>
						<div class="evpx-contact__row">
							<dt><?php echo esc_html( $detail[0] ); ?></dt>
							<dd><?php if ( $detail[2] ) : ?><a href="<?php echo esc_url( $detail[2], array( 'mailto', 'tel' ) ); ?>"><?php echo esc_html( $detail[1] ); ?></a><?php else : ?><?php echo esc_html( $detail[1] ); ?><?php endif; ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>

			<?php if ( $map_html ) : ?><div class="evpx-artpanel evpx-contact__map" data-evpx-spot><?php echo $map_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from EVPX\Support\Art */ ?></div><?php endif; ?>
		</div>

		<form class="evpx-contact__form evpx-surface--raised-sm" method="post" action="<?php echo esc_url( $action ); ?>" data-evpx-contact <?php echo esc_attr( $animate ? 'data-evpx-reveal' : '' ); ?>>
			<?php if ( isset( $messages[ $sent ] ) ) : ?>
				<p class="evpx-contact__notice evpx-contact__notice--<?php echo '1' === $sent ? 'ok' : 'bad'; ?>" role="status"><?php echo esc_html( $messages[ $sent ] ); ?></p>
			<?php endif; ?>

			<input type="hidden" name="action" value="evpx_contact">
			<input type="hidden" name="evpx_return" value="<?php echo esc_url( $return ); ?>">
			<input type="hidden" name="evpx_token" value="<?php echo esc_attr( $token ); ?>">
			<p class="evpx-contact__trap" aria-hidden="true"><label>Website <input type="text" name="evpx_website" tabindex="-1" autocomplete="off"></label></p>

			<div class="evpx-contact__pair">
				<p class="evpx-contact__field"><label for="evpx-c-name"><?php esc_html_e( 'Your name', 'ev-charging-experience' ); ?></label><input id="evpx-c-name" type="text" name="evpx_name" required maxlength="100" autocomplete="name"></p>
				<p class="evpx-contact__field"><label for="evpx-c-email"><?php esc_html_e( 'Email', 'ev-charging-experience' ); ?></label><input id="evpx-c-email" type="email" name="evpx_email" required maxlength="120" autocomplete="email"></p>
			</div>
			<div class="evpx-contact__pair">
				<p class="evpx-contact__field"><label for="evpx-c-phone"><?php esc_html_e( 'Phone (optional)', 'ev-charging-experience' ); ?></label><input id="evpx-c-phone" type="tel" name="evpx_phone" maxlength="40" autocomplete="tel"></p>
				<?php if ( $topics ) : ?>
					<p class="evpx-contact__field"><label for="evpx-c-topic"><?php esc_html_e( 'About', 'ev-charging-experience' ); ?></label>
						<select id="evpx-c-topic" name="evpx_topic">
							<?php foreach ( $topics as $topic ) : ?><option><?php echo esc_html( $topic ); ?></option><?php endforeach; ?>
						</select>
					</p>
				<?php endif; ?>
			</div>
			<p class="evpx-contact__field"><label for="evpx-c-message"><?php esc_html_e( 'How can we help?', 'ev-charging-experience' ); ?></label><textarea id="evpx-c-message" name="evpx_message" rows="6" required minlength="5" maxlength="4000"></textarea></p>
			<button class="evpx-button evpx-button--primary evpx-contact__submit" type="submit"><?php echo esc_html( $button_label ); ?><?php echo \EVPX\Support\Icons::svg( 'arrow-right', 'evpx-button__arrow' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></button>
		</form>
	</div>
</section>
