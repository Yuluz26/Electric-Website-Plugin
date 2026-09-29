#!/usr/bin/env node
/**
 * Browser QA for a running demo article (see docs/QA-REPORT.md). A fast,
 * repeatable version of the checks that caught real bugs during development.
 *
 *   bash tests/docker/setup.sh
 *   node tests/playwright/qa.mjs "$(cat tests/docker/.demo-url)" [outDir]
 *
 * Needs `playwright` (and optionally `axe-core`) resolvable from this folder:
 *   cd tests/playwright && npm install playwright axe-core
 * Point PLAYWRIGHT_CHROMIUM_PATH at a Chromium binary if Playwright's own
 * browser download isn't available.
 *
 * Motion checks need GSAP to load. If cdnjs is unreachable from your network,
 * run setup.sh with EVPX_GSAP_DIR set (see its header); without GSAP those
 * checks report SKIP rather than fail.
 *
 * For the same widgets inside a real Breakdance install, see breakdance-qa.mjs.
 */
import { launch, reporter, scrollThrough, notFullyVisible, overflowProbe, axeViolations } from './lib.mjs';
import fs from 'node:fs';
import path from 'node:path';

const url = process.argv[2];
if (!url) {
	console.error('Usage: node tests/playwright/qa.mjs <url> [outDir]');
	process.exit(1);
}
const outDir = process.argv[3] || './qa-output';
fs.mkdirSync(outDir, { recursive: true });

const browser = await launch();
const { check, skip, finish } = reporter();

// ---------------------------------------------------------------- screenshots
const consoleErrors = [];
const pageErrors = [];
const requestedHosts = new Set();
// Every width the docs claim is tested. Each one is loaded, scrolled through so the
// reveals settle, screenshotted, and probed for horizontal overflow.
const viewports = {
	'mobile-320': { width: 320, height: 640 },
	'mobile-375': { width: 375, height: 812 },
	'mobile-390': { width: 390, height: 844 },
	'mobile-430': { width: 430, height: 932 },
	'tablet-768': { width: 768, height: 1024 },
	'laptop-1024': { width: 1024, height: 768 },
	'desktop-1280': { width: 1280, height: 800 },
	'laptop-1366': { width: 1366, height: 768 },
	'desktop-1440': { width: 1440, height: 900 },
	'desktop-1920': { width: 1920, height: 1080 },
};
const overflows = {};
for (const [name, viewport] of Object.entries(viewports)) {
	const page = await browser.newPage({ viewport });
	page.on('console', (m) => m.type() === 'error' && consoleErrors.push(`[${name}] ${m.text()}`));
	page.on('pageerror', (e) => pageErrors.push(`[${name}] ${e.message}`));
	page.on('request', (r) => {
		try {
			requestedHosts.add(new URL(r.url()).host);
		} catch {}
	});
	await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
	await scrollThrough(page);
	await page.evaluate(() => window.scrollTo(0, 0));
	await page.waitForTimeout(1000);
	const bad = await overflowProbe(page);
	if (bad.length) overflows[name] = bad;
	await page.screenshot({ path: path.join(outDir, `${name}.png`), fullPage: true });
	await page.close();
}
check('no uncaught JavaScript exceptions', pageErrors.length === 0, pageErrors[0]);
check(
	'no third-party font requests (fonts are self-hosted)',
	![...requestedHosts].some((h) => /fonts\.(googleapis|gstatic)\.com/.test(h))
);
check(
	`no horizontal overflow at any of ${Object.keys(viewports).length} widths (${Object.values(viewports).map((v) => v.width).join(', ')})`,
	Object.keys(overflows).length === 0,
	Object.entries(overflows).map(([n, b]) => `${n}: ${b[0]}`).join(' | ')
);
fs.writeFileSync(
	path.join(outDir, 'console-report.txt'),
	`Console errors (${consoleErrors.length}):\n${consoleErrors.join('\n')}\n\nPage errors (${pageErrors.length}):\n${pageErrors.join('\n')}\n`
);

// ------------------------------------------------- structure + interactions
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
const hasGsap = await page.evaluate(() => typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined');

check('no unprocessed [evpx_*] shortcode text left on page', !(await page.evaluate(() => /\[evpx_[a-z_]+/.test(document.body.innerText))));

check(
	'self-hosted fonts actually load',
	await page.evaluate(async () => {
		await document.fonts.ready;
		return document.fonts.check('600 32px "EVPX Fraunces"') && document.fonts.check('400 16px "EVPX Libre Franklin"');
	})
);

const headings = await page.$$eval('h1, .evpx-root h2, .evpx-root h3, .evpx-root h4', (els) =>
	els.filter((e) => e.tagName === 'H1' || e.closest('.evpx-root')).map((e) => +e.tagName[1])
);
const h1Count = headings.filter((l) => l === 1).length;
let skipped = false;
const evLevels = await page.$$eval('.evpx-root h1, .evpx-root h2, .evpx-root h3, .evpx-root h4', (els) => els.map((e) => +e.tagName[1]));
for (let i = 1; i < evLevels.length; i++) if (evLevels[i] > evLevels[i - 1] + 1) skipped = true;
check('heading hierarchy: at most one h1 on the page, no skipped levels inside EV widgets', h1Count <= 1 && !skipped, `h1=${h1Count} levels=${evLevels.join('')}`);

const decisionItems = await page.$$eval('.evpx-decision__item', (e) => e.length);
if (decisionItems) check('decision factors render as a numbered list', decisionItems >= 3);
const relatedItems = await page.$$eval('.evpx-related__item', (e) => e.length);
if (relatedItems) check('related articles list real posts', relatedItems >= 1);

const faqItem = await page.$('.evpx-faq__item');
if (faqItem) {
	const before = await faqItem.$eval('.evpx-faq__answer', (el) => el.hasAttribute('hidden'));
	await faqItem.$eval('.evpx-faq__question', (el) => el.click());
	await page.waitForTimeout(700);
	const after = await faqItem.$eval('.evpx-faq__answer', (el) => el.hasAttribute('hidden'));
	check('FAQ accordion toggles open on click', before === true && after === false);
}

if (await page.$('.evpx-comparison__tab--dc')) {
	await page.$eval('.evpx-comparison__tab--dc', (el) => el.click());
	await page.waitForTimeout(500);
	const dcVisible = await page.$eval('.evpx-comparison__panel--dc', (el) => getComputedStyle(el).display !== 'none');
	const acVisible = await page.$eval('.evpx-comparison__panel--ac', (el) => getComputedStyle(el).display !== 'none');
	check('AC/DC comparison tab switches visible panel', dcVisible && !acVisible);
}

// Keyboard: WAI-ARIA tabs pattern (roving tabindex, arrows, Home/End).
if (await page.$('.evpx-comparison__tab--ac')) {
	await page.$eval('.evpx-comparison__tab--dc', (el) => el.focus());
	await page.keyboard.press('Home');
	const homeOk = await page.$eval('.evpx-comparison__tab--ac', (el) => el.getAttribute('aria-selected') === 'true' && document.activeElement === el);
	await page.keyboard.press('ArrowRight');
	const rightOk = await page.$eval('.evpx-comparison__tab--dc', (el) => el.getAttribute('aria-selected') === 'true' && document.activeElement === el && el.tabIndex === 0);
	const linked = await page.$eval('.evpx-comparison__tab--dc', (el) => {
		const panel = document.getElementById(el.getAttribute('aria-controls'));
		return !!panel && panel.getAttribute('aria-labelledby') === el.id && getComputedStyle(panel).display !== 'none';
	});
	check('comparison tabs: Home/Arrow keys move selection and focus, aria-controls links panel', homeOk && rightOk && linked);
}

const cardTops = await page.$$eval('.evpx-scenarios__grid > *', (els) => els.map((el) => Math.round(el.getBoundingClientRect().top)));
if (cardTops.length >= 3) check('scenario cards align into clean grid rows', new Set(cardTops.slice(0, 3)).size === 1);

// Check the alignfull WRAPPER, not an inner .evpx-container: Hero narrows its
// own text column on purpose (max-width: 46rem).
const wrapperWidth = await page.$eval('.evpx-hero.alignfull', (el) => el.getBoundingClientRect().width).catch(() => 0);
check('top-level section is not squeezed by a theme content-width wrapper', wrapperWidth >= 1280 - 20);

// ------------------------------------------------------------------- motion
if (!hasGsap) {
	for (const l of ['progress bar tracks scroll', 'reveals settle fully visible', 'card hover survives reveal'])
		skip(l, 'GSAP not loaded');
} else {
	const bar = await page.$('.evpx-progress__fill');
	if (bar) {
		await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
		await page.waitForTimeout(600);
		const scale = await bar.evaluate((el) => new DOMMatrix(getComputedStyle(el).transform).a);
		check('progress bar tracks scroll', scale > 0.95, `scaleX=${scale}`);
	}

	await page.evaluate(() => window.scrollTo(0, 0));
	await scrollThrough(page);
	await page.waitForTimeout(1500);
	const stuck = await notFullyVisible(page);
	check('scroll reveals settle fully visible', stuck === 0, `${stuck} element(s) stuck hidden`);

	const card = await page.$('.evpx-scenario-card');
	if (card) {
		await card.scrollIntoViewIfNeeded();
		await card.hover();
		await page.waitForTimeout(900);
		const ty = await card.evaluate((el) => new DOMMatrix(getComputedStyle(el).transform).f);
		check('card :hover lift still works after its reveal ran', ty < -1, `translateY=${ty}`);
	}
}
await page.close();

// ------------------------------------------------------------ reduced motion
{
	const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' });
	const p = await ctx.newPage();
	await p.goto(url, { waitUntil: 'networkidle' });
	await p.waitForTimeout(600);
	const heroOpacity = await p.$eval('.evpx-hero__title', (el) => getComputedStyle(el).opacity);
	const hidden = await notFullyVisible(p);
	const triggers = await p.evaluate(() => (window.ScrollTrigger ? window.ScrollTrigger.getAll().length : 0));
	check('prefers-reduced-motion: nothing hidden, no scroll triggers', heroOpacity === '1' && hidden === 0 && triggers === 0, `hero=${heroOpacity} hidden=${hidden} triggers=${triggers}`);
	await ctx.close();
}

// ------------------------------------------------------------ JavaScript off
{
	const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, javaScriptEnabled: false });
	const p = await ctx.newPage();
	await p.goto(url, { waitUntil: 'load' });
	await p.waitForTimeout(300);
	const s = await p.evaluate(() => ({
		faqVisible: [...document.querySelectorAll('.evpx-faq__answer')].every((e) => getComputedStyle(e).display !== 'none'),
		tabsHidden: [...document.querySelectorAll('.evpx-comparison__tabs')].every((e) => getComputedStyle(e).display === 'none'),
		bothPanels: [...document.querySelectorAll('.evpx-comparison__panel')].every((e) => getComputedStyle(e).display !== 'none'),
		hero: getComputedStyle(document.querySelector('.evpx-hero__title')).opacity,
	}));
	check('JavaScript disabled: FAQ answers and both AC/DC panels readable, no dead tabs, hero visible', s.faqVisible && s.tabsHidden && s.bothPanels && s.hero === '1', JSON.stringify(s));
	await ctx.close();
}

// ------------------------------------------------- builder canvas (iframe)
{
	const host = await browser.newPage({ viewport: { width: 1280, height: 900 } });
	await host.setContent(`<iframe id="canvas" src="${url}" style="width:1280px;height:900px;border:0"></iframe>`);
	await host.waitForTimeout(3500);
	const frame = host.frames().find((f) => f !== host.mainFrame() && f.url().startsWith(url.split('?')[0]));
	if (!frame) {
		check('builder-canvas simulation (page inside an iframe)', false, 'iframe did not load');
	} else {
		const s = await frame.evaluate(() => ({
			allowed: window.EVPX ? window.EVPX.motionAllowed() : null,
			triggers: window.ScrollTrigger ? window.ScrollTrigger.getAll().length : 0,
			hero: getComputedStyle(document.querySelector('.evpx-hero__title')).opacity,
			hidden: [...document.querySelectorAll('[data-evpx-reveal]')].filter((e) => getComputedStyle(e).opacity !== '1').length,
		}));
		check(
			'builder canvas (iframe): motion off, content fully visible, no scroll triggers',
			s.allowed === false && s.triggers === 0 && s.hero === '1' && s.hidden === 0,
			JSON.stringify(s)
		);
	}
	await host.close();
}

// -------------------------------------------- hero never flashes on slow GSAP
if (hasGsap) {
	const p = await browser.newPage({ viewport: { width: 1280, height: 800 } });
	await p.route(/gsap\.min\.js|ScrollTrigger\.min\.js/, async (r) => {
		await new Promise((x) => setTimeout(x, 1200));
		r.continue();
	});
	await p.addInitScript(() => {
		window.__opacity = [];
		const tick = () => {
			const el = document.querySelector('.evpx-hero__title');
			if (el) window.__opacity.push(+getComputedStyle(el).opacity);
			if (performance.now() < 5000) requestAnimationFrame(tick);
		};
		requestAnimationFrame(tick);
	});
	await p.goto(url, { waitUntil: 'networkidle' });
	await p.waitForTimeout(3500);
	const samples = await p.evaluate(() => window.__opacity);
	let flashed = false;
	for (let i = 1; i < samples.length; i++) if (samples[i - 1] > 0.9 && samples[i] < 0.1) flashed = true;
	check('hero entrance never flashes visible → hidden → visible on a slow GSAP load', !flashed && samples.at(-1) === 1, `${samples.length} samples`);
	await p.close();
}

// --------------------------------------------------------------------- axe
// Desktop and phone width: the layouts differ, so their contrast and target sizes can too.
{
	const found = [];
	let axeAvailable = true;
	for (const width of [1280, 390]) {
		const p = await browser.newPage({ viewport: { width, height: 900 } });
		await p.goto(url, { waitUntil: 'networkidle' });
		await scrollThrough(p);
		await p.waitForTimeout(1500);
		await p.evaluate(() => window.scrollTo(0, 0));
		const violations = await axeViolations(p, path.join(outDir, `axe-${width}.json`));
		if (violations === null) axeAvailable = false;
		else found.push(...violations.map((v) => `${v} @${width}px`));
		await p.close();
	}
	if (!axeAvailable) skip('axe-core accessibility audit of EV widgets', 'axe-core not installed');
	else check('axe-core: zero WCAG A/AA + best-practice violations inside EV widgets at 1280px and 390px', found.length === 0, found.join(', '));
}

await browser.close();
finish(outDir);
