<?php

namespace EVPX\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The contact form's server side: a POST to admin-post.php that mails the message and sends the visitor back.
 *
 * It is an anonymous action, so a nonce protects nothing and, on a cached page, would only expire. What stops a
 * bot instead: a field a person never sees, a signed timestamp (a form submitted in under three seconds, or a week
 * after it was printed, is not a person at a desk), and a limit per address. The recipient rides in the same signed
 * token, so a form can only ever mail the address its widget was given.
 */
final class ContactForm {

	public const ACTION = 'evpx_contact';

	private const MIN_AGE = 3;
	private const MAX_AGE = WEEK_IN_SECONDS;
	private const LIMIT   = 5;

	public function register(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/** The hidden field for a form printed now, for this recipient ('' = the site's admin). */
	public static function token( string $to = '' ): string {
		$now  = time();
		$to64 = '' === $to ? '-' : rtrim( strtr( base64_encode( $to ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- a token, not obfuscation.

		return $now . '.' . $to64 . '.' . wp_hash( 'evpx_contact|' . $now . '|' . $to64 );
	}

	/**
	 * @return array{ok: bool, to: string, age: int}
	 */
	public static function verify( string $token ): array {
		$bad   = array( 'ok' => false, 'to' => '', 'age' => 0 );
		$parts = explode( '.', $token );

		if ( 3 !== count( $parts ) || ! ctype_digit( $parts[0] ) || ! hash_equals( wp_hash( 'evpx_contact|' . $parts[0] . '|' . $parts[1] ), $parts[2] ) ) {
			return $bad;
		}

		$to = '';
		if ( '-' !== $parts[1] ) {
			$to = (string) base64_decode( strtr( $parts[1], '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- a token, not obfuscation.

			if ( ! is_email( $to ) ) {
				return $bad;
			}
		}

		return array( 'ok' => true, 'to' => $to, 'age' => time() - (int) $parts[0] );
	}

	public function handle(): void {
		// The form is anonymous by design (see the class comment), so there is no nonce to verify here.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$get = static function ( string $key, int $max = 200 ): string {
			$raw = isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? (string) wp_unslash( $_POST[ $key ] ) : '';

			return mb_substr( trim( sanitize_text_field( $raw ) ), 0, $max );
		};

		$back = wp_validate_redirect( isset( $_POST['evpx_return'] ) ? esc_url_raw( wp_unslash( (string) $_POST['evpx_return'] ) ) : '', home_url( '/' ) );
		$done = static function ( string $state ) use ( $back ) {
			wp_safe_redirect( add_query_arg( 'evpx_sent', $state, $back ) . '#evpx-contact' );
			exit;
		};

		// A person never sees this field. A bot fills it, and is told it worked.
		if ( '' !== $get( 'evpx_website' ) ) {
			$done( '1' );
		}

		$token = self::verify( $get( 'evpx_token', 400 ) );

		if ( ! $token['ok'] || $token['age'] < self::MIN_AGE || $token['age'] > self::MAX_AGE ) {
			$done( 'expired' );
		}

		$name    = $get( 'evpx_name', 100 );
		$email   = sanitize_email( $get( 'evpx_email', 120 ) );
		$phone   = $get( 'evpx_phone', 40 );
		$topic   = $get( 'evpx_topic', 60 );
		$message = isset( $_POST['evpx_message'] ) && is_scalar( $_POST['evpx_message'] ) ? mb_substr( trim( sanitize_textarea_field( wp_unslash( (string) $_POST['evpx_message'] ) ) ), 0, 4000 ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $name || ! is_email( $email ) || mb_strlen( $message ) < 5 ) {
			$done( 'invalid' );
		}

		$key   = 'evpx_contact_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed, never output.
		$count = (int) get_transient( $key );

		if ( $count >= self::LIMIT ) {
			$done( 'limit' );
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );

		$to      = '' !== $token['to'] ? $token['to'] : (string) get_option( 'admin_email' );
		$subject = sprintf( /* translators: 1: site name, 2: topic, 3: sender's name */ __( '[%1$s] %2$s: %3$s', 'ev-charging-experience' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), '' !== $topic ? $topic : __( 'Enquiry', 'ev-charging-experience' ), $name );
		$body    = sprintf( "%s\n%s\n%s\n\n%s\n", $name . ' <' . $email . '>', '' !== $phone ? $phone : '-', '' !== $topic ? $topic : '-', $message );
		$headers = array( 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' <' . $email . '>' );

		$done( wp_mail( $to, $subject, $body, $headers ) ? '1' : 'failed' );
	}
}
