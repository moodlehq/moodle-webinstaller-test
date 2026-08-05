const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Usage: node screenshot.js <phpVersion> [label]
//
// With no label the Moodle site URL is captured, which doubles as a check that the web server is up.
// With a label of "failure" the URL recorded by the Behat context is captured instead, so the
// screenshot shows the page the test actually stopped on rather than the first installer page.
(async () => {
    const phpVersion = process.argv[2] || 'default';
    const label = process.argv[3] || '';
    const artifactDir = process.env.ARTIFACT_DIR || path.join(process.cwd(), 'artifacts');
    const lastUrlFile = path.join(artifactDir, 'last-url.txt');

    let url = process.env.SCREENSHOT_URL || '';
    if (!url && label && fs.existsSync(lastUrlFile)) {
        url = fs.readFileSync(lastUrlFile, 'utf8').trim();
    }
    if (!url) {
        url = process.env.MOODLE_SITE_URL || 'http://localhost:8080/moodle';
    }

    fs.mkdirSync(artifactDir, {recursive: true});
    const target = path.join(artifactDir, `moodle${phpVersion}${label ? `-${label}` : ''}.png`);

    const browser = await puppeteer.launch({
        args: ['--no-sandbox'],
    });
    try {
        const page = await browser.newPage();
        console.log(`Capturing ${url}`);
        const response = await page.goto(url, {waitUntil: 'domcontentloaded'});
        if (response) {
            console.log(`HTTP ${response.status()}`);
        }
        await page.screenshot({path: target, fullPage: true});
        console.log(`Screenshot written to ${target}`);
    } finally {
        await browser.close();
    }
})().catch((error) => {
    console.error(`Screenshot failed: ${error.message}`);
    // A screenshot we could not take must never be the reported cause of a failing job.
    process.exitCode = process.env.SCREENSHOT_OPTIONAL ? 0 : 1;
});
