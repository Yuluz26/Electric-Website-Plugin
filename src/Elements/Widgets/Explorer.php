<?php

namespace EVPX\Elements\Widgets;

use EVPX\Elements\Element;
use EVPX\Support\ChargingModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The interactive: how long the car stays, and which charger, and what reaches the battery. The page arrives with
 * the default answer already worked out (the same model as the script's), so it reads without JavaScript; the
 * script then makes the sliders and the chart live.
 */
final class Explorer extends Element {

	/** Dwell times the presets and the default can be, in minutes. */
	private const DWELLS = array( 30, 60, 120, 240, 480 );

	public function slug(): string {
		return 'explorer';
	}

	public function title(): string {
		return __( 'EV Charging Explorer', 'ev-charging-experience' );
	}

	public function controls(): array {
		$dwells = array();
		foreach ( self::DWELLS as $minutes ) {
			$dwells[ (string) $minutes ] = self::timeLabel( $minutes, self::units() );
		}

		return array(
			self::spacingControl(),
			self::anchorControl(),
			array( 'key' => 'eyebrow', 'label' => __( 'Eyebrow', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'Try it', 'ev-charging-experience' ) ),
			array( 'key' => 'heading', 'label' => __( 'Heading', 'ev-charging-experience' ), 'type' => 'text', 'group' => 'content', 'default' => __( 'What does your dwell time buy?', 'ev-charging-experience' ) ),
			array( 'key' => 'intro', 'label' => __( 'Introduction', 'ev-charging-experience' ), 'type' => 'textarea', 'group' => 'content', 'default' => __( 'Set how long a car stays, then try each charger. Watch where more power stops helping.', 'ev-charging-experience' ) ),
			array( 'key' => 'dwell', 'label' => __( 'Starting dwell time', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'content', 'default' => '120', 'options' => $dwells ),
			array( 'key' => 'charger', 'label' => __( 'Starting charger', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'content', 'default' => 'ac-22', 'options' => array(
				'ac-7'   => __( 'AC 7 kW', 'ev-charging-experience' ),
				'ac-22'  => __( 'AC 22 kW', 'ev-charging-experience' ),
				'dc-50'  => __( 'DC 50 kW', 'ev-charging-experience' ),
				'dc-150' => __( 'DC 150 kW', 'ev-charging-experience' ),
				'dc-350' => __( 'DC 350 kW', 'ev-charging-experience' ),
			) ),
			array( 'key' => 'battery', 'label' => __( 'Usable battery, kWh (20 to 150)', 'ev-charging-experience' ), 'type' => 'number', 'group' => 'content', 'default' => 60 ),
			array( 'key' => 'start_soc', 'label' => __( 'Charge on arrival, % (0 to 90)', 'ev-charging-experience' ), 'type' => 'number', 'group' => 'content', 'default' => 20 ),
			array( 'key' => 'ac_limit', 'label' => __( 'Car\'s onboard AC charger, kW (3 to 22)', 'ev-charging-experience' ), 'type' => 'number', 'group' => 'content', 'default' => 11 ),
			array( 'key' => 'dc_peak', 'label' => __( 'Car\'s peak DC power, kW (20 to 400)', 'ev-charging-experience' ), 'type' => 'number', 'group' => 'content', 'default' => 150 ),
			array( 'key' => 'consumption', 'label' => __( 'Car\'s consumption, kWh per 100 km (10 to 40)', 'ev-charging-experience' ), 'type' => 'number', 'group' => 'content', 'default' => 18 ),
			array( 'key' => 'unit', 'label' => __( 'Distance unit', 'ev-charging-experience' ), 'type' => 'select', 'group' => 'content', 'default' => 'km', 'options' => array(
				'km' => __( 'Kilometres', 'ev-charging-experience' ),
				'mi' => __( 'Miles', 'ev-charging-experience' ),
			) ),
			array( 'key' => 'animate', 'label' => __( 'Enable scroll reveal', 'ev-charging-experience' ), 'type' => 'toggle', 'group' => 'motion', 'default' => true ),
		);
	}

	public function render( array $atts, string $content = '' ): string {
		$battery     = self::clamp( $atts['battery'], 20, 150, 60 );
		$start       = self::clamp( $atts['start_soc'], 0, 90, 20 ) / 100;
		$ac_limit    = self::clamp( $atts['ac_limit'], 3, 22, 11 );
		$dc_peak     = self::clamp( $atts['dc_peak'], 20, 400, 150 );
		$consumption = self::clamp( $atts['consumption'], 10, 40, 18 );
		$miles       = 'mi' === $atts['unit'];
		$dwell       = (int) $atts['dwell'];
		$charger     = isset( ChargingModel::CHARGERS[ $atts['charger'] ] ) ? $atts['charger'] : 'ac-22';
		$model       = new ChargingModel( $battery, $start, $ac_limit, $dc_peak, $consumption );
		$units       = self::units();
		$distance    = $miles ? 'mi' : 'km';
		$per_km      = $miles ? 0.621371 : 1.0;

		$rows = array();
		foreach ( ChargingModel::CHARGERS as $key => $spec ) {
			$run    = $model->run( $key, $dwell );
			$rows[] = array(
				'key'      => $key,
				'type'     => strtoupper( $spec[0] ),
				'rating'   => $spec[1],
				'energy'   => self::number( $run['energy'] ),
				'ratio'    => $model->headroom() > 0 ? min( 1, $run['energy'] / $model->headroom() ) : 0,
				'selected' => $key === $charger,
			);
		}

		$run       = $model->run( $charger, $dwell );
		$rating    = ChargingModel::CHARGERS[ $charger ][1];
		$is_ac     = 'ac' === ChargingModel::CHARGERS[ $charger ][0];
		$delivered = min( (float) $rating, $is_ac ? $ac_limit : $dc_peak );

		$strings = array(
			'h'       => _x( 'h', 'unit: hours', 'ev-charging-experience' ),
			'min'     => _x( 'min', 'unit: minutes', 'ev-charging-experience' ),
			'main'    => __( 'In {time}, a {kw} charger adds about {energy}: {range} of range.', 'ev-charging-experience' ),
			'limited' => __( 'The car takes at most {limit}, so any power above that goes unused.', 'ev-charging-experience' ),
			'full'    => __( 'The battery is full after {full}; time beyond that adds nothing.', 'ev-charging-experience' ),
			'rated'   => __( '{kw} charger', 'ev-charging-experience' ),
		);

		$verdict = strtr(
			$strings['main'],
			array(
				'{time}'   => self::timeLabel( $dwell, $units ),
				'{kw}'     => self::withUnit( (string) $rating, 'kW' ),
				'{energy}' => self::withUnit( self::number( $run['energy'] ), 'kWh' ),
				'{range}'  => self::withUnit( self::number( $model->range( $run['energy'] ) * $per_km, 0 ), $distance ),
			)
		);

		if ( null !== $run['full_at'] ) {
			$verdict .= ' ' . strtr( $strings['full'], array( '{full}' => self::timeLabel( $run['full_at'], $units ) ) );
		} elseif ( $delivered < $rating ) {
			$verdict .= ' ' . strtr( $strings['limited'], array( '{limit}' => self::withUnit( self::number( $delivered, 0 ), 'kW' ) ) );
		}

		$note = strtr(
			/* translators: the numbers are the widget's settings: battery kWh, arrival charge %, onboard AC kW, peak DC kW, consumption kWh per 100 km. */
			__( 'Illustrative: a {battery} kWh battery arriving at {start}%, an onboard AC charger of {ac} kW, DC accepted up to {dc} kW and tapering above half full, {use} kWh per 100 km. Real cars vary.', 'ev-charging-experience' ),
			array(
				'{battery}' => self::number( $battery, 0 ),
				'{start}'   => self::number( $start * 100, 0 ),
				'{ac}'      => self::number( $ac_limit, 0 ),
				'{dc}'      => self::number( $dc_peak, 0 ),
				'{use}'     => self::number( $consumption, 0 ),
			)
		);

		$dwells = array();
		foreach ( self::DWELLS as $minutes ) {
			$dwells[ $minutes ] = self::timeLabel( $minutes, $units );
		}

		return $this->view(
			'explorer',
			array(
				'spacing'   => $atts['spacing'],
				'anchor'    => $atts['anchor'],
				'eyebrow'   => $atts['eyebrow'],
				'heading'   => $atts['heading'],
				'intro'     => $this->autop( $atts['intro'] ),
				'animate'   => $atts['animate'],
				'id'        => wp_unique_id( 'evpx-explorer-' ),
				'config'    => array(
					'battery'     => $battery,
					'start'       => $start,
					'acLimit'     => $ac_limit,
					'dcPeak'      => $dc_peak,
					'consumption' => $consumption,
					'unit'        => $distance,
					'dwell'       => $dwell,
					'charger'     => $charger,
				),
				'strings'   => $strings,
				'chargers'  => $rows,
				'dwells'    => $dwells,
				'dwell'     => $dwell,
				'dwell_pos' => (int) round( 100 * sqrt( max( 0, $dwell - 15 ) / 705 ) ),
				'dwell_text' => self::timeLabel( $dwell, $units ),
				'energy'    => self::number( $run['energy'] ),
				'range'     => self::number( $model->range( $run['energy'] ) * $per_km, 0 ),
				'distance'  => $distance,
				'soc_from'  => (int) round( $start * 100 ),
				'soc_to'    => (int) round( $run['soc'] * 100 ),
				'verdict'   => $verdict,
				'note'      => $note,
			)
		);
	}

	/** A figure and its unit, kept on one line. */
	private static function withUnit( string $figure, string $unit ): string {
		return $figure . "\u{00A0}" . $unit;
	}

	/** A number kept inside a range, or the fallback if it is not a number. */
	private static function clamp( $value, float $min, float $max, float $fallback ): float {
		return is_numeric( $value ) ? max( $min, min( $max, (float) $value ) ) : $fallback;
	}

	/** One decimal under 10, none from 10 up, unless told how many. */
	private static function number( float $value, ?int $decimals = null ): string {
		if ( null === $decimals ) {
			$decimals = $value < 10 ? 1 : 0;
		}

		return number_format_i18n( $value, $decimals );
	}

	/** @return array{h:string,min:string} */
	private static function units(): array {
		return array(
			'h'   => _x( 'h', 'unit: hours', 'ev-charging-experience' ),
			'min' => _x( 'min', 'unit: minutes', 'ev-charging-experience' ),
		);
	}

	/**
	 * "45 min", "2 h", "1 h 7 min": the same wording as the script's.
	 *
	 * @param array{h:string,min:string} $units
	 */
	private static function timeLabel( int $minutes, array $units ): string {
		$hours = intdiv( $minutes, 60 );
		$rest  = $minutes % 60;

		if ( 0 === $hours ) {
			return self::withUnit( (string) $rest, $units['min'] );
		}

		return self::withUnit( (string) $hours, $units['h'] ) . ( $rest ? ' ' . self::withUnit( (string) $rest, $units['min'] ) : '' );
	}
}
