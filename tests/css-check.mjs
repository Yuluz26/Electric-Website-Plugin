#!/usr/bin/env node
/**
 * Static checks on assets/css/evpx.css — the things a browser never complains about but that break a
 * page. No browser needed. Run: node tests/css-check.mjs
 *
 * 1. Every --evpx-* token that is read (var(--evpx-x) with no fallback) is defined somewhere. A token
 *    that is used but never defined makes its declaration invalid at computed-value time, so the
 *    property silently falls back (0.4.0 shipped FAQ rows with no padding and a Flow heading touching
 *    its steps because --evpx-space-5 and --evpx-space-10 did not exist).
 * 2. Every style rule is scoped under .evpx-root (docs/ARCHITECTURE.md, the isolation contract), except
 *    the one documented zero-specificity rule for the box that holds a widget.
 * 3. No !important outside the reduced-motion block, whose whole job is to win.
 * 4. Every custom property that only JavaScript or an inline style sets is on a short, explicit list, so
 *    a new one has to be added here on purpose.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const raw = fs.readFileSync(path.join(root, 'assets/css/evpx.css'), 'utf8');
const css = raw.replace(/\/\*[\s\S]*?\*\//g, '');

let failures = 0;
const check = (label, ok, detail = '') => {
	console.log(`${ok ? 'PASS' : 'FAIL'} — ${label}${!ok && detail ? ` (${detail})` : ''}`);
	if (!ok) failures++;
};

/** Yields every style rule, descending into @media, @supports and @container. */
function* rules(text) {
	let i = 0;
	while (i < text.length) {
		const open = text.indexOf('{', i);
		if (open === -1) break;
		const head = text.slice(i, open).trim();
		let depth = 1;
		let j = open + 1;
		while (j < text.length && depth) {
			if (text[j] === '{') depth++;
			else if (text[j] === '}') depth--;
			j++;
		}
		const body = text.slice(open + 1, j - 1);
		if (/^@(media|supports|container)\b/.test(head)) yield* rules(body);
		else if (!/^@(font-face|keyframes)\b/.test(head)) yield { selector: head, body };
		i = j;
	}
}

const all = [...rules(css)];

// 1. Tokens.
const defined = new Set([...css.matchAll(/(--evpx-[a-z0-9-]+)\s*:/g)].map((m) => m[1]));
// Set at run time: by evpx.js (the comparison thumb, the card highlight) or by an inline style in a template
// (the flow step index, the ruler range). Each has a fallback where it is read, or is only read when set.
const runtime = new Set(['--evpx-thumb-x', '--evpx-thumb-y', '--evpx-thumb-w', '--evpx-thumb-h', '--evpx-mx', '--evpx-my', '--evpx-step', '--evpx-from', '--evpx-to', '--evpx-other-from', '--evpx-other-to', '--evpx-button-fill', '--evpx-charge-from']);
const readWithoutFallback = new Set([...css.matchAll(/var\((--evpx-[a-z0-9-]+)\s*\)/g)].map((m) => m[1]));
const undefinedTokens = [...readWithoutFallback].filter((t) => !defined.has(t) && !runtime.has(t));
check('every --evpx-* token that is read is defined', undefinedTokens.length === 0, undefinedTokens.join(', '));

const setOnlyAtRuntime = [...runtime].filter((t) => !new RegExp(`var\\(${t}\\b`).test(css));
check('every entry on the run-time token list is actually read by the stylesheet', setOnlyAtRuntime.length === 0, setOnlyAtRuntime.join(', '));

// 2. Scope.
const unscoped = [];
for (const { selector } of all) {
	for (const part of selector.split(/,(?![^(]*\))/).map((s) => s.trim())) {
		if (part.startsWith('.evpx-root') || part === ':where(:has(> .evpx-root))') continue;
		unscoped.push(part);
	}
}
check('every selector is scoped under .evpx-root (the isolation contract)', unscoped.length === 0, unscoped.slice(0, 5).join(' | '));

// 3. !important.
const important = all.filter((r) => /!important/.test(r.body) && !/prefers-reduced-motion/.test(r.selector)).map((r) => r.selector);
const reducedBlock = raw.match(/@media \(prefers-reduced-motion: reduce\) \{[\s\S]*?\n\}/);
const outsideReduced = important.filter((sel) => !(reducedBlock && reducedBlock[0].includes(sel)));
check('no !important outside the reduced-motion rule', outsideReduced.length === 0, outsideReduced.join(' | '));

// 4. Motion never runs unless the widget has been marked (docs/ARCHITECTURE.md, the motion contract).
const animated = all.filter((r) => /animation(-name)?\s*:/.test(r.body) && !/evpx-failsafe-reveal/.test(r.body) && !/animation:\s*none/.test(r.body));
const ungated = animated.filter((r) => !/data-evpx-motion='on'/.test(r.selector)).map((r) => r.selector);
check("every animation is gated on [data-evpx-motion='on']", ungated.length === 0, ungated.join(' | '));

console.log(failures ? `\n${failures} check(s) failed.` : '\nAll static CSS checks passed.');
process.exit(failures ? 1 : 0);
