<?php
/**
 * Media fixtures for the image checks. No photography ships with the plugin, so this generates a
 * handful of photo-like files with GD (plain gradients with a 2px orange frame at the exact edges, so
 * a crop is visible), imports them as attachments, and builds:
 *
 *   1. a post of shortcodes whose image controls hold attachment ids,
 *   2. a Breakdance page of native elements whose image controls hold Breakdance-shaped media values,
 *   3. a page whose Related Articles row is deliberately mixed — one article with a featured image,
 *      one without,
 *   4. a page with a hero and a CTA over a bright, overcast-sky picture: the worst case for white type.
 *
 * Every published post gets a featured image, so the Related Articles row on 1 and 2 is a full row of
 * pictures. Prints "<post id> <native page id> <mixed page id> <bright page id>".
 *
 *   wp eval-file tests/docker/media-pages.php /tmp/demo-article.txt
 *
 * Needs the real Breakdance plugin for page 2 (see tests/docker/setup.sh, EVPX_BREAKDANCE_ZIP) and
 * tests/docker/native-helpers.php copied to /tmp (media-pages.sh does both).
 */

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require '/tmp/native-helpers.php';

if ( ! function_exists( '\Breakdance\Data\set_meta' ) ) {
	fwrite( STDERR, "Breakdance is not active.\n" );
	exit( 1 );
}

/**
 * The fixture images: key => width, height, top colour, bottom colour, format, alt text.
 * Widths follow how the widgets use them: a wide hero, a 4:3 section picture, a wide banner behind
 * the CTA, a small square icon, a landscape thumbnail.
 */
function evpx_media_specs(): array {
	return array(
		'hero'    => array( 2400, 1350, array( 20, 40, 70 ), array( 160, 90, 40 ), 'jpg', 'Charger bay at dusk, three cars plugged in' ),
		'section' => array( 1600, 1200, array( 90, 120, 130 ), array( 30, 40, 50 ), 'jpg', 'Transformer and switchgear in a plant room' ),
		'cta'     => array( 1800, 900, array( 15, 20, 30 ), array( 100, 60, 30 ), 'jpg', 'Row of DC chargers at a depot' ),
		'icon'    => array( 240, 240, array( 200, 210, 220 ), array( 120, 130, 145 ), 'png', 'Hotel car park' ),
		'thumb'   => array( 1200, 800, array( 60, 90, 110 ), array( 20, 30, 40 ), 'jpg', 'Cable and connector close-up' ),
		'bright'  => array( 2400, 1350, array( 248, 250, 252 ), array( 200, 214, 230 ), 'jpg', 'Overcast sky above a car park' ),
	);
}

/** Writes one gradient image to a temp file and returns its path. */
function evpx_media_file( string $key, array $spec ): string {
	list( $w, $h, $top, $bottom, $format ) = $spec;

	$im = imagecreatetruecolor( $w, $h );
	for ( $y = 0; $y < $h; $y++ ) {
		$t = $y / max( 1, $h - 1 );
		imageline(
			$im,
			0,
			$y,
			$w,
			$y,
			imagecolorallocate(
				$im,
				(int) ( $top[0] + ( $bottom[0] - $top[0] ) * $t ),
				(int) ( $top[1] + ( $bottom[1] - $top[1] ) * $t ),
				(int) ( $top[2] + ( $bottom[2] - $top[2] ) * $t )
			)
		);
	}

	// Soft shapes, so the aspect ratio is readable in a screenshot.
	for ( $i = 0; $i < 9; $i++ ) {
		imagefilledellipse( $im, (int) ( $w * ( 0.1 + 0.1 * $i ) ), (int) ( $h * ( 0.3 + 0.05 * ( $i % 4 ) ) ), (int) ( $h * 0.35 ), (int) ( $h * 0.35 ), imagecolorallocatealpha( $im, 255, 255, 255, 110 ) );
	}

	// The frame: shows exactly where an image is cropped.
	$edge = imagecolorallocate( $im, 255, 80, 40 );
	imagerectangle( $im, 0, 0, $w - 1, $h - 1, $edge );
	imagerectangle( $im, 1, 1, $w - 2, $h - 2, $edge );

	$file = wp_tempnam( $key ) . '.' . $format;
	'png' === $format ? imagepng( $im, $file ) : imagejpeg( $im, $file, 82 );
	imagedestroy( $im );

	return $file;
}

/** Attachment ids by key. Reuses what an earlier run imported. */
function evpx_media_attachments(): array {
	$ids = array();

	foreach ( evpx_media_specs() as $key => $spec ) {
		$existing = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_evpx_fixture', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		if ( $existing ) {
			$ids[ $key ] = (int) $existing[0];
			continue;
		}

		$id = media_handle_sideload(
			array(
				'name'     => 'evpx-fixture-' . $key . '.' . $spec[4],
				'tmp_name' => evpx_media_file( $key, $spec ),
			),
			0,
			'EVPX fixture: ' . $key
		);

		if ( is_wp_error( $id ) ) {
			fwrite( STDERR, $key . ': ' . $id->get_error_message() . "\n" );
			exit( 1 );
		}

		update_post_meta( $id, '_wp_attachment_image_alt', $spec[5] );
		update_post_meta( $id, '_evpx_fixture', $key );
		$ids[ $key ] = (int) $id;
	}

	return $ids;
}

// Start clean: an earlier run's pages would otherwise show up among the "latest" articles.
foreach ( get_posts(
	array(
		'post_type'   => 'any',
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
		'meta_key'    => '_evpx_fixture_page', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	)
) as $old_id ) {
	wp_delete_post( $old_id, true );
}

$article = (string) file_get_contents( $args[0] ?? '/tmp/demo-article.txt' );
$media   = evpx_media_attachments();

// A full row of pictures for Related Articles: every published post gets a featured image.
foreach ( get_posts(
	array(
		'post_type'   => 'post',
		'post_status' => 'publish',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
) as $post_id ) {
	set_post_thumbnail( $post_id, $media['thumb'] );
}

// Put the pictures into the demo article: the hero, the first two sections (one each way round),
// an icon on every scenario card, and the media variant of the CTA.
$nodes   = evpx_test_native_nodes( $article );
$section = 0;
foreach ( $nodes as $index => $node ) {
	$content = &$nodes[ $index ]['properties']['content'];

	switch ( $node['class'] ) {
		case 'EVPX\\Hero':
			$content['media']['media'] = evpx_test_wpmedia( $media['hero'] );
			break;

		case 'EVPX\\Section':
			if ( $section < 2 ) {
				$content['media']['media']   = evpx_test_wpmedia( $media['section'] );
				$content['layout']['layout'] = 0 === $section ? 'media-right' : 'media-left';
			}
			++$section;
			break;

		case 'EVPX\\ScenarioCards':
			foreach ( array_keys( $content['items']['rows'] ) as $row ) {
				$content['items']['rows'][ $row ]['icon'] = evpx_test_wpmedia( $media['icon'] );
			}
			break;

		case 'EVPX\\Cta':
			$content['visual']['variant'] = 'media';
			$content['media']['media']    = evpx_test_wpmedia( $media['cta'] );
			break;
	}

	unset( $content );
}

// 1. Shortcodes, in a post filed like the demo article so its siblings are the related articles.
$demo       = get_posts(
	array(
		'post_type'   => 'post',
		'title'       => 'Choosing AC or DC Charging for Your Site',
		'numberposts' => 1,
		'fields'      => 'ids',
	)
);
$categories = $demo ? wp_get_post_categories( (int) $demo[0] ) : array();

$post = wp_insert_post(
	array(
		'post_title'    => 'Media check (shortcodes)',
		'post_status'   => 'publish',
		'post_type'     => 'post',
		'post_content'  => implode( "\n\n", array_map( 'evpx_test_shortcode', $nodes ) ),
		'post_category' => $categories,
	)
);
set_post_thumbnail( $post, $media['thumb'] );

// 2. Native elements, on a page designed in Breakdance.
$native = wp_insert_post(
	array(
		'post_title'   => 'Media check (native Breakdance elements)',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	)
);
\Breakdance\Data\set_meta( $native, '_breakdance_data', array( 'tree_json_string' => wp_json_encode( evpx_test_tree( $nodes, true ) ) ) );

// 3. A mixed Related Articles row: one hand-picked article with a featured image, one without.
$with    = get_posts(
	array(
		'post_type'   => 'post',
		'post_status' => 'publish',
		'numberposts' => 1,
		'fields'      => 'ids',
		'orderby'     => 'ID',
		'order'       => 'ASC',
	)
)[0];
// Dated two years back so it never lands among the "latest" articles the other pages list.
$without = wp_insert_post(
	array(
		'post_title'    => 'Media check: an article with no featured image',
		'post_status'   => 'publish',
		'post_type'     => 'post',
		'post_content'  => 'Left without a featured image on purpose.',
		'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '-2 years' ) ),
		'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-2 years' ) ),
	)
);
$mixed   = wp_insert_post(
	array(
		'post_title'   => 'Media check (mixed related row)',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '[evpx_related source="manual" post_ids="' . $with . ',' . $without . '" count="2"]',
	)
);

// 4. A hero and a CTA over the brightest picture the widgets should be asked to carry.
$bright = wp_insert_post(
	array(
		'post_title'   => 'Media check (type over a bright picture)',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '[evpx_hero title="Choosing AC or DC Charging for Your Site" excerpt="The right charging technology depends less on what a charger can do and more on how people use your site." author="EV Charging Experience Editorial" date="September 2026" reading_time="7 min read" media="' . $media['bright'] . '" cta_label="Jump to the decision framework" cta_url="#decision" title_tag="h2" progress_bar="false" animate="false"]'
			. "\n\n"
			. '[evpx_cta variant="media" media="' . $media['bright'] . '" eyebrow="Next step" title="Plan the charging system around how your site actually works." body="Every recommendation starts from the same question: how long do vehicles actually stay?" button_label="Start the conversation" button_url="#contact" animate="false"]',
	)
);

foreach ( array( $post, $native, $without, $mixed, $bright ) as $created_id ) {
	update_post_meta( $created_id, '_evpx_fixture_page', '1' );
}

echo $post . ' ' . $native . ' ' . $mixed . ' ' . $bright . "\n";
