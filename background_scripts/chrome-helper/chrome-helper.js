#!/usr/bin/env node
const puppeteer = require('puppeteer-core');
const args = require('yargs').argv;

(async () => {
    const browser = await puppeteer.launch({
        executablePath: args['path-to-chrome'],
        args: ['--no-sandbox', '--disable-extensions', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();

    if (args['window-size']) {
        let dimensions = args['window-size'].split(',');
        await page.setViewport({
            width: parseInt(dimensions[0], 10),
            height: parseInt(dimensions[1], 10),
            deviceScaleFactor: 1
        });
    }

    await page.goto('file://' + args['input-file']);

    const innerHtml = await page.evaluate(() => document.querySelector('.highcharts-container').innerHTML);

    console.log(JSON.stringify(innerHtml));

    await browser.close();
})();
