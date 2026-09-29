<?php
/**
 * The Breakdance element for the ScenarioCards widget.
 *
 * The class name is the element slug Breakdance stores in a page's tree ("EVPX\ScenarioCards"), and it
 * expects exactly Namespace\Name, which is why this lives in the plain EVPX namespace rather than
 * under EVPX\Breakdance\Native. Renaming it orphans the element on every page that uses it
 * (Breakdance then shows its "missing element" placeholder), so treat the name like a public API.
 * Loaded by NativeElements::declareElements(), only when Breakdance is active.
 */

namespace EVPX;

use Breakdance\Elements\Element;
use EVPX\Breakdance\Native\NativeElement;
use EVPX\Elements\Element as Widget;
use EVPX\Elements\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ScenarioCards extends Element {
	use NativeElement;

	protected static function widget(): Widget {
		return new Widgets\ScenarioCards();
	}
}
