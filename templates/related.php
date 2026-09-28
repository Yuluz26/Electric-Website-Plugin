<?php
/**
 * @var string                         $eyebrow
 * @var string                         $heading
 * @var string                         $count
 * @var bool                           $animate
 * @var array<int, array<string,string>> $items
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only reserve image space when at least one article has an image; a row of
// identical empty placeholders reads as broken, a text-only list reads as intended.
$has_any_image = (bool) array_filter( array_column( $items, 'image_html' ) );
?>
<section class="evpx-root alignfull evpx-related">
	<div class="evpx-container">
		<?php if ( $eyebrow || $heading ) : ?>
			<div class="evpx-related__intro" <?php echo $animate ? 'data-evpx-reveal' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string ?>>
				<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
				<?php if ( $heading ) : ?><h2 class="evpx-heading evpx-related__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			</div>
		<?php endif; ?>

		<ul class="evpx-related__list evpx-related__list--cols-<?php echo esc_attr( $count ); ?>" role="list">
			<?php foreach ( $items as $item ) : ?>
				<li class="evpx-related__item">
					<?php if ( $item['image_html'] ) : ?>
						<div class="evpx-related__media"><?php echo $item['image_html']; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_the_post_thumbnail() output */ ?></div>
					<?php elseif ( $has_any_image ) : ?>
						<div class="evpx-related__media evpx-related__media--empty evpx-surface--recessed" aria-hidden="true"></div>
					<?php endif; ?>

					<div class="evpx-related__body">
						<?php if ( $item['category'] ) : ?>
							<p class="evpx-eyebrow evpx-related__category"><?php echo esc_html( $item['category'] ); ?></p>
						<?php endif; ?>

						<h3 class="evpx-related__title">
							<a class="evpx-related__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
						</h3>

						<?php if ( $item['excerpt'] ) : ?>
							<p class="evpx-related__excerpt"><?php echo esc_html( $item['excerpt'] ); ?></p>
						<?php endif; ?>

						<time class="evpx-related__date" datetime="<?php echo esc_attr( $item['date_iso'] ); ?>"><?php echo esc_html( $item['date'] ); ?></time>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
