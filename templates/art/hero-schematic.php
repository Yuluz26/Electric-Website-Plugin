<?php
/**
 * The hero's drawing: a car on a charger, in section, on a receding floor. Line work in currentColor, light in
 * the neon token; the figures are a plausible session, not data. Everything that moves has a class the
 * stylesheet animates (evpx-art__draw, __pulse, __arc, __scan); as printed, it is finished and still.
 *
 * @var string $uid Unique per piece, for the ids the gradients, masks and filter need.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$u = esc_attr( $uid );

// Outlines used more than once: the body is filled, stroked, clipped to (the scan) and mirrored (the floor).
$body   = 'M238 360C226 346 226 318 234 298C244 270 292 244 340 230C372 221 420 220 452 228C486 236 520 258 546 276C556 283 570 290 600 296L650 304C676 309 690 322 692 342L690 360H630A40 40 0 0 0 550 360H370A40 40 0 0 0 290 360Z';
$roof   = 'M234 298C244 270 292 244 340 230C372 221 420 220 452 228C486 236 520 258 546 276C556 283 570 290 600 296';
$glass  = 'M268 290C282 268 314 250 352 240H446C478 246 510 266 530 284Z';
$cable  = 'M198 344C222 344 220 312 244 312';

// $u is the escaped uid, and the path data above is this file's own constants: nothing here is user input.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<svg class="evpx-art evpx-art--schematic" xmlns="http://www.w3.org/2000/svg" viewBox="74 0 688 540" preserveAspectRatio="xMidYMax meet" fill="none" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="<?php echo $u; ?>-body" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#232830"/>
			<stop offset=".55" stop-color="#14171c"/>
			<stop offset="1" stop-color="#0d0f13"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-glass" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0" stop-color="#fff" stop-opacity=".14"/>
			<stop offset=".6" stop-color="#fff" stop-opacity=".03"/>
			<stop offset="1" stop-color="#fff" stop-opacity=".07"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-rim" x1="0" y1="0" x2="1" y2="0">
			<stop offset="0" stop-color="#fff" stop-opacity=".25"/>
			<stop offset=".45" stop-color="#fff" stop-opacity="1"/>
			<stop offset="1" stop-color="#fff" stop-opacity=".55"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-horizon" x1="0" y1="0" x2="1" y2="0">
			<stop class="evpx-art__stop-neon" offset=".36" stop-opacity="0"/>
			<stop class="evpx-art__stop-neon" offset=".5" stop-opacity=".9"/>
			<stop class="evpx-art__stop-neon" offset=".62" stop-opacity=".9"/>
			<stop class="evpx-art__stop-neon" offset=".808" stop-opacity="0"/>
		</linearGradient>
		<radialGradient id="<?php echo $u; ?>-aura" gradientUnits="userSpaceOnUse" cx="470" cy="345" r="340">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".2"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<radialGradient id="<?php echo $u; ?>-pool" cx=".5" cy=".5" r=".5">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity=".34"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="<?php echo $u; ?>-scan" x1="0" y1="0" x2="1" y2="0">
			<stop class="evpx-art__stop-neon" offset="0" stop-opacity="0"/>
			<stop class="evpx-art__stop-neon" offset=".8" stop-opacity=".2"/>
			<stop class="evpx-art__stop-neon" offset="1" stop-opacity=".75"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-ground" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#fff" stop-opacity="1"/>
			<stop offset="1" stop-color="#fff" stop-opacity="0"/>
		</linearGradient>
		<linearGradient id="<?php echo $u; ?>-mirror" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#fff" stop-opacity=".2"/>
			<stop offset="1" stop-color="#fff" stop-opacity="0"/>
		</linearGradient>
		<filter id="<?php echo $u; ?>-halo" filterUnits="userSpaceOnUse" x="-40" y="-40" width="840" height="620" color-interpolation-filters="sRGB">
			<feGaussianBlur stdDeviation="5"/>
		</filter>
		<mask id="<?php echo $u; ?>-floor"><rect x="-400" y="330" width="1560" height="210" fill="url(#<?php echo $u; ?>-ground)"/></mask>
		<mask id="<?php echo $u; ?>-mirror-mask"><rect x="-400" y="362" width="1560" height="70" fill="url(#<?php echo $u; ?>-mirror)"/></mask>
		<clipPath id="<?php echo $u; ?>-body-clip"><path d="<?php echo $body; ?>"/></clipPath>
		<g id="<?php echo $u; ?>-wheel" stroke="currentColor" stroke-linecap="round">
			<path d="M-40 0A40 40 0 0 1 40 0Z" fill="#0b0d11" stroke="none"/>
			<circle r="34" fill="#0b0d11" stroke-opacity=".8" stroke-width="1.5"/>
			<circle r="21" stroke-opacity=".34" stroke-width="1.2"/>
			<path d="M0 -7L0 -20M6.7 -2.2L19 -6.2M4.1 5.7L11.8 16.2M-4.1 5.7L-11.8 16.2M-6.7 -2.2L-19 -6.2" stroke-opacity=".3" stroke-width="1.2"/>
			<circle r="4" stroke-opacity=".5" stroke-width="1.2"/>
		</g>
		<g id="<?php echo $u; ?>-car" stroke="currentColor" stroke-linejoin="round" stroke-linecap="round">
			<path d="<?php echo $body; ?>" fill="url(#<?php echo $u; ?>-body)" stroke="none"/>
			<path d="<?php echo $glass; ?>" fill="url(#<?php echo $u; ?>-glass)" stroke="none"/>
			<path class="evpx-art__draw" pathLength="1" style="--i:2" d="<?php echo $body; ?>" stroke-opacity=".5" stroke-width="1.5"/>
			<path class="evpx-art__draw" pathLength="1" style="--i:3" d="<?php echo $roof; ?>" stroke="url(#<?php echo $u; ?>-rim)" stroke-width="1.8"/>
			<path class="evpx-art__draw" pathLength="1" style="--i:4" d="<?php echo $glass; ?>" stroke-opacity=".34" stroke-width="1.2"/>
			<path d="M396 242V286M334 290L333 352M472 288L471 352" stroke-opacity=".24" stroke-width="1.1"/>
			<path d="M444 302h22" stroke-opacity=".5" stroke-width="1.4"/>
			<use href="#<?php echo $u; ?>-wheel" x="330" y="358"/>
			<use href="#<?php echo $u; ?>-wheel" x="590" y="358"/>
		</g>
	</defs>

	<rect class="evpx-art__fade" x="-400" width="1560" height="540" fill="url(#<?php echo $u; ?>-aura)"/>

	<g class="evpx-art__floor" mask="url(#<?php echo $u; ?>-floor)" stroke="currentColor" stroke-opacity=".13" stroke-width="1" vector-effect="non-scaling-stroke">
		<path d="M-400 338H1160M-400 344H1160M-400 352H1160M-400 364H1160M-400 382H1160M-400 408H1160M-400 444H1160M-400 492H1160M-400 538H1160"/>
		<path d="M380 330L-842 540M380 330L-517 540M380 330L-231 540M380 330L36 540M380 330L208 540M380 330L380 540M380 330L552 540M380 330L724 540M380 330L991 540M380 330L1277 540M380 330L1602 540"/>
	</g>
	<rect class="evpx-art__fade" x="-400" y="329.25" width="1560" height="1.5" fill="url(#<?php echo $u; ?>-horizon)"/>
	<ellipse class="evpx-art__fade" cx="460" cy="366" rx="230" ry="18" fill="url(#<?php echo $u; ?>-pool)"/>

	<g class="evpx-art__fade evpx-art__reflection" mask="url(#<?php echo $u; ?>-mirror-mask)"><use href="#<?php echo $u; ?>-car" transform="translate(0 724) scale(1 -1)"/></g>

	<use href="#<?php echo $u; ?>-car"/>
	<g clip-path="url(#<?php echo $u; ?>-body-clip)"><rect class="evpx-art__scan" x="120" y="200" width="150" height="190" fill="url(#<?php echo $u; ?>-scan)"/></g>

	<g class="evpx-art__charger" stroke-linecap="round">
		<rect x="150" y="278" width="48" height="114" rx="9" fill="url(#<?php echo $u; ?>-body)"/>
		<path class="evpx-art__draw" pathLength="1" style="--i:1" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5" d="M159 278h30a9 9 0 0 1 9 9v96a9 9 0 0 1-9 9h-30a9 9 0 0 1-9-9v-96a9 9 0 0 1 9-9Z"/>
		<rect x="159" y="291" width="30" height="24" rx="4" stroke="currentColor" stroke-opacity=".38" stroke-width="1.2"/>
		<path class="evpx-art__dot" d="<?php echo esc_attr( \EVPX\Support\Icons::path( 'lightning' ) ); ?>" transform="translate(166 294) scale(.06)"/>
		<path d="M144 392h60" stroke="currentColor" stroke-opacity=".7" stroke-width="1.5"/>
	</g>

	<?php // The light: the wide strokes are blurred as one group; the hard cores sit on top of it. ?>
	<g class="evpx-art__halo" filter="url(#<?php echo $u; ?>-halo)" stroke-linecap="round">
		<path class="evpx-art__glow" d="M376 362H544"/>
		<path class="evpx-art__glow" d="M174 332v40"/>
		<path class="evpx-art__glow evpx-art__draw" pathLength="1" style="--i:5" d="<?php echo $cable; ?>"/>
		<circle class="evpx-art__glow" cx="252" cy="312" r="8"/>
		<circle class="evpx-art__glow evpx-art__arc" cx="628" cy="104" r="42" pathLength="100" stroke-dasharray="78 100" transform="rotate(-90 628 104)"/>
	</g>
	<g class="evpx-art__lit" stroke-linecap="round">
		<path class="evpx-art__neon" d="M376 362H544" stroke-width="1.5"/>
		<path class="evpx-art__neon" d="M678 318l10 8" stroke-width="1.6"/>
		<path class="evpx-art__neon" d="M174 332v40" stroke-width="2"/>
		<path class="evpx-art__neon evpx-art__draw" pathLength="1" style="--i:5" d="<?php echo $cable; ?>" stroke-width="1.6"/>
		<path class="evpx-art__pulse" d="<?php echo $cable; ?>" stroke-width="1.8" stroke-dasharray="3 17"/>
		<circle class="evpx-art__neon" cx="252" cy="312" r="8" stroke-width="1.5"/>
		<circle class="evpx-art__dot" cx="252" cy="312" r="3"/>
	</g>

	<g class="evpx-art__notes" stroke="currentColor" stroke-opacity=".4" stroke-width="1">
		<path d="M252 304V200"/>
		<circle class="evpx-art__dot" cx="252" cy="304" r="2" stroke="none"/>
		<text class="evpx-art__label" x="252" y="188">CCS2 · 150 <tspan class="evpx-art__unit">kW</tspan></text>
		<path d="M300 398v10M560 398v10M300 403H560"/>
		<text class="evpx-art__label" x="430" y="428" text-anchor="middle"><?php echo esc_html( __( 'Battery', 'ev-charging-experience' ) ); ?> · 82 <tspan class="evpx-art__unit">kWh</tspan></text>
	</g>

	<g class="evpx-art__soc" transform="translate(628 104)">
		<circle r="42" stroke="currentColor" stroke-opacity=".16" stroke-width="2"/>
		<circle r="54" stroke="currentColor" stroke-opacity=".3" stroke-width="4" stroke-dasharray="1 5.9"/>
		<circle class="evpx-art__neon evpx-art__arc" r="42" pathLength="100" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="78 100" transform="rotate(-90)"/>
		<text class="evpx-art__value" y="10" text-anchor="middle">78%</text>
		<text class="evpx-art__label" y="86" text-anchor="middle"><?php esc_html_e( 'State of charge', 'ev-charging-experience' ); ?></text>
	</g>
</svg>
<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
