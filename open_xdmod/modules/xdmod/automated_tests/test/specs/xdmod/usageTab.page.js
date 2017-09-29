/* eslint-env node, es6 */
var xdmod = require('./xdmod.page.js');

class Usage {
    constructor() {
        this.tab = '#main_tab_panel__tg_usage';
        this.startField = '#tg_usage input[id^=start_field_ext]';
        this.endField = '#tg_usage input[id^=end_field_ext]';
        this.legendText = 'g.highcharts-legend-item';
        this.refreshButton = '//div[@id="tg_usage"]//button[text()="Refresh"]/ancestor::node()[5]';
        this.toolbar = {
            exportButton: '//div[@id="tg_usage"]//button[text()="Export"]/ancestor::node()[5]'
        };
        this.panel = '//div[@id="tg_usage"]';
        this.availableForReportCheckbox = '//div[@id="tg_usage"]//label[text()="Available For Report"]/parent::node()/input[@type="checkbox"]';
        this.mask = '.ext-el-mask';
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
        this.durationButton = this.panel + '//button[contains(@class,"custom_date")]';
        this.durationMenu = '//div[contains(@class,"x-menu-floating")]';
        this.durationMenuItem = name => `${this.durationMenu}//li/a[./span[text()="${name}"]]`;
    }

    checkLegendText(text) {
        browser.waitForChart();
        browser.waitForExist(this.legendText, 50000);
        expect(browser.getText(this.legendText)).to.equal(text);
    }

    /**
     * Select the "Usage" tab by clicking it.
     */
    selectTab() {
        xdmod.selectTab('tg_usage');
        browser.waitForVisible(this.chart);
    }

    /**
     * Select a duration from the list of preset options.
     *
     * @param {String} name The name of the duration preset.
     */
    selectDuration(name) {
        browser.click(this.durationButton);
        browser.waitForVisible(this.durationMenu);
        browser.click(this.durationMenuItem(name));
        browser.waitForAllInvisible(this.mask, 5000);

        // The chart automatically refreshes after a new duration is
        // selected, but the menu remains open.  Clicking the refresh
        // button will close the menu.
        this.refresh();
    }

    /**
     * Set the start date.
     *
     * @param {String} date Start date.
     */
    setStartDate(date) {
        browser.setValue(this.startField, date);
    }

    /**
     * Set the end date.
     *
     * @param {String} date End date.
     */
    setEndDate(date) {
        browser.setValue(this.endField, date);
    }

    /**
     * Refresh current chart by clicking the "Refresh" button.
     */
    refresh() {
        browser.click(this.refreshButton);
        browser.waitForAllInvisible(this.mask, 5000);
    }

    /**
     * Make the current chart available for use in the report generator by
     * clicking the "Available for Report" checkbox.
     *
     * Preconditions:
     * - The "Available for Report" checkbox is visible, enabled and not checked.
     *
     * Postconditions:
     * - The "Available for Report" checkbox is checked.
     */
    makeCurrentChartAvailableForReport() {
        const checkbox = $(this.availableForReportCheckbox);
        expect(checkbox.isVisible(), '"Available for Report" checkbox is visible').to.be.true;
        expect(checkbox.isEnabled(), '"Available for Report" checkbox is enabled').to.be.true;
        expect(checkbox.isSelected(), '"Available for Report" checkbox is not checked').to.be.false;
        checkbox.click();
        expect(checkbox.isSelected(), '"Available for Report" checkbox is checked').to.be.true;
    }

    /**
     * Remove the current chart from the report generator by clicking the
     * "Available for Report" checkbox.
     *
     * Preconditions:
     * - The "Available for Report" checkbox is visible, enabled and checked.
     *
     * Postconditions:
     * - The "Available for Report" checkbox is not checked.
     */
    makeCurrentChartUnavailableForReport() {
        const checkbox = $(this.availableForReportCheckbox);
        expect(checkbox.isVisible(), '"Available for Report" checkbox is visible').to.be.true;
        expect(checkbox.isEnabled(), '"Available for Report" checkbox is enabled').to.be.true;
        expect(checkbox.isSelected(), '"Available for Report" checkbox is checked').to.be.true;
        checkbox.click();
        expect(checkbox.isSelected(), '"Available for Report" checkbox is not checked').to.be.false;
    }

    /**
     * Check if a top-level tree node is expanded.
     *
     * @param {String} name The name of the tree node.
     *
     * @return {Boolean} True if the node is expanded.
     */
    isTreeNodeExpanded(name) {
        return $(this.unfoldTreeNodeByName(name)).getAttribute('class').match(/[$ ]x-tree-node-plus[^ ]/) === null;
    }

    /**
     * Expand a top-level node in the metrics tree by clicking the
     * plus/minus icon.
     *
     * @param {String} name The name of the tree node.
     */
    expandTreeNode(name) {
        expect(this.isTreeNodeExpanded(name), 'Tree node is collapsed').to.be.false;
        browser.waitForLoadedThenClick(this.unfoldTreeNodeByName(name));
    }

    /**
     * Collapse a top-level node in the metrics tree by clicking the
     * plus/minus icon.
     *
     * @param {String} name The name of the tree node.
     */
    collapseTreeNode(name) {
        expect(this.isTreeNodeExpanded(name), 'Tree node is expanded').to.be.true;
        browser.waitForLoadedThenClick(this.unfoldTreeNodeByName(name));
    }

    /**
     * Select a top-level tree node.
     *
     * @param {String} name The name of the tree node.
     */
    selectTreeNode(name) {
        browser.waitForLoadedThenClick(this.topTreeNodeByName(name));
        browser.waitForAllInvisible(this.mask, 5000);
    }

    /**
     * Select a child node in the metrics tree by clicking.
     *
     * @param {String} topName The name of the top-level tree node.
     * @param {String} childName The name of the child tree node.
     */
    selectChildTreeNode(topName, childName) {
        if (!this.isTreeNodeExpanded(topName)) {
            this.expandTreeNode(topName);
        }
        browser.waitUntilAnimEndAndClick(this.treeNodeByPath(topName, childName));
        browser.waitForAllInvisible(this.mask, 5000);
    }
}
module.exports = new Usage();
