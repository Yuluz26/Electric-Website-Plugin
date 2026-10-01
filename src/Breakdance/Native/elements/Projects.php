<?php
/**
 * The Breakdance element for the Projects widget (see Hero.php for why the class lives in the plain EVPX namespace).
 */

namespace EVPX;

use Breakdance\Elements\Element;
use EVPX\Breakdance\Native\NativeElement;
use EVPX\Elements\Element as Widget;
use EVPX\Elements\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Projects extends Element {
	use NativeElement;

	protected static function widget(): Widget {
		return new Widgets\Projects();
	}
}
