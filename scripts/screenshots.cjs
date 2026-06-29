/**
 * Capture demo screenshots of Tend for the README.
 *
 * Logs in as the seeded demo user and screenshots the Today, Tasks, and Habits
 * pages in dark mode, plus Today in light mode. Used locally and by the
 * `.github/workflows/screenshots.yml` GitHub Action.
 *
 * Env:
 *   SHOT_BASE      base URL of a running app (default http://127.0.0.1:8000)
 *   SHOT_OUT       output directory (default docs/assets)
 *   CHROME_PATH    path to a Chrome/Chromium binary (required)
 *   SHOT_EMAIL     login email (default demo@tend.app)
 *   SHOT_PASSWORD  login password (default password)
 */
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const BASE = process.env.SHOT_BASE || 'http://127.0.0.1:8000';
const OUT = process.env.SHOT_OUT || 'docs/assets';
const CHROME = process.env.CHROME_PATH || process.env.PUPPETEER_EXECUTABLE_PATH;
const EMAIL = process.env.SHOT_EMAIL || 'demo@tend.app';
const PASSWORD = process.env.SHOT_PASSWORD || 'password';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function login(page) {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
    await sleep(500);
    if (page.url().includes('/dashboard')) {
        return; // session already authenticated (shared browser cookies)
    }
    await page.type('input[type=email]', EMAIL);
    await page.type('input[type=password]', PASSWORD);
    await page.click('button[type=submit]');
    await page
        .waitForFunction(() => location.pathname.includes('dashboard'), { timeout: 20000 })
        .catch(() => {});
    await sleep(1200);
}

async function shot(page, url, file, fullPage) {
    await page.goto(`${BASE}${url}`, { waitUntil: 'networkidle0' });
    await sleep(1000);
    await page.screenshot({ path: path.join(OUT, file), fullPage });
}

(async () => {
    fs.mkdirSync(OUT, { recursive: true });

    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--no-sandbox', '--disable-gpu'],
    });

    const dark = await browser.newPage();
    await dark.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'dark' }]);
    await dark.setViewport({ width: 1280, height: 1000, deviceScaleFactor: 1.5 });
    await login(dark);
    await shot(dark, '/dashboard', 'tend_today_dark.png', true);
    await shot(dark, '/tasks', 'tend_tasks_dark.png', true);
    await shot(dark, '/habits', 'tend_habits_dark.png', false);
    await dark.close();

    const light = await browser.newPage();
    await light.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);
    await light.setViewport({ width: 1280, height: 1000, deviceScaleFactor: 1.5 });
    await login(light);
    await shot(light, '/dashboard', 'tend_today_light.png', true);
    await light.close();

    await browser.close();
    console.log('Screenshots written to', OUT);
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
