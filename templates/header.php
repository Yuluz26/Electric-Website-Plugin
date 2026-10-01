<?php
/**
 * @var string $brand
 * @var string $brand_url
 * @var string $logo       <img>, or ''.
 * @var array  $links      [ [label, url, current], … ]
 * @var string $cta_label
 * @var string $cta_url
 * @var bool   $search
 * @var string $search_url
 * @var array  $search_hid  Query arguments of the search page's address, sent as hidden fields.
 * @var string $search_var q (a results page of our own) | s (WordPress's)
 * @var string $rest       REST route for live results.
 * @var bool   $sticky
 * @var string $uid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<header class="evpx-root alignfull evpx-header" data-evpx-theme="dark" data-evpx-header data-evpx-sticky="<?php echo $sticky ? '1' : '0'; ?>">
	<div class="evpx-container evpx-header__bar">
		<a class="evpx-header__brand" href="<?php echo esc_url( $brand_url ); ?>">
			<?php if ( $logo ) : ?>
				<?php echo $logo; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?>
			<?php else : ?>
				<span class="evpx-header__mark" aria-hidden="true"><?php echo \EVPX\Support\Icons::svg( 'lightning' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></span>
			<?php endif; ?>
			<span class="evpx-header__name"><?php echo esc_html( $brand ); ?></span>
		</a>

		<?php if ( $links ) : ?>
			<nav class="evpx-header__nav" aria-label="<?php echo esc_attr_x( 'Primary', 'navigation label', 'ev-charging-experience' ); ?>">
				<ul class="evpx-header__links" role="list">
					<?php foreach ( $links as $nav_link ) : ?>
						<li><a class="evpx-header__link" href="<?php echo esc_url( $nav_link['url'] ); ?>"<?php echo $nav_link['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $nav_link['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<div class="evpx-header__tools">
			<?php if ( $search ) : ?>
				<button type="button" class="evpx-header__search" data-evpx-search-open aria-label="<?php esc_attr_e( 'Search', 'ev-charging-experience' ); ?>" aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $uid ); ?>-search">
					<?php echo \EVPX\Support\Icons::svg( 'magnifying-glass' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
					<span class="evpx-header__search-label"><?php esc_html_e( 'Search', 'ev-charging-experience' ); ?></span>
					<kbd class="evpx-header__kbd" aria-hidden="true">Ctrl K</kbd>
				</button>
			<?php endif; ?>

			<?php if ( $cta_label && $cta_url ) : ?>
				<a class="evpx-button evpx-button--primary evpx-header__cta" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
			<?php endif; ?>

			<button type="button" class="evpx-header__toggle" data-evpx-menu-toggle aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>-menu">
				<?php echo \EVPX\Support\Icons::svg( 'list', 'evpx-header__toggle-open' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
				<?php echo \EVPX\Support\Icons::svg( 'x', 'evpx-header__toggle-close' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
				<span class="evpx-visually-hidden"><?php esc_html_e( 'Menu', 'ev-charging-experience' ); ?></span>
			</button>
		</div>
	</div>

	<?php if ( $links ) : ?>
		<nav class="evpx-header__menu" id="<?php echo esc_attr( $uid ); ?>-menu" aria-label="<?php echo esc_attr_x( 'Menu', 'navigation label', 'ev-charging-experience' ); ?>" data-evpx-menu>
			<ul class="evpx-container" role="list">
				<?php foreach ( $links as $nav_link ) : ?>
					<li><a class="evpx-header__menu-link" href="<?php echo esc_url( $nav_link['url'] ); ?>"<?php echo $nav_link['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $nav_link['label'] ); ?><?php echo \EVPX\Support\Icons::svg( 'arrow-right' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>

	<?php if ( $search ) : ?>
		<?php // Without a script the button does nothing useful, so the form is also a plain link target: the results page or WordPress's own search. ?>
		<dialog class="evpx-root evpx-searchbox" id="<?php echo esc_attr( $uid ); ?>-search" data-evpx-theme="dark" data-evpx-searchbox data-rest="<?php echo esc_url( $rest ); ?>" data-label-page="<?php echo esc_attr_x( 'Page', 'search result type', 'ev-charging-experience' ); ?>" data-label-post="<?php echo esc_attr_x( 'Article', 'search result type', 'ev-charging-experience' ); ?>" aria-label="<?php esc_attr_e( 'Search the site', 'ev-charging-experience' ); ?>">
			<form class="evpx-searchbox__form" role="search" method="get" action="<?php echo esc_url( $search_url ); ?>">
				<?php foreach ( $search_hid as $hidden_name => $hidden_value ) : ?><input type="hidden" name="<?php echo esc_attr( $hidden_name ); ?>" value="<?php echo esc_attr( $hidden_value ); ?>"><?php endforeach; ?>
				<label class="evpx-visually-hidden" for="<?php echo esc_attr( $uid ); ?>-q"><?php esc_html_e( 'Search', 'ev-charging-experience' ); ?></label>
				<?php echo \EVPX\Support\Icons::svg( 'magnifying-glass', 'evpx-searchbox__icon' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
				<input class="evpx-searchbox__input" id="<?php echo esc_attr( $uid ); ?>-q" type="search" name="<?php echo esc_attr( $search_var ); ?>" placeholder="<?php esc_attr_e( 'Search pages and articles', 'ev-charging-experience' ); ?>" autocomplete="off" spellcheck="false" data-evpx-search-input>
				<button type="button" class="evpx-searchbox__close" data-evpx-search-close><span class="evpx-visually-hidden"><?php esc_html_e( 'Close search', 'ev-charging-experience' ); ?></span><?php echo \EVPX\Support\Icons::svg( 'x' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></button>
			</form>
			<ul class="evpx-searchbox__results" role="listbox" aria-label="<?php esc_attr_e( 'Results', 'ev-charging-experience' ); ?>" data-evpx-search-results></ul>
			<p class="evpx-searchbox__status" role="status" aria-live="polite" data-evpx-search-status><?php esc_html_e( 'Type to search. Enter opens all results.', 'ev-charging-experience' ); ?></p>
		</dialog>
	<?php endif; ?>
</header>
