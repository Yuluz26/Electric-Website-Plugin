/**
 * The example articles the plugin makes on activation (src/Setup/ExamplePages.php), met the way a site owner
 * meets them: as a logged-in administrator, in a browser. tests/docker/example-pages-check.sh drives WordPress
 * around this file (activation, options, cleanup); this does what only a real admin request can.
 *
 *   node tests/playwright/examples-qa.mjs <origin> landing
 *       Logs in and looks at the screen WordPress lands on: the first admin request after a scripted (WP-CLI)
 *       activation, which is what makes the examples. Checks the notice it shows, and that the notice is shown
 *       once. Prints the ids it links to on the last line, as "ids <article> <breakdance>".
 *   node tests/playwright/examples-qa.mjs <origin> activate
 *       The same, the way it is usually done: with the plugin inactive, opens Plugins and presses Activate.
 *   node tests/playwright/examples-qa.mjs <origin> inspect <articleId> <pageId> [outDir]
 *       Opens both as an administrator (a draft is only visible to one) and holds them to each other and to
 *       the article's own promises.
 *
 * Needs the same Playwright setup as qa.mjs; the login is admin / admin, as setup.sh creates it.
 */
import { launch, reporter, overflowProbe, scrollThrough, settled } from './lib.mjs';
import fs from 'node:fs';
import path from 'node:path';

const [origin, mode, articleId, pageId, outArg] = process.argv.slice(2);
if (!origin || !['landing', 'activate', 'inspect'].includes(mode)) {
	console.error('usage: node examples-qa.mjs <origin> landing | activate | inspect <articleId> <pageId> [outDir]');
	process.exit(2);
}

const { check, skip, finish } = reporter();
const outDir = outArg || 'qa-examples-output';
fs.mkdirSync(outDir, { recursive: true });

const browser = await launch();
// A logged-in page asks an outside host for the admin bar's avatar, and the QA site has no route out: that request
// would hold every page load open. Nothing under test lives elsewhere than the site itself.
const local = (context) => context.route(/^https?:\/\/(?!localhost(:\d+)?\/)/, (route) => route.abort());
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
await local(ctx);
const page = await ctx.newPage();
const pageErrors = [];
page.on('pageerror', (e) => pageErrors.push(e.message));

await page.goto(`${origin}/wp-login.php`);
await page.fill('#user_login', process.env.EVPX_WP_USER || 'admin');
await page.fill('#user_pass', process.env.EVPX_WP_PASS || 'admin');
await Promise.all([page.waitForURL(/\/wp-admin\//), page.click('#wp-submit')]);

if (mode !== 'inspect') {
	if (mode === 'activate') {
		await page.goto(`${origin}/wp-admin/plugins.php`);
		const pluginDir = process.env.EVPX_PLUGIN_DIR || 'ev-charging-experience';
		const link = page.locator(`tr[data-plugin="${pluginDir}/ev-charging-experience.php"] span.activate a`);
		check('the plugin is inactive, with an Activate link', (await link.count()) === 1);
		await Promise.all([page.waitForURL(/[?&]activate=true/), link.click()]);
	}

	// Whatever screen this is, it is the first admin request after the plugin was activated.
	const notice = page.locator('.notice-success', { hasText: 'EV Charging Experience' });
	const shown = await notice.count();
	check('the first admin request after activation shows two notices: one about the example articles, one about the site', shown === 2, `${shown} notices on ${page.url()}`);

	const text = shown ? (await notice.first().innerText()).replace(/\s+/g, ' ') : '';
	const links = shown ? await notice.first().locator('a').evaluateAll((as) => as.map((a) => ({ text: a.textContent.trim(), href: a.href }))) : [];
	console.log('notice:', text);

	check('it says they are drafts, so nothing is public', /draft/i.test(text) && /nothing is public/i.test(text), text);
	const previews = links.filter((l) => l.text === 'Preview');
	check('each example has a Preview link', previews.length === 2 && previews.every((l) => /preview=true/.test(l.href)), JSON.stringify(links));
	const builder = links.find((l) => /breakdance=builder/.test(l.href));
	check('the Breakdance example opens in the builder, the article in the editor', !!builder && builder.text === 'Edit in Breakdance' && links.filter((l) => l.text === 'Edit').length === 1, JSON.stringify(links));

	await page.goto(`${origin}/wp-admin/plugins.php`);
	check('the notice is shown once', (await page.locator('.notice-success', { hasText: 'EV Charging Experience' }).count()) === 0);

	const idOf = (l, key) => (l ? new URL(l.href).searchParams.get(key) : '');
	console.log(`ids ${idOf(previews.find((l) => /[?&]p=\d+/.test(l.href)), 'p')} ${idOf(previews.find((l) => /[?&]page_id=\d+/.test(l.href)), 'page_id')}`);

	check('no uncaught JavaScript exceptions in the admin', pageErrors.length === 0, pageErrors[0]);
	await browser.close();
	finish();
}

// ============================================================ inspect
const urls = { article: `${origin}/?p=${articleId}&preview=true`, page: `${origin}/?page_id=${pageId}&preview=true` };
const shape = {};

for (const [name, url] of Object.entries(urls)) {
	const p = await ctx.newPage();
	const errors = [];
	p.on('pageerror', (e) => errors.push(e.message));
	await p.setViewportSize({ width: 1440, height: 900 });
	await p.goto(url, { waitUntil: 'networkidle' });
	await scrollThrough(p);
	await settled(p);

	shape[name] = await p.evaluate(() => ({
		roots: [...document.querySelectorAll('.evpx-root:not(.evpx-progress)')].map((el) => [...el.classList].find((c) => /^evpx-(?!root|in-view)[a-z]+$/.test(c)) || '?'),
		h1: [...document.querySelectorAll('h1')].map((h) => h.textContent.trim()),
		targets: document.querySelectorAll('#decision').length,
		heroLink: document.querySelector('.evpx-hero__cta, .evpx-hero a[href^="#"]')?.getAttribute('href') || '',
		faqSchema: document.querySelectorAll('script[type="application/ld+json"]').length,
		reading: (document.querySelector('.evpx-hero__meta-item:last-child, .evpx-hero__meta-group .evpx-hero__meta-item:last-child')?.textContent || '').replace(/\s+/g, ' ').trim(),
	}));

	check(`${name}: renders every element of the article (${shape[name].roots.length} widgets)`, shape[name].roots.length >= 12, shape[name].roots.join(','));
	// Judged under the QA site's theme, Twenty Twenty-Five, which prints a page's title as an h1: the hero is an h2 there.
	check(`${name}: exactly one h1 on the page`, shape[name].h1.length === 1, shape[name].h1.join(' | '));
	check(`${name}: the hero's button has something to jump to (#decision exists once)`, shape[name].heroLink === '#decision' && shape[name].targets === 1, `${shape[name].heroLink} → ${shape[name].targets}`);
	check(`${name}: no uncaught JavaScript exceptions`, errors.length === 0, errors[0]);

	await p.screenshot({ path: path.join(outDir, `${name}-fold.png`) });
	await p.close();
}

check(
	'the Breakdance example is the same article as the shortcode one, element for element',
	shape.article.roots.join(',') === shape.page.roots.join(','),
	`${shape.article.roots.join(',')}  vs  ${shape.page.roots.join(',')}`
);

// A draft is not public: only the two of them, only for someone who may edit.
const anon = await browser.newContext();
await local(anon);
const ap = await anon.newPage();
const [postRes, pageRes] = [await ap.goto(`${origin}/?p=${articleId}`), await ap.goto(`${origin}/?page_id=${pageId}`)];
check('logged out, neither example can be seen (drafts)', postRes.status() === 404 && pageRes.status() === 404, `${postRes.status()} / ${pageRes.status()}`);
await anon.close();

// The Breakdance page, at the widths the suites hold every other page to.
for (const width of [320, 390, 768, 1024, 1440]) {
	const p = await ctx.newPage();
	await p.setViewportSize({ width, height: 900 });
	await p.goto(urls.page, { waitUntil: 'networkidle' });
	await scrollThrough(p);
	await settled(p);
	const bad = await overflowProbe(p);
	check(`Breakdance example at ${width}px: nothing overflows its widget or scrolls the page sideways`, bad.length === 0, bad.join(' · '));
	if (width === 390) await p.screenshot({ path: path.join(outDir, 'page-390-fold.png') });
	await p.close();
}

if (!shape.page.reading) skip('reading time on the Breakdance example', 'byline not found');
else check('the Breakdance example reads its time from the element tree (not the empty post_content)', /\d+ min read/.test(shape.page.reading), shape.page.reading);

await browser.close();
finish(outDir);
