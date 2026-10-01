/**
 * The buttons in the notice that follows activation: "Publish all six" and "Publish and use Home as the front page".
 * Logs in as an administrator, follows the button as a click, and prints what the site is like afterwards (the
 * shell script reads the options back).
 *
 *   node tests/playwright/site-publish-qa.mjs <origin> <all|front>
 */
import { launch, reporter } from './lib.mjs';

const [origin, which] = process.argv.slice(2);
const { check, finish } = reporter();
const browser = await launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
await ctx.route(/^https?:\/\/(?!localhost(:\d+)?\/)/, (r) => r.abort());
const page = await ctx.newPage();
await page.goto(`${origin}/wp-login.php`);
await page.fill('#user_login', 'admin');
await page.fill('#user_pass', 'admin');
await Promise.all([page.waitForURL(/\/wp-admin\//), page.click('#wp-submit')]);

const notice = page.locator('.notice-success', { hasText: 'added a site' });
check('the first admin request shows the site notice with its buttons', (await notice.count()) === 1);
const label = which === 'front' ? 'Publish and use Home as the front page' : 'Publish all six';
const button = notice.locator('a', { hasText: label });
check(`the notice offers "${label}"`, (await button.count()) === 1);
const href = await button.getAttribute('href');
check('the button is a nonce-protected admin-post address', /admin-post\.php\?action=evpx_publish_site/.test(href) && /_wpnonce=/.test(href));

await Promise.all([page.waitForURL(/edit\.php\?post_type=page/), button.click()]);
check('it lands on the Pages list', /post_type=page/.test(page.url()));

// Without the nonce nothing happens.
const bare = await ctx.request.get(`${origin}/wp-admin/admin-post.php?action=evpx_publish_site`, { maxRedirects: 0 });
check('the address without its nonce is refused', bare.status() === 403 || bare.status() === 200 && /link you followed has expired|are you sure/i.test(await bare.text()), String(bare.status()));

// A visitor cannot use it.
const visitor = await (await browser.newContext()).request.get(`${origin}/wp-admin/admin-post.php?action=evpx_publish_site&_wpnonce=abc`, { maxRedirects: 0 });
check('a visitor who is not logged in is sent to the login, not given the action', [301, 302].includes(visitor.status()) && /wp-login/.test(visitor.headers().location || ''), `${visitor.status()} ${visitor.headers().location}`);
await browser.close();
finish();
