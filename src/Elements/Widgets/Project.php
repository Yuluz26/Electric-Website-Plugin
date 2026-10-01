<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Lines;
use EVPX\Support\Scene;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One project card, nested inside Projects. */
final class Project extends Element {

	public function slug(): string {
		return 'project';
	}

	public function title(): string {
		return __( 'EV Project', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'name', 'label' => __( 'Name', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'place', 'label' => __( 'Place', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'sector', 'label' => __( 'Sector label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'summary', 'label' => __( 'Summary', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'metrics', 'label' => __( 'Figures, one per line as "value | label" (up to three)', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'url', 'label' => __( 'Link address', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'scene', 'label' => __( 'Scene (used when there is no photograph)', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'media', 'default' => 'station', 'options' => Scene::options() ),
			array( 'key' => 'media', 'label' => __( 'Photograph', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
		);
	}

	public function sampleRows(): array {
		return array(
			array( 'name' => __( 'Regional distribution centre', 'ev-charging-experience' ), 'place' => __( 'Sample site', 'ev-charging-experience' ), 'sector' => __( 'Fleet depot', 'ev-charging-experience' ), 'summary' => __( 'Overnight AC for the vans, two DC bays for the trucks that turn around inside the hour.', 'ev-charging-experience' ), 'metrics' => "48 | Bays\n1.2 MW | Peak load\n2 | Months on site", 'scene' => 'station' ),
			array( 'name' => __( 'Motorway service area', 'ev-charging-experience' ), 'place' => __( 'Sample site', 'ev-charging-experience' ), 'sector' => __( 'Highway corridor', 'ev-charging-experience' ), 'summary' => __( 'Twelve high-power bays behind a new grid connection, open around the clock.', 'ev-charging-experience' ), 'metrics' => "12 | Bays\n350 kW | Per bay\n24/7 | Open", 'scene' => 'highway' ),
			array( 'name' => __( 'Hotel and conference centre', 'ev-charging-experience' ), 'place' => __( 'Sample site', 'ev-charging-experience' ), 'sector' => __( 'Hospitality', 'ev-charging-experience' ), 'summary' => __( 'Slow charging where guests stay overnight, one fast charger at the entrance.', 'ev-charging-experience' ), 'metrics' => "60 | Bays\n22 kW | AC\n1 | DC bay", 'scene' => 'cabinet' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$metrics = array();
		foreach ( Lines::pairs( $atts['metrics'], 3 ) as $pair ) {
			$metrics[] = array( $pair[0], $pair[1] );
		}

		return $this->view(
			'project',
			array(
				'name'       => $atts['name'],
				'place'      => $atts['place'],
				'sector'     => $atts['sector'],
				'summary'    => $atts['summary'],
				'metrics'    => $metrics,
				'url'        => $atts['url'] ? Lines::url( $atts['url'] ) : '',
				'media_html' => $atts['media'] ? $this->image( (int) $atts['media'], 'large', array( 'class' => 'evpx-project__image' ) ) : '',
				'scene_html' => $atts['media'] ? '' : Scene::render( $atts['scene'] ),
			)
		);
	}
}
