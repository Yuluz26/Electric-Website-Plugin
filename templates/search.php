<?php
/**
 * @var string $spacing
 * @var string $anchor
 * @var string $heading
 * @var string $placeholder
 * @var string $query
 * @var string $type        '' | page | post
 * @var array  $results     [ [title, url, kind, excerpt], … ]
 * @var int    $total
 * @var bool   $searched
 * @var int    $pages
 * @var int    $paged
 * @var array  $suggestions [ [label, url], … ]
 * @var string $action
 * @var array  $hidden   Query arguments of the page's own address, sent as hidden fields.      This page's own address, without the search.
 * @var string $base        The current address, without paging.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_link = static function ( array $args ) use ( $base, $query ) {
	return esc_url( add_query_arg( array_merge( array( 'q' => $query ), $args ), $base ) );
};
?>
<section class="evpx-root alignfull evpx-search" <?php echo $anchor ? 'id="' . esc_attr( $anchor ) . '"' : ''; ?> data-evpx-theme="dark" data-evpx-spacing="<?php echo esc_attr( $spacing ); ?>">
	<div class="evpx-container evpx-search__grid">
		<?php if ( $heading ) : ?><h1 class="evpx-heading evpx-search__heading"><?php echo esc_html( $heading ); ?></h1><?php endif; ?>

		<form class="evpx-search__form" role="search" method="get" action="<?php echo esc_url( $action ); ?>">
			<?php foreach ( $hidden as $hidden_name => $hidden_value ) : ?><input type="hidden" name="<?php echo esc_attr( $hidden_name ); ?>" value="<?php echo esc_attr( $hidden_value ); ?>"><?php endforeach; ?>
			<label class="evpx-visually-hidden" for="evpx-search-page-q"><?php esc_html_e( 'Search', 'ev-charging-experience' ); ?></label>
			<?php echo \EVPX\Support\Icons::svg( 'magnifying-glass', 'evpx-search__icon' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
			<input class="evpx-search__input" id="evpx-search-page-q" type="search" name="q" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off">
			<button class="evpx-button evpx-button--primary" type="submit"><?php esc_html_e( 'Search', 'ev-charging-experience' ); ?></button>
		</form>

		<?php if ( $searched ) : ?>
			<div class="evpx-search__filters" role="group" aria-label="<?php esc_attr_e( 'Filter results', 'ev-charging-experience' ); ?>">
				<a class="evpx-search__chip" href="<?php echo $page_link( array() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url() in $link */ ?>"<?php echo '' === $type ? ' aria-current="true"' : ''; ?>><?php esc_html_e( 'All', 'ev-charging-experience' ); ?></a>
				<a class="evpx-search__chip" href="<?php echo $page_link( array( 'type' => 'page' ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url() in $link */ ?>"<?php echo 'page' === $type ? ' aria-current="true"' : ''; ?>><?php esc_html_e( 'Pages', 'ev-charging-experience' ); ?></a>
				<a class="evpx-search__chip" href="<?php echo $page_link( array( 'type' => 'post' ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url() in $link */ ?>"<?php echo 'post' === $type ? ' aria-current="true"' : ''; ?>><?php esc_html_e( 'Articles', 'ev-charging-experience' ); ?></a>
			</div>

			<p class="evpx-search__count" role="status">
				<?php
				printf(
					/* translators: 1: number of results, 2: the words searched for */
					esc_html( _n( '%1$s result for “%2$s”', '%1$s results for “%2$s”', $total, 'ev-charging-experience' ) ),
					esc_html( number_format_i18n( $total ) ),
					esc_html( $query )
				);
				?>
			</p>

			<?php if ( $results ) : ?>
				<ol class="evpx-search__list" role="list">
					<?php foreach ( $results as $i => $result ) : ?>
						<li class="evpx-search__item" style="--evpx-i:<?php echo (int) $i; ?>">
							<a class="evpx-search__hit" href="<?php echo esc_url( $result['url'] ); ?>">
								<span class="evpx-search__kind"><?php echo esc_html( $result['kind'] ); ?></span>
								<span class="evpx-search__title"><?php echo esc_html( $result['title'] ); ?></span>
								<?php if ( $result['excerpt'] ) : ?><span class="evpx-search__excerpt"><?php echo esc_html( $result['excerpt'] ); ?></span><?php endif; ?>
								<?php echo \EVPX\Support\Icons::svg( 'arrow-up-right', 'evpx-search__go' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>

				<?php if ( $pages > 1 ) : ?>
					<nav class="evpx-search__pager" aria-label="<?php esc_attr_e( 'Result pages', 'ev-charging-experience' ); ?>">
						<?php if ( $paged > 1 ) : ?><a class="evpx-search__chip" href="<?php echo $page_link( array( 'type' => $type, 'pg' => $paged - 1 ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url() in $link */ ?>"><?php esc_html_e( 'Previous', 'ev-charging-experience' ); ?></a><?php endif; ?>
						<span class="evpx-search__page"><?php echo esc_html( sprintf( /* translators: 1: page, 2: pages */ __( 'Page %1$d of %2$d', 'ev-charging-experience' ), $paged, $pages ) ); ?></span>
						<?php if ( $paged < $pages ) : ?><a class="evpx-search__chip" href="<?php echo $page_link( array( 'type' => $type, 'pg' => $paged + 1 ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url() in $link */ ?>"><?php esc_html_e( 'Next', 'ev-charging-experience' ); ?></a><?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<p class="evpx-search__empty"><?php esc_html_e( 'Nothing matches that. Try fewer words, or start from one of these.', 'ev-charging-experience' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $suggestions && ( ! $searched || ! $results ) ) : ?>
			<ul class="evpx-search__suggest" role="list">
				<?php foreach ( $suggestions as $suggestion ) : ?>
					<li><a class="evpx-search__hit evpx-search__hit--quick" href="<?php echo esc_url( $suggestion[1] ); ?>"><span class="evpx-search__title"><?php echo esc_html( $suggestion[0] ); ?></span><?php echo \EVPX\Support\Icons::svg( 'arrow-up-right', 'evpx-search__go' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
