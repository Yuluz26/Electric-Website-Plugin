/**
 * The explorer: that the page a visitor gets before the script runs is the one they get after, that the
 * controls do what they say, and that it holds together at the sizes a page is read at.
 *
 *   node tests/playwright/explorer-qa.mjs "$(cat tests/docker/.demo-url)" [outDir]
 *
 * Needs a page with an [evpx_explorer] on it (the demo article has one) and `php` on the path, for the model grid
 * (tests/php/model-matrix.php): the PHP model and the script's copy of it are held to the same answers.
 */
import { launch, reporter, overflowProbe } from './lib.mjs';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const [url, outDir = 'qa-explorer-output'] = process.argv.slice(2);
if (!url) {
	console.error('usage: node explorer-qa.mjs <page url> [outDir]');
	process.exit(2);
}
fs.mkdirSync(outDir, { recursive: true });

const { check, skip, finish } = reporter();
const browser = await launch();

const OUT = {
	energy: '[data-out="energy"]',
	range: '[data-out="range"]',
	socFrom: '[data-out="soc-from"]',
	socTo: '[data-out="soc-to"]',
	verdict: '[data-out="verdict"]',
};

/** Everything the explorer prints, as text. */
const readout = (page) =>
	page.evaluate((sel) => {
		const text = (s) => (document.querySelector(s)?.textContent || '').trim();
		return {
			energy: text(sel.energy),
			range: text(sel.range),
			socFrom: text(sel.socFrom),
			socTo: text(sel.socTo),
			verdict: text(sel.verdict),
			rows: [...document.querySelectorAll('.evpx-explorer__amount-value')].map((e) => e.textContent.trim()),
		};
	}, OUT);

/* ---------------------------------------------------------------- 1. without a script */
let server;
{
	const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, javaScriptEnabled: false });
	const page = await context.newPage();
	await page.goto(url, { waitUntil: 'load' });

	const state = await page.evaluate(() => {
		const visible = (s) => {
			const el = document.querySelector(s);
			return !!el && getComputedStyle(el).display !== 'none' && el.getClientRects().length > 0;
		};
		return {
			explorer: !!document.querySelector('.evpx-explorer'),
			controlsShown: visible('.evpx-explorer__controls'),
			rowsShown: visible('.evpx-explorer__compare'),
			chartShown: visible('.evpx-explorer__chartbox'),
			rows: document.querySelectorAll('.evpx-explorer__row').length,
		};
	});
	server = await readout(page);

	check('no script: the explorer is on the page', state.explorer);
	check('no script: the controls, which would do nothing, are not shown', !state.controlsShown);
	check('no script: the comparison is shown, five chargers', state.rowsShown && state.rows === 5, JSON.stringify(state));
	check('no script: the empty chart box is not shown', !state.chartShown);
	check('no script: the figures and the sentence are worked out already', /\d/.test(server.energy) && /\d/.test(server.range) && server.verdict.length > 40, JSON.stringify(server));
	await context.close();
}

/* ---------------------------------------------------------------- 2. with one */
{
	const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const page = await context.newPage();
	const problems = [];
	page.on('pageerror', (e) => problems.push(e.message));
	page.on('console', (m) => m.type() === 'error' && problems.push(m.text()));
	await page.goto(url, { waitUntil: 'networkidle' });
	await page.locator('.evpx-explorer').first().scrollIntoViewIfNeeded();
	await page.waitForTimeout(1200);

	const boxBefore = await page.evaluate(() => document.querySelector('.evpx-explorer__chartbox')?.getBoundingClientRect().height);
	const shown = await page.evaluate(() => {
		const visible = (s) => {
			const el = document.querySelector(s);
			return !!el && getComputedStyle(el).display !== 'none' && el.getClientRects().length > 0;
		};
		return {
			controls: visible('.evpx-explorer__controls'),
			chart: visible('.evpx-explorer__chart'),
			curve: document.querySelectorAll('.evpx-explorer__chart .evpx-explorer__curve').length,
			curveD: (document.querySelector('.evpx-explorer__chart .evpx-explorer__curve:not(.evpx-explorer__curve--dim)')?.getAttribute('d') || '').length,
			valuetext: document.querySelector('.evpx-explorer__range')?.getAttribute('aria-valuetext'),
			live: document.querySelector('[data-out="verdict"]')?.getAttribute('aria-live'),
		};
	});
	check('the script shows the controls and draws the chart', shown.controls && shown.chart && shown.curve >= 2 && shown.curveD > 200, JSON.stringify(shown));
	check('the slider says its value in words, for a screen reader', /\d/.test(shown.valuetext || ''), shown.valuetext);
	check('the sentence is a polite live region', shown.live === 'polite');
	check('no console errors, no failed requests', problems.length === 0, problems.join(' | '));

	// What the script prints first is what the server printed.
	const client = await readout(page);
	check('the page before the script and after it print the same figures, sentence and comparison', JSON.stringify(client) === JSON.stringify(server), `${JSON.stringify(server)} vs ${JSON.stringify(client)}`);

	// The chart's box was reserved: drawing into it did not move the page.
	const boxAfter = await page.evaluate(() => document.querySelector('.evpx-explorer__chartbox')?.getBoundingClientRect().height);
	check('the chart\'s box keeps its height when it is drawn (nothing below it moves)', Math.abs(boxAfter - boxBefore) < 1.5, `${boxBefore} -> ${boxAfter}`);

	/* ---------------- the model, held to the PHP one */
	let grid = null;
	try {
		grid = JSON.parse(execFileSync('php', [path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'php', 'model-matrix.php')], { encoding: 'utf8' }));
	} catch (e) {
		skip('the script\'s charging model answers as the PHP one does', 'php is not on the path: ' + e.message.split('\n')[0]);
	}
	if (grid) {
		const bad = await page.evaluate((rows) => {
			const off = [];
			for (const r of rows) {
				const [battery, start, acLimit, dcPeak, consumption] = r.car;
				const model = window.EVPX.explorerModel({ battery, start, acLimit, dcPeak, consumption });
				const run = model.run(r.charger, r.minutes);
				const close = (a, b) => Math.abs(a - b) <= 1e-9 * Math.max(1, Math.abs(b));
				if (!close(run.energy, r.energy) || !close(run.soc, r.soc) || run.fullAt !== r.fullAt || !close(model.range(run.energy), r.range)) {
					off.push(`${r.car.join('/')} ${r.charger} ${r.minutes}min: ${run.energy} vs ${r.energy}, full ${run.fullAt} vs ${r.fullAt}`);
				}
			}
			return off.slice(0, 5);
		}, grid);
		check(`the script's charging model answers as the PHP one does (${grid.length} cases: five cars, five chargers, eight dwell times)`, bad.length === 0, bad.join(' | '));
	}

	/* ---------------- the controls */
	const start = await readout(page);

	await page.click('.evpx-explorer__preset[data-minutes="480"]');
	await page.waitForTimeout(600);
	let now = await readout(page);
	const dwell = await page.textContent('.evpx-explorer__dwell');
	check('a preset sets the dwell time: the readout says 8 h', /^8\s?h$/.test((dwell || '').trim().replace(/ /g, ' ')), dwell);
	check('a preset presses itself and releases the others', (await page.$$eval('.evpx-explorer__preset[aria-pressed="true"]', (b) => b.map((x) => x.getAttribute('data-minutes')))).join() === '480');
	check('eight hours on AC 22 kW fills the battery, and the sentence says when', /full after/i.test(now.verdict) || /4 h/.test(now.verdict), now.verdict);
	check('the figures changed with the dwell time', now.energy !== start.energy, `${start.energy} -> ${now.energy}`);
	check('the battery bar ends at full', now.socTo === '100', now.socTo);

	// The radios are the real controls, but what a pointer hits is their labels.
	const choose = async (value) => page.click(`label[for="${await page.getAttribute(`.evpx-explorer__radio[value="${value}"]`, 'id')}"]`);
	await choose('dc-350');
	await page.waitForTimeout(500);
	const rated = await page.evaluate(() => {
		const line = document.querySelector('.evpx-explorer__rated');
		return { line: !!line && !line.hasAttribute('hidden') && getComputedStyle(line).display !== 'none', selected: document.querySelector('.evpx-explorer__row--selected')?.getAttribute('data-charger') };
	});
	check('choosing a 350 kW charger draws its rating as a dashed line above what the car takes', rated.line, JSON.stringify(rated));
	check('the chosen charger is the lit row of the comparison', rated.selected === 'dc-350', rated.selected);

	await choose('ac-7');
	await page.waitForTimeout(500);
	const ratedAc = await page.evaluate(() => {
		const line = document.querySelector('.evpx-explorer__rated');
		return !!line && !line.hasAttribute('hidden') && getComputedStyle(line).display !== 'none';
	});
	check('a 7 kW charger, which the car takes in full, draws no such line', !ratedAc);

	// The slider, from the keyboard.
	await page.focus('.evpx-explorer__range');
	await page.keyboard.press('Home');
	await page.waitForTimeout(300);
	const low = (await page.textContent('.evpx-explorer__dwell')).replace(/ /g, ' ').trim();
	await page.keyboard.press('End');
	await page.waitForTimeout(300);
	const high = (await page.textContent('.evpx-explorer__dwell')).replace(/ /g, ' ').trim();
	check('the slider runs from the keyboard: Home is 15 min, End is 12 h', low === '15 min' && high === '12 h', `${low} / ${high}`);
	check('the slider\'s spoken value follows it', /12/.test(await page.getAttribute('.evpx-explorer__range', 'aria-valuetext')));

	// The sentence is announced once the pointer settles, not on every step.
	await page.keyboard.press('ArrowLeft');
	await page.keyboard.press('ArrowLeft');
	await page.keyboard.press('ArrowLeft');
	const during = (await readout(page)).verdict;
	await page.waitForTimeout(700);
	const after = (await readout(page)).verdict;
	check('while a key is held the sentence waits, and then settles on the last position', during !== after || after.length > 40, `${during} | ${after}`);

	await page.screenshot({ path: path.join(outDir, 'explorer-1440.png'), clip: await page.locator('.evpx-explorer').first().boundingBox() });

	/* ---------------- focus is visible on every control */
	const focus = await page.evaluate(async () => {
		const rows = [];
		const controls = [
			['range', '.evpx-explorer__range'],
			['preset', '.evpx-explorer__preset'],
			['charger', '.evpx-explorer__radio:checked + .evpx-explorer__segment'],
		];
		for (const [name, sel] of controls) {
			const el = document.querySelector(sel);
			if (!el) {
				rows.push([name, 'missing']);
				continue;
			}
			const target = name === 'charger' ? el.previousElementSibling : el;
			target.focus({ focusVisible: true });
			await new Promise((r) => setTimeout(r, 50));
			const cs = getComputedStyle(name === 'range' ? target : el);
			rows.push([name, target.matches(':focus-visible'), cs.outlineStyle, cs.outlineWidth]);
		}
		return rows;
	});
	const badFocus = focus.filter((r) => r[1] === 'missing' || (r[0] !== 'range' && !(r[2] !== 'none' && parseFloat(r[3]) >= 2)));
	check('a keyboard sees a 2px ring on the presets and on the chosen charger', badFocus.length === 0, JSON.stringify(focus));

	await context.close();
}

/* ---------------------------------------------------------------- 3. the sizes it is read at */
for (const [w, h] of [[1440, 900], [1366, 768], [1024, 768], [768, 1024], [390, 844], [320, 640]]) {
	const context = await browser.newContext({ viewport: { width: w, height: h } });
	const page = await context.newPage();
	await page.goto(url, { waitUntil: 'networkidle' });
	await page.locator('.evpx-explorer').first().scrollIntoViewIfNeeded();
	await page.waitForTimeout(900);

	const over = await overflowProbe(page);
	check(`${w}px: nothing pokes out of a widget, and the page does not scroll sideways`, over.length === 0, over.join(' | '));

	const legible = await page.evaluate(() => {
		const tick = document.querySelector('.evpx-explorer__chart .evpx-explorer__tick');
		if (!tick) return { size: 0 };
		const box = tick.getBoundingClientRect();
		return { size: Math.round(box.height), family: getComputedStyle(tick).fontFamily.slice(0, 20) };
	});
	// A drawing's labels are text: 12px on the screen, whatever the drawing is scaled to.
	const ticks = await page.evaluate(() => {
		const svg = document.querySelector('.evpx-explorer__chart');
		const t = document.querySelector('.evpx-explorer__chart .evpx-explorer__tick');
		if (!svg || !t) return 0;
		// A box too narrow to read a curve on hides it (the figures and bars carry the answer).
		if (getComputedStyle(svg.parentElement).display === 'none') return -1;
		const scale = svg.getBoundingClientRect().width / 640;
		return parseFloat(getComputedStyle(t).fontSize) * scale;
	});
	check(`${w}px: the chart's labels are 12px or more on the screen, or the chart is not shown`, ticks === -1 || ticks >= 11.5, `${ticks.toFixed(1)}px`);

	const targets = await page.evaluate(() => {
		const small = [];
		for (const el of document.querySelectorAll('.evpx-explorer__preset, .evpx-explorer__segment, .evpx-explorer__range')) {
			const r = el.getBoundingClientRect();
			if (r.height < 43.5 || r.width < 24) small.push(`${el.className}: ${Math.round(r.width)}×${Math.round(r.height)}`);
		}
		return small;
	});
	check(`${w}px: every control is a target of at least 44px tall`, targets.length === 0, targets.join(' | '));

	if (w === 390 || w === 768) await page.screenshot({ path: path.join(outDir, `explorer-${w}.png`), clip: await page.locator('.evpx-explorer').first().boundingBox() });
	await context.close();
}

/* ---------------------------------------------------------------- 4. reduced motion */
{
	const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
	const page = await context.newPage();
	await page.goto(url, { waitUntil: 'networkidle' });
	const t = await page.evaluate(() => parseFloat(getComputedStyle(document.querySelector('.evpx-explorer__bar-fill')).transitionDuration));
	check('reduced motion: the bars do not slide (their transition is instant)', t < 0.05, `${t}s`);
	await context.close();
}

await browser.close();
finish(outDir);
