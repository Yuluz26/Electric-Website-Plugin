#!/usr/bin/env node
/**
 * The PRD's first definition of done: activating the plugin does not visually alter unrelated pages.
 * Two modes, driven by tests/docker/isolation-check.sh, which toggles the plugin between them:
 *
 *   node tests/playwright/isolation-qa.mjs shoot <out-dir> <state> <url> [<url> …]
 *       Screenshots each page (full page, 1280 px wide, motion and carets off) into <out-dir>/<state>-<n>.png
 *       and, when <state> is "active", asserts the page carries nothing from this plugin: no class, style
 *       or script with "evpx" / the plugin's folder in it.
 *   node tests/playwright/isolation-qa.mjs compare <out-dir> <count>
 *       Compares <out-dir>/active-<n>.png with <out-dir>/inactive-<n>.png pixel by pixel.
 *
 * Same Playwright requirements as qa.mjs; the comparison runs in the browser, so no image library is needed.
 */
import { launch, reporter } from './lib.mjs';
import fs from 'node:fs';
import path from 'node:path';

const [mode, outDir, ...rest] = process.argv.slice(2);
if (!['shoot', 'compare'].includes(mode) || !outDir) {
	console.error('Usage: isolation-qa.mjs shoot <out-dir> <state> <url>… | isolation-qa.mjs compare <out-dir> <count>');
	process.exit(1);
}
fs.mkdirSync(outDir, { recursive: true });

const browser = await launch();
const { check, finish } = reporter();

if (mode === 'shoot') {
	const [state, ...urls] = rest;
	for (const [i, url] of urls.entries()) {
		const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
		await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
		await page.waitForTimeout(500);

		if (state === 'active') {
			const found = await page.evaluate(() => ({
				markup: document.querySelectorAll('[class*="evpx"], [id*="evpx"], [data-evpx-animate]').length,
				assets: document.querySelectorAll('link[href*="ev-charging-experience"], script[src*="ev-charging-experience"], link[href*="evpx"], script[src*="evpx"]').length,
				inline: [...document.querySelectorAll('style, script:not([src])')].filter((n) => /evpx/i.test(n.textContent)).length,
			}));
			check(`${url.replace(/^https?:\/\/[^/]+/, '')}: no markup, style or script from this plugin on a page without EV elements`, found.markup === 0 && found.assets === 0 && found.inline === 0, JSON.stringify(found));
		}

		await page.screenshot({ path: path.join(outDir, `${state}-${i}.png`), fullPage: true, animations: 'disabled', caret: 'hide' });
		await page.close();
	}
} else {
	const count = Number(rest[0] || 0);
	const page = await browser.newPage();
	for (let i = 0; i < count; i++) {
		const read = (state) => fs.readFileSync(path.join(outDir, `${state}-${i}.png`)).toString('base64');
		const diff = await page.evaluate(
			async ({ a, b }) => {
				const load = async (b64) => {
					const img = new Image();
					img.src = 'data:image/png;base64,' + b64;
					await img.decode();
					return img;
				};
				const [x, y] = [await load(a), await load(b)];
				if (x.width !== y.width || x.height !== y.height) return { size: `${x.width}x${x.height} vs ${y.width}x${y.height}`, pixels: -1 };
				const data = (img) => {
					const c = document.createElement('canvas');
					c.width = img.width;
					c.height = img.height;
					const ctx = c.getContext('2d');
					ctx.drawImage(img, 0, 0);
					return ctx.getImageData(0, 0, img.width, img.height).data;
				};
				const [p, q] = [data(x), data(y)];
				let pixels = 0;
				for (let k = 0; k < p.length; k += 4) if (p[k] !== q[k] || p[k + 1] !== q[k + 1] || p[k + 2] !== q[k + 2]) pixels++;
				return { size: `${x.width}x${x.height}`, pixels };
			},
			{ a: read('active'), b: read('inactive') }
		);
		check(`page ${i}: identical with the plugin active and inactive (${diff.size})`, diff.pixels === 0, `${diff.pixels} pixels differ`);
	}
}

await browser.close();
finish();
