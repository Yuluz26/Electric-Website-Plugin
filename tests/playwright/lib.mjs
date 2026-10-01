/**
 * Helpers shared by qa.mjs (any WordPress page with EV widgets) and
 * breakdance-qa.mjs (a real Breakdance install). Needs `playwright`, and
 * optionally `axe-core`, resolvable from this folder — see qa.mjs.
 */
import { chromium } from 'playwright';
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';

/** Point PLAYWRIGHT_CHROMIUM_PATH at a Chromium binary if Playwright's own download isn't available. */
export const launch = () => chromium.launch(process.env.PLAYWRIGHT_CHROMIUM_PATH ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_PATH } : {});

/** PASS/FAIL/SKIP printer that remembers whether anything failed. */
export function reporter() {
	let failures = 0;
	return {
		check(label, ok, detail = '') {
			console.log(`${ok ? 'PASS' : 'FAIL'} — ${label}${!ok && detail ? ` (${detail})` : ''}`);
			if (!ok) failures++;
		},
		skip: (label, why) => console.log(`SKIP — ${label} (${why})`),
		finish(outDir) {
			console.log(`\n${failures === 0 ? 'All checks passed.' : failures + ' check(s) failed.'}${outDir ? ` Artifacts in ${outDir}/` : ''}`);
			process.exit(failures === 0 ? 0 : 1);
		},
	};
}

/** Scroll the whole page slowly so every scroll-triggered reveal fires. */
export const scrollThrough = (page) =>
	page.evaluate(async () => {
		for (let y = 0; y < document.body.scrollHeight; y += 400) {
			window.scrollTo(0, y);
			await new Promise((r) => setTimeout(r, 120));
		}
	});

/**
 * Wait until every finite animation and transition on the page has finished, up to `limit` ms (an endless one, like the
 * pulse along a cable, is left running). A page is judged as it settles, not part-way through a reveal: text that is
 * fading in is measured at its blended colour, which is a lower contrast than the one it comes to rest on.
 * Returns how many were still running when it gave up.
 */
export const settled = (page, limit = 8000) =>
	page.evaluate(
		(max) =>
			new Promise((resolve) => {
				const started = performance.now();
				const poll = () => {
					const running = document.getAnimations().filter((a) => a.playState === 'running' && Number.isFinite(a.effect.getComputedTiming().endTime));
					if (!running.length || performance.now() - started > max) resolve(running.length);
					else setTimeout(poll, 100);
				};
				poll();
			}),
		limit
	);

export const notFullyVisible = (page) =>
	page.$$eval('[data-evpx-reveal]', (els) => els.filter((e) => getComputedStyle(e).opacity !== '1' || getComputedStyle(e).visibility === 'hidden').length);

/**
 * Horizontal overflow: the page itself must not scroll sideways, and nothing
 * visible inside a widget may poke out of that widget's box (which is the width
 * of the column or container it was placed in, not necessarily the viewport).
 * Returns up to five offenders; empty means clean.
 */
export const overflowProbe = (page) =>
	page.evaluate(() => {
		const bad = [];
		const doc = document.documentElement;
		if (doc.scrollWidth > window.innerWidth + 1) bad.push(`page scrolls sideways: ${doc.scrollWidth}px in a ${window.innerWidth}px window`);

		for (const root of document.querySelectorAll('.evpx-root:not(.evpx-progress)')) {
			const box = root.getBoundingClientRect();
			for (const el of root.querySelectorAll('*')) {
				if (el.closest('[hidden]')) continue;
				// A drawing's shapes may run past its viewBox (a floor that goes on, a glow); the widget or panel
				// clips them, and the drawing's own box, the <svg>, is still held to the widget's.
				if (el.ownerSVGElement) continue;
				// The hero's drawing bleeds off the right edge on purpose; the hero clips it.
				if (el.closest('.evpx-hero__visual')) continue;
				// A rail is a scroll container: what runs on inside it is meant to, and the rail itself is held to the widget.
				if (el.closest('.evpx-projects__rail') && !el.classList.contains('evpx-projects__rail')) continue;
				const cs = getComputedStyle(el);
				if (cs.display === 'none' || cs.visibility === 'hidden') continue;
				const r = el.getBoundingClientRect();
				if (r.width === 0 || r.height === 0 || el.classList.contains('evpx-visually-hidden')) continue;
				if (r.right > box.right + 1 || r.left < box.left - 1) {
					bad.push(`${(el.className && el.className.toString()) || el.tagName}: ${Math.round(r.left)}–${Math.round(r.right)}px outside its ${Math.round(box.left)}–${Math.round(box.right)}px widget`);
				}
			}
		}

		return bad.slice(0, 5);
	});

/** Run axe-core over the EV widgets on an already-loaded, settled page. Returns null when axe-core isn't installed. */
export async function axeViolations(page, outFile) {
	let axePath = process.env.EVPX_AXE_PATH;
	try {
		axePath ||= createRequire(import.meta.url).resolve('axe-core/axe.min.js');
	} catch {}
	if (!axePath) return null;

	await page.addScriptTag({ path: axePath });
	await settled(page);
	const results = await page.evaluate(() =>
		window.axe.run(
			{ include: [['.evpx-root']] },
			{ runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] } }
		)
	);
	if (outFile) fs.writeFileSync(outFile, JSON.stringify(results.violations, null, 2));

	return results.violations.map((v) => `${v.id}×${v.nodes.length}`);
}

export const artifact = (outDir, name) => path.join(outDir, name);
