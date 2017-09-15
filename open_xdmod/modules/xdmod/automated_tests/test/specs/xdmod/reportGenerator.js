const loginPage = require('./loginPage.page.js');
const usagePage = require('./usageTab.page.js');
const reportGeneratorPage = require('./reportGenerator.page.js');

describe('Report Generator', function () {
    // These dates correspond to the dates of the test job data.
    const startDate = '2016-12-20';
    const endDate = '2017-01-01';

    // Current date used in calculations below.
    const currentDate = new Date();

    // Calculate start and end dates of previous month.
    const previousMonth = new Date();
    previousMonth.setDate(1);
    previousMonth.setMonth(previousMonth.getMonth() - 1);
    const previousMonthStartDate = previousMonth.toISOString().substr(0, 10);

    // Setting the date to day "0" of a month will result in the last
    // day of the previous month.
    previousMonth.setMonth(previousMonth.getMonth() + 1);
    previousMonth.setDate(0);
    const previousMonthEndDate = previousMonth.toISOString().substr(0, 10);

    // Calculate start and end dates of previous quarter.
    const previousQuarter = new Date();
    previousQuarter.setDate(1);
    const currentMonth = previousQuarter.getMonth();
    const monthModThree = currentMonth % 3;
    previousQuarter.setMonth(currentMonth - 3 - monthModThree);
    const previousQuarterStartDate = previousQuarter.toISOString().substr(0, 10);
    previousQuarter.setMonth(previousQuarter.getMonth() + 3);
    previousQuarter.setDate(0);
    const previousQuarterEndDate = previousQuarter.toISOString().substr(0, 10);

    // Calculate start and end dates of previous year.
    const previousYearStartDate = (currentDate.getFullYear() - 1) + '-01-01';
    const previousYearEndDate = (currentDate.getFullYear() - 1) + '-12-31';

    // Calculate year-to-date start and end dates.
    const yearToDateStartDate = currentDate.getFullYear() + '-01-01';
    const yearToDateEndDate = currentDate.toISOString().substr(0, 10);

    // Descriptive text displayed in empty fields of a newly created
    // report.
    const reportEmptyText = {
        title: 'Optional, 50 max',
        header: 'Optional, 40 max',
        footer: 'Optional, 40 max'
    };

    // Default values expected when a new report is created.
    const defaultReport = {
        name: 'TAS Report 1',
        chartsPerPage: 1,
        schedule: 'Once',
        deliveryFormat: 'PDF',
        derivedFrom: 'Manual',
        charts: []
    };

    // These charts correspond to those that will be added to the
    // "Available Charts" list from the usage tab.
    const usageTabCharts = [
        {
            realm: 'Jobs',
            startDate: startDate,
            endDate: endDate,
            title: 'CPU Hours: Total',
            drillDetails: '',
            timeframeType: 'User Defined'
        },
        {
            realm: 'Jobs',
            startDate: startDate,
            endDate: endDate,
            title: 'CPU Hours: Per Job',
            drillDetails: '',
            timeframeType: 'User Defined'
        },
        {
            realm: 'Jobs',
            startDate: previousMonthStartDate,
            endDate: previousMonthEndDate,
            title: 'CPU Hours: Total',
            drillDetails: '',
            timeframeType: 'Previous month'
        },
        {
            realm: 'Jobs',
            startDate: previousYearStartDate,
            endDate: previousYearEndDate,
            title: 'CPU Hours: Total',
            drillDetails: '',
            timeframeType: 'Previous year'
        }
    ];

    const centerDirectorReportTemplates = [
        {
            name: 'Quarterly Report - Center Director',
            chartsPerPage: 1,
            schedule: 'Quarterly',
            deliveryFormat: 'PDF',
            charts: [
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: Total CPU Hours and Jobs',
                    drillDetails: '',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: Total CPU Hours and Jobs',
                    drillDetails: '',
                    timeframeType: 'Year to date'
                },
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: Percent Utilization',
                    drillDetails: '',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: Percent Utilization',
                    drillDetails: '',
                    timeframeType: 'Year to date'
                },
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: CPU Hours and Number of Jobs - Top 20 Users',
                    drillDetails: '',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: CPU Hours and Number of Jobs - Top 20 Users',
                    drillDetails: '',
                    timeframeType: 'Year to date'
                },
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: CPU Hours and Number of Jobs',
                    drillDetails: 'by Resource',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: CPU Hours and Number of Jobs',
                    drillDetails: 'by Resource',
                    timeframeType: 'Year to date'
                },
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: CPU Hours, Number of Jobs, and Wait Time per Job',
                    drillDetails: 'by Job Size',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: CPU Hours, Number of Jobs, and Wait Time per Job',
                    drillDetails: 'by Job Size',
                    timeframeType: 'Year to date'
                },
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: CPU Hours and User Expansion Factor',
                    drillDetails: 'by Job Size',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: CPU Hours and User Expansion Factor',
                    drillDetails: 'by Job Size',
                    timeframeType: 'Year to date'
                },
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: Wait Hours per Job',
                    drillDetails: 'by Queue',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: Wait Hours per Job',
                    drillDetails: 'by Queue',
                    timeframeType: 'Year to date'
                },
                {
                    realm: 'Jobs',
                    startDate: previousQuarterStartDate,
                    endDate: previousQuarterEndDate,
                    title: 'PREVIOUS QUARTER: CPU Hours and Number of Jobs',
                    drillDetails: 'by Queue',
                    timeframeType: 'Previous quarter'
                },
                {
                    realm: 'Jobs',
                    startDate: yearToDateStartDate,
                    endDate: yearToDateEndDate,
                    title: 'YEAR TO DATE: CPU Hours and Number of Jobs',
                    drillDetails: 'by Queue',
                    timeframeType: 'Year to date'
                }
            ]
        }
    ];

    // Public user
    describe('Public user default report generator state', function () {
        it('Report Generator is not enabled', function () {
            expect(reportGeneratorPage.isEnabled()).to.be.false;
        });
    });

    // User
    const { username: userLoginName, password: userLoginPassword, display: userDisplayName } = testHelpers.auth.roles.user;
    loginPage.login('Open XDMoD', '/', userLoginName, userLoginPassword, userDisplayName);

    describe('Normal user default report generator state', function () {
        it('Report Generator is enabled', function () {
            expect(reportGeneratorPage.isEnabled()).to.be.true;
        });
        it('Select Report Generator tab', function () {
            reportGeneratorPage.selectTab();
        });
        it('No reports listed', function () {
            expect(reportGeneratorPage.getMyReportsRows().length, 'No rows in the list of reports').to.be.equal(0);
        });
        it('No available charts listed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'No charts in the list of available charts').to.be.equal(0);
        });
        it('No report templates available', function () {
            expect(reportGeneratorPage.isNewBasedOnEnabled()).to.be.false;
        });
    });

    loginPage.logout();

    // PI
    const { username: piLoginName, password: piLoginPassword, display: piDisplayName } = testHelpers.auth.roles.principalinvestigator;
    loginPage.login('Open XDMoD', '/', piLoginName, piLoginPassword, piDisplayName);

    describe('Principal investigator default report generator state', function () {
        it('Report Generator is enabled', function () {
            expect(reportGeneratorPage.isEnabled()).to.be.true;
        });
        it('Select Report Generator tab', function () {
            reportGeneratorPage.selectTab();
        });
        it('No reports listed', function () {
            expect(reportGeneratorPage.getMyReportsRows().length, 'No rows in the list of reports').to.be.equal(0);
        });
        it('No available charts listed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'No charts in the list of available charts').to.be.equal(0);
        });
        it('No report templates available', function () {
            expect(reportGeneratorPage.isNewBasedOnEnabled()).to.be.false;
        });
    });

    loginPage.logout();

    // Center staff
    const { username: csLoginName, password: csLoginPassword, display: csDisplayName } = testHelpers.auth.roles.centerstaff;
    loginPage.login('Open XDMoD', '/', csLoginName, csLoginPassword, csDisplayName);

    describe('Center staff default report generator state', function () {
        it('Report Generator is enabled', function () {
            expect(reportGeneratorPage.isEnabled()).to.be.true;
        });
        it('Select Report Generator tab', function () {
            reportGeneratorPage.selectTab();
        });
        it('No reports listed', function () {
            expect(reportGeneratorPage.getMyReportsRows().length, 'No rows in the list of reports').to.be.equal(0);
        });
        it('No available charts listed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'No charts in the list of available charts').to.be.equal(0);
        });
        it('No report templates available', function () {
            expect(reportGeneratorPage.isNewBasedOnEnabled()).to.be.false;
        });
    });

    loginPage.logout();

    // Center director
    const { username: cdLoginName, password: cdLoginPassword, display: cdDisplayName } = testHelpers.auth.roles.centerdirector;
    loginPage.login('Open XDMoD', '/', cdLoginName, cdLoginPassword, cdDisplayName);

    describe('Center director default report generator state', function () {
        it('Report Generator is enabled', function () {
            expect(reportGeneratorPage.isEnabled()).to.be.true;
        });
        it('Select Report Generator tab', function () {
            reportGeneratorPage.selectTab();
        });
        it('No reports listed', function () {
            expect(reportGeneratorPage.getMyReportsRows().length, 'No rows in the list of reports').to.be.equal(0);
        });
        it('No available charts listed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'No charts in the list of available charts').to.be.equal(0);
        });
        it('Buttons are disabled', function () {
            expect(reportGeneratorPage.isEditSelectedReportsEnabled(), '"Edit" button is disabled').to.be.false;
            expect(reportGeneratorPage.isPreviewSelectedReportsEnabled(), '"Preview" button is disabled').to.be.false;
            expect(reportGeneratorPage.isSendSelectedReportsEnabled(), '"Send" button is disabled').to.be.false;
            expect(reportGeneratorPage.isDownloadSelectedReportsEnabled(), '"Download" button is disabled').to.be.false;
            expect(reportGeneratorPage.isDeleteSelectedReportsEnabled(), '"Delete" button is disabled').to.be.false;
        });
    });

    describe('Make usage tab charts available in the Report Generator', function () {
        usageTabCharts.forEach((testChart, index) => {
            it(`Make "${testChart.title}" chart available`, function () {
                usagePage.selectTab();
                const topNodeName = testChart.realm + ' ' + (testChart.drillDetails === '' ? 'Summary' : testChart.drillDetails);
                usagePage.selectChildTreeNode(topNodeName, testChart.title);

                if (testChart.timeframeType === 'User Defined') {
                    usagePage.setStartDate(testChart.startDate);
                    usagePage.setEndDate(testChart.endDate);
                    usagePage.refresh();
                } else {
                    usagePage.selectDuration(testChart.timeframeType);
                }

                usagePage.makeCurrentChartAvailableForReport();
            });
            it('Check available charts', function () {
                reportGeneratorPage.selectTab();
                const charts = reportGeneratorPage.getAvailableCharts();
                expect(charts.length, `${index + 1} chart(s) in the list of available charts`).to.be.equal(index + 1);

                for (let i = 0; i <= index; ++i) {
                    const chart = charts[i];
                    expect(chart.getTitle(), 'Chart title is correct').to.be.equal(usageTabCharts[i].title);
                    expect(chart.getDrillDetails(), 'Drill details are correct').to.be.equal(usageTabCharts[i].drillDetails);
                    expect(chart.getDateDescription(), 'Date description is correct').to.be.equal(`${usageTabCharts[i].startDate} to ${usageTabCharts[i].endDate}`);
                    expect(chart.getTimeframeType(), 'Timeframe type is correct').to.be.equal(usageTabCharts[i].timeframeType);
                }
            });
        });
    });

    describe('Create report with default options', function () {
        // Copy default report data for the report being tested.
        const testReport = Object.assign({}, defaultReport);

        it('Create a new report', function () {
            reportGeneratorPage.createNewReport();
        });
        it('Check default values', function () {
            expect(reportGeneratorPage.getFileName(), 'Default report file name').to.be.equal(defaultReport.name);
            expect(reportGeneratorPage.getNumberOfChartsPerPage(), 'Default report number of charts per page').to.be.equal(defaultReport.chartsPerPage);
            expect(reportGeneratorPage.getSchedule(), 'Default report schedule').to.be.equal(defaultReport.schedule);
            expect(reportGeneratorPage.getDeliveryFormat(), 'Default report delivery format').to.be.equal(defaultReport.deliveryFormat);
            expect(reportGeneratorPage.getIncludedCharts().length, 'Default report chart count').to.be.equal(defaultReport.charts.length);
        });
        it('Empty text fields', function () {
            expect(reportGeneratorPage.getReportTitle(), 'Empty report title').to.be.equal(reportEmptyText.title);
            expect(reportGeneratorPage.getHeaderText(), 'Empty report header').to.be.equal(reportEmptyText.header);
            expect(reportGeneratorPage.getFooterText(), 'Empty report footer').to.be.equal(reportEmptyText.footer);
        });
        it('Add chart to report', function () {
            reportGeneratorPage.addChartToReport(0);

            // Add chart to test report data to reflect the change made
            // to the report.
            testReport.charts.push(usageTabCharts[0]);
        });
        it('Save report', function () {
            reportGeneratorPage.saveReport();
        });
        it('Return to "My Reports"', function () {
            reportGeneratorPage.returnToMyReports();
        });
        it('Check report list', function () {
            const reportRows = reportGeneratorPage.getMyReportsRows();
            expect(reportRows.length, '1 report in list').to.be.equal(1);
            const reportRow = reportRows[0];
            expect(reportRow.getName(), 'Name is correct').to.be.equal(testReport.name);
            expect(reportRow.getDerivedFrom(), '"Derived From" is correct').to.be.equal(testReport.derivedFrom);
            expect(reportRow.getSchedule(), 'Schedule is correct').to.be.equal(testReport.schedule);
            expect(reportRow.getDeliveryFormat(), 'Delivery format is correct').to.be.equal(testReport.deliveryFormat);
            expect(reportRow.getNumberOfCharts(), 'Number of charts of is correct').to.be.equal(testReport.charts.length);
            expect(reportRow.getNumberOfChartsPerPage(), 'Number of charts per page is correct').to.be.equal(testReport.chartsPerPage);
        });
    });

    describe('Create report and change options', function () {
        const testReport = {
            name: 'Test Report 1',
            title: 'Test Report',
            header: 'Test header',
            footer: 'Test footer',
            chartsPerPage: 2,
            schedule: 'Monthly',
            deliveryFormat: 'Word Document',
            derivedFrom: 'Manual',
            charts: []
        };

        it('Create a new report', function () {
            reportGeneratorPage.createNewReport();
        });
        it('Set file name', function () {
            reportGeneratorPage.setFileName(testReport.name);
        });
        it('Set report title', function () {
            reportGeneratorPage.setReportTitle(testReport.title);
        });
        it('Set header text', function () {
            reportGeneratorPage.setHeaderText(testReport.header);
        });
        it('Set footer text', function () {
            reportGeneratorPage.setFooterText(testReport.footer);
        });
        it('Set number of charts per page', function () {
            reportGeneratorPage.setNumberOfChartsPerPage(testReport.chartsPerPage);
        });
        it('Set schedule', function () {
            reportGeneratorPage.setSchedule(testReport.schedule);
        });
        it('Set delivery format', function () {
            reportGeneratorPage.setDeliveryFormat(testReport.deliveryFormat);
        });
        it('Add chart to report', function () {
            reportGeneratorPage.addChartToReport(0);

            // Add chart to test report data to reflect the change made
            // to the report.
            testReport.charts.push(usageTabCharts[0]);
        });
        it('Edit the timeframe of the chart', function () {
            reportGeneratorPage.getIncludedCharts()[0].editTimeframe();
        });
        it('Select "Specific" for the timeframe type', function () {
            reportGeneratorPage.selectSpecificChartTimeframe();
        });
        it('Clear start and end date', function () {
            reportGeneratorPage.setSpecificChartTimeframeStartDate('');
            reportGeneratorPage.setSpecificChartTimeframeEndDate('');
        });
        it('Click the update button, expect error about start date', function () {
            reportGeneratorPage.confirmEditTimeframeOfSelectedCharts();
            expect(reportGeneratorPage.getEditChartTimeframeErrorMessage(), 'Start date error').to.be.equal('Valid start date required');
        });
        it('Set a start date', function () {
            reportGeneratorPage.setSpecificChartTimeframeStartDate(startDate);
        });
        it('Click the update button, expect error about end date', function () {
            reportGeneratorPage.confirmEditTimeframeOfSelectedCharts();
            expect(reportGeneratorPage.getEditChartTimeframeErrorMessage(), 'End date error').to.be.equal('Valid end date required');
        });
        it('Set an end date', function () {
            reportGeneratorPage.setSpecificChartTimeframeEndDate(endDate);
        });
        it('Click the update button', function () {
            reportGeneratorPage.confirmEditTimeframeOfSelectedCharts();
        });
        it('Verify that the date has been changed', function () {
            const chart = reportGeneratorPage.getIncludedCharts()[0];
            expect(chart.getTimeframeType(), 'Timeframe type').to.be.equal('User Defined');
            expect(chart.getDateDescription(), 'Date description').to.be.equal(startDate + ' to ' + endDate);
        });
        it('Save report', function () {
            reportGeneratorPage.saveReport();
        });
        it('Return to "My Reports"', function () {
            reportGeneratorPage.returnToMyReports();
        });
        it('Check report list', function () {
            const reportRows = reportGeneratorPage.getMyReportsRows();
            expect(reportRows.length, '2 reports in list').to.be.equal(2);
            const reportRow = reportRows[1];
            expect(reportRow.getName(), 'Name is correct').to.be.equal(testReport.name);
            expect(reportRow.getDerivedFrom(), '"Derived From" is correct').to.be.equal(testReport.derivedFrom);
            expect(reportRow.getSchedule(), 'Schedule is correct').to.be.equal(testReport.schedule);
            expect(reportRow.getDeliveryFormat(), 'Delivery format is correct').to.be.equal(testReport.deliveryFormat);
            expect(reportRow.getNumberOfCharts(), 'Number of charts of is correct').to.be.equal(testReport.charts.length);
            expect(reportRow.getNumberOfChartsPerPage(), 'Number of charts per page is correct').to.be.equal(testReport.chartsPerPage);
        });
        it('Edit report and compare values', function () {
            reportGeneratorPage.getMyReportsRows()[1].doubleClick();
            expect(reportGeneratorPage.getFileName(), 'File name is correct').to.be.equal(testReport.name);
            expect(reportGeneratorPage.getReportTitle(), 'Report title is correct').to.be.equal(testReport.title);
            expect(reportGeneratorPage.getHeaderText(), 'Header text is correct').to.be.equal(testReport.header);
            expect(reportGeneratorPage.getFooterText(), 'Footer text is correct').to.be.equal(testReport.footer);
            expect(reportGeneratorPage.getNumberOfChartsPerPage(), 'Number of charts per page is correct').to.be.equal(testReport.chartsPerPage);
            expect(reportGeneratorPage.getSchedule(), 'Schedule is correct').to.be.equal(testReport.schedule);
            expect(reportGeneratorPage.getDeliveryFormat(), 'Delivery format is correct').to.be.equal(testReport.deliveryFormat);
            reportGeneratorPage.returnToMyReports();
        });
    });

    describe('Edit report and "Save As"', function () {
        const testReport = {
            name: 'Copied Report'
        };

        it('Store data for report that will be copied', function () {
            const reportRow = reportGeneratorPage.getMyReportsRows()[1];
            testReport.derivedFrom = reportRow.getDerivedFrom();
            testReport.schedule = reportRow.getSchedule();
            testReport.deliveryFormat = reportRow.getDeliveryFormat();
            testReport.numberOfCharts = reportRow.getNumberOfCharts();
            testReport.chartsPerPage = reportRow.getNumberOfChartsPerPage();
        });
        it('Edit report', function () {
            reportGeneratorPage.getMyReportsRows()[1].doubleClick();
        });
        it('Click "Save As" and set file name', function () {
            reportGeneratorPage.saveReportAs(testReport.name);
        });
        it('Click "Save" in "Save As" window', function () {
            reportGeneratorPage.confirmSaveReportAs();
        });
        it('Close "Save As" window', function () {
            reportGeneratorPage.closeSaveReportAs();
        });
        it('Return to "My Reports"', function () {
            reportGeneratorPage.returnToMyReports();
        });
        it('Check report list', function () {
            const reportRows = reportGeneratorPage.getMyReportsRows();
            expect(reportRows.length, '3 reports in list').to.be.equal(3);
            const reportRow = reportRows[2];
            expect(reportRow.getName(), 'Name is correct').to.be.equal(testReport.name);
            expect(reportRow.getDerivedFrom(), '"Derived From" is correct').to.be.equal(testReport.derivedFrom);
            expect(reportRow.getSchedule(), 'Schedule is correct').to.be.equal(testReport.schedule);
            expect(reportRow.getDeliveryFormat(), 'Delivery format is correct').to.be.equal(testReport.deliveryFormat);
            expect(reportRow.getNumberOfCharts(), 'Number of charts of is correct').to.be.equal(testReport.numberOfCharts);
            expect(reportRow.getNumberOfChartsPerPage(), 'Number of charts per page is correct').to.be.equal(testReport.chartsPerPage);
        });
    });

    describe('Edit report and make changes', function () {
        // The row index of the report that will be edited.
        const reportIndex = 2;

        // The data that will be changed in the report.
        const testReport = {
            name: 'Edited Test Report 1',
            title: 'Edited Test Report',
            header: 'Edited header',
            footer: 'Edited footer',
            chartsPerPage: 1,
            schedule: 'Quarterly',
            deliveryFormat: 'PDF',
            derivedFrom: 'Manual',
            // The report being edited contains one chart already, but this
            // test does not check it's contents.
            charts: [{}]
        };

        it('Open the report', function () {
            reportGeneratorPage.getMyReportsRows()[reportIndex].doubleClick();
        });
        it('Set file name', function () {
            reportGeneratorPage.setFileName(testReport.name);
        });
        it('Set report title', function () {
            reportGeneratorPage.setReportTitle(testReport.title);
        });
        it('Set header text', function () {
            reportGeneratorPage.setHeaderText(testReport.header);
        });
        it('Set footer text', function () {
            reportGeneratorPage.setFooterText(testReport.footer);
        });
        it('Set number of charts per page', function () {
            reportGeneratorPage.setNumberOfChartsPerPage(testReport.chartsPerPage);
        });
        it('Set schedule', function () {
            reportGeneratorPage.setSchedule(testReport.schedule);
        });
        it('Set delivery format', function () {
            reportGeneratorPage.setDeliveryFormat(testReport.deliveryFormat);
        });
        it('Add chart to report', function () {
            reportGeneratorPage.addChartToReport(1);

            // Add chart to test report data to reflect the change made
            // to the report.
            testReport.charts.push(usageTabCharts[1]);
        });
        it('Save report', function () {
            reportGeneratorPage.saveReport();
        });
        it('Return to "My Reports"', function () {
            reportGeneratorPage.returnToMyReports();
        });
        it('Check report list', function () {
            const reportRows = reportGeneratorPage.getMyReportsRows();
            expect(reportRows.length, '3 reports in list').to.be.equal(3);
            const reportRow = reportRows[reportIndex];
            expect(reportRow.getName(), 'Name is correct').to.be.equal(testReport.name);
            expect(reportRow.getDerivedFrom(), '"Derived From" is correct').to.be.equal(testReport.derivedFrom);
            expect(reportRow.getSchedule(), 'Schedule is correct').to.be.equal(testReport.schedule);
            expect(reportRow.getDeliveryFormat(), 'Delivery format is correct').to.be.equal(testReport.deliveryFormat);
            expect(reportRow.getNumberOfCharts(), 'Number of charts of is correct').to.be.equal(testReport.charts.length);
            expect(reportRow.getNumberOfChartsPerPage(), 'Number of charts per page is correct').to.be.equal(testReport.chartsPerPage);
        });
        it('Edit report and compare values', function () {
            reportGeneratorPage.getMyReportsRows()[reportIndex].doubleClick();
            expect(reportGeneratorPage.getFileName(), 'File name is correct').to.be.equal(testReport.name);
            expect(reportGeneratorPage.getReportTitle(), 'Report title is correct').to.be.equal(testReport.title);
            expect(reportGeneratorPage.getHeaderText(), 'Header text is correct').to.be.equal(testReport.header);
            expect(reportGeneratorPage.getFooterText(), 'Footer text is correct').to.be.equal(testReport.footer);
            expect(reportGeneratorPage.getNumberOfChartsPerPage(), 'Number of charts per page is correct').to.be.equal(testReport.chartsPerPage);
            expect(reportGeneratorPage.getSchedule(), 'Schedule is correct').to.be.equal(testReport.schedule);
            expect(reportGeneratorPage.getDeliveryFormat(), 'Delivery format is correct').to.be.equal(testReport.deliveryFormat);
            reportGeneratorPage.returnToMyReports();
        });
    });

    describe('Create report from template', function () {
        it('Click "New Based On"', function () {
            reportGeneratorPage.clickNewBasedOn();
        });
        it('Check list of report templates', function () {
            reportGeneratorPage.getReportTemplateNames().forEach((reportTemplateName, i) => {
                expect(reportTemplateName, 'Report template ' + i).to.be.equal(centerDirectorReportTemplates[i].name);
            });
        });
        it('Click "New Based On" to close menu', function () {
            // Close the menu so that it can be re-opened in the loop below.
            reportGeneratorPage.clickNewBasedOn();
        });
        centerDirectorReportTemplates.forEach(template => {
            let reportIndex;

            it('Click "New Based On"', function () {
                reportIndex = reportGeneratorPage.getMyReportsRows().length;
                reportGeneratorPage.clickNewBasedOn();
            });
            it(`Select "${template.name}"`, function () {
                reportGeneratorPage.selectNewBasedOnTemplate(template.name);
            });
            it('Check list of reports', function () {
                const reportRows = reportGeneratorPage.getMyReportsRows();
                expect(reportRows.length, 'New report added').to.be.equal(reportIndex + 1);
                const reportRow = reportRows[reportIndex];
                expect(reportRow.getName(), 'Name is correct').to.be.equal(template.name + ' 1');
                expect(reportRow.getDerivedFrom(), '"Derived From" is correct').to.be.equal(template.name);
                expect(reportRow.getSchedule(), 'Schedule is correct').to.be.equal(template.schedule);
                expect(reportRow.getDeliveryFormat(), 'Delivery format is correct').to.be.equal(template.deliveryFormat);
                expect(reportRow.getNumberOfCharts(), 'Number of charts of is correct').to.be.equal(template.charts.length);
                expect(reportRow.getNumberOfChartsPerPage(), 'Number of charts per page is correct').to.be.equal(template.chartsPerPage);
            });
            it('Edit report based on template', function () {
                reportGeneratorPage.getMyReportsRows()[reportIndex].doubleClick();
            });
            it('Check charts', function () {
                reportGeneratorPage.getIncludedCharts().forEach((chart, i) => {
                    const templateChart = template.charts[i];
                    expect(chart.getTitle(), 'Chart title').to.be.equal(templateChart.title);
                    expect(chart.getDrillDetails(), 'Drill details').to.be.equal(templateChart.drillDetails);
                    expect(chart.getTimeframeType(), 'Timeframe type').to.be.equal(templateChart.timeframeType);
                    expect(chart.getDateDescription(), 'Date description').to.be.equal(templateChart.startDate + ' to ' + templateChart.endDate);
                });
            });
            it('Return to "My Reports"', function () {
                reportGeneratorPage.returnToMyReports();
            });
        });
    });

    describe('Preview report', function () {
        it('Select a report', function () {
            reportGeneratorPage.getMyReportsRows()[0].click();
        });
        it('Preview selected report', function () {
            reportGeneratorPage.previewSelectedReports();
        });
        it('Return to reports overview', function () {
            reportGeneratorPage.returnToReportsOverview();
        });
        it('Deselect reports', function () {
            reportGeneratorPage.deselectAllReports();
        });
    });

    describe('Download report', function () {
        it('Select a report', function () {
            reportGeneratorPage.getMyReportsRows()[0].click();
        });
        it('Click "Download" button', function () {
            reportGeneratorPage.downloadSelectedReports();
        });
        it('Click "As PDF"', function () {
            reportGeneratorPage.downloadSelectedReportsAsPdf();
        });
        it('Close "Report Built" window', function () {
            reportGeneratorPage.closeReportBuiltWindow();
        });
        it('Click "Download" button', function () {
            reportGeneratorPage.downloadSelectedReports();
        });
        it('Click "As Word Document"', function () {
            reportGeneratorPage.downloadSelectedReportsAsWordDocument();
        });
        it('Close "Report Built" window', function () {
            reportGeneratorPage.closeReportBuiltWindow();
        });
        it('Deselect reports', function () {
            reportGeneratorPage.deselectAllReports();
        });
    });

    describe('Select reports', function () {
        it('Select all', function () {
            reportGeneratorPage.selectAllReports();
            reportGeneratorPage.getMyReportsRows().forEach((row, i) => {
                expect(row.isSelected(), `Row ${i} is selected`).to.be.true;
            });
        });
        it('Select none', function () {
            reportGeneratorPage.deselectAllReports();
            reportGeneratorPage.getMyReportsRows().forEach((row, i) => {
                expect(row.isSelected(), `Row ${i} is not selected`).to.be.false;
            });
        });
        it('Invert selection', function () {
            // Select one row then invert selection.
            reportGeneratorPage.getMyReportsRows()[1].toggleSelection();
            const selectedStatus = reportGeneratorPage.getMyReportsRows().map(row => row.isSelected());
            reportGeneratorPage.invertReportSelection();
            reportGeneratorPage.getMyReportsRows().forEach((row, i) => {
                expect(row.isSelected(), `Row ${i} has been inverted`).to.be.equal(!selectedStatus[i]);
            });
        });
    });

    describe('Attempt to edit multiple reports from "My Reports"', function () {
        it('Select reports', function () {
            reportGeneratorPage.selectAllReports();
        });
        it('Edit reports (should not be possible)', function () {
            expect(reportGeneratorPage.isEditSelectedReportsEnabled(), '"Edit" button is disabled').to.be.false;
        });
        it('Deselect reports', function () {
            reportGeneratorPage.deselectAllReports();
        });
    });

    describe('Attempt to preview multiple reports from "My Reports"', function () {
        it('Select reports', function () {
            reportGeneratorPage.selectAllReports();
        });
        it('Preview reports (should not be possible)', function () {
            expect(reportGeneratorPage.isPreviewSelectedReportsEnabled(), '"Preview" button is disabled').to.be.false;
        });
        it('Deselect reports', function () {
            reportGeneratorPage.deselectAllReports();
        });
    });

    describe('Attempt to send multiple reports from "My Reports"', function () {
        it('Select reports', function () {
            reportGeneratorPage.selectAllReports();
        });
        it('Send reports (should not be possible)', function () {
            expect(reportGeneratorPage.isSendSelectedReportsEnabled(), '"Send" button is disabled').to.be.false;
        });
        it('Deselect reports', function () {
            reportGeneratorPage.deselectAllReports();
        });
    });

    describe('Attempt to download multiple reports from "My Reports"', function () {
        it('Select reports', function () {
            reportGeneratorPage.selectAllReports();
        });
        it('Download reports (should not be possible)', function () {
            expect(reportGeneratorPage.isDownloadSelectedReportsEnabled(), '"Download" button is disabled').to.be.false;
        });
        it('Deselect reports', function () {
            reportGeneratorPage.deselectAllReports();
        });
    });

    describe('Delete report from "My Reports"', function () {
        let reportCount;

        it('Select report', function () {
            const reports = reportGeneratorPage.getMyReportsRows();
            reportCount = reports.length;
            reports[0].click();
            expect(reports[0].isSelected(), 'Report is selected').to.be.true;
        });
        it('Click delete button', function () {
            reportGeneratorPage.deleteSelectedReports();
        });
        it('Cancel deletion', function () {
            reportGeneratorPage.cancelDeleteSelectedReports();
            const reports = reportGeneratorPage.getMyReportsRows();
            expect(reports.length, 'Report count has not changed').to.be.equal(reportCount);
            expect(reports[0].isSelected(), 'Report is still selected').to.be.true;
        });
        it('Click delete button', function () {
            reportGeneratorPage.deleteSelectedReports();
        });
        it('Confirm deletion', function () {
            reportGeneratorPage.confirmDeleteSelectedReports();
            --reportCount;
        });
        it('Check list of reports', function () {
            expect(reportGeneratorPage.getMyReportsRows().length).to.be.equal(reportCount);
        });
    });

    describe('Select charts listed in "Available Charts"', function () {
        it('Select all', function () {
            reportGeneratorPage.selectAllAvailableCharts();
            reportGeneratorPage.getAvailableCharts().forEach((chart, i) => {
                expect(chart.isSelected(), `Chart ${i} is selected`).to.be.true;
            });
        });
        it('Select none', function () {
            reportGeneratorPage.deselectAllAvailableCharts();
            reportGeneratorPage.getAvailableCharts().forEach((chart, i) => {
                expect(chart.isSelected(), `Chart ${i} is not selected`).to.be.false;
            });
        });
        it('Invert selection', function () {
            // Select one chart then invert selection.
            reportGeneratorPage.getAvailableCharts()[1].toggleSelection();
            const selectedStatus = reportGeneratorPage.getAvailableCharts().map(chart => chart.isSelected());
            reportGeneratorPage.invertAvailableChartsSelection();
            reportGeneratorPage.getAvailableCharts().forEach((chart, i) => {
                expect(chart.isSelected(), `Chart ${i} selection has been inverted`).to.be.equal(!selectedStatus[i]);
            });
        });
    });

    // Removes all but the first chart.
    describe('Remove charts from "Available Charts"', function () {
        let chartCount;

        it('Select all charts', function () {
            chartCount = reportGeneratorPage.getAvailableCharts().length;
            reportGeneratorPage.selectAllAvailableCharts();
        });
        it('Deselect first chart', function () {
            reportGeneratorPage.getAvailableCharts()[0].toggleSelection();
        });
        it('Click delete button', function () {
            reportGeneratorPage.deleteSelectedAvailableCharts();
        });
        it('Cancel deletion', function () {
            reportGeneratorPage.cancelDeleteSelectedAvailableCharts();
        });
        it('Confirm that no charts were removed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'Chart count not changed').to.be.equal(chartCount);
        });
        it('Click delete button again', function () {
            reportGeneratorPage.deleteSelectedAvailableCharts();
        });
        it('Confirm deletion', function () {
            reportGeneratorPage.confirmDeleteSelectedAvailableCharts();
        });
        it('Confirm that charts were removed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'All but one chart removed').to.be.equal(1);
        });
    });

    // Removes the first chart.
    describe('Remove chart from Report Generator from the Usage tab', function () {
        it('Click on the "Usage" tab', function () {
            usagePage.selectTab();
        });
        it('Set date range', function () {
            usagePage.setStartDate(usageTabCharts[0].startDate);
            usagePage.setEndDate(usageTabCharts[0].endDate);
            usagePage.refresh();
        });
        it('Make chart unavailable', function () {
            usagePage.makeCurrentChartUnavailableForReport();
        });
        it('Select Report Generator tab', function () {
            reportGeneratorPage.selectTab();
        });
        it('No available charts listed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'No charts in the list of available charts').to.be.equal(0);
        });
    });

    describe('Delete multiple reports from "My Reports"', function () {
        it('Select reports', function () {
            reportGeneratorPage.selectAllReports();
        });
        it('Delete reports', function () {
            reportGeneratorPage.deleteSelectedReports();
            reportGeneratorPage.confirmDeleteSelectedReports();
            expect(reportGeneratorPage.getMyReportsRows().length).to.be.equal(0);
        });
    });

    // These tests confirm that the report generator state is the same
    // at the end of the tests as it was at the beginning.
    describe('Confirm that there are no reports or available charts', function () {
        it('No reports listed', function () {
            expect(reportGeneratorPage.getMyReportsRows().length, 'No rows in the list of reports').to.be.equal(0);
        });
        it('No available charts listed', function () {
            expect(reportGeneratorPage.getAvailableCharts().length, 'No charts in the list of available charts').to.be.equal(0);
        });
    });

    loginPage.logout();
});
