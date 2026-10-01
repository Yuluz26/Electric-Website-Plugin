#!/usr/bin/env node
/**
 * Browser QA against a REAL Breakdance install: a page designed in Breakdance whose Sections hold
 * the EV widgets, on the front end and inside the builder itself. Runs against either page that
 * tests/docker/breakdance-page.sh creates, and adapts:
 *
 *   EVPX_BREAKDANCE_ZIP=/path/to/breakdance.zip bash tests/docker/setup.sh
 *   bash tests/docker/breakdance-page.sh
 *   node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-url)"          # Shortcode elements
 *   node tests/playwright/breakdance-qa.mjs "$(cat tests/docker/.breakdance-native-url)"   # native EV elements
 *
 * On a native page it also chooses a picture in the builder's media library — that check needs the
 * fixture images from `bash tests/docker/media-pages.sh` (skipped without them), and the page that
 * script builds (`tests/docker/.media-native-url`) is a good one to run this against as well.
 *
 * The builder needs a login: admin / admin by default (what setup.sh creates), override with
 * EVPX_WP_USER / EVPX_WP_PASS. Same Playwright / axe-core / Chromium requirements as qa.mjs. The builder
 * selectors were written against Breakdance 2.8.3.
 *
 * Every check here was written after the bug it guards was seen on a real Breakdance page: the hero
 * title rendered dark-on-dark (Breakdance's `.breakdance h2 { color }`), headings fell back to a system
 * sans, primary buttons turned blue, desktop layouts appeared inside a narrow section, the stylesheet
 * arrived after the content, and builder renders were animated.
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
const native = (await page.$('[class*="evpx-native-"]')) !== null;
console.log(`Page under test: ${native ? 'native EV elements' : 'EV widgets in Breakdance Shortcode elements'}`);

const widgetCount = await page.$$eval('.evpx-root:not(.evpx-progress)', (e) => e.length);
const leftover = await page.evaluate(() => /\[evpx_[a-z_]+/.test(document.body.innerText));
check('every EV widget in the Breakdance tree renders, none left as shortcode text', widgetCount >= 10 && !leftover, `widgets=${widgetCount} leftover=${leftover}`);

// Once, in <head>: the page never paints unstyled before it loads, and nothing is fetched twice.
const assets = await page.evaluate(() => ({
	styleInHead: !!document.head.querySelector('link[rel=stylesheet][href*="assets/css/evpx.css"]'),
	styleTags: document.querySelectorAll('link[rel=stylesheet][href*="assets/css/evpx.css"]').length,
	coreTags: document.querySelectorAll('script[src*="assets/js/evpx.js"]').length,
	motionTags: document.querySelectorAll('script[src*="assets/js/motion.js"]').length,
	gsapTags: document.querySelectorAll('script[src*="gsap.min.js"]').length,
}));
check(
	'stylesheet is printed in <head>, and each asset once (no double load between WordPress and Breakdance)',
	assets.styleInHead && assets.styleTags === 1 && assets.coreTags === 1 && assets.motionTags === 1 && assets.gsapTags === 1,
	JSON.stringify(assets)
);

const notEvpxHeadings = await page.$$eval('.evpx-root :is(h1, h2, h3, h4, h5, h6)', (els) =>
	els.map((h) => ({ tag: h.tagName, cls: h.className, font: getComputedStyle(h).fontFamily })).filter((h) => !/EVPX/.test(h.font)).map((h) => `${h.tag}.${h.cls} → ${h.font.slice(0, 30)}`)
);
check("Breakdance's heading rules can't restyle widget headings (every heading keeps the EVPX typefaces)", notEvpxHeadings.length === 0, notEvpxHeadings.slice(0, 3).join(' | '));

const heroTitleColour = await page.$eval('.evpx-hero__title', (el) => getComputedStyle(el).color).catch(() => '');
const [r, g, b] = (heroTitleColour.match(/\d+/g) || [0, 0, 0]).map(Number);
check("hero title stays light on the dark hero (not Breakdance's dark heading colour)", (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255 > 0.85, heroTitleColour);

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
	const ctx = await browser.newContext({ viewport: { width: 1600, height: 1000 } });
	const b = await ctx.newPage();
	await b.goto(`${origin}/wp-login.php`);
	await b.fill('#user_login', process.env.EVPX_WP_USER || 'admin');
	await b.fill('#user_pass', process.env.EVPX_WP_PASS || 'admin');
	await Promise.all([b.waitForNavigation(), b.click('#wp-submit')]);

	// Breakdance renders every Shortcode / native element through its own server-side-render
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
		ssr.push({ status: res.status(), html, body: req.postData() || '' });
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
			// Shortcode elements load the scripts through WordPress and get the "builder" flag; native
			// elements deliver only the stylesheet in the builder, so there is nothing to switch off.
			scripts: typeof window.EVPX !== 'undefined',
			motionAllowed: window.EVPX ? window.EVPX.motionAllowed() : null,
			builderFlag: window.EVPX_CONFIG && window.EVPX_CONFIG.builderContext,
			styled: !!document.querySelector('link[rel=stylesheet][href*="assets/css/evpx.css"]'),
			heroOpacity: document.querySelector('.evpx-hero__title') ? getComputedStyle(document.querySelector('.evpx-hero__title')).opacity : 'no hero',
			heroFont: document.querySelector('.evpx-hero__title') ? getComputedStyle(document.querySelector('.evpx-hero__title')).fontFamily.slice(0, 12) : '',
			triggers: window.ScrollTrigger ? window.ScrollTrigger.getAll().length : 0,
			hidden: [...document.querySelectorAll('[data-evpx-reveal]')].filter((e) => getComputedStyle(e).opacity !== '1').length,
		}));
		const motionOff = native ? !s.scripts : s.motionAllowed === false && !!s.builderFlag;
		check(
			'builder canvas: every widget present and styled, hero visible in its own typeface, motion off, no scroll triggers',
			s.widgets >= 10 && s.styled && motionOff && s.heroOpacity === '1' && /EVPX/.test(s.heroFont) && s.triggers === 0 && s.hidden === 0,
			JSON.stringify(s)
		);
	}

	if (native && frame) {
		// The PRD's definition of done: widgets appear in Breakdance and can be edited visually.
		await b.getByText('Add', { exact: true }).first().click();
		await b.fill('input[placeholder="Search elements"]', 'EV ');
		await b.waitForTimeout(1200);
		const listed = await b.evaluate(() =>
			[...document.querySelectorAll('.breakdance-add-panel__element-name')].map((e) => e.textContent.trim()).filter((t) => /^EV /.test(t))
		);
		await b.screenshot({ path: path.join(outDir, 'builder-add-panel.png') });
		const expected = ['EV Article Hero', 'EV Section', 'EV AC/DC Comparison', 'EV Charging Explorer', 'EV Scenario Cards', 'EV Technical Flow', 'EV Decision Factors', 'EV FAQ', 'EV Related Articles', 'EV CTA', 'EV Page Hero', 'EV Site Header', 'EV Search Results', 'EV Site Footer', 'EV Stats', 'EV Services', 'EV Process', 'EV Projects', 'EV Quotes', 'EV Contact'];
		check('the Add panel lists all twenty EV elements', listed.length === 20 && expected.every((name) => listed.includes(name)), `missing: ${expected.filter((n) => !listed.includes(n)).join(', ') || 'none'}; listed ${listed.length}`);

		// Select each kind of element in the canvas: its panel opens with the Content section and inputs.
		const kinds = await frame.evaluate(() => [...new Set([...document.querySelectorAll('[class*="evpx-native-"]')].flatMap((e) => [...e.classList].filter((c) => /^evpx-native-[a-z-]+$/.test(c))))]);
		const problems = [];
		for (const kind of kinds) {
			const el = frame.locator(`.${kind}`).first();
			await el.scrollIntoViewIfNeeded();
			await el.click({ position: { x: 20, y: 20 }, force: true });
			await b.waitForTimeout(1500);
			const panel = await b.evaluate(() => ({ text: document.body.innerText, inputs: document.querySelectorAll('input, textarea').length }));
			if (!panel.text.includes('Content') || panel.inputs < 2) problems.push(`${kind}: no controls`);
		}
		check(`selecting each of the ${kinds.length} kinds of native element in the canvas opens its controls`, kinds.length === 10 && problems.length === 0, problems.join(' | ') || `kinds=${kinds.length}`);

		// Edit a control: the canvas re-renders through one SSR call and shows the change.
		const faq = frame.locator('.evpx-native-faq').first();
		await faq.scrollIntoViewIfNeeded();
		await faq.click({ position: { x: 20, y: 20 }, force: true });
		await b.waitForTimeout(1500);
		const before = ssr.length;
		await b.locator('input[value="FAQ"]').first().fill('Edited in the builder');
		await b.waitForTimeout(2500);
		const shown = await frame.locator('.evpx-native-faq .evpx-eyebrow').first().innerText().catch(() => '');
		const posted = ssr.at(-1)?.body.match(/name="properties"\r?\n\r?\n([\s\S]*?)\r?\n------/);
		const eyebrow = posted ? JSON.parse(posted[1])?.content?.content?.eyebrow : null;
		check('editing a control re-renders the canvas through one server-side render and shows the change', ssr.length - before === 1 && /edited in the builder/i.test(shown) && eyebrow === 'Edited in the builder', `renders=${ssr.length - before} shown=${JSON.stringify(shown)} posted=${JSON.stringify(eyebrow)}`);

		// A toggle switched off is saved as an explicit false — the plugin must treat that as "off", not "unset".
		await b.getByText('Motion', { exact: true }).first().click();
		await b.waitForTimeout(600);
		const beforeToggle = ssr.length;
		await b.locator('.breakdance-control-toggle, [class*="toggle"] input[type="checkbox"], [role="switch"]').first().click({ force: true });
		await b.waitForTimeout(2500);
		const toggled = ssr.at(-1)?.body.match(/name="properties"\r?\n\r?\n([\s\S]*?)\r?\n------/);
		check('a toggle switched off in the builder is saved as false and re-renders', ssr.length - beforeToggle === 1 && toggled && JSON.parse(toggled[1])?.content?.motion?.animate === false, toggled ? JSON.stringify(JSON.parse(toggled[1])?.content?.motion) : 'no render');

		// The Items repeater, on the FAQ: the empty row "Add" creates isn't rendered (an empty button, an empty
		// entry in the structured data), giving it a question makes the item appear, its answer fills it.
		const faqEl = frame.locator('.evpx-native-faq').first();
		await faqEl.scrollIntoViewIfNeeded();
		await faqEl.click({ position: { x: 20, y: 20 }, force: true });
		await b.waitForTimeout(1500);
		const faqItems = () => frame.locator('.evpx-native-faq .evpx-faq__item').count();
		const itemsBefore = await faqItems();
		await b.getByText('Items', { exact: true }).first().click();
		await b.waitForTimeout(600);
		await b.getByRole('button', { name: 'Add EV FAQ Item' }).click();
		await b.waitForTimeout(2500);
		const itemsAfterAdd = await faqItems();
		const rowControl = (field, tag) => b.locator(`xpath=(//span[contains(@path,"].${field}")])[last()]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " breakdance-control-wrapper ")][1]//${tag}`).last();
		await rowControl('question', 'input').fill('Do you offer site surveys?');
		await b.waitForTimeout(3000);
		const itemsAfterQuestion = await faqItems();
		await rowControl('answer', 'textarea').fill('Yes: a short survey comes first.');
		await b.waitForTimeout(3000);
		const lastItem = await frame.evaluate(() => ([...document.querySelectorAll('.evpx-native-faq .evpx-faq__item')].at(-1)?.innerText || '').replace(/\s+/g, ' ').trim());
		check(
			'the Items repeater works: a new empty row is not rendered, its question adds the item, its answer shows in the canvas',
			itemsBefore >= 2 && itemsAfterAdd === itemsBefore && itemsAfterQuestion === itemsBefore + 1 && lastItem === 'Do you offer site surveys? Yes: a short survey comes first.',
			JSON.stringify({ itemsBefore, itemsAfterAdd, itemsAfterQuestion, lastItem })
		);

		// A picture chosen in the builder's own media library: the control saves an object, the plugin keeps
		// its attachment id, and the canvas shows the described, responsive picture. Needs the fixture images
		// (tests/docker/media-pages.sh); without them there is nothing to choose.
		const heroEl = frame.locator('.evpx-native-hero').first();
		await heroEl.scrollIntoViewIfNeeded();
		await heroEl.click({ position: { x: 20, y: 20 }, force: true });
		await b.waitForTimeout(1500);
		await b.getByText('Media', { exact: true }).first().click();
		await b.waitForTimeout(600);
		const chooser = b.locator('.media-chooser-layer-button').first();
		await chooser.scrollIntoViewIfNeeded();
		await chooser.click({ force: true });
		await b.waitForTimeout(2500);
		const library = b.frames().find((f) => f.url().includes('breakdance_wpuiforbuilder_media'));
		const tile = library?.locator('li.attachment[aria-label="EVPX fixture: hero"]').first();
		if (!tile || (await tile.count()) === 0) {
			skip('choosing a picture in the builder saves its attachment id and shows it', 'no fixture images in the media library (run tests/docker/media-pages.sh)');
		} else {
			const beforePicture = ssr.length;
			// Already ticked when the page's hero has this picture; clicking it again would untick it.
			if ((await tile.getAttribute('aria-checked')) !== 'true') await tile.click();
			await library.locator('button.media-button-select').first().click();
			await b.waitForTimeout(4000);
			const picked = ssr.at(-1)?.body.match(/name="properties"\r?\n\r?\n([\s\S]*?)\r?\n------/);
			const saved = picked ? JSON.parse(picked[1])?.content?.media?.media : null;
			const shown = await frame.evaluate(() => {
				const img = document.querySelector('.evpx-native-hero .evpx-hero__media img');
				return img ? { loaded: img.complete && img.naturalWidth > 0, alt: img.alt, srcset: !!img.srcset, src: img.currentSrc } : null;
			});
			check(
				'choosing a picture in the builder saves it as a media object with an id, re-renders once, and the canvas shows it described and responsive',
				ssr.length - beforePicture === 1 && Number.isInteger(saved?.id) && saved.id > 0 && !!shown?.loaded && shown.alt !== '' && shown.srcset && /evpx-fixture-hero/.test(shown.src),
				JSON.stringify({ renders: ssr.length - beforePicture, savedId: saved?.id, shown })
			);
		}
	}

	if (native && frame) {
		// Dynamic data through the builder's own button on a text control. Breakdance hands the server-side
		// render the raw properties, tokens included, so a native element has to resolve them itself or the
		// canvas shows "[breakdance_dynamic …]"; and the plugin's own field must be open to everyone (a
		// "Pro" badge means it can't be chosen without a Breakdance Pro licence).
		const dynamicControl = (path) => b.locator(`xpath=//span[@path="${path}"]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " breakdance-control-wrapper ")][1]`);
		const pickDynamic = async (path, field) => {
			const wrapper = dynamicControl(path);
			if (!(await wrapper.isVisible())) await b.getByText('Content', { exact: true }).first().click();
			await wrapper.scrollIntoViewIfNeeded();
			await wrapper.hover();
			await wrapper.locator('button.dynamic-data-chooser-button').click({ force: true });
			await b.waitForTimeout(1000);
			const choice = b.locator('button', { hasText: field }).first();
			const label = ((await choice.innerText().catch(() => '')) || '').replace(/\s+/g, ' ').trim();
			const beforeChoice = ssr.length;
			await choice.click();
			await b.waitForTimeout(3500);
			const sent = ssr.at(-1)?.body.match(/name="properties"\r?\n\r?\n([\s\S]*?)\r?\n------/);
			const key = path.split('.').pop();
			return { label, renders: ssr.length - beforeChoice, saved: sent ? JSON.parse(sent[1])?.content?.content?.[key] : null };
		};

		await frame.locator('.evpx-native-hero').first().click({ position: { x: 20, y: 20 }, force: true });
		await b.waitForTimeout(1500);

		const pageTitle = await (async () => {
			for (const type of ['pages', 'posts']) {
				const res = await b.request.get(`${origin}/?rest_route=/wp/v2/${type}/${postId}`);
				if (res.ok()) return b.evaluate((html) => Object.assign(document.createElement('textarea'), { innerHTML: html }).value, (await res.json()).title.rendered);
			}
			return '';
		})();
		const postTitle = await pickDynamic('content.content.title', 'Post Title');
		const shownTitle = await frame.locator('.evpx-native-hero .evpx-hero__title').first().innerText().catch(() => '');
		check(
			'dynamic data: "Post Title" chosen on the Hero title is saved as a token and the canvas shows the title, not the token',
			/^\[breakdance_dynamic field=.post_title.\]$/.test(postTitle.saved || '') && postTitle.renders >= 1 && pageTitle !== '' && shownTitle.trim() === pageTitle.trim(),
			JSON.stringify({ ...postTitle, shownTitle, pageTitle })
		);

		const readingTime = await pickDynamic('content.content.reading_time', 'EV Reading Time');
		const shownMeta = await frame.evaluate(() => [...document.querySelectorAll('.evpx-native-hero .evpx-hero__meta-item')].map((e) => e.textContent.trim()));
		check(
			"dynamic data: the plugin's own EV Reading Time field is open to everyone (no Pro badge), is saved as a token, and the canvas shows the value",
			readingTime.label === 'EV Reading Time' && /^\[breakdance_dynamic field=.evpx_reading_time.\]$/.test(readingTime.saved || '') && shownMeta.some((t) => /min read/.test(t)) && !shownMeta.some((t) => t.includes('[breakdance_dynamic')),
			JSON.stringify({ ...readingTime, shownMeta })
		);
	}

	const ours = builderErrors.concat(builderPageErrors).filter((e) => /evpx|ev-charging-experience/i.test(e));
	check('builder: no console or page errors from this plugin', ours.length === 0, ours[0]);
	await ctx.close();
}

await browser.close();
finish(outDir);
