<?php

namespace EVPX\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The charging model behind the explorer widget: what a car adds to its battery while it is parked, on a given
 * charger. It is deliberately simple and it is illustrative, not a specification of any car.
 *
 * - AC: the charger gives its rating, but the car's onboard charger caps what it accepts (the reason a 22 kW
 *   wall box mostly delivers 11 kW or less).
 * - DC: the car accepts up to a peak, in full up to half full, then less and less: down to 35% of the peak at
 *   80%, and 10% at 100%. The charger gives the smaller of its rating and what the car accepts.
 *
 * Time is stepped a whole minute at a time. assets/js/evpx.js carries the same model, operation for operation, so
 * the page a visitor sees before the script runs and the one after agree; tests/playwright/explorer-qa.mjs holds
 * the two to each other.
 */
final class ChargingModel {

	/** Longest dwell the explorer offers, in minutes. */
	public const MAX_MINUTES = 720;

	/** Charger key => [ current type, rating in kW ]. */
	public const CHARGERS = array(
		'ac-7'   => array( 'ac', 7 ),
		'ac-22'  => array( 'ac', 22 ),
		'dc-50'  => array( 'dc', 50 ),
		'dc-150' => array( 'dc', 150 ),
		'dc-350' => array( 'dc', 350 ),
	);

	/** @var float */
	private $battery;

	/** @var float */
	private $start;

	/** @var float */
	private $ac_limit;

	/** @var float */
	private $dc_peak;

	/** @var float */
	private $consumption;

	/**
	 * @param float $battery     Usable battery, kWh.
	 * @param float $start       State of charge on arrival, 0 to 1.
	 * @param float $ac_limit    Most the car's onboard charger takes from AC, kW.
	 * @param float $dc_peak     Most the car takes from DC, kW.
	 * @param float $consumption kWh the car uses per 100 km.
	 */
	public function __construct( float $battery, float $start, float $ac_limit, float $dc_peak, float $consumption ) {
		$this->battery     = $battery;
		$this->start       = $start;
		$this->ac_limit    = $ac_limit;
		$this->dc_peak     = $dc_peak;
		$this->consumption = $consumption;
	}

	/** The share of its peak DC power the car accepts at a state of charge. */
	public static function acceptance( float $soc ): float {
		if ( $soc <= 0.5 ) {
			return 1.0;
		}

		if ( $soc <= 0.8 ) {
			return 1 - ( $soc - 0.5 ) / 0.3 * 0.65;
		}

		return 0.35 - ( $soc - 0.8 ) / 0.2 * 0.25;
	}

	/** kW the charger delivers to this car at a state of charge. */
	public function power( string $charger, float $soc ): float {
		list( $type, $rating ) = self::CHARGERS[ $charger ];

		if ( 'ac' === $type ) {
			return min( (float) $rating, $this->ac_limit );
		}

		return min( (float) $rating, $this->dc_peak * self::acceptance( $soc ) );
	}

	/**
	 * A whole number of minutes on a charger.
	 *
	 * @return array{energy:float,soc:float,full_at:?int} kWh added, the state of charge reached, and the minute the
	 *                                                    battery filled (null if it did not).
	 */
	public function run( string $charger, int $minutes ): array {
		$soc     = $this->start;
		$energy  = 0.0;
		$full_at = null;

		for ( $m = 0; $m < $minutes; $m++ ) {
			$added   = min( $this->power( $charger, $soc ) / 60, ( 1 - $soc ) * $this->battery );
			$energy += $added;
			$soc    += $added / $this->battery;

			if ( 1 - $soc < 1e-9 ) {
				$full_at = $m + 1;
				break;
			}
		}

		return array(
			'energy'  => $energy,
			'soc'     => $soc,
			'full_at' => $full_at,
		);
	}

	/** Kilometres of range a quantity of energy is worth. */
	public function range( float $energy ): float {
		return $energy / $this->consumption * 100;
	}

	/** The most a session can add: from the arrival charge to full, kWh. */
	public function headroom(): float {
		return ( 1 - $this->start ) * $this->battery;
	}
}
