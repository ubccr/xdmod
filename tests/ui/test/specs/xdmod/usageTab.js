var logIn = require('./loginPage.page.js');
var usg = require('./usageTab.page.js');
var expected = global.testHelpers.artifacts.getArtifact('usage');
describe('Usage', function () {
    logIn.login('centerdirector');
    var baselineDate = {
        start: '2016-12-25',
        end: '2017-01-02'
    };
    describe('(Center Director)', function xdmod() {
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
    logIn.logout();
    describe('(Public User)', function () {
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
});
