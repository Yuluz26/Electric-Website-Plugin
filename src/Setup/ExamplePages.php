<?php

namespace EVPX\Setup;

use EVPX\Breakdance\Compatibility;
use EVPX\Breakdance\Native\Tree;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What a new install starts with, so it has something to open, look at and copy from instead of an empty
 * Pages list.
 *
 * - `article`: a post, "Choosing AC or DC Charging for Your Site", built from the article widgets as
 *   shortcodes (content/demo-article.txt). Works on any WordPress.
 * - `breakdance`: a page with the same article as native Breakdance elements, one full-width Section
 *   each, ready to open in the builder. Made once Breakdance is active.
 * - `site`: six pages, Home, About, Services, Projects, Contact and Search (content/site/), with the
 *   header, the page heroes, the interactive sections and the footer. As native elements when Breakdance is
 *   active; otherwise as shortcodes on the "EV full-width page" template (Setup\Canvas), so they run edge
 *   to edge in any theme. Their names, figures and quotations are placeholders to replace.
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

	private const KINDS = array( 'article', 'breakdance', 'site' );

	/** The site pages, in the order they are made and listed: slug => title. */
	private const SITE = array(
		'home'     => 'Home',
		'about'    => 'About',
		'services' => 'Services',
		'projects' => 'Projects',
		'contact'  => 'Contact',
		'search'   => 'Search',
	);

	/** @var array<string, int> The site pages made by this request: slug => id. */
	private array $site = array();

	/** Called on activation. Does nothing on a site that has already had its examples. */
	public static function queue(): void {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, array( 'pending' => true ) );
		}
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'createPending' ), 20 );
		add_action( 'admin_notices', array( $this, 'showNotice' ) );
		add_action( 'admin_post_evpx_publish_site', array( $this, 'publishSite' ) );
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

			if ( 'site' === $kind && $this->site ) {
				$state['site_pages'] = $this->site;
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
		$site  = isset( $made['site'] ) && is_array( $state ) && ! empty( $state['site_pages'] ) ? (array) $state['site_pages'] : array();
		unset( $made['site'] );

		if ( ! $made ) {
			$this->printSiteNotice( $site );

			return;
		}

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

		$this->printSiteNotice( $site );
	}

	/**
	 * @param array<string, int> $site slug => id
	 */
	private function printSiteNotice( array $site ): void {
		if ( ! $site ) {
			return;
		}

		$publish = wp_nonce_url( admin_url( 'admin-post.php?action=evpx_publish_site' ), 'evpx_publish_site' );

		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>%1$s</strong> %2$s</p><p><a href="%3$s">%4$s</a> &middot; <a href="%5$s">%6$s</a> &middot; <a href="%7$s">%8$s</a> &middot; <a href="%9$s">%10$s</a></p></div>',
			esc_html__( 'EV Charging Experience', 'ev-charging-experience' ),
			esc_html__( 'added a site: Home, About, Services, Projects, Contact and Search, with the header, page heroes and footer. They are drafts. The names, figures and quotations in them are placeholders.', 'ev-charging-experience' ),
			esc_url( (string) get_preview_post_link( (int) reset( $site ) ) ),
			esc_html__( 'Preview Home', 'ev-charging-experience' ),
			esc_url( admin_url( 'edit.php?post_type=page' ) ),
			esc_html__( 'See the pages', 'ev-charging-experience' ),
			esc_url( $publish ),
			esc_html__( 'Publish all six', 'ev-charging-experience' ),
			esc_url( add_query_arg( 'front', '1', $publish ) ),
			esc_html__( 'Publish and use Home as the front page', 'ev-charging-experience' )
		);
	}

	/** The notice's buttons: publish the site pages, and if asked, make Home the front page. */
	public function publishSite(): void {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'publish_pages' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'ev-charging-experience' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'evpx_publish_site' );

		$state = get_option( self::OPTION );
		$ids   = is_array( $state ) && ! empty( $state['site_pages'] ) ? array_map( 'intval', (array) $state['site_pages'] ) : array();

		foreach ( $ids as $id ) {
			if ( $id && 'page' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) ) {
				wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'publish',
					)
				);
			}
		}

		if ( ! empty( $_GET['front'] ) && ! empty( $ids['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $ids['home'] );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}

	private function canMake( string $kind ): bool {
		if ( 'breakdance' === $kind ) {
			return ( new Compatibility() )->isBreakdanceActive() && function_exists( '\Breakdance\Data\set_meta' );
		}

		return true;
	}

	private function make( string $kind ): int {
		if ( 'site' === $kind ) {
			return $this->makeSite();
		}

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
	 * The six site pages. Returns the id of Home (0 if nothing could be made); all of them are in $this->site.
	 */
	private function makeSite(): int {
		$this->site = array(); // this object may be asked more than once; what an earlier call made is not this call's

		$status    = apply_filters( 'evpx_example_pages_status', 'draft' );
		$status    = in_array( $status, array( 'draft', 'private', 'publish' ), true ) ? $status : 'draft';
		$builder   = $this->canMake( 'breakdance' );
		// Each Breakdance Section is its own containing block, so a sticky bar cannot stay. The page has no theme
		// title (the template prints only the content), so the hero, an h1 by default, is the page's one.
		$overrides = array( 'evpx_header' => array( 'sticky' => 'false' ) );

		foreach ( self::SITE as $slug => $title ) {
			// A page at this slug, on this template, is this plugin's own from an earlier install: the record of it
			// (evpx_examples) can be lost (the site was deleted and reinstalled; a multisite copied the database) while
			// the page itself survives, since uninstall.php never removes content. Adopt it rather than clone it — a
			// second "Home" would confuse whoever next opens Pages, and would overwrite nothing of theirs only by luck.
			$existing = get_page_by_path( $slug, OBJECT, 'page' );

			if ( $existing && Canvas::SLUG === get_page_template_slug( $existing->ID ) ) {
				$this->site[ $slug ] = $existing->ID;
				continue;
			}

			$content = $this->siteContent( $slug );

			if ( '' === $content ) {
				continue;
			}

			$id = (int) wp_insert_post(
				wp_slash(
					array(
						'post_type'    => 'page',
						'post_status'  => $status,
						'post_title'   => $title,
						'post_name'    => $slug,
						'post_content' => $builder ? '' : $content,
						'meta_input'   => array( '_wp_page_template' => Canvas::SLUG ),
					)
				)
			);

			if ( ! $id ) {
				continue;
			}

			if ( $builder ) {
				\Breakdance\Data\set_meta( $id, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( Tree::fromArticle( $content, $overrides ) ) ) );
			}

			$this->site[ $slug ] = $id;
		}

		return $this->site['home'] ?? 0;
	}

	/** A site page's shortcodes, with each {{name}} replaced by the shared rows in content/site/_name.txt. */
	private function siteContent( string $slug ): string {
		$read = static function ( string $name ): string {
			$file = EVPX_PATH . 'content/site/' . $name . '.txt';

			return is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a file inside the plugin.
		};

		$page = $read( $slug );

		return preg_replace_callback(
			'/\{\{([a-z]+)\}\}/',
			static function ( array $m ) use ( $read ): string {
				return $read( '_' . $m[1] );
			},
			$page
		) ?? '';
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
