var logIn = require('./loginPage.page.js');
var usg = require('./usageTab.page.js');
var reportGen = require('./reportGenerator.page');

var expected = global.testHelpers.artifacts.getArtifact('usage');
describe('Usage', function () {
    var baselineDate = {
        start: '2016-12-25',
        end: '2017-01-02'
    };
    logIn.login('centerdirector');
    describe('(Center Director)', function xdmod() {
        describe('Check Usage Charts', function checkChart() {
            it('Select "Usage" tab', function () {
                usg.selectTab();
                browser.waitForChart();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));

                // by refreshing we ensure that there are not stale legend-item elements
                // on the page.
                browser.refresh();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));
            });
            it('Set a known start and end date', function meSetStartEnd() {
                usg.setStartDate(baselineDate.start);
                usg.setEndDate(baselineDate.end);
                usg.refresh();
                browser.waitForExist(usg.chartXAxisLabelByName(baselineDate.start));
            });
            it('Select Job Size Min', function () {
                browser.waitForLoadedThenClick(usg.treeNodeByPath('Jobs Summary', 'Job Size: Min'));
                browser.waitForExist(usg.chartByTitle('Job Size: Min (Core Count)'));
                usg.checkLegendText(expected.centerdirector.legend);
            });
            it('View CPU Hours by System Username', function () {
                browser.waitForLoadedThenClick(usg.unfoldTreeNodeByName('Jobs Summary'));
                browser.waitForLoadedThenClick(usg.unfoldTreeNodeByName('Jobs by System Username'));
                browser.waitUntilAnimEndAndClick(usg.treeNodeByPath('Jobs by System Username', 'CPU Hours: Per Job'));
                browser.waitForExist(usg.chartByTitle('CPU Hours: Per Job: by System Username'));
            });
        });
        describe('Check Available For Report', function usageAvailableForReport() {
            it('Reset to first Chart', function () {
                // by refreshing we ensure that there are not stale legend-item elements
                // on the page.
                browser.refresh();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));
            });
            it('Select "Available For Report"', function selectAvailableForReport() {
                browser.waitForVisible(usg.availableForReportCheckbox);
                var isSelected = browser.isSelected(usg.availableForReportCheckbox);
                if (isSelected === false) {
                    browser.waitForLoadedThenClick(usg.availableForReportCheckbox);
                    var nowSelected = browser.isSelected(usg.availableForReportCheckbox);
                    expect(nowSelected).to.equal(true);
                }
            });
            it('Select the Report Generator tab', function selectReportGenerator() {
                browser.waitForLoadedThenClick(reportGen.selectors.tab());
                browser.waitForVisible(reportGen.selectors.panel(), 3000);
            });
            it('Check that the last entry has the same title as the one we just made available for report', function titleIsTheSame() {
                const availableCharts = reportGen.getAvailableCharts();
                const lastAvailable = availableCharts[availableCharts.length - 1];
                expect(lastAvailable.getTitle()).to.equal(expected.centerdirector.default_chart_title.trim());
            });
            it('Select the "Usage" tab again', function () {
                usg.selectTab();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));
                // by refreshing we ensure that there are not stale legend-item elements
                // on the page.
                browser.refresh();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));
            });
            it('Uncheck "Available For Report"', function () {
                browser.waitForVisible(usg.availableForReportCheckbox);
                var isSelected = browser.isSelected(usg.availableForReportCheckbox);
                expect(isSelected).to.equal(true);
                browser.waitForLoadedThenClick(usg.availableForReportCheckbox);
                var nowSelected = browser.isSelected(usg.availableForReportCheckbox);
                expect(nowSelected).to.equal(false);
            });
            logIn.logout();
        });
    });
    describe('(Public User)', function () {
        describe('Check Usage Chart', function () {
            it('Selected', function () {
                usg.selectTab();
                browser.waitForChart();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));

                // by refreshing we ensure that there are not stale legend-item elements
                // on the page.
                browser.refresh();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));
            });
            it('Set a known start and end date', function meSetStartEnd() {
                usg.setStartDate(baselineDate.start);
                usg.setEndDate(baselineDate.end);
                usg.refresh();
                browser.waitForExist(usg.chartXAxisLabelByName(baselineDate.start));
            });
            it('View Job Size Min', function () {
                browser.waitForLoadedThenClick(usg.treeNodeByPath('Jobs Summary', 'Job Size: Min'));
                browser.waitForExist(usg.chartByTitle('Job Size: Min (Core Count)'));
                usg.checkLegendText(expected.centerdirector.legend);
            });
            it('Confirm System Username is not selectable', function () {
                browser.waitForLoadedThenClick(usg.unfoldTreeNodeByName('Jobs Summary'));
                browser.waitUntilAnimEndAndClick(usg.topTreeNodeByName('Jobs by System Username'));
                // The click should do nothing and the chart should remain unchanged
                // since nothing should happen, there is no action to wait for, so we
                // have to pause for a bit
                browser.pause(500);
                browser.waitForExist(usg.chartByTitle('Job Size: Min (Core Count)'));
            });
        });
        describe('Check Available For Report', function usageAvailableForReport() {
            it('Select "Usage" tab', function () {
                // by refreshing we ensure that there are not stale legend-item elements
                // on the page.
                browser.refresh();
                browser.waitForExist(usg.chartByTitle(expected.centerdirector.default_chart_title));
            });
            it('Ensure "Available For Report" is not present', function selectAvailableForReport() {
                var exists = browser.isExisting(usg.availableForReportCheckbox);
                expect(exists).to.equal(false);
            });
        });
    });
});
