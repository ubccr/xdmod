/* eslint-env node, es6 */
class Usage {
    constructor() {
        this.tab = '#main_tab_panel__tg_usage';
        this.startField = '#tg_usage input[id^=start_field_ext]';
        this.endField = '#tg_usage input[id^=end_field_ext]';
        this.legendText = 'g.highcharts-legend-item';
        this.refreshButton = '//div[@id="tg_usage"]//button[text()="Refresh"]/ancestor::node()[5]';
        this.topTreeNodeByName = function (name) {
            return '//div[@id="tg_usage"]//div[@class="x-tree-root-node"]/li/div[contains(@class,"x-tree-node-el")]//span[text() = "' + name + '"]';
        };
        this.treeNodeByPath = function (topname, childname) {
            return module.exports.topTreeNodeByName(topname) + '/ancestor::node()[3]//span[text() = "' + childname + '"]';
        };
        this.unfoldTreeNodeByName = function (name) {
            return module.exports.topTreeNodeByName(name) + '/ancestor::node()[2]/img[contains(@class,"x-tree-ec-icon")]';
        };
        this.chart = '//div[@id="tg_usage"]//div[@class="highcharts-container"]//*[local-name() = "svg"]';
        this.chartByTitle = function (title) {
            return module.exports.chart + '/*[name()="text" and contains(@class, "title")]/*[name()="tspan" and contains(text(),"' + title + '")]';
        };
        this.chartXAxisLabelByName = function (name) {
            return module.exports.chart + '/*[name() = "g" and contains(@class, "highcharts-xaxis-labels")]/*[name() = "text" and text() = "' + name + '"]';
        };
    }

    checkLegendText(text) {
        browser.waitForChart();
        browser.waitForExist(this.legendText, 50000);
        expect(browser.getText(this.legendText)).to.equal(text);
    }

}
module.exports = new Usage();
