#!/usr/bin/env node
/**
 * Browser QA against a REAL Breakdance install: a page designed in Breakdance
 * whose Shortcode elements hold the EV widgets, on the front end and inside the
 * builder itself.
 *
 *   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
 *   bash tests/docker/breakdance-page.sh
 *   node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)" [outDir]
 *
 * The builder needs a login: admin / admin by default (what setup.sh creates),
 * override with EVPX_WP_USER / EVPX_WP_PASS. Same Playwright / axe-core / Chromium
 * requirements as qa.mjs.
 *
 * Every check here was written after the bug it guards was seen on a real
 * Breakdance 2.8.3 page: the hero title rendered dark-on-dark (Breakdance's
 * `.breakdance h2 { color }`), headings fell back to a system sans, primary
 * buttons turned blue, desktop layouts appeared inside a narrow section, the
 * stylesheet arrived after the content, and builder renders were animated.
 */
import { launch, reporter, scrollThrough, notFullyVisible, overflowProbe, axeViolations } from './lib.mjs';
import fs from 'node:fs';
import path from 'node:path';

const pageUrl = process.argv[2];
if (!pageUrl) {
	console.error('Usage: node tests/playwright/breakdance-qa.mjs <breakdance-page-url> [outDir]');
	process.exit(1);
}
const outDir = process.argv[3] || './qa-breakdance-output';
fs.mkdirSync(outDir, { recursive: true });

const origin = new URL(pageUrl).origin;
const postId = new URL(pageUrl).searchParams.get('page_id') || new URL(pageUrl).searchParams.get('p');
const browser = await launch();
const { check, skip, finish } = reporter();

// ============================================================ front end
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
const pageErrors = [];
page.on('pageerror', (e) => pageErrors.push(e.message));
await page.goto(pageUrl, { waitUntil: 'networkidle', timeout: 30000 });
const hasGsap = await page.evaluate(() => typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined');

const widgetCount = await page.$$eval('.evpx-root:not(.evpx-progress)', (e) => e.length);
const leftover = await page.evaluate(() => /\[evpx_[a-z_]+/.test(document.body.innerText));
check('every EV widget in the Breakdance tree renders, none left as shortcode text', widgetCount >= 10 && !leftover, `widgets=${widgetCount} leftover=${leftover}`);

check(
	'stylesheet is printed in <head> (the page never paints unstyled before it loads)',
	await page.evaluate(() => !!document.head.querySelector('link#evpx-styles-css'))
);

const notEvpxHeadings = await page.$$eval('.evpx-root :is(h1, h2, h3, h4, h5, h6)', (els) =>
	els.map((h) => ({ tag: h.tagName, cls: h.className, font: getComputedStyle(h).fontFamily })).filter((h) => !/EVPX/.test(h.font)).map((h) => `${h.tag}.${h.cls} → ${h.font.slice(0, 30)}`)
);
check("Breakdance's heading rules can't restyle widget headings (every heading keeps the EVPX typefaces)", notEvpxHeadings.length === 0, notEvpxHeadings.slice(0, 3).join(' | '));

const heroTitleColour = await page.$eval('.evpx-hero__title', (el) => getComputedStyle(el).color).catch(() => '');
const [r, g, b] = (heroTitleColour.match(/\d+/g) || [0, 0, 0]).map(Number);
check('hero title stays light on the dark hero (not Breakdance\'s dark heading colour)', (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255 > 0.85, heroTitleColour);

{
	const button = await page.$('.evpx-button--primary');
	if (!button) {
		skip('primary buttons keep their colour, also on hover', 'no primary button on the page');
	} else {
		await button.scrollIntoViewIfNeeded();
		const rest = await button.evaluate((el) => getComputedStyle(el).color);
		await button.hover();
		await page.waitForTimeout(400);
		const hover = await button.evaluate((el) => getComputedStyle(el).color);
		check("primary buttons keep white text, also on hover (Breakdance's link colours are blue)", rest === 'rgb(255, 255, 255)' && hover === 'rgb(255, 255, 255)', `rest=${rest} hover=${hover}`);
		await page.mouse.move(0, 0);
	}
}

const readingTime = await page.$eval('.evpx-hero__meta-item:last-child', (el) => el.textContent.trim()).catch(() => '');
const minutes = Number((readingTime.match(/(\d+) min read/) || [])[1] || 0);
check("hero reading time comes from the Breakdance tree, not the empty post_content ('1 min read')", minutes >= 5, readingTime || 'no reading time in hero');

{
	const faq = await page.$('.evpx-faq__item');
	if (faq) {
		await faq.scrollIntoViewIfNeeded();
		const before = await faq.$eval('.evpx-faq__answer', (el) => el.hasAttribute('hidden'));
		await faq.$eval('.evpx-faq__question', (el) => el.click());
		await page.waitForTimeout(700);
		const after = await faq.$eval('.evpx-faq__answer', (el) => el.hasAttribute('hidden'));
		check('FAQ opens inside a Breakdance section', before === true && after === false);
	}
	if (await page.$('.evpx-comparison__tab--dc')) {
		await page.$eval('.evpx-comparison__tab--dc', (el) => el.click());
		await page.waitForTimeout(500);
		const dc = await page.$eval('.evpx-comparison__panel--dc', (el) => getComputedStyle(el).display !== 'none');
		const ac = await page.$eval('.evpx-comparison__panel--ac', (el) => getComputedStyle(el).display !== 'none');
		check('AC/DC tabs switch inside a Breakdance section', dc && !ac);
	}
}

if (!hasGsap) {
	skip('motion runs on the front end and settles fully visible', 'GSAP not loaded');
} else {
	const p = await browser.newPage({ viewport: { width: 1280, height: 900 } });
	await p.goto(pageUrl, { waitUntil: 'networkidle' });
	await scrollThrough(p);
	await p.waitForTimeout(1500);
	const ready = await p.$eval('.evpx-hero', (el) => el.hasAttribute('data-evpx-ready')).catch(() => false);
	const stuck = await notFullyVisible(p);
	check('motion runs on the front end (hero released) and every reveal settles fully visible', ready && stuck === 0, `ready=${ready} stuck=${stuck}`);
	await p.close();
}

// Widths from a phone to a large monitor. The widgets sit in whatever column the
// theme/Breakdance gives them, so this is about the widgets fitting their own box.
{
	const widths = [320, 390, 768, 1024, 1440, 1920];
	const failures = [];
	for (const width of widths) {
		const p = await browser.newPage({ viewport: { width, height: 900 } });
		await p.goto(pageUrl, { waitUntil: 'networkidle' });
		await scrollThrough(p);
		await p.waitForTimeout(1000);
		const bad = await overflowProbe(p);
		if (bad.length) failures.push(`${width}px: ${bad[0]}`);
		if (width === 1440 || width === 390) await p.screenshot({ path: path.join(outDir, `front-${width}.png`), fullPage: true });
		await p.close();
	}
	check(`no widget overflows its box or scrolls the page sideways at ${widths.join(', ')}px`, failures.length === 0, failures.join(' | '));
}

{
	const found = [];
	let available = true;
	for (const width of [1280, 390]) {
		const p = await browser.newPage({ viewport: { width, height: 900 } });
		await p.goto(pageUrl, { waitUntil: 'networkidle' });
		await scrollThrough(p);
		await p.waitForTimeout(1500);
		await p.evaluate(() => window.scrollTo(0, 0));
		const v = await axeViolations(p, path.join(outDir, `axe-front-${width}.json`));
		if (v === null) available = false;
		else found.push(...v.map((x) => `${x} @${width}px`));
		await p.close();
	}
	if (!available) skip('axe-core on the Breakdance page', 'axe-core not installed');
	else check('axe-core: zero violations inside the widgets on the Breakdance page (1280px and 390px)', found.length === 0, found.join(', '));
}
check('no uncaught JavaScript exceptions on the front end', pageErrors.length === 0, pageErrors[0]);
await page.close();

// ============================================================ the builder
if (!postId) {
	skip('builder checks', 'could not read the post id from the page URL');
} else {
	const ctx = await browser.newContext({ viewport: { width: 1600, height: 950 } });
	const b = await ctx.newPage();
	await b.goto(`${origin}/wp-login.php`);
	await b.fill('#user_login', process.env.EVPX_WP_USER || 'admin');
	await b.fill('#user_pass', process.env.EVPX_WP_PASS || 'admin');
	await Promise.all([b.waitForNavigation(), b.click('#wp-submit')]);

	// Breakdance renders every Shortcode element through its own server-side-render
	// AJAX call (POST to a front-end URL, not admin-ajax.php).
	const ssr = [];
	b.on('response', async (res) => {
		const req = res.request();
		if (req.method() !== 'POST' || !(req.postData() || '').includes('breakdance_server_side_render')) return;
		let html = '';
		try {
			const j = await res.json();
			html = String(j?.data?.html ?? j?.html ?? '');
		} catch {}
		ssr.push({ status: res.status(), html });
	});
	const builderErrors = [];
	const builderPageErrors = [];
	b.on('console', (m) => m.type() === 'error' && builderErrors.push(m.text()));
	b.on('pageerror', (e) => builderPageErrors.push(e.message));

	await b.goto(`${origin}/?breakdance=builder&id=${postId}`, { waitUntil: 'load', timeout: 60000 });
	await b.waitForTimeout(12000);
	await b.screenshot({ path: path.join(outDir, 'builder.png') });

	check(
		"builder renders every widget through Breakdance's server-side render (all 200, none left as shortcode text)",
		ssr.length >= 10 && ssr.every((s) => s.status === 200 && s.html.includes('evpx-') && !/\[evpx_/.test(s.html)),
		`requests=${ssr.length} statuses=${[...new Set(ssr.map((s) => s.status))]}`
	);

	const hero = ssr.find((s) => s.html.includes('evpx-hero'));
	check(
		'builder render of the hero is static: data-evpx-animate="0" and no progress bar (recognised as a builder request)',
		!!hero && /data-evpx-animate="0"/.test(hero.html) && !hero.html.includes('evpx-progress'),
		hero ? hero.html.match(/data-evpx-animate="\d"/)?.[0] : 'no hero response'
	);

	const frame = b.frames().find((f) => f.url().includes('breakdance_iframe'));
	if (!frame) {
		check('builder canvas: widgets visible, motion off, no scroll triggers', false, 'canvas iframe not found');
	} else {
		const s = await frame.evaluate(() => ({
			widgets: document.querySelectorAll('.evpx-root:not(.evpx-progress)').length,
			motionAllowed: window.EVPX ? window.EVPX.motionAllowed() : 'no EVPX',
			builderFlag: window.EVPX_CONFIG && window.EVPX_CONFIG.builderContext,
			heroOpacity: document.querySelector('.evpx-hero__title') ? getComputedStyle(document.querySelector('.evpx-hero__title')).opacity : 'no hero',
			heroFont: document.querySelector('.evpx-hero__title') ? getComputedStyle(document.querySelector('.evpx-hero__title')).fontFamily.slice(0, 12) : '',
			triggers: window.ScrollTrigger ? window.ScrollTrigger.getAll().length : 0,
			hidden: [...document.querySelectorAll('[data-evpx-reveal]')].filter((e) => getComputedStyle(e).opacity !== '1').length,
		}));
		check(
			'builder canvas: every widget present, hero visible in its own typeface, motion off, no scroll triggers',
			s.widgets >= 10 && s.motionAllowed === false && !!s.builderFlag && s.heroOpacity === '1' && /EVPX/.test(s.heroFont) && s.triggers === 0 && s.hidden === 0,
			JSON.stringify(s)
		);
	}

	const ours = builderErrors.concat(builderPageErrors).filter((e) => /evpx|ev-charging-experience/i.test(e));
	check('builder: no console or page errors from this plugin', ours.length === 0, ours[0]);
	await ctx.close();
}

await browser.close();
finish(outDir);
