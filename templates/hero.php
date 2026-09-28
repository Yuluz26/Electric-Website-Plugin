<?php
/**
 * @var string $category
 * @var string $title
 * @var string $excerpt HTML, already wpautop'd + kses'd.
 * @var string $author
 * @var string $date
 * @var string $reading_time
 * @var string $media_html
 * @var string $cta_label
 * @var string $cta_url
 * @var string $visual_mode
 * @var bool   $animate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_meta = $author || $date || $reading_time;
?>
<header
	class="evpx-root alignfull evpx-hero"
	data-evpx-hero-mode="<?php echo esc_attr( $visual_mode ); ?>"
	data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>"
>
	<?php if ( $media_html ) : ?>
		<div class="evpx-hero__media"><?php echo $media_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?></div>
		<div class="evpx-hero__scrim" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="evpx-container evpx-hero__content">
		<?php if ( $category ) : ?>
			<p class="evpx-eyebrow"><?php echo esc_html( $category ); ?></p>
		<?php endif; ?>

		<h1 class="evpx-heading evpx-hero__title"><?php echo esc_html( $title ); ?></h1>

		<?php if ( $excerpt ) : ?>
			<div class="evpx-body evpx-hero__excerpt"><?php echo $excerpt; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
		<?php endif; ?>

		<?php if ( $has_meta ) : ?>
			<div class="evpx-hero__meta">
				<?php if ( $author ) : ?><span class="evpx-hero__meta-item"><?php echo esc_html( $author ); ?></span><?php endif; ?>
				<?php if ( $date ) : ?><span class="evpx-hero__meta-item"><?php echo esc_html( $date ); ?></span><?php endif; ?>
				<?php if ( $reading_time ) : ?><span class="evpx-hero__meta-item"><?php echo esc_html( $reading_time ); ?></span><?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $cta_label && $cta_url ) : ?>
			<a class="evpx-button evpx-button--primary evpx-hero__cta" href="<?php echo esc_url( $cta_url ); ?>">
				<?php echo esc_html( $cta_label ); ?>
				<span class="evpx-button__arrow" aria-hidden="true">&rarr;</span>
			</a>
		<?php endif; ?>
	</div>
</header>
