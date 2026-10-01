/**
 * The accessibility rules that axe cannot see, as checks: that a keyboard can always see where it is, on every
 * surface, in both colour schemes; that a control is a target a thumb can hit; that a line of text is a length the
 * eye can hold; that nothing meant to be read is smaller than 12px; that a forced-colours browser still gets an
 * edge where the design used a shadow.
 *
 *   node tests/playwright/a11y-qa.mjs "$(cat tests/docker/.demo-url)" [outDir]
 *
 * Needs a page with every EV widget (the demo article).
 */
import { launch, reporter, scrollThrough } from './lib.mjs';
import fs from 'node:fs';
import path from 'node:path';

const [url, outDir = 'qa-a11y-output'] = process.argv.slice(2);
if (!url) {
	console.error('usage: node a11y-qa.mjs <page url> [outDir]');
	process.exit(2);
}
fs.mkdirSync(outDir, { recursive: true });

const { check, finish } = reporter();
const browser = await launch();

async function open(contextOptions, width = 1440, height = 900) {
	const context = await browser.newContext({ viewport: { width, height }, ...contextOptions });
	const page = await context.newPage();
	await page.goto(url, { waitUntil: 'networkidle' });
	await page.addStyleTag({ content: 'html{scroll-behavior:auto!important}' });
	await scrollThrough(page);
	await page.evaluate(() => window.scrollTo(0, 0));
	await page.waitForTimeout(700);
	return { context, page };
}

/** WCAG relative luminance and contrast, on 0-255 channels. */
const lum = ({ r, g, b }) => {
	const f = (v) => {
		v /= 255;
		return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
	};
	return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
};
const ratio = (a, b) => {
	const [hi, lo] = [lum(a), lum(b)].sort((x, y) => y - x);
	return (hi + 0.05) / (lo + 0.05);
};

/* ---------------------------------------------------------------- 1. focus rings, both schemes */
for (const scheme of ['light', 'dark']) {
	const { context, page } = await open({ colorScheme: scheme });
	const rows = [];
	const seen = new Set();

	await page.evaluate(() => document.activeElement && document.activeElement.blur());
	for (let i = 0; i < 160; i++) {
		await page.keyboard.press('Tab');
		const info = await page.evaluate(() => {
			const el = document.activeElement;
			if (!el || !el.closest('.evpx-root')) return null;
			const cs = getComputedStyle(el);
			// The colour the ring is drawn on: the ring sits outside the control (offset from it), so it is the first
			// ancestor with a mostly opaque background, not the control's own fill.
			let bg = null;
			for (let n = el.parentElement; n && n !== document.documentElement; n = n.parentElement) {
				const c = getComputedStyle(n).backgroundColor;
				const m = c.match(/rgba?\(([^)]+)\)/);
				if (m) {
					const p = m[1].split(',').map(parseFloat);
					if ((p[3] === undefined ? 1 : p[3]) > 0.5) {
						bg = p;
						break;
					}
				}
			}
			const oc = cs.outlineColor.match(/rgba?\(([^)]+)\)/);
			return {
				key: el.tagName + '.' + [...el.classList].filter((c) => c.startsWith('evpx-')).join('.') + '#' + (el.textContent || el.value || '').trim().slice(0, 16),
				range: el.matches('input[type="range"]'),
				radio: el.matches('input[type="radio"]'),
				style: cs.outlineStyle,
				width: parseFloat(cs.outlineWidth),
				color: oc ? oc[1].split(',').map(parseFloat) : null,
				bg,
				// A radio is drawn by its label; a range by its thumb: the ring is on those.
				label: el.matches('input[type="radio"]') && el.nextElementSibling ? (() => { const l = getComputedStyle(el.nextElementSibling); return { style: l.outlineStyle, width: parseFloat(l.outlineWidth), color: (l.outlineColor.match(/rgba?\(([^)]+)\)/) || [])[1]?.split(',').map(parseFloat) }; })() : null,
			};
		});
		if (!info) continue;
		if (seen.has(info.key)) break;
		seen.add(info.key);
		rows.push(info);
	}

	const rgb = (a) => ({ r: a[0], g: a[1], b: a[2] });
	const bad = [];
	for (const r of rows) {
		if (r.range) continue; // the thumb carries it (a pseudo-element, not measurable from here)
		const ring = r.radio ? r.label : r;
		if (!ring || ring.style === 'none' || ring.width < 2 || !ring.color) {
			bad.push(`${r.key}: no 2px ring (${ring ? ring.style + ' ' + ring.width : 'no label'})`);
			continue;
		}
		if (r.bg) {
			const c = ratio(rgb(ring.color), rgb(r.bg));
			if (c < 3) bad.push(`${r.key}: ring ${c.toFixed(2)}:1 against its surface`);
		}
	}
	check(`${scheme} scheme: the keyboard sees a 2px ring at 3:1 on each of the ${rows.length} controls it reaches`, rows.length > 12 && bad.length === 0, bad.slice(0, 4).join(' | ') || `controls=${rows.length}`);
	await context.close();
}

/* ---------------------------------------------------------------- 2. targets, type, measure */
{
	const { context, page } = await open({});

	const targets = await page.evaluate(() => {
		const bad = [];
		const sel = 'a[href], button, input:not([type="hidden"]), select, textarea, [role="tab"], summary';
		for (const el of document.querySelectorAll('.evpx-root ' + sel.split(', ').join(', .evpx-root '))) {
			if (el.closest('[hidden]') || el.closest('.evpx-visually-hidden')) continue;
			const cs = getComputedStyle(el);
			if (cs.display === 'none' || cs.visibility === 'hidden' || cs.position === 'absolute' && el.matches('input[type="radio"]')) continue;
			// Inline links inside a sentence are exempt from a target minimum (WCAG 2.5.8).
			if (el.matches('a') && el.closest('p, li, .evpx-body') && cs.display === 'inline') continue;
			const r = el.getBoundingClientRect();
			if (r.width < 24 || r.height < 24) bad.push(`${el.tagName}.${[...el.classList].join('.')}: ${Math.round(r.width)}×${Math.round(r.height)}`);
		}
		return bad;
	});
	check('every control is a target of at least 24×24px (WCAG 2.2, 2.5.8)', targets.length === 0, targets.slice(0, 4).join(' | '));

	const tabs = await page.evaluate(() => [...document.querySelectorAll('.evpx-comparison__tab')].map((t) => Math.round(t.getBoundingClientRect().height)));
	check('the comparison\'s tabs are 44px tall', tabs.length >= 2 && tabs.every((h) => h >= 44), tabs.join(','));

	const small = await page.evaluate(() => {
		const bad = [];
		const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
		while (walker.nextNode()) {
			const node = walker.currentNode;
			if (!node.textContent.trim()) continue;
			const el = node.parentElement;
			if (!el.closest('.evpx-root') || el.closest('svg') || el.closest('[aria-hidden="true"]') || el.closest('script,style')) continue;
			const cs = getComputedStyle(el);
			if (cs.display === 'none' || cs.visibility === 'hidden' || el.closest('.evpx-visually-hidden')) continue;
			if (parseFloat(cs.fontSize) < 11.99) bad.push(`${el.className || el.tagName}: ${cs.fontSize} «${node.textContent.trim().slice(0, 24)}»`);
		}
		return [...new Set(bad)];
	});
	check('nothing meant to be read is set smaller than 12px', small.length === 0, small.slice(0, 4).join(' | '));

	// A line of running text: 45 to 78 characters. Counted as characters over the lines a paragraph fills.
	const measure = await page.evaluate(() => {
		const rows = [];
		for (const p of document.querySelectorAll('.evpx-body p, .evpx-faq__answer p, .evpx-hero__excerpt p')) {
			const cs = getComputedStyle(p);
			const lines = Math.round(p.getBoundingClientRect().height / parseFloat(cs.lineHeight));
			const chars = p.textContent.trim().length;
			if (lines >= 3 && chars > 160) rows.push({ cpl: Math.round(chars / lines), cls: p.parentElement.className.toString().slice(0, 40) });
		}
		return rows;
	});
	const long = measure.filter((r) => r.cpl > 80);
	check(`running text keeps to 80 characters a line or fewer (${measure.length} paragraphs measured, widest ${Math.max(0, ...measure.map((m) => m.cpl))})`, measure.length > 0 && long.length === 0, JSON.stringify(long.slice(0, 3)));

	await context.close();
}

/* ---------------------------------------------------------------- 3. icons */
{
	const { context, page } = await open({});
	const icons = await page.evaluate(() => {
		const all = [...document.querySelectorAll('.evpx-root svg.evpx-icon')];
		const wrong = all.filter((s) => s.getAttribute('aria-hidden') !== 'true' || s.getAttribute('focusable') !== 'false' || s.getAttribute('fill') !== 'currentColor');
		const families = new Set(all.map((s) => s.getAttribute('viewBox')));
		const sizes = new Set(all.map((s) => Math.round(s.getBoundingClientRect().width)));
		return { count: all.length, wrong: wrong.length, families: [...families], sizes: [...sizes].sort((a, b) => a - b) };
	});
	check(`icons: ${icons.count} on the page, all decorative, all in currentColor, all from one family (one viewBox)`, icons.count > 15 && icons.wrong === 0 && icons.families.length === 1, JSON.stringify(icons));
	check('icons: they come in few sizes, set by the size tokens', icons.sizes.length <= 8, icons.sizes.join(','));
	await context.close();
}

/* ---------------------------------------------------------------- 4. forced colours */
{
	const { context, page } = await open({ forcedColors: 'active' });
	const state = await page.evaluate(() => {
		const border = (sel) => {
			const el = document.querySelector(sel);
			return el ? parseFloat(getComputedStyle(el).borderTopWidth) : -1;
		};
		const fill = (sel) => {
			const el = document.querySelector(sel);
			return el ? getComputedStyle(el).backgroundColor : null;
		};
		return {
			forced: matchMedia('(forced-colors: active)').matches,
			card: border('.evpx-scenario-card'),
			plate: border('.evpx-comparison__data'),
			node: border('.evpx-flow__node'),
			panel: border('.evpx-explorer__panel'),
			barFill: fill('.evpx-explorer__bar-fill'),
			tabOutline: (() => {
				const t = document.querySelector('.evpx-comparison__tab--active');
				return t ? getComputedStyle(t).outlineStyle : 'no tab';
			})(),
			heroArt: (() => {
				const v = document.querySelector('.evpx-hero__visual');
				return v ? getComputedStyle(v).display : 'none';
			})(),
		};
	});
	check('forced colours: the emulation is on', state.forced);
	check('forced colours: cards, the comparison\'s plate, the flow\'s nodes and the explorer\'s panel get an edge where the design had a shadow', state.card >= 1 && state.plate >= 1 && state.node >= 1 && state.panel >= 1, JSON.stringify(state));
	check('forced colours: the explorer\'s bars are a system colour, not the page background', !!state.barFill && state.barFill !== 'rgba(0, 0, 0, 0)', state.barFill);
	check('forced colours: the selected tab is outlined, and the hero\'s decoration steps aside', state.tabOutline === 'solid' && state.heroArt === 'none', JSON.stringify(state));
	await page.locator('.evpx-comparison').first().screenshot({ path: path.join(outDir, 'forced-comparison.png') }).catch(() => {});
	await context.close();
}

await browser.close();
finish(outDir);
