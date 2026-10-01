<?php
/** Editors-only note shown when a Related Articles widget has nothing to list. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="evpx-root alignfull evpx-related evpx-related--empty">
	<div class="evpx-container">
		<p class="evpx-related__empty"><?php esc_html_e( 'Related articles will appear here once other published posts match. Visitors see nothing until then.', 'ev-charging-experience' ); ?></p>
	</div>
</section>
