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

    let svgInnerHtml;

    if (args.plotly) {
        // Chart traces and axis values svg
        let plotlyChart = await page.evaluate(() => document.querySelector('.user-select-none.svg-container').children[0].outerHTML);
        // Chart title and axis titles svg
        const plotlyLabels = await page.evaluate(() => document.querySelector('.user-select-none.svg-container').children[2].innerHTML);

        plotlyChart = plotlyChart.substring(0, plotlyChart.length - 6);
        const plotlyImage = plotlyChart + '' + plotlyLabels + '</svg>';
        // HTML tags in titles thorw xml not well-formed error
        svgInnerHtml = plotlyImage.replace(/<br>|<b>|<\/b>/gm, '');
    } else {
        svgInnerHtml = await page.evaluate(() => document.querySelector('.highcharts-container').innerHTML);
    }

    console.log(JSON.stringify(svgInnerHtml));

    await browser.close();
})();
