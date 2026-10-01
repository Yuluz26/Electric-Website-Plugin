<?php
/**
 * Shared pieces of the cinematic scenes: the car and the charger post, as symbols each scene <use>s.
 * Numbers and this file's own constants only; nothing here is user input.
 *
 * @var string $u Escaped unique id of the scene.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<linearGradient id="<?php echo $u; ?>-carbody" x1="0" y1="0" x2="0" y2="1">
	<stop offset="0" stop-color="#2a313b"/>
	<stop offset=".55" stop-color="#151a21"/>
	<stop offset="1" stop-color="#0b0e13"/>
</linearGradient>
<linearGradient id="<?php echo $u; ?>-postbody" x1="0" y1="0" x2="1" y2="0">
	<stop offset="0" stop-color="#0d1015"/>
	<stop offset=".45" stop-color="#262d37"/>
	<stop offset="1" stop-color="#0a0c10"/>
</linearGradient>
<symbol id="<?php echo $u; ?>-car" viewBox="0 0 400 120">
	<path d="M8 104c0-10 12-15 34-18l46-14c18-16 46-27 92-29h58c34 2 60 16 80 30l40 8c22 5 36 12 38 23v10H8z" fill="url(#<?php echo $u; ?>-carbody)"/>
	<path d="M98 72c24-19 56-31 104-31h42c30 2 54 16 70 31z" fill="#07090d" opacity=".9"/>
	<path d="M204 42v30" stroke="#1d232b" stroke-width="2"/>
	<path d="M8 104c0-10 12-15 34-18l46-14c18-16 46-27 92-29h58c34 2 60 16 80 30l40 8c22 5 36 12 38 23" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="1.2" stroke-linejoin="round"/>
	<g fill="#05070a"><circle cx="92" cy="104" r="21"/><circle cx="316" cy="104" r="21"/></g>
	<g fill="none" stroke="#fff" stroke-opacity=".38" stroke-width="1.4"><circle cx="92" cy="104" r="21"/><circle cx="316" cy="104" r="21"/><circle cx="92" cy="104" r="10" stroke-opacity=".2"/><circle cx="316" cy="104" r="10" stroke-opacity=".2"/></g>
	<path class="evpx-scene__neon-line" d="M372 88l24 6" stroke-width="3" stroke-linecap="round"/>
	<path class="evpx-scene__neon-line" d="M124 116h168" stroke-width="2.4" stroke-linecap="round" opacity=".85"/>
</symbol>
<symbol id="<?php echo $u; ?>-post" viewBox="0 0 60 210">
	<rect x="6" y="4" width="48" height="200" rx="14" fill="url(#<?php echo $u; ?>-postbody)"/>
	<rect x="6.5" y="4.5" width="47" height="199" rx="13.5" fill="none" stroke="#fff" stroke-opacity=".26"/>
	<rect x="16" y="22" width="28" height="42" rx="5" fill="#05070a"/>
	<rect class="evpx-scene__neon evpx-scene__pulse" x="27" y="80" width="6" height="86" rx="3" opacity=".95"/>
	<circle class="evpx-scene__neon" cx="30" cy="184" r="3.2"/>
</symbol>
