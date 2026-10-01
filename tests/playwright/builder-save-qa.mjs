#!/usr/bin/env node
/**
 * The builder round-trip on native elements. On the first page (a copy of the demo article): pick a value in
 * a dropdown, edit a text field, press Save, then look at the front end and reopen the builder. On the second
 * (one empty Section): add an element from the Add panel — its stylesheet arrives with it, the canvas is
 * styled without a reload — save, and look at the front end. It changes and saves the pages it is given, so
 * tests/docker/builder-save-check.sh hands it scratch pages and deletes them afterwards:
 *
 *   bash tests/docker/builder-save-check.sh
 *
 * Needs a login (admin / admin by default, EVPX_WP_USER / EVPX_WP_PASS) and the same Playwright / Chromium
 * setup as breakdance-qa.mjs, whose selectors for Breakdance 2.8.3's builder it reuses.
 */
import { launch, reporter } from './lib.mjs';

const [pageUrl, emptyUrl] = process.argv.slice(2);
if (!pageUrl || !emptyUrl) {
	console.error('Usage: node tests/playwright/builder-save-qa.mjs <scratch-native-page-url> <scratch-empty-page-url>');
	process.exit(1);
}

const origin = new URL(pageUrl).origin;
const postId = new URL(pageUrl).searchParams.get('page_id');
const NEW_TITLE = 'Saved from the builder';

const browser = await launch();
const { check, finish } = reporter();
const ctx = await browser.newContext({ viewport: { width: 1600, height: 1000 } });
const b = await ctx.newPage();
await b.goto(`${origin}/wp-login.php`);
await b.fill('#user_login', process.env.EVPX_WP_USER || 'admin');
await b.fill('#user_pass', process.env.EVPX_WP_PASS || 'admin');
await Promise.all([b.waitForNavigation(), b.click('#wp-submit')]);

const saves = [];
b.on('response', (res) => {
	const req = res.request();
	if (req.method() === 'POST' && /name="action"\r?\n\r?\nbreakdance_save\b|action=breakdance_save\b/.test(req.postData() || '')) saves.push(res.status());
});

const openBuilder = async () => {
	await b.goto(`${origin}/?breakdance=builder&id=${postId}`, { waitUntil: 'load', timeout: 60000 });
	await b.waitForTimeout(10000);
	const frame = b.frames().find((f) => f.url().includes('breakdance_iframe'));
	const hero = frame.locator('.evpx-native-hero').first();
	await hero.scrollIntoViewIfNeeded();
	await hero.click({ position: { x: 20, y: 20 }, force: true });
	await b.waitForTimeout(1500);
	return frame;
};
const control = (path) => b.locator(`xpath=//span[@path="${path}"]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " breakdance-control-wrapper ")][1]`);

// ---- edit
let frame = await openBuilder();
await b.getByText('Visual', { exact: true }).first().click();
await b.waitForTimeout(800);
const dropdown = b.locator('[data-test-id="control-content-visual-visual_mode"] [role="combobox"]').first();
await dropdown.click();
await b.waitForTimeout(600);
const options = await b.evaluate(() => [...document.querySelectorAll('.v-list-item, [role="option"]')].map((e) => e.innerText.trim()));
await b.locator('[role="option"], .v-list-item').filter({ hasText: /^Light/ }).first().click();
await b.waitForTimeout(3000);
const canvasMode = await frame.evaluate(() => document.querySelector('.evpx-native-hero .evpx-hero')?.getAttribute('data-evpx-hero-mode'));
check(
	'a dropdown lists the widget\'s options, and choosing one re-renders the canvas with it',
	options.length === 3 && options.includes('Light') && canvasMode === 'light',
	JSON.stringify({ options, canvasMode })
);

await control('content.content.title').locator('input').first().fill(NEW_TITLE);
await b.waitForTimeout(2500);

// ---- save
await b.getByRole('button', { name: 'Save' }).click();
await b.waitForTimeout(4000);
check('Save goes through (breakdance_save answers 200)', saves.length >= 1 && saves.every((s) => s === 200), JSON.stringify(saves));

// ---- the front end shows what was saved
const html = await (await b.request.get(pageUrl)).text();
check(
	'the front end of the saved page shows the edited title and the chosen mode',
	html.includes(`>${NEW_TITLE}<`) && /data-evpx-hero-mode="light"/.test(html),
	JSON.stringify({ title: html.includes(NEW_TITLE), mode: (html.match(/data-evpx-hero-mode="[a-z]+"/) || [])[0] })
);

// ---- reopening the builder keeps it
frame = await openBuilder();
const reopenedTitle = await control('content.content.title').locator('input').first().inputValue();
const reopenedMode = await frame.evaluate(() => document.querySelector('.evpx-native-hero .evpx-hero')?.getAttribute('data-evpx-hero-mode'));
check('reopening the builder shows the saved title and mode', reopenedTitle === NEW_TITLE && reopenedMode === 'light', JSON.stringify({ reopenedTitle, reopenedMode }));

// ---- an element added from the Add panel to an empty page
const emptyId = new URL(emptyUrl).searchParams.get('page_id');
await b.goto(`${origin}/?breakdance=builder&id=${emptyId}`, { waitUntil: 'load', timeout: 60000 });
await b.waitForTimeout(9000);
frame = b.frames().find((f) => f.url().includes('breakdance_iframe'));
const styledBefore = await frame.evaluate(() => !!document.querySelector('link[href*="assets/css/evpx.css"]'));
const pageErrors = [];
b.on('pageerror', (e) => pageErrors.push(e.message));
await b.getByText('Add', { exact: true }).first().click();
await b.fill('input[placeholder="Search elements"]', 'EV FAQ');
await b.waitForTimeout(1200);
await b.locator('.breakdance-add-panel__element-name', { hasText: 'EV FAQ' }).first().click();
await b.waitForTimeout(5000);
const added = await frame.evaluate(() => ({
	elements: document.querySelectorAll('.evpx-native-faq').length,
	items: document.querySelectorAll('.evpx-native-faq .evpx-faq__item').length,
	styled: !!document.querySelector('link[href*="assets/css/evpx.css"]'),
	font: (() => {
		const h = document.querySelector('.evpx-native-faq .evpx-heading');
		return h ? getComputedStyle(h).fontFamily : '';
	})(),
}));
check(
	'adding an element from the Add panel to an empty page renders it with its starting copy, and the canvas gets the stylesheet with it (no reload)',
	!styledBefore && added.elements === 1 && added.items === 2 && added.styled && /EVPX/.test(added.font) && pageErrors.length === 0,
	JSON.stringify({ styledBefore, ...added, pageErrors })
);

saves.length = 0;
await b.getByRole('button', { name: 'Save' }).click();
await b.waitForTimeout(4000);
const savedHtml = await (await b.request.get(emptyUrl)).text();
check(
	'the added element is saved with its starting properties and the front end renders it',
	saves.length >= 1 && saves.every((s) => s === 200) && (savedHtml.match(/data-evpx-question=/g) || []).length === 2 && savedHtml.includes('evpx-native-faq'),
	JSON.stringify({ saves, questions: (savedHtml.match(/data-evpx-question=/g) || []).length })
);

await ctx.close();
await browser.close();
finish();
