<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Lines;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The site header: brand, links, a button, and a search that opens over the page (Ctrl/Cmd + K, or /). */
final class SiteHeader extends Element {

	public function slug(): string {
		return 'header';
	}

	public function title(): string {
		return __( 'EV Site Header', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'brand', 'label' => __( 'Brand name (blank = the site title)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'brand_url', 'label' => __( 'Brand link (blank = the home page)', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'logo', 'label' => __( 'Logo (blank = the brand mark)', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
			array( 'key' => 'links', 'label' => __( 'Links, one per line as "label | address"', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => "Home | /\nAbout | /about/\nServices | /services/\nProjects | /projects/\nContact | /contact/" ),
			array( 'key' => 'cta_label', 'label' => __( 'Button label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Get in touch', 'ev-charging-experience' ) ),
			array( 'key' => 'cta_url', 'label' => __( 'Button address', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '/contact/' ),
			array( 'key' => 'search', 'label' => __( 'Show the search', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'content', 'default' => true ),
			array( 'key' => 'search_url', 'label' => __( 'Search results page (blank = WordPress\'s own search)', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '/search/' ),
			array( 'key' => 'sticky', 'label' => __( 'Stay at the top while scrolling', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'layout', 'default' => true ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$current = trailingslashit( (string) get_permalink() );
		$links   = array();

		foreach ( Lines::pairs( $atts['links'], 8 ) as $pair ) {
			$url     = Lines::url( '' === $pair[1] ? '#' : $pair[1] );
			$links[] = array(
				'label'   => $pair[0],
				'url'     => $url,
				'current' => '' !== $url && trailingslashit( $url ) === $current,
			);
		}

		$search_url = '' === $atts['search_url'] ? '' : Lines::url( $atts['search_url'] );
		$target     = Lines::target( '' !== $search_url ? $search_url : home_url( '/' ) );

		return $this->view(
			'header',
			array(
				'brand'      => '' !== $atts['brand'] ? $atts['brand'] : get_bloginfo( 'name' ),
				'brand_url'  => '' !== $atts['brand_url'] ? Lines::url( $atts['brand_url'] ) : esc_url( home_url( '/' ) ),
				'logo'       => ! empty( $atts['logo'] ) ? $this->image( (int) $atts['logo'], 'medium', array( 'class' => 'evpx-header__logo', 'alt' => '' ) ) : '',
				'links'      => $links,
				'cta_label'  => $atts['cta_label'],
				'cta_url'    => '' === $atts['cta_url'] ? '' : Lines::url( $atts['cta_url'] ),
				'search'     => $atts['search'],
				'search_url' => $target['action'],
				'search_hid' => $target['fields'],
				'search_var' => '' !== $search_url ? 'q' : 's',
				'rest'       => esc_url_raw( rest_url( 'wp/v2/search' ) ),
				'sticky'     => $atts['sticky'],
				'uid'        => $this->uniqueId( 'evpx-header' ),
			)
		);
	}
}
