<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** One quotation, nested inside Quotes. */
final class Quote extends Element {

	public function slug(): string {
		return 'quote';
	}

	public function title(): string {
		return __( 'EV Quote', 'ev-charging-experience' );
	}

	public function controls(): array {
		return array(
			array( 'key' => 'quote', 'label' => __( 'Quotation', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'name', 'label' => __( 'Name', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
			array( 'key' => 'role', 'label' => __( 'Role and organisation', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => '' ),
		);
	}

	public function sampleRows(): array {
		return array(
			array( 'quote' => __( 'Replace this with something a customer actually said about the work. A sentence or two is plenty.', 'ev-charging-experience' ), 'name' => __( 'Customer name', 'ev-charging-experience' ), 'role' => __( 'Role, organisation', 'ev-charging-experience' ) ),
			array( 'quote' => __( 'A second quotation, ideally about something different: the install, the running of it, the support.', 'ev-charging-experience' ), 'name' => __( 'Customer name', 'ev-charging-experience' ), 'role' => __( 'Role, organisation', 'ev-charging-experience' ) ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		return $this->view(
			'quote',
			array(
				'quote' => $atts['quote'],
				'name'  => $atts['name'],
				'role'  => $atts['role'],
			)
		);
	}
}
