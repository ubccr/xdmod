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

    // Chart traces and axis values svg
    let chart = await page.evaluate(() => document.querySelector('.user-select-none.svg-container').children[0].outerHTML);
    // Chart title and axis titles svg
    const chartLabels = await page.evaluate(() => document.querySelector('.user-select-none.svg-container').children[2].innerHTML);

    chart = chart.substring(0, chart.length - 6);
    let svg = chart + '' + chartLabels + '</svg>';

    // Unencoded HTML tags throw xml not well-formed error
    svg = svg.replace(/data-unformatted="(.*?)"/g, (str) => str.replace(/>/g, '&gt;').replace(/</g, '&lt;'));

    console.log(JSON.stringify(svg));

    await browser.close();
})();
