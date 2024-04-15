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
    const svg = chart + '' + chartLabels + '</svg>';
    // HTML tags in titles thorw xml not well-formed error
    const svgInnerHtml = svg.replace(/<br>|<br\/>|<br \/>|<\/span>|<span|<sub>|<\/sub>|<sup>|<\/sup>|<b>|<\/b>/gm, (str) => {
        switch (str) {
            case '<br>':
                return '&lt;br&gt;';
            case '<br/>':
                return '&lt;br/&gt;';
            case '<br />':
                return '&lt;br /&gt;';
            case '<b>':
                return '&lt;b&gt;';
            case '</b>':
                return '&lt;/b&gt;';
            case '<span':
                return '&lt;span';
            case '</span>':
                return '&lt;/span&gt;';
            case '<sub>':
                return '&lt;sub&gt;';
            case '</sub>':
                return '&lt;/sub&gt;';
            case '<sup>':
                return '&lt;sup&gt;';
            case '</sup>':
                return '&lt;/sup&gt;';
            default:
                return str;
        }
    });
    console.log(JSON.stringify(svgInnerHtml));

    await browser.close();
})();
