<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Lines;
use EVPX\Support\Scene;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page hero: full width, cinematic. A photograph if the page has one, else a layered scene (Support\Scene)
 * that moves against itself; over either, the headline, two buttons and a strip of figures that count up.
 */
final class Stage extends Element {

	public function slug(): string {
		return 'stage';
	}

	public function title(): string {
		return __( 'EV Page Hero', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'title', 'label' => __( 'Headline (put *asterisks* around a word to set it in copper)', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Power that arrives *before* you do', 'ev-charging-experience' ) ),
			array( 'key' => 'lede', 'label' => __( 'Summary', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta_label', 'label' => __( 'Button label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta_url', 'label' => __( 'Button URL', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta2_label', 'label' => __( 'Second button label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta2_url', 'label' => __( 'Second button URL', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'facts', 'label' => __( 'Figures, one per line as "value | label" (a number counts up)', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'media', 'label' => __( 'Photograph (replaces the scene)', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
			array( 'key' => 'media_alt', 'label' => __( 'Photograph alt text', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'media', 'default' => '' ),
			array(
				'key'     => 'scene',
				'label'   => __( 'Scene (shown when there is no photograph)', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'visual',
				'default' => 'station',
				'options' => Scene::options(),
			),
			array(
				'key'     => 'height',
				'label'   => __( 'Height', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'layout',
				'default' => 'tall',
				'options' => array(
					'full'    => __( 'Fills the window', 'ev-charging-experience' ),
					'tall'    => __( 'Tall', 'ev-charging-experience' ),
					'compact' => __( 'Compact (an inner page)', 'ev-charging-experience' ),
				),
			),
			array(
				'key'     => 'overlay',
				'label'   => __( 'Darkening over a photograph', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'visual',
				'default' => 'medium',
				'options' => array(
					'light'  => __( 'Light', 'ev-charging-experience' ),
					'medium' => __( 'Medium', 'ev-charging-experience' ),
					'deep'   => __( 'Deep (busy or bright photographs)', 'ev-charging-experience' ),
				),
			),
			array( 'key' => 'animate', 'label' => __( 'Move: parallax, particles, counting figures', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
			array(
				'key'     => 'title_tag',
				'label'   => __( 'Headline level (h1 unless the theme already prints the page title)', 'ev-charging-experience' ),
				'type'    => 'select',
				'group'   => 'advanced',
				'default' => 'h1',
				'options' => array(
					'h1' => __( 'h1', 'ev-charging-experience' ),
					'h2' => __( 'h2', 'ev-charging-experience' ),
				),
			),
		);
	}

	public function sampleAtts(): array {
		return array(
			'eyebrow' => __( 'Charging, built around your site', 'ev-charging-experience' ),
			'lede'    => __( 'We plan, install and run charging for sites that need it to work every day.', 'ev-charging-experience' ),
			'facts'   => "24/7 | Monitored\n150 kW | Per bay, DC\n99% | Uptime target",
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$photo = '';
		if ( ! empty( $atts['media'] ) ) {
			$attr = array( 'class' => 'evpx-stage__image' );
			if ( '' !== $atts['media_alt'] ) {
				$attr['alt'] = $atts['media_alt'];
			}
			$photo = $this->image( (int) $atts['media'], 'full', $attr );
		}

		$title = preg_replace( '/\*([^*]+)\*/', '<em>$1</em>', esc_html( $atts['title'] ) );

		return $this->view(
			'stage',
			array(
				'eyebrow'   => $atts['eyebrow'],
				'title'     => (string) $title,
				'title_tag' => $atts['title_tag'],
				'lede'      => $atts['lede'],
				'cta'       => array( $atts['cta_label'], $atts['cta_url'] ),
				'cta2'      => array( $atts['cta2_label'], $atts['cta2_url'] ),
				'facts'     => self::facts( $atts['facts'] ),
				'photo'     => $photo,
				'scene_key' => $atts['scene'],
				'scene'     => '' === $photo ? Scene::render( $atts['scene'] ) : '',
				'height'    => $atts['height'],
				'overlay'   => $atts['overlay'],
				'animate'   => $atts['animate'],
			)
		);
	}

	/**
	 * "150 kW | Per bay" lines as value, label, and the pieces of the value a counter needs.
	 *
	 * @return array<int, array{value: string, label: string, prefix: string, number: string, suffix: string}>
	 */
	public static function facts( string $text ): array {
		$out = array();

		foreach ( Lines::pairs( $text, 4 ) as $pair ) {
			$out[] = array_merge(
				array(
					'value' => $pair[0],
					'label' => $pair[1],
				),
				Lines::figure( $pair[0] )
			);
		}

		return $out;
	}
}
