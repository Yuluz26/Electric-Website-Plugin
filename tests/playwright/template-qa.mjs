#!/usr/bin/env node
/**
 * Browser check for EV elements that live in a Breakdance template — a footer or a Single Post
 * template — rather than in the page itself. Run through tests/docker/template-check.sh, which
 * creates the template, calls this once per page, and removes the template again:
 *
 *   node tests/playwright/template-qa.mjs [--classic] <label> <expected-widgets> <url> [<url> …]
 *
 * <expected-widgets> is a number, or "n" to skip the count (a page whose own widgets are counted
 * elsewhere). What it guards, on every page it is given:
 *   - the stylesheet, and each script, is delivered exactly once — a page that mixes shortcodes with a
 *     native element in a template used to load both twice (two copies of GSAP), because WordPress and
 *     Breakdance each queued them;
 *   - the stylesheet is in <head> (on a block theme, and under Breakdance's own templates, the body
 *     renders first, so an element in a footer or template is already known when <head> is printed);
 *   - the widgets are styled (their own typeface and colours, not the host's), have a width (a size
 *     container collapses to nothing inside a shrink-wrapped box) and nothing overflows;
 *   - no console errors.
 *
 * --classic is for a plain classic theme, where <head> is printed before the body renders: there the
 * scripts must still load once, but the stylesheet may appear twice (a page that mixes both ways of
 * adding widgets) and a widget in a footer may bring its stylesheet late.
 *
 * Same Playwright requirements as qa.mjs.
 */
import { launch, reporter, scrollThrough, overflowProbe } from './lib.mjs';

const args = process.argv.slice(2);
const classic = args.includes('--classic');
const [label, expected, ...urls] = args.filter((a) => a !== '--classic');
if (!label || !expected || urls.length === 0) {
	console.error('Usage: node tests/playwright/template-qa.mjs [--classic] <label> <expected-widgets|n> <url> [<url> …]');
	process.exit(1);
}

const browser = await launch();
const { check, finish } = reporter();

for (const url of urls) {
	const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
	const errors = [];
	page.on('pageerror', (e) => errors.push(e.message));
	page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));

	await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
	await scrollThrough(page);
	await page.waitForTimeout(800);

	const found = await page.evaluate(() => {
		const sheets = [...document.querySelectorAll('link[rel=stylesheet][href*="assets/css/evpx.css"]')];
		const scripts = (name) => document.querySelectorAll(`script[src*="${name}"]`).length;
		const heading = document.querySelector('.evpx-root :is(h1, h2)');

		return {
			widgets: document.querySelectorAll('.evpx-root:not(.evpx-progress)').length,
			collapsed: [...document.querySelectorAll('.evpx-root:not(.evpx-progress)')].filter((w) => w.getBoundingClientRect().width < 50).length,
			sheets: sheets.length,
			sheetsInHead: sheets.filter((l) => l.closest('head')).length,
			core: scripts('evpx.js'),
			motion: scripts('motion.js'),
			gsap: scripts('gsap.min.js'),
			font: heading ? getComputedStyle(heading).fontFamily : '',
		};
	});
	const where = `${label}: ${url.replace(/^https?:\/\/[^/]+/, '')}`;

	if (expected !== 'n') {
		check(`${where} — ${expected} widget(s) rendered`, found.widgets === Number(expected), `found ${found.widgets}`);
	}
	const scriptsOnce = found.core === 1 && found.motion === 1 && found.gsap === 1;
	if (classic) {
		check(`${where} — each script delivered exactly once (stylesheet: ${found.sheets})`, scriptsOnce && found.sheets >= 1 && found.sheets <= 2, JSON.stringify(found));
	} else {
		check(`${where} — stylesheet and each script delivered exactly once`, found.sheets === 1 && scriptsOnce, JSON.stringify(found));
		check(`${where} — stylesheet is in <head>`, found.sheetsInHead === 1, JSON.stringify(found));
	}
	check(`${where} — no widget collapsed to nothing`, found.collapsed === 0, JSON.stringify(found));
	check(`${where} — widgets are styled in their own typeface`, /EVPX/.test(found.font), found.font);

	const overflow = await overflowProbe(page);
	check(`${where} — nothing overflows`, overflow.length === 0, overflow.join('; '));
	check(`${where} — no console errors`, errors.length === 0, errors.join('; '));
	await page.close();
}

await browser.close();
finish();
