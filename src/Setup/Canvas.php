<?php

namespace EVPX\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "EV full-width page": a page template that prints only the page's content, edge to edge, in a bare document.
 *
 * The site pages (Setup\ExamplePages) bring their own header and footer as widgets, and a full-width hero has
 * to reach the browser's edges. A theme's column, title and footer are in the way of both, and how to get them
 * out of the way is different in every theme. This is the same in all of them. It is chosen on the page like
 * any template (Page attributes), so a site can stop using it by choosing another. A page built in Breakdance
 * can use it too: Breakdance puts the page it built into the content, and the template prints the content.
 */
final class Canvas {

	public const SLUG = 'evpx-canvas';

	public function register(): void {
		add_filter( 'theme_page_templates', array( $this, 'offer' ), 10, 4 );
		add_filter( 'template_include', array( $this, 'choose' ), 99 );
	}

	/**
	 * @param array<string, string> $templates
	 * @return array<string, string>
	 */
	public function offer( array $templates, $theme = null, $post = null, string $post_type = 'page' ): array {
		if ( 'page' === $post_type ) {
			$templates[ self::SLUG ] = __( 'EV full-width page (no theme header or footer)', 'ev-charging-experience' );
		}

		return $templates;
	}

	public function choose( string $template ): string {
		if ( is_singular( 'page' ) && self::SLUG === get_page_template_slug() ) {
			return EVPX_PATH . 'templates/canvas.php';
		}

		return $template;
	}
}
