class Usage {
    constructor() {
        this.tab = '#main_tab_panel__tg_usage';
        this.startField = '#tg_usage input[id^=start_field_ext]';
        this.endField = '#tg_usage input[id^=end_field_ext]';
        this.jobSizeMin = '.x-tree-root-node > li:nth-child(1) > ul:nth-child(2) > li:nth-child(5)';
        this.legendText = 'g.highcharts-legend-item';
    }

    checkLegendText(text) {
        browser.waitForChart();
        browser.pause(2500);
        browser.waitForExist(this.legendText, 50000);
        expect(browser.getText(this.legendText)).to.equal(text);
    }

}
module.exports = new Usage();
