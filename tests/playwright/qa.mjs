#!/usr/bin/env node
/**
 * Smoke-tests a running instance of the demo article page (see
 * docs/QA-REPORT.md for how this was used against the Docker environment
 * in tests/docker/). Not a full E2E suite — a fast, repeatable version of
 * the checks that caught real bugs during development.
 *
 * Usage:
 *   npm install playwright   # or symlink a global install, see README
 *   node tests/playwright/qa.mjs http://localhost:8080/?page_id=4
 *
 * If Chromium isn't already resolvable via `playwright`, point
 * PLAYWRIGHT_CHROMIUM_PATH at a binary (e.g. /opt/pw-browsers/chromium).
 */
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const url = process.argv[2];
if (!url) {
	console.error('Usage: node tests/playwright/qa.mjs <url>');
	process.exit(1);
}

const outDir = process.argv[3] || './qa-output';
fs.mkdirSync(outDir, { recursive: true });

const launchOpts = {};
if (process.env.PLAYWRIGHT_CHROMIUM_PATH) {
	launchOpts.executablePath = process.env.PLAYWRIGHT_CHROMIUM_PATH;
}

const browser = await chromium.launch(launchOpts);
const consoleErrors = [];
const pageErrors = [];
let failures = 0;

function check(label, condition) {
	console.log((condition ? 'PASS' : 'FAIL') + ' — ' + label);
	if (!condition) failures++;
}

const viewports = {
	'desktop-1440': { width: 1440, height: 900 },
	'laptop-1366': { width: 1366, height: 768 },
	'tablet-768': { width: 768, height: 1024 },
	'mobile-390': { width: 390, height: 844 },
};

for (const [name, viewport] of Object.entries(viewports)) {
	const page = await browser.newPage({ viewport });
	page.on('console', (msg) => msg.type() === 'error' && consoleErrors.push(`[${name}] ${msg.text()}`));
	page.on('pageerror', (err) => pageErrors.push(`[${name}] ${err.message}`));
	await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
	await page.waitForTimeout(1000);
	await page.screenshot({ path: path.join(outDir, `${name}.png`), fullPage: true });
	await page.close();
}

// Interaction + structural checks, once, at a stable desktop viewport.
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

check('page loads (200)', (await page.goto(url)).status() === 200);

const shortcodeResidue = await page.evaluate(() => document.body.innerText.match(/\[evpx_[a-z_]+/));
check('no unprocessed [evpx_*] shortcode text left on page', !shortcodeResidue);

const faqItem = await page.$('.evpx-faq__item');
if (faqItem) {
	const before = await faqItem.$eval('.evpx-faq__answer', (el) => el.hasAttribute('hidden'));
	await faqItem.$eval('.evpx-faq__question', (el) => el.click());
	await page.waitForTimeout(200);
	const after = await faqItem.$eval('.evpx-faq__answer', (el) => el.hasAttribute('hidden'));
	check('FAQ accordion toggles open on click', before === true && after === false);
}

const dcTab = await page.$('.evpx-comparison__tab--dc');
if (dcTab) {
	await dcTab.evaluate((el) => el.click());
	await page.waitForTimeout(200);
	const dcVisible = await page.$eval('.evpx-comparison__panel--dc', (el) => getComputedStyle(el).display !== 'none');
	const acVisible = await page.$eval('.evpx-comparison__panel--ac', (el) => getComputedStyle(el).display !== 'none');
	check('AC/DC comparison tab switches visible panel', dcVisible === true && acVisible === false);
}

const cardTops = await page.$$eval('.evpx-scenarios__grid > *', (els) =>
	els.map((el) => Math.round(el.getBoundingClientRect().top))
);
if (cardTops.length >= 3) {
	check('scenario cards align into clean grid rows (first 3 share a row top)', new Set(cardTops.slice(0, 3)).size === 1);
}

// Check the alignfull WRAPPER itself, not an inner .evpx-container — some
// widgets (Hero) intentionally narrow their own text column with a more
// specific max-width, which is correct editorial design, not a regression.
const wrapperWidth = await page.$eval('.evpx-scenarios.alignfull', (el) => el.getBoundingClientRect().width).catch(() => 0);
const viewportWidth = page.viewportSize().width;
check(
	'top-level section is not squeezed by a theme content-width wrapper',
	wrapperWidth >= viewportWidth - 20
);

await browser.close();

fs.writeFileSync(
	path.join(outDir, 'console-report.txt'),
	`Console errors (${consoleErrors.length}):\n${consoleErrors.join('\n')}\n\nPage errors (${pageErrors.length}):\n${pageErrors.join('\n')}\n`
);

console.log(`\n${failures === 0 ? 'All checks passed.' : failures + ' check(s) failed.'} Screenshots + console report in ${outDir}/`);
process.exit(failures === 0 ? 0 : 1);
