/**
 * The six site pages (Home, About, Services, Projects, Contact, Search) in a browser, as a visitor gets them:
 * nothing scrolls sideways from 320 to 1920px, one h1 and real landmarks, no script errors, axe-core clean, and every
 * interactive piece works with a pointer and with a keyboard: the services strip, the quotations, the projects rail, the
 * process rail and counter, the counters, the header's search and menu, the contact form.
 *
 * Pages are given as name=url pairs; they must be published (a visitor cannot see a draft):
 *   node tests/playwright/site-qa.mjs <outDir> home=http://…/ about=http://…/ services=… projects=… contact=… search=…
 *
 * Needs `playwright` (and optionally `axe-core`) resolvable from this folder, like qa.mjs.
 */
import fs from 'node:fs';
import { launch, reporter, scrollThrough, settled, overflowProbe, axeViolations } from './lib.mjs';

const [outDir, ...pairs] = process.argv.slice(2);
const pages = Object.fromEntries(pairs.map((p) => [p.split('=')[0], p.slice(p.indexOf('=') + 1)]));
fs.mkdirSync(outDir, { recursive: true });

// EVPX_QA_ONLY=live,contact runs only those parts (still, live, contact, search): a quick way to watch one check fail on a deliberate fault.
const only = (process.env.EVPX_QA_ONLY || '').split(',').filter(Boolean);
const wants = (part) => only.length === 0 || only.includes(part);

const { check, skip, finish } = reporter();
const browser = await launch();
const errors = [];
const watch = (page, name) => {
	page.on('pageerror', (e) => errors.push(`${name}: ${e.message}`));
	page.on('console', (m) => m.type() === 'error' && !/Failed to load resource/.test(m.text()) && errors.push(`${name}: ${m.text()}`));
};
const open = async (ctx, name, width = 1440, height = 900, query = '') => {
	const page = await ctx.newPage();
	watch(page, `${name}@${width}`);
	await page.route(/^https?:\/\/(?!localhost(:\d+)?\/)/, (r) => r.abort());
	await page.setViewportSize({ width, height });
	await page.goto(pages[name] + query, { waitUntil: 'load' });
	return page;
};

// --------------------------------------------------------------------- still: the finished state, every width
const still = await browser.newContext({ reducedMotion: 'reduce' });
const widths = [320, 390, 768, 1024, 1366, 1440, 1920];
const sideways = [];

for (const name of wants('still') ? Object.keys(pages) : []) {
	for (const w of widths) {
		const page = await open(still, name, w, w < 500 ? 800 : 900);
		await scrollThrough(page);
		await settled(page, 4000);
		const bad = await overflowProbe(page);
		if (bad.length) sideways.push(`${name}@${w}: ${bad[0]}`);
		if (w === 1440 || w === 390) {
			const violations = await axeViolations(page, `${outDir}/axe-${name}-${w}.json`);
			if (violations === null) skip(`axe-core on ${name}`, 'axe-core not installed');
			else check(`${name} @${w}: axe-core finds no WCAG A/AA or best-practice violation in the widgets`, violations.length === 0, violations.join(', '));
		}
		if (w === 1440) {
			const facts = await page.evaluate(() => ({
				h1: document.querySelectorAll('h1').length,
				main: document.querySelectorAll('main').length,
				header: document.querySelectorAll('header.evpx-header').length,
				footer: document.querySelectorAll('footer.evpx-footer').length,
				navs: [...document.querySelectorAll('nav')].map((n) => n.getAttribute('aria-label') || ''),
				skip: !!document.querySelector('.evpx-canvas__skip[href="#evpx-main"]') && !!document.getElementById('evpx-main'),
				motion: document.querySelectorAll('[data-evpx-motion="on"]').length,
				noAlt: [...document.querySelectorAll('.evpx-root img:not([alt])')].length,
				stuckBars: document.querySelectorAll('.evpx-root.evpx-header').length,
			}));
			check(`${name}: one h1, one main, one site header and one site footer`, facts.h1 === 1 && facts.main === 1 && facts.header === 1 && facts.footer === 1, JSON.stringify(facts));
			check(`${name}: every navigation has its own name (screen readers list them by it)`, facts.navs.every(Boolean) && new Set(facts.navs).size === facts.navs.length, facts.navs.join(' / '));
			check(`${name}: the skip link has something to skip to`, facts.skip);
			check(`${name}: with reduced motion nothing is marked as moving, and every picture has an alt`, facts.motion === 0 && facts.noAlt === 0, JSON.stringify(facts));
			await page.screenshot({ path: `${outDir}/${name}-1440.png`, fullPage: true });
		}
		await page.close();
	}
}
if (wants('still')) check('no page scrolls sideways or has anything poke out of its widget, at 320, 390, 768, 1024, 1366, 1440 and 1920px', sideways.length === 0, sideways.slice(0, 4).join('; '));
await still.close();

// --------------------------------------------------------------------- the home page as it plays, with a pointer
if (wants('live')) {
	const live = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const home = await open(live, 'home');
	await home.addStyleTag({ content: 'html{scroll-behavior:auto!important}' });
	await home.waitForTimeout(800);

	// The hero.
	const hero = await home.evaluate(() => {
		const s = document.querySelector('.evpx-stage');
		const r = s.getBoundingClientRect();
		const facts = document.querySelector('.evpx-stage__facts')?.getBoundingClientRect();
		return { motion: s.closest('.evpx-root').getAttribute('data-evpx-motion'), top: Math.round(r.top), factsBottom: facts ? Math.round(facts.bottom) : 0, vh: innerHeight, layers: s.querySelectorAll('.evpx-scene__layer').length, canvas: !!s.querySelector('canvas.evpx-stage__fx') };
	});
	check('the hero is marked as moving, its scene has layers and its weather canvas, and its figures are on the first screen', hero.motion === 'on' && hero.layers >= 4 && hero.canvas && hero.factsBottom > 0 && hero.factsBottom <= hero.vh, JSON.stringify(hero));
	await home.mouse.move(200, 200);
	await home.mouse.move(900, 500, { steps: 6 });
	await home.waitForTimeout(500);
	const moved = await home.evaluate(() => [...document.querySelectorAll('.evpx-stage .evpx-scene__layer')].filter((l) => l.style.transform && l.style.transform !== 'translate(0px,0px)').length);
	check('the pointer moves the scene\'s layers against each other (parallax)', moved >= 2, String(moved));

	// The counters.
	await home.locator('.evpx-stats').scrollIntoViewIfNeeded();
	await home.waitForTimeout(2600);
	const counters = await home.$$eval('.evpx-stat__value[data-count]', (els) => els.map((e) => [e.getAttribute('data-count'), e.querySelector('.evpx-stat__num').textContent]));
	check('the counters end on their real values', counters.length >= 3 && counters.every(([want, got]) => want === got), JSON.stringify(counters));
	const rings = await home.$$eval('.evpx-stat__ring', (els) => els.map((e) => getComputedStyle(e.querySelector('.evpx-stat__arc')).strokeDasharray));
	check('a ring meter has drawn to its value', rings.length >= 1 && rings.every((d) => /^\d/.test(d) && parseFloat(d) > 90), JSON.stringify(rings));

	// The services strip.
	const strip = home.locator('[data-evpx-services]');
	await strip.scrollIntoViewIfNeeded();
	await home.waitForTimeout(700);
	const state = () => home.$$eval('.evpx-service', (els) => els.map((e) => [e.hasAttribute('data-open'), e.querySelector('[data-evpx-service-toggle]').getAttribute('aria-expanded')]));
	let st = await state();
	check('services: the strip is ready, the first panel open, the others shut, and aria-expanded says the same', (await strip.getAttribute('data-evpx-ready')) === '1' && st[0][0] && st[0][1] === 'true' && st.slice(1).every(([o, a]) => !o && a === 'false'), JSON.stringify(st));
	const widthsNow = await home.$$eval('.evpx-service', (els) => els.map((e) => Math.round(e.getBoundingClientRect().width)));
	check('services: on a wide screen the open panel is much wider than the shut ones', widthsNow[0] > widthsNow[1] * 3, widthsNow.join(','));
	const hiddenBody = await home.$$eval('.evpx-service:not([data-open]) .evpx-service__body', (els) => els.every((e) => getComputedStyle(e).visibility === 'hidden'));
	check('services: what is in a shut panel cannot be reached (hidden from the tab order and from screen readers)', hiddenBody);
	await home.locator('.evpx-service').nth(2).click({ position: { x: 60, y: 200 } });
	await home.waitForTimeout(900);
	st = await state();
	check('services: clicking anywhere on a shut panel opens it and shuts the other', st[2][0] && st[2][1] === 'true' && st.filter(([o]) => o).length === 1, JSON.stringify(st));
	await home.locator('.evpx-service__toggle').nth(2).focus();
	await home.keyboard.press('ArrowRight');
	const focusedIndex = await home.evaluate(() => [...document.querySelectorAll('.evpx-service__toggle')].indexOf(document.activeElement));
	check('services: the arrow keys move between the headings', focusedIndex === 3, String(focusedIndex));
	await home.keyboard.press('Enter');
	await home.waitForTimeout(900);
	st = await state();
	check('services: Enter opens the one that has the focus', st[3][0] && st.filter(([o]) => o).length === 1, JSON.stringify(st));
	const onTop = (page) => page.evaluate(() => {
		const text = document.querySelector('.evpx-service[data-open] .evpx-service__summary');
		const b = text.getBoundingClientRect();
		const hit = document.elementFromPoint(b.left + Math.min(40, b.width / 2), b.top + b.height / 2);
		return !!hit && text.contains(hit);
	});
	await settled(home, 4000); // a text that is still fading in is painted above what is behind it, so judge it once it has arrived
check('services: the open panel\'s words are on top of its picture (the picture never paints over them)', await onTop(home));
	const openLink = await home.$$eval('.evpx-service[data-open] .evpx-service__link', (els) => els.map((a) => getComputedStyle(a).visibility + ':' + new URL(a.href).pathname.length));
	check('services: the link in the open panel can be reached', openLink.length === 1 && openLink[0].startsWith('visible'), JSON.stringify(openLink));

	// The process rail.
	await home.locator('[data-evpx-process]').scrollIntoViewIfNeeded();
	await home.evaluate(() => window.scrollBy(0, 300));
	await home.waitForTimeout(700);
	const mid = await home.evaluate(() => ({ p: parseFloat(document.querySelector('[data-evpx-process]').style.getPropertyValue('--evpx-progress')), now: document.querySelector('.evpx-process__now').textContent, of: document.querySelector('.evpx-process__of').textContent, reached: document.querySelectorAll('.evpx-step.evpx-is-reached').length }));
	check('process: part-way down, the rail is part filled, the counter is running and some sockets are lit', mid.p > 0 && mid.p < 1 && mid.reached >= 1 && /^0\d$/.test(mid.now) && /05/.test(mid.of), JSON.stringify(mid));
	await home.evaluate(() => document.querySelector('.evpx-step:last-child').scrollIntoView({ block: 'center' }));
	await home.waitForTimeout(700);
	const end = await home.evaluate(() => ({ p: parseFloat(document.querySelector('[data-evpx-process]').style.getPropertyValue('--evpx-progress')), now: document.querySelector('.evpx-process__now').textContent }));
	check('process: at the last step the rail is full and the counter says 05', end.p > 0.97 && end.now === '05', JSON.stringify(end));

	// The projects rail.
	await home.locator('[data-evpx-rail]').scrollIntoViewIfNeeded();
	await home.waitForTimeout(500);
	const rail0 = await home.evaluate(() => { const r = document.querySelector('[data-evpx-rail]'); return { left: r.scrollLeft, over: r.scrollWidth > r.clientWidth, prev: document.querySelector('[data-evpx-rail-prev]').disabled, navHidden: document.querySelector('[data-evpx-rail-nav]').hidden, bar: document.querySelector('[data-evpx-rail-bar]').style.width }; });
	check('projects: the rail runs off the page, so its buttons show, the back one is off and the bar shows how much is in view', rail0.over && !rail0.navHidden && rail0.prev && rail0.left === 0 && parseFloat(rail0.bar) > 0 && parseFloat(rail0.bar) < 100, JSON.stringify(rail0));
	const firstCard = await home.evaluate(() => { const c = document.querySelector('.evpx-project').getBoundingClientRect(); const k = document.querySelector('.evpx-projects .evpx-container').getBoundingClientRect(); return [Math.round(c.left), Math.round(k.left + parseFloat(getComputedStyle(document.querySelector('.evpx-projects .evpx-container')).paddingLeft))]; });
	check('projects: the first card lines up with the page\'s own left edge', Math.abs(firstCard[0] - firstCard[1]) <= 2, JSON.stringify(firstCard));
	await home.locator('[data-evpx-rail-next]').click();
	await home.waitForTimeout(1200);
	const rail1 = await home.evaluate(() => ({ left: document.querySelector('[data-evpx-rail]').scrollLeft, prev: document.querySelector('[data-evpx-rail-prev]').disabled }));
	check('projects: the next button moves the rail and wakes the back button', rail1.left > 100 && !rail1.prev, JSON.stringify(rail1));
	const box = await home.locator('[data-evpx-rail]').boundingBox();
	await home.mouse.move(box.x + 700, box.y + 100);
	await home.mouse.down();
	await home.mouse.move(box.x + 400, box.y + 100, { steps: 8 });
	await home.mouse.up();
	await home.waitForTimeout(400);
	const rail2 = await home.evaluate(() => document.querySelector('[data-evpx-rail]').scrollLeft);
	check('projects: a mouse can drag the rail', rail2 > rail1.left + 100, `${rail1.left} → ${rail2}`);
	const railFocus = await home.$eval('[data-evpx-rail]', (r) => r.tabIndex === 0 && r.getAttribute('role') === 'region' && !!r.getAttribute('aria-label'));
	check('projects: the rail can be scrolled with the keyboard (it takes focus and has a name)', railFocus);

	// The quotations.
	await home.locator('[data-evpx-quotes]').scrollIntoViewIfNeeded();
	await home.waitForTimeout(600);
	const quoteState = () => home.evaluate(() => ({ current: [...document.querySelectorAll('[data-evpx-quote]')].map((q) => q.classList.contains('evpx-is-current')), dots: [...document.querySelectorAll('.evpx-quotes__dot')].map((d) => d.getAttribute('aria-current')), live: document.querySelector('[data-evpx-quotes-stage]').getAttribute('aria-live'), h: Math.round(document.querySelector('[data-evpx-quotes-stage]').getBoundingClientRect().height), shown: [...document.querySelectorAll('[data-evpx-quote]')].filter((el) => { const c = getComputedStyle(el); return c.visibility !== 'hidden' && c.opacity !== '0'; }).length }));
	let q = await quoteState();
	check('quotes: one is showing (the others are really hidden, not just unmarked), its dot is the current one, and the section is not announcing while it turns by itself', q.current.filter(Boolean).length === 1 && q.shown === 1 && q.current[0] && q.dots[0] === 'true' && q.live === 'off', JSON.stringify(q));
	await home.locator('[data-evpx-quotes-next]').click();
	await home.waitForTimeout(900);
	const q2 = await quoteState();
	check('quotes: next shows the next one, moves the dot, and hands over to a screen reader (aria-live polite)', q2.current[1] && q2.current.filter(Boolean).length === 1 && q2.shown === 1 && q2.dots[1] === 'true' && q2.live === 'polite', JSON.stringify(q2));
	check('quotes: the section keeps its height whichever is showing', q2.h === q.h, `${q.h} → ${q2.h}`);
	await home.locator('[data-evpx-quotes-prev]').click();
	await home.locator('[data-evpx-quotes-prev]').click();
	await home.waitForTimeout(900);
	q = await quoteState();
	check('quotes: back from the first comes round to the last', q.current[q.current.length - 1], JSON.stringify(q));
	await home.locator('.evpx-quotes__dot').nth(1).click();
	await home.waitForTimeout(800);
	q = await quoteState();
	check('quotes: a dot shows its own', q.current[1] && q.dots[1] === 'true', JSON.stringify(q));
	const target = await home.$eval('.evpx-quotes__dot', (d) => { const r = d.getBoundingClientRect(); return Math.min(r.width, r.height); });
	check('quotes: a dot is a target of at least 24px, however small it looks', target >= 24, String(target));

	// The header.
	await home.evaluate(() => window.scrollTo(0, 0));
	await home.waitForTimeout(400);
	await home.keyboard.press('Control+k');
	await home.waitForTimeout(400);
	let dlg = await home.evaluate(() => ({ open: document.querySelector('dialog.evpx-searchbox').open, focus: document.activeElement?.getAttribute('type') }));
	check('header: Ctrl+K opens the search with the cursor in its field', dlg.open && dlg.focus === 'search', JSON.stringify(dlg));
	await home.keyboard.press('Escape');
	await home.waitForTimeout(300);
	check('header: Escape closes it', !(await home.evaluate(() => document.querySelector('dialog.evpx-searchbox').open)));
	await home.locator('main, body').first().click({ position: { x: 5, y: 300 } }).catch(() => {});
	await home.keyboard.press('/');
	await home.waitForTimeout(300);
	check('header: "/" opens it too', await home.evaluate(() => document.querySelector('dialog.evpx-searchbox').open));
	await home.keyboard.type('about');
	await home.waitForTimeout(1500);
	const results = await home.$$eval('.evpx-searchbox__results [role="option"]', (as) => as.map((a) => a.textContent.trim().slice(0, 40)));
	check('header: typing lists matching pages from the site as you go', results.length >= 1, JSON.stringify(results));
	await home.keyboard.press('ArrowDown');
	const active = await home.evaluate(() => document.querySelectorAll('.evpx-searchbox__results [aria-selected="true"]').length === 1);
	check('header: the arrow keys move through the results', active);
	await home.keyboard.press('Escape');
	await home.evaluate(() => window.scrollTo(0, 900));
	await home.waitForTimeout(500);
	check('header: once the page has moved under it the bar is the translucent one, and it stays at the top', await home.evaluate(() => { const h = document.querySelector('.evpx-header'); return h.classList.contains('evpx-is-stuck') || getComputedStyle(h).position !== 'sticky'; }));
	await home.close();

	// Enter in the search takes the visitor to the search page with the words.
	const home2 = await open(live, 'home');
	await home2.keyboard.press('Control+k');
	await home2.keyboard.type('services');
	await Promise.all([home2.waitForURL(/q=services/), home2.keyboard.press('Enter')]);
	await home2.waitForLoadState('load');
	const landed = await home2.evaluate(() => ({ url: location.href, q: document.querySelector('.evpx-search__input')?.value, hits: document.querySelectorAll('.evpx-search__hit').length }));
	check('header: Enter goes to the search page with the words in its field and the matches listed', /q=services/.test(landed.url) && landed.q === 'services' && landed.hits >= 1, JSON.stringify(landed));
	await home2.close();

	// The mobile menu.
	const phone = await live.newPage();
	watch(phone, 'home@390');
	await phone.route(/^https?:\/\/(?!localhost(:\d+)?\/)/, (r) => r.abort());
	await phone.setViewportSize({ width: 390, height: 844 });
	await phone.goto(pages.home, { waitUntil: 'load' });
	const menuBtn = phone.locator('.evpx-header__toggle');
	check('mobile: the links are behind a menu button, which says whether it is open', (await menuBtn.getAttribute('aria-expanded')) === 'false' && (await phone.locator('.evpx-header__nav').isHidden()));
	await menuBtn.click();
	await phone.waitForTimeout(500);
	const menuShown = await phone.evaluate(() => ({ expanded: document.querySelector('.evpx-header__toggle').getAttribute('aria-expanded'), links: [...document.querySelectorAll('.evpx-header__menu a')].filter((a) => a.offsetParent).length }));
	check('mobile: opened, it lists the links and the button says so', menuShown.expanded === 'true' && menuShown.links >= 5, JSON.stringify(menuShown));
	await phone.keyboard.press('Escape');
	await phone.waitForTimeout(400);
	check('mobile: Escape shuts it', (await menuBtn.getAttribute('aria-expanded')) === 'false');
	const railPhone = await phone.evaluate(() => { const r = document.querySelector('[data-evpx-rail]'); return r.scrollWidth > r.clientWidth; });
	check('mobile: the projects rail scrolls sideways on its own, without the page doing it', railPhone && (await phone.evaluate(() => document.documentElement.scrollWidth <= innerWidth)));
	await phone.close();

	// The services accordion on a narrow screen: full-width rows.
	const narrow = await live.newPage();
	watch(narrow, 'services@390');
	await narrow.route(/^https?:\/\/(?!localhost(:\d+)?\/)/, (r) => r.abort());
	await narrow.setViewportSize({ width: 390, height: 844 });
	await narrow.goto(pages.home, { waitUntil: 'load' });
	await narrow.locator('[data-evpx-services]').scrollIntoViewIfNeeded();
	await narrow.waitForTimeout(500);
	await narrow.locator('.evpx-service__toggle').nth(1).click();
	await narrow.waitForTimeout(900);
	const acc = await narrow.$$eval('.evpx-service', (els) => els.map((e) => [e.hasAttribute('data-open'), Math.round(e.getBoundingClientRect().width), Math.round(e.getBoundingClientRect().height)]));
	await settled(narrow, 4000);
	check('services: on a phone the open row\'s words are on top of its picture', await narrow.evaluate(() => { const t = document.querySelector('.evpx-service[data-open] .evpx-service__summary'); const b = t.getBoundingClientRect(); const hit = document.elementFromPoint(b.left + 30, b.top + b.height / 2); return !!hit && t.contains(hit); }));
	check('services: on a phone it is an accordion of full-width rows, one open', acc.filter(([o]) => o).length === 1 && acc[1][0] && acc.every(([, w]) => w >= 380) && acc[1][2] > acc[0][2], JSON.stringify(acc));
	await narrow.close();
	await live.close();
}

// --------------------------------------------------------------------- contact
if (wants('contact')) {
	const ctx2 = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const contact = await open(ctx2, 'contact');
	const labels = await contact.$$eval('.evpx-contact__form input:not([type=hidden]):not([name=evpx_website]), .evpx-contact__form select, .evpx-contact__form textarea', (els) => els.map((e) => e.labels.length));
	check('contact: every field the visitor fills has exactly one label', labels.length === 5 && labels.every((n) => n === 1), JSON.stringify(labels));
	const tabOrder = await contact.evaluate(() => { const t = document.querySelector('.evpx-contact__trap'); const b = t.getBoundingClientRect(); return document.querySelector('.evpx-contact__trap input').tabIndex === -1 && b.width <= 1 && b.height <= 1; });
	check('contact: the trap field takes no room and is out of the tab order', tabOrder);
	await contact.fill('#evpx-c-name', 'Ada Lovelace');
	await contact.fill('#evpx-c-email', 'ada@example.com');
	await contact.fill('#evpx-c-message', 'We have a car park with forty bays.');
	let posted = 0;
	await contact.route('**/admin-post.php', (r) => { posted++; r.fulfill({ status: 204, body: '' }); }); // 204: the page stays where it is, as it would for a form that has been sent
	await contact.click('.evpx-contact__submit');
	await contact.waitForTimeout(400);
	check('contact: sending posts the form once and the form shows that it is busy', posted === 1 && (await contact.evaluate(() => document.querySelector('.evpx-contact__form').classList.contains('evpx-is-sending') && document.querySelector('.evpx-contact__form').getAttribute('aria-busy') === 'true')), String(posted));
	await contact.close();
	for (const [state, cls] of [['1', 'ok'], ['invalid', 'bad']]) {
		const p = await open(ctx2, 'contact', 1440, 900, (pages.contact.includes('?') ? '&' : '?') + 'evpx_sent=' + state);
		check(`contact: coming back with "${state}" shows the ${cls === 'ok' ? 'thanks' : 'problem'}, said in words`, (await p.locator(`.evpx-contact__notice--${cls}`).count()) === 1 && (await p.locator('.evpx-contact__notice').innerText()).length > 20);
		await p.close();
	}
	await ctx2.close();
}

// --------------------------------------------------------------------- the search page
if (wants('search')) {
	const ctx3 = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const search = await open(ctx3, 'search', 1440, 900, (pages.search.includes('?') ? '&' : '?') + 'q=contact');
	const found = await search.evaluate(() => ({ hits: document.querySelectorAll('.evpx-search__hit:not(.evpx-search__hit--quick)').length, value: document.querySelector('.evpx-search__input').value, count: document.querySelector('.evpx-search__count')?.textContent.trim() }));
	check('search page: a query in the address is searched, shown in the field and counted', found.hits >= 1 && found.value === 'contact' && /\d/.test(found.count || ''), JSON.stringify(found));
	await search.close();
	const empty = await open(ctx3, 'search');
	check('search page: with nothing asked it offers the ways on', (await empty.locator('.evpx-search__hit--quick').count()) >= 3);
	await empty.close();
	await ctx3.close();
}

check('no script error on any of the six pages', errors.length === 0, errors.slice(0, 3).join(' | '));
await browser.close();
finish(outDir);
