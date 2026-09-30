/**
 * Composition checks: what a screenshot review finds and an overflow sweep cannot. Each one is here because a
 * picture of the page showed it and nothing else had noticed.
 *
 *   node tests/playwright/layout-qa.mjs "$(cat tests/docker/.demo-url)" [outDir]
 *
 * Needs a page with the hero and its drawings (the demo article). Guards:
 *
 *  - the hero's state-of-charge figure is as bright as its type, beside the copy and in the band under it (a
 *    mask meant to soften the band's cut edge once faded the whole gauge to a ghost);
 *  - no drawing paints with a gradient that is measured from a box with no height or width (a gradient on a
 *    zero-height line paints nothing, which is how a horizon once went missing);
 *  - a unit inside a drawing keeps its case (kW, not KW);
 *  - the hero drawing's floor, horizon and glow run out to the edges of its box, in the band under the copy as well
 *    as beside it (they once stopped short and ended in vertical edges);
 *  - a Related Articles row ends square at every width: two to a row at a tablet with the odd card taking the
 *    whole row, three to a row wide, one to a row on a phone. The row is built from the widget's own markup, so
 *    the check does not depend on how many articles a site happens to have.
 */
import { launch, reporter } from './lib.mjs';
import fs from 'node:fs';

const [url, outDir = 'qa-layout-output'] = process.argv.slice(2);
if (!url) {
	console.error('usage: node layout-qa.mjs <page url> [outDir]');
	process.exit(2);
}
fs.mkdirSync(outDir, { recursive: true });

const { check, finish } = reporter();
const browser = await launch();
const scratch = await browser.newPage();

/** The brightest pixel of a PNG, as a grey level (0-255, by luma). */
async function peakLuma(png) {
	return scratch.evaluate(async (b64) => {
		const img = new Image();
		img.src = 'data:image/png;base64,' + b64;
		await img.decode();
		const canvas = document.createElement('canvas');
		canvas.width = img.width;
		canvas.height = img.height;
		const ctx = canvas.getContext('2d');
		ctx.drawImage(img, 0, 0);
		const data = ctx.getImageData(0, 0, img.width, img.height).data;
		let peak = 0;
		for (let i = 0; i < data.length; i += 4) peak = Math.max(peak, 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2]);
		return Math.round(peak);
	}, png.toString('base64'));
}

async function open(width, height) {
	const context = await browser.newContext({ viewport: { width, height } });
	const page = await context.newPage();
	await page.goto(url, { waitUntil: 'networkidle' });
	await page.addStyleTag({ content: 'html{scroll-behavior:auto!important} .evpx-progress{display:none!important}' });
	return { context, page };
}

/* ---------------------------------------------------------------- 1. the gauge reads */
for (const [width, height] of [[820, 1180], [1440, 900]]) {
	const { context, page } = await open(width, height);
	await page.waitForTimeout(6000); // the hero draws itself in over about four seconds
	// A block theme's header can put the gauge below the window, and a clip outside the window is an error, so it is
	// brought to the middle of it first.
	const box = await page.evaluate(async () => {
		const text = document.querySelector('.evpx-hero .evpx-art__soc .evpx-art__value');
		if (!text || getComputedStyle(text.closest('.evpx-art__soc')).display === 'none') return null;
		text.scrollIntoView({ block: 'center' });
		await new Promise((done) => requestAnimationFrame(() => requestAnimationFrame(done)));
		const r = text.getBoundingClientRect();
		return { x: r.x, y: r.y, width: r.width, height: r.height };
	});
	if (!box) {
		check(`hero @${width}: the state-of-charge gauge is drawn`, false, 'not found, or not displayed');
	} else {
		const peak = await peakLuma(await page.screenshot({ clip: box }));
		await page.locator('.evpx-hero').screenshot({ path: `${outDir}/hero-${width}.png` });
		check(`hero @${width}: the state-of-charge figure is as bright as its type (brightest pixel ${peak} of 255, needs 190)`, peak >= 190);
	}
	await context.close();
}

/* ---------------------------------------------------------------- 2. drawings: paints and units */
{
	const { context, page } = await open(1440, 900);

	// Every shape painted from a gradient measured against its own box, and whether it is on screen (a drawing in
	// a tab that is not showing has no box at all, so the page is read again with the other tab open).
	const scan = () =>
		page.evaluate(() => {
			const rows = [];
			for (const svg of document.querySelectorAll('svg.evpx-art')) {
				const units = new Map([...svg.querySelectorAll('linearGradient, radialGradient')].map((g) => [g.id, g.getAttribute('gradientUnits') || 'objectBoundingBox']));
				for (const el of svg.querySelectorAll('[fill^="url(#"], [stroke^="url(#"]')) {
					for (const attr of ['fill', 'stroke']) {
						const ref = (el.getAttribute(attr) || '').match(/^url\(#([^)]+)\)$/);
						if (!ref || units.get(ref[1]) === 'userSpaceOnUse') continue;
						const box = el.getBBox();
						rows.push({
							key: `${svg.getAttribute('class').split(' ')[1]} ${el.getAttribute('class') || el.tagName} ${attr}=${ref[1].replace(/^evpx-art-\d+-/, '')}`,
							shown: el.getClientRects().length > 0,
							size: `${Math.round(box.width)}×${Math.round(box.height)}`,
							ok: box.width > 0 && box.height > 0,
						});
					}
				}
			}
			return rows;
		});

	const shapes = new Map();
	const tabs = page.locator('.evpx-comparison__tab');
	for (let pass = 0; pass < Math.max(1, await tabs.count()); pass++) {
		if (pass > 0) await tabs.nth(pass).click();
		for (const row of await scan()) {
			const seen = shapes.get(row.key) || { shown: false, bad: '' };
			shapes.set(row.key, { shown: seen.shown || row.shown, bad: seen.bad || (row.shown && !row.ok ? row.size : '') });
		}
	}
	const shown = [...shapes].filter(([, s]) => s.shown);
	const degenerate = shown.filter(([, s]) => s.bad).map(([key, s]) => `${key} in a ${s.bad} box`);
	check(`drawings: none of the ${shown.length} kinds of shape painted with a bounding-box gradient sits in a box with no height or width`, shown.length > 0 && degenerate.length === 0, degenerate.slice(0, 3).join(' | ') || `kinds=${shown.length}`);

	const words = await page.evaluate(() => {
		const uppercased = [];
		let units = 0;
		const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
		while (walker.nextNode()) {
			const node = walker.currentNode;
			if (!node.parentElement.closest('svg.evpx-art') || !/\b(kW|kWh|Hz)\b/.test(node.textContent)) continue;
			units++;
			if (getComputedStyle(node.parentElement).textTransform !== 'none') uppercased.push(node.textContent.trim());
		}
		return { uppercased, units };
	});
	check(`drawings: the ${words.units} units (kW, kWh, Hz) keep their case`, words.units > 0 && words.uppercased.length === 0, words.uppercased.slice(0, 3).join(' | ') || `units=${words.units}`);
	await context.close();
}

/* ---------------------------------------------------------------- 3. the hero's floor reaches the edges */
{
	// The drawing's floor, horizon and glow are drawn past its box so that they run out under the hero's own edge. In
	// the band under the copy the drawing is centred in a box wider than itself, and short extents show as vertical
	// edges there. Measured as how far each falls short of the drawing's box on the side that shows.
	const widths = [[390, 844, 'both'], [700, 1000, 'both'], [820, 1180, 'both'], [1000, 800, 'both'], [1440, 900, 'right'], [1920, 1000, 'right']];
	const short = [];
	for (const [width, height, sides] of widths) {
		const { context, page } = await open(width, height);
		const gaps = await page.evaluate(() => {
			const visual = document.querySelector('.evpx-hero__visual');
			if (!visual) return null;
			const box = visual.getBoundingClientRect();
			return [...visual.querySelectorAll('.evpx-art__floor, rect.evpx-art__fade')].map((el) => {
				const r = el.getBoundingClientRect();
				return { name: el.getAttribute('class').replace('evpx-art__', ''), left: Math.round(r.left - box.left), right: Math.round(box.right - r.right) };
			});
		});
		if (!gaps || gaps.length < 3) short.push(`${width}: ${gaps ? gaps.length : 'no'} extents found`);
		else for (const g of gaps) if (g.right > 1 || (sides === 'both' && g.left > 1)) short.push(`${width}: ${g.name} falls ${sides === 'both' ? g.left + ' left / ' : ''}${g.right}px short`);
		await context.close();
	}
	check(`hero: the floor, horizon and glow reach the drawing's edges at ${widths.map((w) => w[0]).join(', ')} px`, short.length === 0, short.slice(0, 3).join(' | '));
}

/* ---------------------------------------------------------------- 4. the related row ends square */
{
	const item = (n) => `<li class="evpx-related__item"><div class="evpx-related__media"></div><div class="evpx-related__body"><p class="evpx-eyebrow evpx-related__category">EV infrastructure</p><h3 class="evpx-related__title"><a class="evpx-related__link" href="#fixture">An article title that runs to a second line ${n}</a></h3><time class="evpx-related__date" datetime="2026-09-01">September 1, 2026</time></div></li>`;
	const section = (count) => `<section class="evpx-root alignfull evpx-related" data-fixture="${count}"><div class="evpx-container"><ul class="evpx-related__list evpx-related__list--cols-3" role="list">${Array.from({ length: count }, (_, i) => item(i + 1)).join('')}</ul></div></section>`;

	const measure = (page) =>
		page.evaluate(() =>
			Object.fromEntries(
				[1, 2, 3].map((count) => {
					const root = document.querySelector(`[data-fixture="${count}"]`);
					const list = root.querySelector('.evpx-related__list').getBoundingClientRect();
					const rects = [...root.querySelectorAll('.evpx-related__item')].map((i) => i.getBoundingClientRect());
					const last = root.querySelector('.evpx-related__item:last-child');
					const media = last.querySelector('.evpx-related__media').getBoundingClientRect();
					const body = last.querySelector('.evpx-related__body').getBoundingClientRect();
					return [
						count,
						{
							rows: new Set(rects.map((r) => Math.round(r.top))).size,
							lastFills: rects.at(-1).width / list.width,
							lastSideBySide: media.right <= body.left + 1 && Math.abs(media.top + media.height / 2 - (body.top + body.height / 2)) < 60,
						},
					];
				})
			)
		);

	for (const [width, height] of [[820, 1180], [1366, 768], [390, 844]]) {
		const { context, page } = await open(width, height);
		await page.evaluate((html) => document.body.insertAdjacentHTML('beforeend', html), [1, 2, 3].map(section).join(''));
		const m = await measure(page);
		const summary = JSON.stringify(m);

		if (width === 820) {
			check('related @820 (two to a row): three cards make two rows, and the third takes the whole row with its picture beside its text', m[3].rows === 2 && m[3].lastFills > 0.98 && m[3].lastSideBySide, summary);
			check('related @820: two cards make one row, and a single card takes the row', m[2].rows === 1 && m[1].rows === 1 && m[1].lastFills > 0.98, summary);
		} else if (width === 1366) {
			check('related @1366 (three to a row): three cards make one row', m[3].rows === 1 && m[3].lastFills < 0.4, summary);
		} else {
			check('related @390: every card is a row of its own, full width', m[3].rows === 3 && m[3].lastFills > 0.98 && !m[3].lastSideBySide, summary);
		}
		await context.close();
	}
}

await scratch.close();
await browser.close();
finish(outDir);
