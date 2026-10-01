<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Art;
use EVPX\Support\ContactForm;
use EVPX\Support\Lines;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Contact: how to reach the company, and a form that mails it (Support\ContactForm). */
final class Contact extends Element {

	public function slug(): string {
		return 'contact';
	}

	public function title(): string {
		return __( 'EV Contact', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Tell us about the site', 'ev-charging-experience' ) ),
			array( 'key' => 'intro', 'label' => __( 'Introduction', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'details', 'label' => __( 'Details, one per line as "label | value" (Address, Phone, Email, Hours)', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'topics', 'label' => __( 'What the enquiry is about, one per line (blank = no choice)', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => "A new site\nExpanding a site\nSupport for a site\nSomething else" ),
			array( 'key' => 'button_label', 'label' => __( 'Button label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Send the message', 'ev-charging-experience' ) ),
			array( 'key' => 'to_email', 'label' => __( 'Send enquiries to (blank = the site\'s admin email)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'advanced', 'default' => '', 'sanitize' => 'sanitize_email' ),
			array( 'key' => 'map', 'label' => __( 'Show the network drawing', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'visual', 'default' => true ),
			array( 'key' => 'animate', 'label' => __( 'Enable scroll reveal', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'Contact', 'ev-charging-experience' ),
			'intro'   => __( 'A few lines are enough: where the site is, how many vehicles, and what you have in mind. We reply within a working day.', 'ev-charging-experience' ),
			'details' => "Address | 12 Example Road, Sample Town\nPhone | +44 20 7946 0000\nEmail | hello@example.com\nHours | Monday to Friday, 8 to 6",
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$details = array();
		foreach ( Lines::pairs( $atts['details'], 6 ) as $pair ) {
			$value = $pair[1];
			$href  = '';

			if ( is_email( $value ) ) {
				$href = 'mailto:' . antispambot( $value );
			} elseif ( preg_match( '/^\+?[0-9][0-9\s().-]{6,}$/', $value ) ) {
				$href = 'tel:' . preg_replace( '/[^0-9+]/', '', $value );
			}

			$details[] = array( $pair[0], $value, $href );
		}

		$topics = array_map( static fn( $pair ) => $pair[0], Lines::pairs( $atts['topics'], 8 ) );

		// A submitted form sends the visitor back with the outcome in the address (a read, so no nonce).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sent = isset( $_GET['evpx_sent'] ) && is_scalar( $_GET['evpx_sent'] ) ? sanitize_key( wp_unslash( (string) $_GET['evpx_sent'] ) ) : '';

		return $this->view(
			'contact',
			array(
				'spacing'      => $atts['spacing'],
				'anchor'       => $atts['anchor'],
				'eyebrow'      => $atts['eyebrow'],
				'heading'      => $atts['heading'],
				'intro'        => $atts['intro'],
				'details'      => $details,
				'topics'       => $topics,
				'button_label' => $atts['button_label'],
				'map_html'     => $atts['map'] ? Art::render( 'network-map' ) : '',
				'animate'      => $atts['animate'],
				'action'       => esc_url( admin_url( 'admin-post.php' ) ),
				'return'       => esc_url( remove_query_arg( 'evpx_sent' ) ),
				'token'        => ContactForm::token( $atts['to_email'] ),
				'sent'         => $sent,
			)
		);
	}
}
