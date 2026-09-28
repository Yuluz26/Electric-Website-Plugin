#!/usr/bin/env node
/**
 * WCAG contrast check driven by the real tokens in assets/css/evpx.css, so
 * the numbers can't drift from what ships. Run: node tests/contrast-check.mjs
 *
 * Text pairs need 4.5:1 (AA, normal text). Non-text UI pairs (icons, focus
 * rings, control boundaries) need 3:1 (WCAG 1.4.11).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const css = fs.readFileSync(
	path.join(path.dirname(fileURLToPath(import.meta.url)), '../assets/css/evpx.css'),
	'utf8'
);

function tokens(selector) {
	const start = css.indexOf(selector + ' {');
	if (start === -1) throw new Error('Selector not found: ' + selector);
	const block = css.slice(start, css.indexOf('}', start));
	const out = {};
	for (const m of block.matchAll(/(--evpx-[a-z-]+):\s*(#[0-9a-fA-F]{6})/g)) out[m[1]] = m[2];
	return out;
}

const light = tokens('.evpx-root');
const dark = { ...light, ...tokens(".evpx-root[data-evpx-theme='dark']") };

function luminance(hex) {
	const n = parseInt(hex.slice(1), 16);
	const lin = (c) => {
		c /= 255;
		return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
	};
	return 0.2126 * lin(n >> 16) + 0.7152 * lin((n >> 8) & 255) + 0.0722 * lin(n & 255);
}

function ratio(a, b) {
	const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
	return (hi + 0.05) / (lo + 0.05);
}

const pairs = (t, mode) => [
	[`${mode}: body text (ink-muted on surface)`, t['--evpx-ink-muted'], t['--evpx-surface-base'], 4.5],
	[`${mode}: headings (ink on surface)`, t['--evpx-ink'], t['--evpx-surface-base'], 4.5],
	[`${mode}: small labels (ink-faint on surface)`, t['--evpx-ink-faint'], t['--evpx-surface-base'], 4.5],
	[`${mode}: accent text/links (accent-text on surface)`, t['--evpx-accent-text'] || t['--evpx-accent'], t['--evpx-surface-base'], 4.5],
	[`${mode}: accent text on raised surface`, t['--evpx-accent-text'] || t['--evpx-accent'], t['--evpx-surface-raised-hi'], 4.5],
	[`${mode}: button text (accent-ink on accent fill)`, t['--evpx-accent-ink'], t['--evpx-accent'], 4.5],
	[`${mode}: accent icon/focus ring (non-text)`, t['--evpx-accent-text'] || t['--evpx-accent'], t['--evpx-surface-base'], 3],
	[`${mode}: technical accent border (non-text)`, t['--evpx-technical'], t['--evpx-surface-base'], 3],
];

let failures = 0;
for (const [label, fg, bg, min] of [...pairs(light, 'light'), ...pairs(dark, 'dark')]) {
	const r = ratio(fg, bg);
	const ok = r >= min;
	if (!ok) failures++;
	console.log(`${ok ? 'PASS' : 'FAIL'}  ${r.toFixed(2).padStart(5)}:1 (min ${min})  ${label}  ${fg} on ${bg}`);
}

console.log(failures ? `\n${failures} pair(s) below WCAG minimum.` : '\nAll token pairs meet WCAG minimums.');
process.exit(failures ? 1 : 0);
