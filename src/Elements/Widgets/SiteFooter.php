<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Lines;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The site footer: what the brand is, where to go, how to reach it, and the small print. */
final class SiteFooter extends Element {

	public function slug(): string {
		return 'footer';
	}

	public function title(): string {
		return __( 'EV Site Footer', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			self::spacingControl(),
			array( 'key' => 'brand', 'label' => __( 'Brand name (blank = the site title)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'blurb', 'label' => __( 'A line about the brand', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => __( 'Charging that is planned around the site, installed once and looked after.', 'ev-charging-experience' ) ),
			array( 'key' => 'links', 'label' => __( 'Links, one per line as "label | address"', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => "Home | /\nAbout | /about/\nServices | /services/\nProjects | /projects/\nContact | /contact/\nSearch | /search/" ),
			array( 'key' => 'contact', 'label' => __( 'How to reach you, one per line (an address, a phone number, an email)', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'legal', 'label' => __( 'Small print (blank = © year and the site title)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$brand = '' !== $atts['brand'] ? $atts['brand'] : get_bloginfo( 'name' );

		$links = array();
		foreach ( Lines::pairs( $atts['links'], 10 ) as $pair ) {
			$links[] = array( $pair[0], Lines::url( '' === $pair[1] ? '#' : $pair[1] ) );
		}

		$contact = array();
		foreach ( Lines::pairs( str_replace( '|', ' ', $atts['contact'] ), 5 ) as $pair ) {
			$line = trim( $pair[0] . ' ' . $pair[1] );
			$href = '';

			if ( is_email( $line ) ) {
				$href = 'mailto:' . antispambot( $line );
			} elseif ( preg_match( '/^\+?[0-9][0-9\s().-]{6,}$/', $line ) ) {
				$href = 'tel:' . preg_replace( '/[^0-9+]/', '', $line );
			}

			$contact[] = array( $line, $href );
		}

		return $this->view(
			'footer',
			array(
				'spacing' => $atts['spacing'],
				'brand'   => $brand,
				'blurb'   => $atts['blurb'],
				'links'   => $links,
				'contact' => $contact,
				'legal'   => '' !== $atts['legal'] ? $atts['legal'] : '© ' . wp_date( 'Y' ) . ' ' . $brand,
			)
		);
	}
}
