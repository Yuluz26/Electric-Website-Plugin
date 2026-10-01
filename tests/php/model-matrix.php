<?php
/**
 * The charging model's answers for a grid of cars, chargers and dwell times, as JSON on stdout. The browser test
 * (tests/playwright/explorer-qa.mjs) computes the same grid with the script's copy of the model and compares: the
 * page a visitor sees before the script runs must be the one they see after.
 *
 *   php tests/php/model-matrix.php
 */

define( 'ABSPATH', __DIR__ );

require __DIR__ . '/../../src/Support/ChargingModel.php';

use EVPX\Support\ChargingModel;

// battery kWh, arrival charge 0-1, onboard AC kW, peak DC kW, kWh per 100 km
$cars    = array(
	array( 60, 0.2, 11, 150, 18 ),
	array( 82, 0.1, 7.4, 250, 20 ),
	array( 40, 0.5, 22, 60, 15 ),
	array( 100, 0.0, 11, 350, 22 ),
	array( 30, 0.9, 3, 20, 12 ),
);
$minutes = array( 15, 30, 45, 60, 120, 240, 480, 720 );
$rows    = array();

foreach ( $cars as $car ) {
	$model = new ChargingModel( ...$car );

	foreach ( array_keys( ChargingModel::CHARGERS ) as $charger ) {
		foreach ( $minutes as $m ) {
			$run    = $model->run( $charger, $m );
			$rows[] = array(
				'car'     => $car,
				'charger' => $charger,
				'minutes' => $m,
				'energy'  => $run['energy'],
				'soc'     => $run['soc'],
				'fullAt'  => $run['full_at'],
				'range'   => $model->range( $run['energy'] ),
			);
		}
	}
}

echo json_encode( $rows );
