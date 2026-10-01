/**
 * How the widgets behave, not only how they look: the motion that runs when it is allowed and the
 * finished state everywhere else, the hover and focus states, the comparison thumb and its range bar.
 *
 *   node tests/playwright/interaction-qa.mjs "$(cat tests/docker/.demo-url)" [outDir]
 *
 * Needs a page with every EV widget (the demo article, as shortcodes or as native elements) and, for the
 * motion checks, GSAP reachable (setup.sh's EVPX_GSAP_DIR serves it locally). Three visitors are tried:
 * a fine pointer with motion allowed, a visitor who prefers reduced motion, and a touch device.
 */
import { launch, reporter, scrollThrough } from './lib.mjs';
import fs from 'node:fs';
import path from 'node:path';

const [url, outDir = 'qa-interaction-output'] = process.argv.slice(2);
if (!url) {
	console.error('usage: node interaction-qa.mjs <page url> [outDir]');
	process.exit(2);
}
fs.mkdirSync(outDir, { recursive: true });

const { check, finish } = reporter();
const browser = await launch();

async function open(contextOptions) {
	const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, ...contextOptions });
	const page = await context.newPage();
	const problems = [];
	page.on('pageerror', (e) => problems.push(e.message));
	page.on('console', (m) => m.type() === 'error' && problems.push(m.text()));
	page.on('response', (r) => r.status() >= 400 && problems.push(`${r.status()} ${r.url()}`));
	await page.goto(url, { waitUntil: 'networkidle' });
	await page.addStyleTag({ content: 'html{scroll-behavior:auto!important}' });
	return { context, page, problems };
}

/** A computed value, optionally of a pseudo-element, of the first element that matches. */
const style = (page, selector, prop, pseudo = null) =>
	page.evaluate(([s, p, ps]) => {
		const el = document.querySelector(s);
		return el ? getComputedStyle(el, ps)[p] : null;
	}, [selector, prop, pseudo]);

const IDENTITY = /^(none|matrix\(1, 0, 0, 1, 0, 0\))$/;

/* ---------------------------------------------------------------- 0. a flow not yet reached is put away at once */
{
	// When motion is set up each step is hidden until its flow is seen. The stagger that spaces them out on the way in
	// once applied on the way out as well, so for two seconds after the page loaded the last steps were still showing.
	const { context, page } = await open({});
	await page.waitForTimeout(400);
	const showing = await page.evaluate(() => [...document.querySelectorAll('.evpx-flow__step > *')].filter((e) => Number(getComputedStyle(e).opacity) > 0.02).length);
	check('flow: until it has been reached every step is put away, all at once (none left showing just after the page loads)', showing === 0, `${showing} still showing`);
	await context.close();
}

/* ---------------------------------------------------------------- 1. motion allowed, fine pointer */
{
	const { context, page, problems } = await open({});
	await page.waitForTimeout(2500);

	const marked = await page.evaluate(() => {
		const roots = [...document.querySelectorAll('.evpx-root')];
		const off = roots.filter((r) => r.getAttribute('data-evpx-animate') === '0');
		return { roots: roots.length, on: roots.filter((r) => r.getAttribute('data-evpx-motion') === 'on').length, off: off.length, offMarked: off.filter((r) => r.hasAttribute('data-evpx-motion')).length };
	});
	check('motion: every widget is marked data-evpx-motion="on" except those whose own animation control is off', marked.on === marked.roots - marked.off && marked.offMarked === 0 && marked.on > 0, JSON.stringify(marked));

	check('hero: the line at its foot charges in (an animation, not a static rule)', (await style(page, '.evpx-hero__line', 'animationName', '::after')) === 'evpx-charge');

	// The drawing: its outlines draw themselves in, a band of light crosses the car, a pulse runs the cable.
	const drawing = await page.evaluate(() => {
		const name = (sel) => {
			const el = document.querySelector(sel);
			return el ? getComputedStyle(el).animationName : 'missing';
		};
		const scan = document.querySelector('.evpx-hero .evpx-art__scan');
		return {
			draw: name('.evpx-hero .evpx-art__draw'),
			arc: name('.evpx-hero .evpx-art__arc'),
			scan: name('.evpx-hero .evpx-art__scan'),
			scanFill: scan ? getComputedStyle(scan).fill : 'missing',
			pulse: name('.evpx-hero .evpx-art__pulse'),
			pulseOpacity: getComputedStyle(document.querySelector('.evpx-hero .evpx-art__pulse')).opacity,
		};
	});
	check('hero drawing: the outlines draw in, the ring fills, a band of light crosses the car, a pulse runs the cable', drawing.draw === 'evpx-draw' && drawing.arc === 'evpx-arc' && drawing.scan === 'evpx-scan' && drawing.pulse === 'evpx-pulse' && drawing.pulseOpacity === '1', JSON.stringify(drawing));
	check('hero drawing: the band of light paints (its fill is the gradient: a class that says fill:none would hide it)', /^url\(/.test(drawing.scanFill), drawing.scanFill);

	// Scroll like a reader, then let every entrance finish.
	await scrollThrough(page);
	await page.waitForTimeout(3500);

	const settled = await page.evaluate(() => ({
		reveals: [...document.querySelectorAll('[data-evpx-reveal]')].filter((e) => getComputedStyle(e).opacity !== '1' || getComputedStyle(e).visibility === 'hidden').length,
		notIn: [...document.querySelectorAll('[data-evpx-reveal], .evpx-comparison, .evpx-flow')].filter((e) => !e.classList.contains('evpx-in-view')).length,
		flowHidden: [...document.querySelectorAll('.evpx-flow__step > *')].filter((e) => getComputedStyle(e).opacity !== '1').length,
		steps: document.querySelectorAll('.evpx-flow__step').length,
	}));
	check('every reveal target has arrived and is marked in view', settled.reveals === 0 && settled.notIn === 0, JSON.stringify(settled));
	check('flow: every step (node and label) is fully visible once the flow has been seen', settled.steps >= 2 && settled.flowHidden === 0, JSON.stringify(settled));

	// One grid, and no seams: the hero, the sections and the FAQ start at the same left edge, and two widgets that sit
	// side by side in a post's content touch (a theme's block gap must not show the page colour between them).
	const layout = await page.evaluate(() => {
		const left = (selector) => {
			const el = document.querySelector(selector);
			return el ? Math.round(el.getBoundingClientRect().left) : null;
		};
		const gaps = [];
		const widgets = [...document.querySelectorAll('.evpx-root.alignfull')];
		for (let i = 1; i < widgets.length; i++) {
			if (widgets[i - 1].nextElementSibling === widgets[i]) gaps.push(Math.round(widgets[i].getBoundingClientRect().top - widgets[i - 1].getBoundingClientRect().bottom));
		}
		return { hero: left('.evpx-hero__content'), section: left('.evpx-section__head'), faq: left('.evpx-faq__intro'), gaps };
	});
	check('one grid: the hero content, the first section and the FAQ intro share a left edge', layout.hero !== null && layout.section !== null && layout.faq !== null && Math.abs(layout.hero - layout.section) <= 1 && Math.abs(layout.hero - layout.faq) <= 1, JSON.stringify(layout));
	check('no seams: widgets side by side in a post touch, with no stripe of page colour between them', layout.gaps.length >= 3 && layout.gaps.every((g) => Math.abs(g) <= 1), JSON.stringify(layout.gaps));

	// "Media above text" puts the media above the text (built from a clone: the demo has no stacked section).
	const stacked = await page.evaluate(() => {
		const host = document.querySelector('.evpx-section');
		const copy = host.cloneNode(true);
		copy.className = 'evpx-root alignfull evpx-section evpx-section--stacked';
		const media = document.createElement('div');
		media.className = 'evpx-section__media';
		media.innerHTML = '<img alt="" width="600" height="300" style="width:600px;height:300px">';
		copy.querySelector('.evpx-section__grid').appendChild(media);
		host.after(copy);
		const above = media.getBoundingClientRect().top < copy.querySelector('.evpx-section__text').getBoundingClientRect().top;
		copy.remove();
		return above;
	});
	check('section: "Media above text" puts the media above the text', stacked === true);

	const connectors = await page.evaluate(() => [...document.querySelectorAll('.evpx-flow__step:not(:last-child)')].map((s) => getComputedStyle(s, '::after').transform));
	check('flow: every connector has finished charging (nothing left at scale 0)', connectors.length >= 1 && connectors.every((t) => /^(none|matrix\(1, 0, 0, 1, 0, 0\))$/.test(t)), connectors.join(' | '));

	// The comparison: the thumb sits behind the active tab, and follows a tab change.
	const thumb = () =>
		page.evaluate(() => {
			const track = document.querySelector('.evpx-comparison__tabs');
			const tab = document.querySelector('.evpx-comparison__tab--active');
			const v = (n) => parseFloat(track.style.getPropertyValue(n));
			return { marked: track.hasAttribute('data-evpx-thumb'), x: v('--evpx-thumb-x'), y: v('--evpx-thumb-y'), w: v('--evpx-thumb-w'), h: v('--evpx-thumb-h'), tab: { x: tab.offsetLeft, y: tab.offsetTop, w: tab.offsetWidth, h: tab.offsetHeight }, which: tab.getAttribute('data-target') };
		});
	const t1 = await thumb();
	check('comparison: the thumb is measured onto the active AC tab', t1.marked && t1.which === 'ac' && t1.x === t1.tab.x && t1.y === t1.tab.y && t1.w === t1.tab.w && t1.h === t1.tab.h, JSON.stringify(t1));
	await page.locator('.evpx-comparison__tab--dc').scrollIntoViewIfNeeded();
	await page.locator('.evpx-comparison__tab--dc').click();
	await page.waitForTimeout(1800);
	const t2 = await thumb();
	check('comparison: choosing DC moves the thumb onto the DC tab', t2.which === 'dc' && t2.x === t2.tab.x && t2.w === t2.tab.w && t2.x > t1.x, JSON.stringify(t2));

	// The range bar is drawn to scale: its width is the range's share of the ruler.
	const ruler = await page.evaluate(() => {
		const r = document.querySelector('.evpx-comparison__panel--active .evpx-ruler');
		if (!r) return null;
		const span = r.querySelector('.evpx-ruler__span').getBoundingClientRect();
		const cs = r.style;
		const from = parseFloat(cs.getPropertyValue('--evpx-from'));
		const to = parseFloat(cs.getPropertyValue('--evpx-to'));
		const wide = r.getBoundingClientRect().width;
		return { span: span.width, expected: Math.max(((to - from) / 100) * wide, 6), left: span.left - r.getBoundingClientRect().left, expectedLeft: (from / 100) * wide, max: r.getAttribute('data-max'), transform: getComputedStyle(r.querySelector('.evpx-ruler__span')).transform };
	});
	check('comparison: the DC range bar is its share of the scale, in the right place, fully grown', ruler && Math.abs(ruler.span - ruler.expected) < 1.5 && Math.abs(ruler.left - ruler.expectedLeft) < 1.5 && IDENTITY.test(ruler.transform), JSON.stringify(ruler));
	check('comparison: the scale is labelled with its top', !!ruler && /\d/.test(ruler.max || ''), JSON.stringify(ruler));
	await page.locator('.evpx-comparison__tab--ac').click();
	await page.waitForTimeout(1200);

	// Hover states. A fine pointer, so all of them apply.
	const hoverOver = async (selector, position) => {
		const el = page.locator(selector).first();
		await el.scrollIntoViewIfNeeded();
		await page.mouse.move(2, 2);
		await page.waitForTimeout(500);
		const box = await el.boundingBox();
		await page.mouse.move(box.x + box.width * (position?.x ?? 0.5), box.y + box.height * (position?.y ?? 0.5), { steps: 5 });
		await page.waitForTimeout(900);
	};
	const restOf = async (selector, prop, pseudo) => {
		await page.locator(selector).first().scrollIntoViewIfNeeded();
		await page.mouse.move(2, 2);
		await page.waitForTimeout(900);
		return style(page, selector, prop, pseudo);
	};

	const fillRest = await restOf('.evpx-hero__cta', 'transform', '::before');
	await hoverOver('.evpx-hero__cta');
	const fillHover = await style(page, '.evpx-hero__cta', 'transform', '::before');
	check('button: at rest the fill is swept away, on hover it has charged the whole button', /^matrix\(0, 0, 0, 1, 0, 0\)$/.test(fillRest) && IDENTITY.test(fillHover), `rest=${fillRest} hover=${fillHover}`);
	check('button: white type stays white on the charged fill', (await style(page, '.evpx-hero__cta', 'color')) === 'rgb(255, 255, 255)');

	const rimRest = await restOf('.evpx-scenario-card', 'opacity', '::after');
	await hoverOver('.evpx-scenario-card', { x: 0.3, y: 0.3 });
	const rimHover = await style(page, '.evpx-scenario-card', 'opacity', '::after');
	const spot = await page.evaluate(() => document.querySelector('.evpx-scenario-card').style.getPropertyValue('--evpx-mx'));
	const lift = await style(page, '.evpx-scenario-card', 'transform');
	check('scenario card: the copper rim and highlight light up under a fine pointer and follow it', rimRest === '0' && rimHover === '1' && /px$/.test(spot), `rest=${rimRest} hover=${rimHover} mx=${spot}`);
	check('scenario card: it lifts', lift !== 'none' && /matrix\(1, 0, 0, 1, 0, -/.test(lift), lift);

	const numRest = await restOf('.evpx-decision__item', 'transform', '::before');
	await hoverOver('.evpx-decision__item');
	const numHover = await style(page, '.evpx-decision__item', 'transform', '::before');
	check('decision list: the number of the row under the pointer steps forward', IDENTITY.test(numRest) && /matrix\(1, 0, 0, 1, [1-9]/.test(numHover), `rest=${numRest} hover=${numHover}`);

	const goRest = await restOf('.evpx-related__item', 'opacity', null);
	const knobRest = await style(page, '.evpx-related__go', 'opacity');
	await hoverOver('.evpx-related__item', { x: 0.5, y: 0.2 });
	const knobHover = await style(page, '.evpx-related__go', 'opacity');
	check('related: the arrow knob appears on the picture under a fine pointer', knobRest === '0' && knobHover === '1', `rest=${knobRest} hover=${knobHover} item=${goRest}`);

	// FAQ: opening presses the knob and turns the plus into a minus; a click leaves no theme outline behind.
	const q = page.locator('.evpx-faq__question').first();
	await q.scrollIntoViewIfNeeded();
	await q.click();
	await page.waitForTimeout(900);
	const open1 = await page.evaluate(() => {
		const item = document.querySelector('.evpx-faq__item');
		const button = item.querySelector('.evpx-faq__question');
		return { expanded: button.getAttribute('aria-expanded'), panelHidden: item.querySelector('.evpx-faq__answer').hidden, plus: getComputedStyle(item.querySelector('.evpx-faq__icon .evpx-icon--plus')).opacity, minus: getComputedStyle(item.querySelector('.evpx-faq__icon .evpx-icon--minus')).opacity, outline: getComputedStyle(button).outlineStyle };
	});
	check('FAQ: opening a question expands it and turns its plus into a minus', open1.expanded === 'true' && open1.panelHidden === false && open1.plus === '0' && open1.minus === '1', JSON.stringify(open1));
	check('FAQ: a mouse click leaves no focus outline (a keyboard still gets one)', open1.outline === 'none', JSON.stringify(open1));

	// A row opens from its own height. The answer's bottom padding has to travel with its height; when it does not, the
	// row gains that padding in the frame the click lands, so the height is read synchronously, before any frame is drawn.
	const jump = await page.evaluate(() => {
		const item = document.querySelectorAll('.evpx-faq__item')[1];
		const button = item.querySelector('.evpx-faq__question');
		const before = item.getBoundingClientRect().height;
		button.click();
		const after = item.getBoundingClientRect().height;
		button.click(); // and shut it again
		return { before: Math.round(before * 10) / 10, after: Math.round(after * 10) / 10 };
	});
	check('FAQ: a row opens from its own height, with no jump on the first frame', Math.abs(jump.after - jump.before) <= 1, JSON.stringify(jump));

	await page.evaluate(() => document.activeElement && document.activeElement.blur());
	await page.mouse.move(2, 2);
	let keyboardRing = null;
	for (let i = 0; i < 80 && !keyboardRing; i++) {
		await page.keyboard.press('Tab');
		keyboardRing = await page.evaluate(() => {
			const el = document.activeElement;
			return el && el.closest('.evpx-root') ? { tag: el.tagName, style: getComputedStyle(el).outlineStyle, width: getComputedStyle(el).outlineWidth } : null;
		});
	}
	check('keyboard: tabbing onto a widget control draws a focus ring', !!keyboardRing && keyboardRing.style === 'solid' && parseFloat(keyboardRing.width) >= 2, JSON.stringify(keyboardRing));

	check('no console errors, no failed requests, motion allowed', problems.length === 0, problems.join(' | '));
	await page.screenshot({ path: path.join(outDir, 'interaction-motion.png') });
	await context.close();
}

/* ---------------------------------------------------------------- 2. reduced motion */
{
	const { context, page, problems } = await open({ reducedMotion: 'reduce' });
	await page.waitForTimeout(800); // no entrance to wait for, and none held back

	const still = await page.evaluate(() => ({
		marked: document.querySelectorAll('[data-evpx-motion]').length,
		heroHidden: [...document.querySelectorAll('.evpx-hero__content > *')].filter((e) => getComputedStyle(e).opacity !== '1').length,
		line: getComputedStyle(document.querySelector('.evpx-hero__line'), '::after').animationName,
		flowHidden: [...document.querySelectorAll('.evpx-flow__step > *')].filter((e) => getComputedStyle(e).opacity !== '1').length,
		connectors: [...document.querySelectorAll('.evpx-flow__step:not(:last-child)')].map((s) => getComputedStyle(s, '::after').transform),
		ruler: [...document.querySelectorAll('.evpx-ruler__span')].map((s) => getComputedStyle(s).transform),
		thumbMarked: document.querySelector('.evpx-comparison__tabs')?.hasAttribute('data-evpx-thumb'),
	}));
	check('reduced motion: nothing is marked for motion, nothing is held back, no animation runs', still.marked === 0 && still.heroHidden === 0 && still.line === 'none' && still.flowHidden === 0, JSON.stringify(still));
	check('reduced motion: the finished state is already there (connectors drawn, range bars grown)', still.connectors.every((t) => IDENTITY.test(t)) && still.ruler.every((t) => IDENTITY.test(t)), JSON.stringify(still));
	check('reduced motion: the comparison switch still works, its thumb just does not travel', still.thumbMarked === true);
	const stillArt = await page.evaluate(() => {
		const cs = (sel) => {
			const el = document.querySelector(sel);
			return el ? getComputedStyle(el) : null;
		};
		return {
			draw: cs('.evpx-hero .evpx-art__draw')?.animationName,
			dash: cs('.evpx-hero .evpx-art__draw')?.strokeDasharray,
			pulse: cs('.evpx-hero .evpx-art__pulse')?.opacity,
			scan: cs('.evpx-hero .evpx-art__scan')?.opacity,
			arc: cs('.evpx-hero .evpx-art__arc')?.animationName,
		};
	});
	check('reduced motion: the hero drawing is finished and still (nothing draws in, no pulse, no scan, the ring is full)', stillArt.draw === 'none' && stillArt.dash === 'none' && stillArt.pulse === '0' && stillArt.scan === '0' && stillArt.arc === 'none', JSON.stringify(stillArt));
	check('reduced motion: no console errors', problems.length === 0, problems.join(' | '));
	await context.close();
}

/* ---------------------------------------------------------------- 3. a phone: touch, no hover */
{
	const { context, page, problems } = await open({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
	await page.waitForTimeout(1500);
	await scrollThrough(page);
	await page.waitForTimeout(2500);

	const touch = await page.evaluate(() => ({
		hover: matchMedia('(hover: hover)').matches,
		go: getComputedStyle(document.querySelector('.evpx-related__go')).display,
		spotBound: !!(window.EVPX && window.EVPX.__spotlightBound),
		overflow: document.documentElement.scrollWidth - window.innerWidth,
		reveals: [...document.querySelectorAll('[data-evpx-reveal]')].filter((e) => getComputedStyle(e).opacity !== '1').length,
		captionsWrapped: [...document.querySelectorAll('.evpx-comparison__data-row dt')].filter((e) => e.getBoundingClientRect().height > 1.6 * parseFloat(getComputedStyle(e).lineHeight)).length,
	}));
	check('phone: a data row keeps its caption on one line ("Best for" beside a long value)', touch.captionsWrapped === 0, JSON.stringify(touch));
	check('touch: no hover-only decoration (the arrow knob is not drawn) and no pointer highlight is bound', touch.hover === false && touch.go === 'none' && touch.spotBound === false, JSON.stringify(touch));
	check('touch: the page does not scroll sideways and every reveal has arrived', touch.overflow <= 1 && touch.reveals === 0, JSON.stringify(touch));
	check('touch: no console errors', problems.length === 0, problems.join(' | '));
	await context.close();
}

/* ---------------------------------------------------------------- 3b. the hero's drawing keeps clear of the copy */
for (const [w, h] of [[1920, 1080], [1440, 900], [1366, 768], [1280, 720], [1024, 768], [768, 1024], [390, 844]]) {
	const context = await browser.newContext({ viewport: { width: w, height: h } });
	const page = await context.newPage();
	await page.goto(url, { waitUntil: 'networkidle' });
	await page.waitForTimeout(600);
	const clash = await page.evaluate(() => {
		const visible = (el) => {
			const cs = getComputedStyle(el);
			const r = el.getBoundingClientRect();
			return cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0;
		};
		const copy = [...document.querySelectorAll('.evpx-hero__content > *')].filter(visible).map((e) => e.getBoundingClientRect());
		// What counts as the drawing's own ink: its labels, the charger, the car, the gauge (not the floor, which fades under everything).
		const ink = [...document.querySelectorAll('.evpx-hero__visual .evpx-art__label, .evpx-hero__visual .evpx-art__charger, .evpx-hero__visual .evpx-art__soc, .evpx-hero__visual .evpx-art__notes path')].filter(visible);
		const hits = [];
		for (const el of ink) {
			const r = el.getBoundingClientRect();
			for (const c of copy) {
				if (r.left < c.right - 1 && r.right > c.left + 1 && r.top < c.bottom - 1 && r.bottom > c.top + 1) hits.push(`${el.getAttribute('class') || el.tagName} ${Math.round(r.left)}–${Math.round(r.right)}×${Math.round(r.top)}–${Math.round(r.bottom)} over copy ${Math.round(c.left)}–${Math.round(c.right)}×${Math.round(c.top)}–${Math.round(c.bottom)}`);
			}
		}
		return { ink: ink.length, hits: hits.slice(0, 3), art: !!document.querySelector('.evpx-hero__visual .evpx-art') };
	});
	check(`${w}px: the hero's drawing (labels, charger, gauge) lies clear of its copy`, clash.art && clash.hits.length === 0, JSON.stringify(clash));
	await context.close();
}

/* ---------------------------------------------------------------- 4. the hero's "follow system" */
for (const [scheme, expected] of [
	['light', 'rgb(238, 240, 243)'],
	['dark', 'rgb(20, 23, 28)'],
]) {
	const { context, page } = await open({ colorScheme: scheme });
	await page.evaluate(() => document.querySelector('.evpx-hero').setAttribute('data-evpx-hero-mode', 'auto'));
	const bg = await style(page, '.evpx-hero', 'backgroundColor');
	check(`hero: "Follow system" is ${scheme} when the system is ${scheme}`, bg === expected, `${bg} (expected ${expected})`);
	await context.close();
}

await browser.close();
finish(outDir);
