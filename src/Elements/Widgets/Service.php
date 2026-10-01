<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\Icons;
use EVPX\Support\Lines;
use EVPX\Support\Scene;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One panel of Services. */
final class Service extends Element {

	public function slug(): string {
		return 'service';
	}

	public function title(): string {
		return __( 'EV Service', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'title', 'label' => __( 'Title', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'summary', 'label' => __( 'Summary', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'points', 'label' => __( 'Points, one per line', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'link_label', 'label' => __( 'Link label', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'link_url', 'label' => __( 'Link address', 'ev-charging-experience' ), 'type' => 'url', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'symbol', 'label' => __( 'Icon', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'visual', 'default' => 'auto', 'options' => Icons::options() ),
			array( 'key' => 'scene', 'label' => __( 'Scene behind the panel (used when there is no picture)', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'media', 'default' => 'station', 'options' => Scene::options() ),
			array( 'key' => 'media', 'label' => __( 'Picture behind the panel', 'ev-charging-experience' ), 'type' => 'image', 'group' => 'media', 'default' => 0 ),
		);
	}

	public function sampleRows(): array {
		return array(
			array( 'title' => __( 'Site design', 'ev-charging-experience' ), 'summary' => __( 'We start from how long vehicles stay and what the supply can carry, then choose the chargers.', 'ev-charging-experience' ), 'points' => "Dwell-time and demand study\nGrid capacity and load management\nLayout, permits and phasing", 'scene' => 'grid' ),
			array( 'title' => __( 'Installation', 'ev-charging-experience' ), 'summary' => __( 'Trenching, switchgear, chargers and commissioning, delivered as one job.', 'ev-charging-experience' ), 'points' => "AC wall boxes and pedestals\nDC fast charging cabinets\nCommissioning and handover", 'scene' => 'cabinet' ),
			array( 'title' => __( 'Operations', 'ev-charging-experience' ), 'summary' => __( 'Monitoring, billing and support, so the chargers are working when a driver arrives.', 'ev-charging-experience' ), 'points' => "Remote monitoring\nBilling and access control\nMaintenance and support", 'scene' => 'highway' ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		static $n = 0;
		++$n;

		return $this->view(
			'service',
			array(
				'uid'        => 'evpx-service-' . $n,
				'title'      => $atts['title'],
				'summary'    => $atts['summary'],
				'points'     => array_map( static fn( $pair ) => $pair[0], Lines::pairs( $atts['points'], 6 ) ),
				'link_label' => $atts['link_label'],
				'link_url'   => $atts['link_url'] ? Lines::url( $atts['link_url'] ) : '',
				'symbol'     => Icons::resolve( (string) $atts['symbol'], $atts['title'], 'charging-station' ),
				'media_html' => $atts['media'] ? $this->image( (int) $atts['media'], 'large', array( 'class' => 'evpx-service__image' ) ) : '',
				'scene_html' => $atts['media'] ? '' : Scene::render( $atts['scene'] ),
			)
		);
	}
}
