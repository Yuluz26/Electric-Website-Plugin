<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Icons;

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
			array( 'key' => 'symbol', 'label' => __( 'Icon', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'visual', 'default' => 'auto', 'options' => Icons::options() ),
			array( 'key' => 'icon', 'label' => __( 'Own icon image (replaces the icon)', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
			array( 'key' => 'requirement', 'label' => __( 'Key requirement', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'recommendation', 'label' => __( 'Recommended charging approach', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta_label', 'label' => __( 'CTA label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'cta_url', 'label' => __( 'CTA URL', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
		);
	}

	public function sampleRows(): array {
		return array(
			array(
				'scenario'       => __( 'Workplace', 'ev-charging-experience' ),
				'title'          => __( 'Employees, all day', 'ev-charging-experience' ),
				'description'    => __( 'Cars sit for 6–9 hours, so AC charging fills most batteries at a fraction of the cost of DC.', 'ev-charging-experience' ),
				'requirement'    => __( 'Enough AC ports for shift overlap', 'ev-charging-experience' ),
				'recommendation' => __( 'AC across the lot', 'ev-charging-experience' ),
			),
			array(
				'scenario'       => __( 'Retail', 'ev-charging-experience' ),
				'title'          => __( 'Shoppers, under an hour', 'ev-charging-experience' ),
				'description'    => __( 'Short, unpredictable dwell time: AC cannot add meaningful range in the time available.', 'ev-charging-experience' ),
				'requirement'    => __( 'Fast, unattended charging', 'ev-charging-experience' ),
				'recommendation' => __( 'DC fast charging', 'ev-charging-experience' ),
			),
			array(
				'scenario'       => __( 'Highway corridor', 'ev-charging-experience' ),
				'title'          => __( 'Trip charging', 'ev-charging-experience' ),
				'description'    => __( 'Drivers are travelling, not staying. Speed is the whole value of the stop.', 'ev-charging-experience' ),
				'requirement'    => __( 'High uptime, high power', 'ev-charging-experience' ),
				'recommendation' => __( 'DC fast charging', 'ev-charging-experience' ),
			),
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
				'symbol'         => Icons::resolve( (string) $atts['symbol'], $atts['scenario'] . ' ' . $atts['title'], 'charging-station' ),
				'requirement'    => $atts['requirement'],
				'recommendation' => $atts['recommendation'],
				'cta_label'      => $atts['cta_label'],
				'cta_url'        => $atts['cta_url'],
			)
		);
	}
}
