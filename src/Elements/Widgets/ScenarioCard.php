<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One scenario card — only meaningful nested inside ScenarioCards. */
final class ScenarioCard extends Element {

	public function slug(): string {
		return 'scenario-card';
	}

	public function title(): string {
		return __( 'EV Scenario Card', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'scenario', 'label' => __( 'Scenario label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'title', 'label' => __( 'Title', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'description', 'label' => __( 'Description', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'icon', 'label' => __( 'Icon / media', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
			array( 'key' => 'requirement', 'label' => __( 'Key requirement', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'recommendation', 'label' => __( 'Recommended charging approach', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta_label', 'label' => __( 'CTA label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta_url', 'label' => __( 'CTA URL', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'scenario-card',
			array(
				'scenario'       => $atts['scenario'],
				'title'          => $atts['title'],
				'description'    => $this->autop( $atts['description'] ),
				'icon_html'      => $atts['icon'] ? $this->image( (int) $atts['icon'], 'thumbnail', array( 'class' => 'evpx-scenario-card__icon-image' ) ) : '',
				'requirement'    => $atts['requirement'],
				'recommendation' => $atts['recommendation'],
				'cta_label'      => $atts['cta_label'],
				'cta_url'        => $atts['cta_url'],
			)
		);
	}
}
