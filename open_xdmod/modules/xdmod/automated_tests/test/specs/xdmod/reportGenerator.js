var logIn = require('./loginPage.page.js');
var rg = require('./reportGenerator.page.js');

describe('Report Generator', function reportGenerator() {
    var loginName = testHelpers.auth.roles.centerdirector.username;
    var loginPassword = testHelpers.auth.roles.centerdirector.password;
    var displayName = testHelpers.auth.roles.centerdirector.display;
    logIn.login('Open XDMoD', '/', loginName, loginPassword, displayName);
    var columnIndex = 1;
    var reportName = 'TAS Report 1';
    var startDate = '2016-12-20';
    var endDate = '2017-01-01';
    var currentSchedule;
    describe('Report Generator', function xdmod() {
        it('Click on the Report Generator Tab', function clickTab() {
            browser.waitForVisible(rg.mask, 50000, true);
            browser.waitAndClick(rg.tabForName(rg.name));
            browser.waitAndClick(rg.tabForName(rg.name));
        });

        it('Open the report to test', function selectReport() {
            browser.waitForVisible(rg.tableRowForColumnText(columnIndex, reportName), 30000);
            browser.doubleClick(rg.tableRowForColumnText(columnIndex, reportName));
        });

        it('Edit the timeframe for the selected report', function editTimeFrame() {
            browser.waitAndClick(rg.editDateForRowByIndex(1));
        });

        it('Wait for Date Editor to be visible', function waitForEditorVisibility() {
            browser.waitForVisible(rg.dateEditorcmp, 30000);
        });

        it("Select 'Specific' for the timeframe type", function setTimeFrameType() {
            browser.click(rg.radioButton(rg.radial.specific));
        });

        it('Clear start and end date', function clearStartAndEndDate() {
            browser.setValue(rg.startDateField(), '');
            browser.setValue(rg.endDateField(), '');
        });

        it('Press the update button, expect error about start date', function pressUpdateButton() {
            rg.pressUpdateExpectError('start date');
        });

        it('Set a start date', function setStartDate() {
            browser.click(rg.startDateField());
            browser.setValue(rg.startDateField(), startDate);
        });

        it('Press the update button, expect error about end date', function pressUpdateButton() {
            rg.pressUpdateExpectError('end date');
        });

        it('Set an end date', function setEndDate() {
            browser.click(rg.startDateField());
            browser.setValue(rg.endDateField(), endDate);
        });

        it('Press the update button', function pressUpdateButton() {
            rg.pressUpdate();
        });

        it('Verify that the date has been changed', function verifyDateChange() {
            rg.verifyDateChange(startDate, endDate);
        });

        it('Save the Report', function saveTheReport() {
            rg.saveTheReport();
        });

        it('Close the report', function closeReport() {
            browser.click(rg.returnToMyReportsButton());
            browser.waitForVisible(rg.tableRowForColumnText(columnIndex, reportName), 30000);
        });

        it('Open the report', function openReportAgain() {
            browser.doubleClick(rg.tableRowForColumnText(columnIndex, reportName));
        });

        it('Get the current Report Schedule', function getCurrentReportSchedule() {
            browser.waitForVisible(rg.cmp(), 30000);
            currentSchedule = browser.getValue(rg.cmp());
        });

        it('Change the Report Schedule', function changeReportSchedule() {
            browser.click(rg.cmp());
            browser.click(rg.selectionByName(rg.nextValue(currentSchedule)));
        });

        it('Save the Report', function saveTheReport() {
            rg.saveTheReport();
            browser.waitForVisible(rg.chartUpdateInProgressByIndex(1), 50000, true);
        });

        it('Close the report', function closeReport() {
            browser.click(rg.returnToMyReportsButton());
            browser.waitForVisible(rg.tableRowForColumnText(columnIndex, reportName), 30000);
        });

        it('Validate that the Schedule has been changed', function validateScheduleChange() {
            browser.isExisting(rg.tableRowForColumnText(3, rg.nextValue(currentSchedule)));
        });
        logIn.logout();
    });
});
