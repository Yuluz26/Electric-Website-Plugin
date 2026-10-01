<?php
/**
 * @var string   $eyebrow
 * @var string   $title     Escaped, with *word* already turned into <em>.
 * @var string   $title_tag h1|h2
 * @var string   $lede
 * @var string[] $cta       [label, url]
 * @var string[] $cta2      [label, url]
 * @var array    $facts     See Stage::facts().
 * @var string   $photo     <img>, or ''.
 * @var string   $scene_key
 * @var string   $scene     Inline SVG from Support\Scene, or ''.
 * @var string   $height    full|tall|compact
 * @var string   $overlay   light|medium|deep
 * @var bool     $animate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading_tag = tag_escape( $title_tag );
?>
<header
	class="evpx-root alignfull evpx-stage evpx-stage--<?php echo esc_attr( $height ); ?><?php echo $photo ? ' evpx-stage--photo' : ''; ?>"
	data-evpx-theme="dark"
	data-evpx-stage
	data-evpx-scene="<?php echo esc_attr( $photo ? 'photo' : $scene_key ); ?>"
	data-evpx-overlay="<?php echo esc_attr( $overlay ); ?>"
	data-evpx-animate="<?php echo $animate ? '1' : '0'; ?>"
>
	<div class="evpx-stage__backdrop" aria-hidden="true">
		<?php if ( $photo ) : ?>
			<div class="evpx-stage__photo"><?php echo $photo; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output */ ?></div>
		<?php elseif ( $scene ) : ?>
			<div class="evpx-stage__scene"><?php echo $scene; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from EVPX\Support\Scene */ ?></div>
		<?php else : ?>
			<div class="evpx-stage__grid"></div>
		<?php endif; ?>
		<canvas class="evpx-stage__fx"></canvas>
		<div class="evpx-stage__grade"></div>
		<div class="evpx-stage__grain"></div>
	</div>

	<div class="evpx-container evpx-stage__inner">
		<?php if ( $eyebrow ) : ?><p class="evpx-eyebrow evpx-stage__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>

		<<?php echo $heading_tag; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag_escape() */ ?> class="evpx-heading evpx-stage__title"><?php echo $title; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html() first, then the only tag we add */ ?></<?php echo $heading_tag; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag_escape() */ ?>>

		<?php if ( $lede ) : ?><p class="evpx-stage__lede"><?php echo esc_html( $lede ); ?></p><?php endif; ?>

		<?php if ( ( $cta[0] && $cta[1] ) || ( $cta2[0] && $cta2[1] ) ) : ?>
			<div class="evpx-stage__actions">
				<?php if ( $cta[0] && $cta[1] ) : ?>
					<a class="evpx-button evpx-button--primary" href="<?php echo esc_url( $cta[1] ); ?>"><?php echo esc_html( $cta[0] ); ?><?php echo \EVPX\Support\Icons::svg( 'arrow-right', 'evpx-button__arrow' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG from the plugin's own icon set */ ?></a>
				<?php endif; ?>
				<?php if ( $cta2[0] && $cta2[1] ) : ?>
					<a class="evpx-button evpx-button--secondary" href="<?php echo esc_url( $cta2[1] ); ?>"><?php echo esc_html( $cta2[0] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $facts ) : ?>
		<ul class="evpx-container evpx-stage__facts" role="list">
			<?php foreach ( $facts as $fact ) : ?>
				<li class="evpx-stage__fact">
					<span class="evpx-stage__value"<?php echo '' !== $fact['number'] ? ' data-count="' . esc_attr( $fact['number'] ) . '"' : ''; ?>><?php echo esc_html( $fact['prefix'] ); ?><span class="evpx-stage__num"><?php echo esc_html( '' !== $fact['number'] ? $fact['number'] : $fact['value'] ); ?></span><?php echo esc_html( $fact['suffix'] ); ?></span>
					<?php if ( $fact['label'] ) : ?><span class="evpx-stage__label"><?php echo esc_html( $fact['label'] ); ?></span><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<span class="evpx-stage__line" aria-hidden="true"></span>
</header>
