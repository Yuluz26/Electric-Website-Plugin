#!/usr/bin/env node
/**
 * Browser QA for pictures. The plugin ships no photography, so tests/docker/media-pages.sh generates
 * test files and builds three pages; this checks how the widgets treat them:
 *
 *   bash tests/docker/media-pages.sh
 *   node tests/playwright/media-qa.mjs "$(cat tests/docker/.media-url)" \
 *        "$(cat tests/docker/.media-native-url)" "$(cat tests/docker/.media-mixed-url)" \
 *        "$(cat tests/docker/.media-bright-url)"
 *
 * Arguments: the shortcode page, then optionally the native-element page, the mixed Related Articles
 * page and the bright-hero page (each skipped when absent). Each content page is opened at 1440 and 390 px. Same Playwright
 * requirements as qa.mjs; no login needed (the builder with pictures is covered by running
 * breakdance-qa.mjs against the native page).
 *
 * What it guards: an attachment id (shortcode) and a Breakdance media value (native element) end up as
 * the same responsive, described, correctly-cropped <img>; a Related Articles row is all pictures or
 * none, never blank tiles next to photographs; the hero's meta line never leaves a separator
 * dangling when it wraps; and the hero's and the CTA's white type stays legible over a bright picture.
 */
import { launch, reporter, scrollThrough, overflowProbe } from './lib.mjs';
import fs from 'node:fs';
import path from 'node:path';

const [shortcodeUrl, nativeUrl, mixedUrl, brightUrl] = process.argv.slice(2);
if (!shortcodeUrl) {
	console.error('Usage: node tests/playwright/media-qa.mjs <shortcode-page-url> [<native-page-url> [<mixed-related-url> [<bright-hero-url>]]]');
	process.exit(1);
}
const outDir = process.env.EVPX_OUT_DIR || './qa-media-output';
fs.mkdirSync(outDir, { recursive: true });

const browser = await launch();
const { check, skip, finish } = reporter();

/** Everything about the pictures on a page, read in the browser. */
const readPictures = () => {
	const describe = (img) => {
		const box = img.getBoundingClientRect();
		const parent = img.parentElement.getBoundingClientRect();
		return {
			loaded: img.complete && img.naturalWidth > 0,
			alt: img.getAttribute('alt') || '',
			srcset: !!img.getAttribute('srcset'),
			fit: getComputedStyle(img).objectFit,
			box: `${Math.round(box.width)}x${Math.round(box.height)}`,
			parent: `${Math.round(parent.width)}x${Math.round(parent.height)}`,
			distortion: Math.abs(box.width / box.height / (img.naturalWidth / img.naturalHeight) - 1),
		};
	};
	const all = (sel) => [...document.querySelectorAll(sel)].map(describe);

	const meta = document.querySelector('.evpx-hero__meta');
	const items = meta ? [...meta.querySelectorAll('.evpx-hero__meta-item')] : [];

	return {
		hero: all('.evpx-hero__media img'),
		section: all('.evpx-section__media img'),
		icons: all('.evpx-scenario-card__icon img'),
		cta: all('.evpx-cta__media img'),
		related: all('.evpx-related__media img'),
		relatedItems: document.querySelectorAll('.evpx-related__item').length,
		meta: {
			rows: new Set(items.map((i) => Math.round(i.getBoundingClientRect().top))).size,
			overflowX: meta ? getComputedStyle(meta).overflowX : '',
		},
	};
};

async function checkContentPage(label, url) {
	for (const width of [1440, 390]) {
		const tag = `${label} @${width}`;
		const page = await browser.newPage({ viewport: { width, height: 900 } });
		const errors = [];
		const failed = [];
		page.on('pageerror', (e) => errors.push(e.message));
		page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
		page.on('response', (r) => r.status() >= 400 && failed.push(`${r.status()} ${r.url()}`));

		await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
		await scrollThrough(page);
		await page.evaluate(() => window.scrollTo(0, 0));
		await page.waitForTimeout(1000);
		// Whether a lazy picture has been fetched by now depends on how the host scrolls (Breakdance
		// smooth-scrolls); what is checked here is that every picture can load and render.
		await page.evaluate(() => Promise.all([...document.images].map((img) => ((img.loading = 'eager'), img.decode().catch(() => {})))));

		const p = await page.evaluate(readPictures);
		const loaded = (list) => list.length > 0 && list.every((i) => i.loaded);
		const covers = (i) => i.fit === 'cover' && i.box === i.parent;

		check(`${tag}: hero picture loads, is described, and has a srcset`, p.hero.length === 1 && loaded(p.hero) && p.hero[0].alt !== '' && p.hero[0].srcset, JSON.stringify(p.hero));
		check(`${tag}: hero picture fills its box (cover, no letterboxing)`, p.hero.length === 1 && covers(p.hero[0]), JSON.stringify(p.hero));
		check(`${tag}: both section pictures load, are described, and keep their proportions`, p.section.length === 2 && loaded(p.section) && p.section.every((i) => i.alt !== '' && (i.fit === 'cover' || i.distortion < 0.03)), JSON.stringify(p.section));
		check(`${tag}: all six scenario icons load`, p.icons.length === 6 && loaded(p.icons), JSON.stringify(p.icons.slice(0, 1)));
		check(`${tag}: CTA picture loads and fills its box`, p.cta.length === 1 && loaded(p.cta) && covers(p.cta[0]), JSON.stringify(p.cta));
		check(`${tag}: related row is a full row of same-sized pictures`, p.relatedItems >= 2 && p.related.length === p.relatedItems && loaded(p.related) && new Set(p.related.map((i) => i.box)).size === 1, JSON.stringify({ items: p.relatedItems, pictures: p.related.map((i) => i.box) }));
		if (width === 390) {
			check(`${tag}: a wrapped hero meta line clips the separator that would start a row`, p.meta.rows >= 2 && ['clip', 'hidden'].includes(p.meta.overflowX), JSON.stringify(p.meta));
		}

		const overflow = await overflowProbe(page);
		check(`${tag}: nothing overflows`, overflow.length === 0, overflow.join('; '));
		check(`${tag}: no console errors, no failed requests`, errors.length === 0 && failed.length === 0, [...errors, ...failed].join('; '));

		await page.screenshot({ path: path.join(outDir, `${label}-${width}.png`), fullPage: true });
		await page.close();
	}
}

/** The two widgets whose type sits on a picture, and the lines of type to measure on each. */
const OVER_A_PICTURE = {
	hero: { root: '.evpx-hero', lines: { eyebrow: '.evpx-hero .evpx-eyebrow', title: '.evpx-hero__title', excerpt: '.evpx-hero__excerpt', meta: '.evpx-hero__meta' } },
	CTA: { root: '.evpx-cta--media', lines: { eyebrow: '.evpx-cta--media .evpx-eyebrow', title: '.evpx-cta__title', body: '.evpx-cta__body' } },
};

/**
 * Contrast of a widget's type over its picture. The type is hidden, the widget screenshotted, and the
 * brightest 5% of the background under each line measured (the worst place a letter can land), then
 * compared with the type's colour: 3:1 for large type, 4.5:1 for the rest.
 */
async function checkLegibility(label, url, width, name, { root, lines: selectors }) {
	const page = await browser.newPage({ viewport: { width, height: 900 } });
	await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
	await page.evaluate(() => Promise.all([...document.images].map((img) => img.decode().catch(() => {}))));
	await page.waitForTimeout(500);

	const widget = page.locator(root).first();
	await widget.scrollIntoViewIfNeeded();
	const lines = await page.evaluate(
		({ root, selectors }) => {
			const box = document.querySelector(root).getBoundingClientRect();
			const out = {};
			for (const [key, selector] of Object.entries(selectors)) {
				const el = document.querySelector(selector);
				if (!el) continue;
				const r = el.getBoundingClientRect();
				const cs = getComputedStyle(el);
				out[key] = { x: r.left - box.left, y: r.top - box.top, w: r.width, h: r.height, color: cs.color, opacity: Number(cs.opacity), size: parseFloat(cs.fontSize), weight: Number(cs.fontWeight) };
			}
			return out;
		},
		{ root, selectors }
	);

	await page.addStyleTag({ content: '.evpx-hero__content *, .evpx-cta__content * { color: transparent !important; text-shadow: none !important; } .evpx-hero__cta, .evpx-cta__button { visibility: hidden !important; }' });
	await page.waitForTimeout(300);
	const shot = (await widget.screenshot()).toString('base64');

	const ratios = await page.evaluate(
		async ({ shot, lines }) => {
			const img = new Image();
			img.src = 'data:image/png;base64,' + shot;
			await img.decode();
			const canvas = document.createElement('canvas');
			canvas.width = img.width;
			canvas.height = img.height;
			const ctx = canvas.getContext('2d');
			ctx.drawImage(img, 0, 0);
			const lin = (v) => ((v /= 255) <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
			const lum = (r, g, b) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
			const out = {};
			for (const [key, t] of Object.entries(lines)) {
				const d = ctx.getImageData(Math.max(0, Math.floor(t.x)), Math.max(0, Math.floor(t.y)), Math.max(1, Math.floor(t.w)), Math.max(1, Math.floor(t.h))).data;
				const px = [];
				for (let i = 0; i < d.length; i += 4) px.push([lum(d[i], d[i + 1], d[i + 2]), d[i], d[i + 1], d[i + 2]]);
				px.sort((a, b) => a[0] - b[0]);
				const worst = px[Math.floor(px.length * 0.95)];
				const [r, g, b, a = 1] = (t.color.match(/[\d.]+/g) || []).map(Number);
				const alpha = a * t.opacity;
				const text = lum(r * alpha + worst[1] * (1 - alpha), g * alpha + worst[2] * (1 - alpha), b * alpha + worst[3] * (1 - alpha));
				const ratio = (Math.max(text, worst[0]) + 0.05) / (Math.min(text, worst[0]) + 0.05);
				const large = t.size >= 24 || (t.size >= 18.66 && t.weight >= 700);
				out[key] = { ratio: Number(ratio.toFixed(2)), needs: large ? 3 : 4.5, size: t.size };
			}
			return out;
		},
		{ shot, lines }
	);
	for (const [key, r] of Object.entries(ratios)) {
		check(`${label} @${width}: ${name} ${key} (${r.size}px) is legible over a bright picture, ${r.ratio}:1 (needs ${r.needs}:1)`, r.ratio >= r.needs);
	}
	await page.close();
}

await checkContentPage('shortcodes', shortcodeUrl);

if (nativeUrl) {
	await checkContentPage('native', nativeUrl);
} else {
	skip('native elements with pictures', 'no native-page URL given');
}

if (mixedUrl) {
	const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
	await page.goto(mixedUrl, { waitUntil: 'networkidle', timeout: 30000 });
	const row = await page.evaluate(() => ({
		items: document.querySelectorAll('.evpx-related__item').length,
		slots: document.querySelectorAll('.evpx-related__media').length,
		pictures: document.querySelectorAll('.evpx-related__media img').length,
		drawings: document.querySelectorAll('.evpx-related__media.evpx-artpanel svg.evpx-art').length,
		blank: [...document.querySelectorAll('.evpx-related__media')].filter((m) => !m.querySelector('img, svg.evpx-art')).length,
	}));
	check('a related row where one article has no picture gives it a drawing, so no tile is blank beside a photograph', row.items === 2 && row.slots === 2 && row.pictures === 1 && row.drawings === 1 && row.blank === 0, JSON.stringify(row));
	await page.screenshot({ path: path.join(outDir, 'mixed-related.png'), fullPage: true });
	await page.close();
} else {
	skip('mixed related row', 'no URL given');
}

if (brightUrl) {
	for (const [name, target] of Object.entries(OVER_A_PICTURE)) {
		for (const width of [1280, 390]) await checkLegibility('bright picture', brightUrl, width, name, target);
	}
} else {
	skip('type legibility over a bright picture', 'no URL given');
}

await browser.close();
finish(outDir);
