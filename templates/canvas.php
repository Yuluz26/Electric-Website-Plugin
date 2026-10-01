<?php
/**
 * The "EV full-width page" template (Setup\Canvas): the page's content and nothing else.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>body.evpx-canvas{margin:0;background:#14171c}.evpx-canvas__skip{position:absolute;inset-inline-start:-9999px;z-index:100;padding:.75rem 1rem;background:#fff;color:#14171c;font:500 1rem/1.2 system-ui,sans-serif}.evpx-canvas__skip:focus{inset-inline-start:.5rem;inset-block-start:.5rem}</style>
</head>
<body <?php body_class( 'evpx-canvas' ); ?>>
<?php wp_body_open(); ?>
<a class="evpx-canvas__skip" href="#evpx-main"><?php esc_html_e( 'Skip to content', 'ev-charging-experience' ); ?></a>
<main id="evpx-main">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
