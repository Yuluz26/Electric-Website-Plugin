<?php

namespace EVPX\Setup;

use EVPX\Breakdance\Compatibility;
use EVPX\Breakdance\Native\Tree;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The example articles: "Choosing AC or DC Charging for Your Site", built from every widget, so a new
 * install has something to open, look at and copy from instead of an empty Pages list.
 *
 * - `article`: a post with the article as shortcodes (content/demo-article.txt). Works on any WordPress.
 * - `breakdance`: a page with the same article as native Breakdance elements, one full-width Section
 *   each, ready to open in the builder. Made once Breakdance is active.
 *
 * Activation only queues them (queue()); they are made on the next admin request, when every plugin is
 * loaded, so activating Breakdance and this plugin together, in either order, gives both. They are drafts:
 * nothing is public until someone publishes it. Each is made once: the option keeps its id, so deleting
 * an example never brings it back (delete the option to start again). A kind that fails is recorded as
 * 0 and not retried, so a fault here can never repeat on every admin page.
 */
final class ExamplePages {

	public const OPTION = 'evpx_examples';
	public const NOTICE = 'evpx_examples_notice';

	private const KINDS = array( 'article', 'breakdance' );

	/** Called on activation. Does nothing on a site that has already had its examples. */
	public static function queue(): void {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, array( 'pending' => true ) );
		}
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'createPending' ), 20 );
		add_action( 'admin_notices', array( $this, 'showNotice' ) );
	}

	/**
	 * Makes whichever example can be made now. Runs on every admin request, so almost always it does nothing:
	 * one option read, which is autoloaded once the site has the option.
	 */
	public function createPending(): void {
		$state = get_option( self::OPTION );

		if ( ! is_array( $state ) || empty( $state['pending'] ) || wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$made = array();

		foreach ( self::KINDS as $kind ) {
			if ( isset( $state[ $kind ] ) || ! $this->canMake( $kind ) ) {
				continue;
			}

			// Recorded before it is made: whatever goes wrong, it is not attempted again.
			$state[ $kind ] = 0;
			update_option( self::OPTION, $state );

			try {
				$id = $this->make( $kind );
			} catch ( \Throwable $error ) {
				$id = 0;
			}

			if ( $id ) {
				$state[ $kind ] = $id;
				$made[ $kind ]  = $id;
			}
		}

		// Pending until every kind has been attempted: the Breakdance page waits for Breakdance.
		$state['pending'] = count( array_intersect_key( $state, array_flip( self::KINDS ) ) ) < count( self::KINDS );
		update_option( self::OPTION, $state );

		if ( $made ) {
			set_transient( self::NOTICE, $made, DAY_IN_SECONDS );
		}
	}

	public function showNotice(): void {
		$made = get_transient( self::NOTICE );

		if ( ! is_array( $made ) || ! $made || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		delete_transient( self::NOTICE );

		$state = get_option( self::OPTION );

		if ( count( $made ) > 1 ) {
			$lead = __( 'added two example articles so you can see every element in place.', 'ev-charging-experience' );
		} elseif ( isset( $made['breakdance'] ) ) {
			$lead = __( 'found Breakdance and added an example article built with its elements.', 'ev-charging-experience' );
		} else {
			$lead = __( 'added an example article so you can see every element in place.', 'ev-charging-experience' );
		}

		$items = '';
		foreach ( $made as $kind => $id ) {
			$items .= sprintf(
				'<p>%1$s &mdash; <a href="%2$s">%3$s</a> &middot; <a href="%4$s">%5$s</a></p>',
				esc_html( get_the_title( $id ) ),
				esc_url( (string) get_preview_post_link( $id ) ),
				esc_html__( 'Preview', 'ev-charging-experience' ),
				esc_url( $this->editUrl( $kind, $id ) ),
				esc_html( 'breakdance' === $kind && $this->hasBuilderLink() ? __( 'Edit in Breakdance', 'ev-charging-experience' ) : __( 'Edit', 'ev-charging-experience' ) )
			);
		}

		$later = '';
		if ( is_array( $state ) && ! isset( $state['breakdance'] ) ) {
			$later = ' ' . esc_html__( 'Once Breakdance is active, a page built in it is added too.', 'ev-charging-experience' );
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>%1$s</strong> %2$s %3$s%4$s</p>%5$s</div>',
			esc_html__( 'EV Charging Experience', 'ev-charging-experience' ),
			esc_html( $lead ),
			esc_html__( 'They are drafts: nothing is public until you publish.', 'ev-charging-experience' ),
			$later, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			$items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
		);
	}

	private function canMake( string $kind ): bool {
		if ( 'breakdance' === $kind ) {
			return ( new Compatibility() )->isBreakdanceActive() && function_exists( '\Breakdance\Data\set_meta' );
		}

		return true;
	}

	private function make( string $kind ): int {
		$file    = EVPX_PATH . 'content/demo-article.txt';
		$article = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a file inside the plugin.

		if ( '' === $article ) {
			return 0;
		}

		$status = apply_filters( 'evpx_example_pages_status', 'draft' );
		$post   = array(
			'post_status' => in_array( $status, array( 'draft', 'private', 'publish' ), true ) ? $status : 'draft',
			'post_title'  => 'Choosing AC or DC Charging for Your Site',
		);

		if ( 'article' === $kind ) {
			return (int) wp_insert_post(
				wp_slash(
					array_merge(
						$post,
						array(
							'post_type'    => 'post',
							'post_content' => $article,
						)
					)
				)
			);
		}

		$id = (int) wp_insert_post(
			wp_slash(
				array_merge(
					$post,
					array(
						'post_type'    => 'page',
						'post_title'   => $post['post_title'] . ' (Breakdance)',
						'post_content' => '',
					)
				)
			)
		);

		if ( $id ) {
			\Breakdance\Data\set_meta( $id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( Tree::fromArticle( $article, $this->pageOverrides() ) ) ) );
		}

		return $id;
	}

	/**
	 * What a page needs that the post does not. It has no category, so its Related row lists the latest posts.
	 * And the hero is the page's h1 only where nothing else is: Breakdance's own Zero theme prints no title, but
	 * any other theme prints the page's title as an h1 above the content, and the hero, which repeats it, is an
	 * h2 there, as it is in the post (docs/INSTALLATION.md, "One h1 per page").
	 *
	 * @return array<string, array<string, string>>
	 */
	private function pageOverrides(): array {
		return array(
			'evpx_hero'    => array( 'title_tag' => 'breakdance-zero' === get_template() ? 'h1' : 'h2' ),
			'evpx_related' => array( 'source' => 'latest' ),
		);
	}

	private function hasBuilderLink(): bool {
		return function_exists( '\Breakdance\Admin\get_builder_loader_url' );
	}

	private function editUrl( string $kind, int $id ): string {
		if ( 'breakdance' === $kind && $this->hasBuilderLink() ) {
			return \Breakdance\Admin\get_builder_loader_url( $id );
		}

		return (string) get_edit_post_link( $id, 'raw' );
	}
}
