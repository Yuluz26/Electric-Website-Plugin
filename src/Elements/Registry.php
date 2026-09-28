<?php

namespace EVPX\Elements;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Registry {

	/** @return Element[] */
	public function all(): array {
		return array(
			new Widgets\Hero(),
			new Widgets\Section(),
			new Widgets\Comparison(),
			new Widgets\ScenarioCards(),
			new Widgets\ScenarioCard(),
			new Widgets\TechnicalFlow(),
			new Widgets\Faq(),
			new Widgets\FaqItem(),
			new Widgets\DecisionFactors(),
			new Widgets\DecisionFactor(),
			new Widgets\RelatedArticles(),
			new Widgets\Cta(),
		);
	}

	public function register(): void {
		add_filter( 'block_categories_all', array( $this, 'registerBlockCategory' ) );

		foreach ( $this->all() as $element ) {
			$element->register();
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $categories
	 * @return array<int, array<string, mixed>>
	 */
	public function registerBlockCategory( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'ev-charging',
				'title' => __( 'EV Charging Experience', 'ev-charging-experience' ),
			)
		);

		return $categories;
	}

	/**
	 * Data the generic block-editor JS needs to render every EV block's
	 * Inspector controls from one shared component, instead of hand-writing
	 * a bespoke edit() for each of the 9 elements.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function editorSchema(): array {
		$schema = array();

		foreach ( $this->all() as $element ) {
			$schema[] = array(
				'name'            => $element->blockName(),
				'title'           => $element->title(),
				'controls'        => $element->controls(),
				'allowedChildren' => $element->allowedChildren(),
			);
		}

		return $schema;
	}
}
