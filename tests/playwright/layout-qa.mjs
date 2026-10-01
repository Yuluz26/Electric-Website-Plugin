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
 *  - a wrapped hero byline is the author over the date and the reading time, never a lone item at the end;
 *  - the flow's rail starts at one socket, ends at the next and runs through their centres, across and down, and in a
 *    220px column nothing pokes out of it;
 *  - a Related Articles row ends square at every width: two to a row at a tablet with the odd card taking the
 *    whole row, three to a row wide, one to a row on a phone. The row is built from the widget's own markup, so
 *    the check does not depend on how many articles a site happens to have.
 */
import { launch, reporter, overflowProbe } from './lib.mjs';
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

async function open(width, height, options = {}) {
	const context = await browser.newContext({ viewport: { width, height }, ...options });
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

/* ---------------------------------------------------------------- 4. the byline never strands its last item */
{
	// The date and the reading time are a pair that wraps as one, so a byline that will not fit on a line is the author
	// over the two of them. It once wrapped its third item alone under the first (the strip needed 546px of a 544px
	// column, a hair too much, and again at every narrower width beside the drawing).
	const widths = [1920, 1440, 1100, 1024, 900, 390, 320];
	const strays = [];
	for (const width of widths) {
		const { context, page } = await open(width, 900);
		await page.evaluate(() => document.fonts.ready);
		const rows = await page.evaluate(() => {
			const tops = {};
			for (const item of document.querySelectorAll('.evpx-hero__meta-item')) (tops[Math.round(item.getBoundingClientRect().top)] ||= []).push(item.getAttribute('data-label'));
			return Object.keys(tops).map(Number).sort((a, b) => a - b).map((top) => tops[top]);
		});
		if (rows.flat().length >= 3 && rows.slice(1).some((row) => row.length < 2)) strays.push(`${width}: ${rows.map((row) => row.join(' + ')).join(' / ')}`);
		await context.close();
	}
	check(`hero: a wrapped byline is the author over the date and the reading time, never a lone item at the end (${widths.join(', ')} px)`, strays.length === 0, strays.slice(0, 3).join(' | '));
}

/* ---------------------------------------------------------------- 5. the flow's rail joins its sockets */
{
	// The rail (a groove and the light in it) is drawn from each step's socket to the next one's, behind both. Its
	// length and its place are arithmetic on the gap between rows and the size of a socket, which is easy to get
	// almost right: measured here from the drawn pseudo-elements against the sockets they should join.
	const rails = (page) =>
		page.evaluate(() => {
			const steps = [...document.querySelectorAll('.evpx-flow__step')];
			const across = getComputedStyle(document.querySelector('.evpx-flow__steps')).gridAutoFlow.startsWith('column');
			const centre = (step) => {
				const r = step.querySelector('.evpx-flow__node').getBoundingClientRect();
				return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
			};
			let worst = 0;
			let where = '';
			for (let i = 0; i < steps.length - 1; i++) {
				const a = centre(steps[i]);
				const b = centre(steps[i + 1]);
				const box = steps[i].getBoundingClientRect();
				for (const pseudo of ['::before', '::after']) {
					const cs = getComputedStyle(steps[i], pseudo);
					const left = box.left + parseFloat(cs.left);
					const top = box.top + parseFloat(cs.top);
					const w = parseFloat(cs.width);
					const h = parseFloat(cs.height);
					const gaps = across
						? { length: w - (b.x - a.x), start: left - a.x, centred: top + h / 2 - a.y }
						: { length: h - (b.y - a.y), start: top - a.y, centred: left + w / 2 - a.x };
					for (const [what, gap] of Object.entries(gaps)) {
						if (Math.abs(gap) > worst) {
							worst = Math.abs(gap);
							where = `step ${i + 1} ${pseudo} ${what} ${gap.toFixed(1)}px`;
						}
					}
				}
			}
			return { across, steps: steps.length, worst, where, box: document.querySelector('.evpx-flow').getBoundingClientRect().width };
		});

	const cases = [[1440, 900, false], [1100, 800, false], [768, 1024, false], [390, 844, false], [320, 700, false], [1440, 900, true]];
	const bad = [];
	for (const [width, height, vertical] of cases) {
		// Measured at rest: before the flow has been seen, motion holds each step 14px low.
		const { context, page } = await open(width, height, { reducedMotion: 'reduce' });
		if (vertical) {
			// A container query is re-evaluated a frame or two after a class changes: let it.
			await page.evaluate(async () => {
				document.querySelector('.evpx-flow').classList.replace('evpx-flow--horizontal', 'evpx-flow--vertical');
				await new Promise((done) => requestAnimationFrame(() => requestAnimationFrame(done)));
			});
		}
		await page.evaluate(() => document.fonts.ready);
		const r = await rails(page);
		// It runs across from a box of 48rem (768px), and the box is the widget's own: a host that pads it (a builder Section)
		// gives it less than the window.
		const expectStacked = vertical || r.box < 768;
		if (r.steps < 2 || r.worst > 1.5 || r.across === expectStacked) bad.push(`${width}${vertical ? ' (vertical)' : ''}: ${r.where || 'not laid out as expected'} (across=${r.across}, box ${Math.round(r.box)}px)`);
		await context.close();
	}
	check('flow: the rail starts at one socket, ends at the next and runs through their centres (across at 1440, 1100 and 768 px, down at 390, 320 and when set vertical)', bad.length === 0, bad.slice(0, 3).join(' | '));
}

{
	// The steps' track is sized to the box, not to its longest name: in a 220px column (a phone inside a builder Section
	// with padding of its own, which is where the 320px sweep of a Breakdance page found it) nothing may poke out.
	const { context, page } = await open(320, 700, { reducedMotion: 'reduce' });
	await page.evaluate(() => {
		const flow = document.querySelector('.evpx-flow');
		const box = document.createElement('div');
		box.style.cssText = 'width:220px;margin:0 auto';
		flow.before(box);
		box.append(flow);
	});
	await page.evaluate(() => document.fonts.ready);
	const bad = await overflowProbe(page);
	check('flow: in a 220 px column nothing pokes out of the widget', bad.length === 0, bad.slice(0, 2).join(' | '));
	await context.close();
}

/* ---------------------------------------------------------------- 6. the related row ends square */
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
