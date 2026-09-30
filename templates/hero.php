<?php
/**
 * @var string $category
 * @var string $title
 * @var string $title_tag h1|h2 (whitelisted by the control's options)
 * @var string $excerpt HTML, already wpautop'd + kses'd.
 * @var string $author
 * @var string $date
 * @var string $reading_time
 * @var string $media_html
 * @var string $artwork_html Inline SVG from EVPX\Support\Art, or ''.
 * @var string $cta_label
 * @var string $cta_url
 * @var string $visual_mode
 * @var bool   $animate
 * @var bool   $progress_bar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_meta = $author || $date || $reading_time;
?>
<?php if ( $progress_bar ) : ?>
	<div class="evpx-root evpx-progress" aria-hidden="true"><span class="evpx-progress__fill"></span></div>
<?php endif; ?>
<header
	class="evpx-root alignfull evpx-hero<?php echo $media_html ? ' evpx-hero--media' : ''; ?><?php echo $artwork_html ? ' evpx-hero--art' : ''; ?>"
	data-evpx-hero-mode="<?php echo esc_attr( $visual_mode ); ?>"
	data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>"
	data-evpx-spot
>
	<?php if ( $media_html ) : ?>
		<div class="evpx-hero__media"><?php echo $media_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?></div>
		<div class="evpx-hero__scrim" aria-hidden="true"></div>
	<?php else : ?>
		<?php // Without a picture the hero is a drawing surface: a faint blueprint grid, and below, the line that charges. ?>
		<div class="evpx-hero__grid" aria-hidden="true"></div>
		<?php if ( $artwork_html ) : ?>
			<div class="evpx-hero__visual" aria-hidden="true"><?php echo $artwork_html; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from EVPX\Support\Art */ ?></div>
		<?php endif; ?>
	<?php endif; ?>

	<?php // A soft light that follows a fine pointer across the hero (evpx.js writes --evpx-mx/--evpx-my). ?>
	<div class="evpx-hero__torch" aria-hidden="true"></div>

	<div class="evpx-container evpx-hero__inner">
		<div class="evpx-hero__content">
			<?php if ( $category ) : ?>
				<p class="evpx-eyebrow"><?php echo esc_html( $category ); ?></p>
			<?php endif; ?>

			<<?php echo esc_attr( tag_escape( $title_tag ) ); ?> class="evpx-heading evpx-hero__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( tag_escape( $title_tag ) ); ?>>

			<?php if ( $excerpt ) : ?>
				<div class="evpx-body evpx-hero__excerpt"><?php echo $excerpt; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpautop + wp_kses_post already applied */ ?></div>
			<?php endif; ?>

			<?php // The captions come from data-label (CSS), so each item's text stays just its value. ?>
			<?php if ( $has_meta ) : ?>
				<div class="evpx-hero__meta">
					<?php if ( $author ) : ?><span class="evpx-hero__meta-item" data-label="<?php echo esc_attr_x( 'Author', 'article byline caption', 'ev-charging-experience' ); ?>"><?php echo \EVPX\Support\Icons::svg( 'user' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?><span class="evpx-hero__meta-value"><?php echo esc_html( $author ); ?></span></span><?php endif; ?>
					<?php if ( $date ) : ?><span class="evpx-hero__meta-item" data-label="<?php echo esc_attr_x( 'Published', 'article date caption', 'ev-charging-experience' ); ?>"><?php echo \EVPX\Support\Icons::svg( 'calendar-blank' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?><span class="evpx-hero__meta-value"><?php echo esc_html( $date ); ?></span></span><?php endif; ?>
					<?php if ( $reading_time ) : ?><span class="evpx-hero__meta-item" data-label="<?php echo esc_attr_x( 'Reading time', 'article length caption', 'ev-charging-experience' ); ?>"><?php echo \EVPX\Support\Icons::svg( 'clock' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?><span class="evpx-hero__meta-value"><?php echo esc_html( $reading_time ); ?></span></span><?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $cta_label && $cta_url ) : ?>
				<a class="evpx-button evpx-button--primary evpx-hero__cta" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( $cta_label ); ?>
					<?php echo \EVPX\Support\Icons::svg( 'arrow-right', 'evpx-button__arrow' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<span class="evpx-hero__line" aria-hidden="true"></span>
</header>
