import {test, expect} from '@playwright/test';
import {LoginPage} from "../../lib/login.page";
import Usage from '../../lib/usageTab.page';
import {MyReportsRow, AvailableChart, IncludedChart, ReportGenerator} from '../../lib/reportGenerator.page';
import artifacts from "../helpers/artifacts";
const expected = artifacts.getArtifact('reportGenerator');
let XDMOD_REALMS = process.env.XDMOD_REALMS;
import globalConfig from '../../playwright.config';
import testing from  '../../../ci/testing.json';
let roles = testing.role;

test.describe('Report Generator', async () => {
    // These dates correspond to the dates of the test job data.
    const startDate = '2016-12-20';
    const endDate = '2017-01-01';

    // Current date used in calculations below.
    const currentDate = new Date();

    // Calculate start and end dates of previous month.
    const previousMonth = new Date();
    previousMonth.setDate(1);
    previousMonth.setMonth(previousMonth.getMonth() - 1);
    const previousMonthStartDate = previousMonth.toISOString().substring(0, 10);

    // Setting the date to day "0" of a month will result in the last
    // day of the previous month.
    previousMonth.setMonth(previousMonth.getMonth() + 1);
    previousMonth.setDate(0);
    const previousMonthEndDate = previousMonth.toISOString().substring(0, 10);

    // Calculate start and end dates of previous quarter.
    const previousQuarter = new Date();
    previousQuarter.setDate(1);
    const currentMonth = previousQuarter.getMonth();
    const monthModThree = currentMonth % 3;
    previousQuarter.setMonth(currentMonth - 3 - monthModThree);
    const previousQuarterStartDate = previousQuarter.toISOString().substring(0, 10);
    previousQuarter.setMonth(previousQuarter.getMonth() + 3);
    previousQuarter.setDate(0);
    const previousQuarterEndDate = previousQuarter.toISOString().substring(0, 10);

    // Calculate start and end dates of previous year.
    const previousYearStartDate = (currentDate.getFullYear() - 1) + '-01-01';
    const previousYearEndDate = (currentDate.getFullYear() - 1) + '-12-31';

    // Calculate year-to-date start and end dates.
    const yearToDateStartDate = currentDate.getFullYear() + '-01-01';
    const yearToDateEndDate = currentDate.toISOString().substring(0, 10);

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
            name: expected.centerdirector.report_templates[0].name,
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
    test('Public user default report generator state', async ({page}) => {
        await page.goto('/');
        await page.waitForLoadState();
        const reportGeneratorPage = new ReportGenerator(page);
        await test.step('Report Generator is not enabled', async () => {
            const isPageEnabled = await reportGeneratorPage.isEnabled();
            await expect(isPageEnabled).toBe(false);
        });
    });

    // User
    test('Normal user default report generator state', async ({page}) => {
        //Generate pages
        const reportGeneratorPage = new ReportGenerator(page);
        let baseUrl = globalConfig.use.baseURL;
        const loginPage = new LoginPage(page, baseUrl, page.sso);
        await loginPage.login(roles['usr'].username, roles['usr'].password, (roles['usr'].givenname + " " + roles['usr'].surname));
        await test.step('Report Generator is enabled', async () => {
            await expect(reportGeneratorPage.isEnabled()).toBeTruthy();
        });
        await test.step('Select Report Generator tab', async () => {
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
        });
        await test.step('No reports listed', async () => {
            const first = await reportGeneratorPage.getMyReportsRows();
            await expect(first.length, 'No rows in the list of reports').toEqual(0);
        });
        await test.step('No available charts listed', async () => {
            const first = await reportGeneratorPage.getAvailableCharts();
            await expect(first.length, 'No charts in the list of available charts').toEqual(0);
        });
        await test.step('No report templates available', async () => {
            const isOptionEnabled = await reportGeneratorPage.isNewBasedOnEnabled();
            await expect(isOptionEnabled).toBe(false);
        });
    });

    // There are no tests for storage and cloud realms currently
    if (XDMOD_REALMS.includes('jobs')) {
        // PI
        test('Principal investigator default report generator state', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['pi'].username, roles['pi'].password, (roles['pi'].givenname + " " + roles['pi'].surname));
            await test.step('Report Generator is enabled', async () => {
                await expect(reportGeneratorPage.isEnabled()).toBeTruthy();
            });
            await test.step('Select Report Generator tab', async () => {
                await reportGeneratorPage.selectTab();
                await reportGeneratorPage.waitForMyReportsPanelVisible();
            });
            await test.step('No reports listed', async () => {
                const first = await reportGeneratorPage.getMyReportsRows();
                await expect(first.length, 'No rows in the list of reports').toEqual(0);
            });
            await test.step('No available charts listed', async () => {
                const first = await reportGeneratorPage.getAvailableCharts();
                await expect(first.length, 'No charts in the list of available charts').toEqual(0);
            });
            await test.step('No report templates available', async () => {
                const isOptionEnabled = await reportGeneratorPage.isNewBasedOnEnabled();
                await expect(isOptionEnabled).toBe(false);
            });
        });
    }

    // Center staff
    test('Center staff default report generator state', async ({page}) => {
        //Generate pages
        const reportGeneratorPage = new ReportGenerator(page);
        let baseUrl = globalConfig.use.baseURL;
        const loginPage = new LoginPage(page, baseUrl, page.sso);
        await loginPage.login(roles['cs'].username, roles['cs'].password, (roles['cs'].givenname + " " + roles['cs'].surname));
        await test.step('Report Generator is enabled', async () => {
            await expect(reportGeneratorPage.isEnabled()).toBeTruthy();
        });
        await test.step('Select Report Generator tab', async () => {
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
        });
        await test.step('Reports listed', async () => {
            const first = await reportGeneratorPage.getMyReportsRows();
            await expect(first.length, 'Rows in the list of reports').toEqual(0);
        });
        await test.step('No available charts listed', async () => {
            const first = await reportGeneratorPage.getAvailableCharts();
            await expect(first.length, 'No charts in the list of available charts').toEqual(0);
        });
        await test.step('No report templates available', async () => {
            const isOptionEnabled = await reportGeneratorPage.isNewBasedOnEnabled();
            await expect(isOptionEnabled).toEqual(expected.centerstaff.report_templates_available);
        });
    });

    // There are no tests for storage and cloud realms currently
    if (XDMOD_REALMS.includes('jobs')) {
        // Center director
        test('Center director default report generator state', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['cd'].username, roles['cd'].password, (roles['cd'].givenname + " " + roles['cd'].surname));
            await test.step('Report Generator is enabled', async () => {
                await expect(reportGeneratorPage.isEnabled()).toBeTruthy();
            });
            await test.step('Select Report Generator tab', async () => {
                await reportGeneratorPage.selectTab();
                await reportGeneratorPage.waitForMyReportsPanelVisible();
            });

            await test.step('No reports listed', async () => {
                const first = await reportGeneratorPage.getMyReportsRows();
                await expect(first.length, 'No rows in the list of reports').toEqual(0);
            });
            await test.step('No available charts listed', async () => {
                const first = await reportGeneratorPage.getAvailableCharts();
                await expect(first.length, 'No charts in the list of available charts').toEqual(0);
            });
            await test.step('Buttons are disabled', async () => {
                const edit = await reportGeneratorPage.isEditSelectedReportsEnabled();
                await expect(edit, '"Edit" button is disabled').toBeFalsy();
                const preview = await reportGeneratorPage.isPreviewSelectedReportsEnabled();
                await expect(preview, '"Preview" button is disabled').toBeFalsy();
                const send = await reportGeneratorPage.isSendSelectedReportsEnabled();
                await expect(send, '"Send" button is disabled').toBeFalsy();
                const download = await reportGeneratorPage.isDownloadSelectedReportsEnabled();
                await expect(download, '"Download" button is disabled').toBeFalsy();
                const deleteSelect = await reportGeneratorPage.isDeleteSelectedReportsEnabled();
                await expect(deleteSelect, '"Delete" button is disabled').toBeFalsy();
            });
        });
        test('Make usage tab charts available in the Report Generator', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const usagePage = new Usage(page, baseUrl);
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['cd'].username, roles['cd'].password, (roles['cd'].givenname + " " + roles['cd'].surname));
            let index = 0;
            for (const testChart of usageTabCharts){
                await test.step('Select Usage Tab', async () => {
                    await usagePage.selectTab();
                });
                await test.step(`Select "${testChart.title}" chart`, async () => {
                    const topNodeName = testChart.realm + ' ' + (testChart.drillDetails === '' ? 'Summary' : testChart.drillDetails);
                    await usagePage.selectChildTreeNode(topNodeName, testChart.title);
                });
                await test.step('Set chart timeframe', async () => {
                    await page.click(reportGeneratorPage.selectors.configureTime.frameButton);
                    await page.click(reportGeneratorPage.selectors.configureTime.byTimeFrameName(testChart.timeframeType));
                    await usagePage.setStartDate(testChart.startDate);
                    await usagePage.setEndDate(testChart.endDate);
                    await usagePage.refresh();
                });
                await test.step(`Make "${testChart.title}" chart available in the Report Generator`, async () => {
                    const checkbox = await page.$eval(usagePage.selectors.availableForReportCheckbox, node => node.checked);
                    if (!checkbox){
                        await usagePage.makeCurrentChartAvailableForReport();
                    }
                });
                await test.step('Check available charts', async () => {
                    await reportGeneratorPage.selectTab();
                    await reportGeneratorPage.waitForMyReportsPanelVisible();
                    let charts;
                    for (let i = 0; i < 100; i++) {
                        charts = await reportGeneratorPage.getAvailableCharts();
                        if (charts.length === (index + 1)) {
                            break;
                        }
                    }
                    await expect(charts.length, `${index + 1} chart(s) in the list of available charts`).toEqual(index + 1);
                    for (let i = 0; i <= index; ++i) {
                        const chart:AvailableChart = charts[i];
                        const title = await chart.getTitle();
                        await expect(title, 'Chart title is correct').toEqual(usageTabCharts[i].title);
                        const drillDetails = await chart.getDrillDetails();
                        await expect(drillDetails, 'Drill details are correct').toEqual(usageTabCharts[i].drillDetails);
                        const dateDescription = await chart.getDateDescription();
                        await expect(dateDescription, 'Date description is correct').toEqual(`${usageTabCharts[i].startDate} to ${usageTabCharts[i].endDate}`);
                        const timeframeType = await chart.getTimeframeType();
                        await expect(timeframeType, 'Timeframe type is correct').toEqual(usageTabCharts[i].timeframeType);
                    }
                });
                index+=1;
            }
        });
        test('Create report with default options', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['cd'].username, roles['cd'].password, (roles['cd'].givenname + " " + roles['cd'].surname));
            // Copy default report data for the report being tested.
            const testReport = Object.assign({}, defaultReport);
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            const rows = await reportGeneratorPage.getMyReportsRows();
            if (rows != 0){
                await reportGeneratorPage.selectAllReports();
                await reportGeneratorPage.deleteSelectedReports();
                await reportGeneratorPage.confirmDeleteSelectedReports();
            }
            await test.step('Create a new report', async () => {
                await reportGeneratorPage.createNewReport();
            });
            await test.step('Check default values', async () => {
                const name = await reportGeneratorPage.getReportName();
                await expect(name, 'Default report name').toEqual(defaultReport.name);
                const numChartsPerPage = await reportGeneratorPage.getNumberOfChartsPerPage();
                await expect(numChartsPerPage, 'Default report number of charts per page').toEqual(defaultReport.chartsPerPage);
                const schedule = await reportGeneratorPage.getSchedule();
                await expect(schedule, 'Default report schedule').toEqual(defaultReport.schedule);
                const deliveryForm = await reportGeneratorPage.getDeliveryFormat();
                await expect(deliveryForm, 'Default report delivery format').toEqual(defaultReport.deliveryFormat);
                const first = await reportGeneratorPage.getIncludedCharts();
                await expect(first.length, 'Default report chart count').toEqual(defaultReport.charts.length);
            });
            await test.step('Empty text fields', async () => {
                const title = await reportGeneratorPage.getReportTitle();
                await expect(title, 'Empty report title').toEqual(reportEmptyText.title);
                const headerText = await reportGeneratorPage.getHeaderText();
                await expect(headerText, 'Empty report header').toEqual(reportEmptyText.header);
                const footerText = await reportGeneratorPage.getFooterText();
                await expect(footerText, 'Empty report footer').toEqual(reportEmptyText.footer);
            });
            await test.step('Add chart to report', async () => {
                await reportGeneratorPage.addChartToReport(0);

                // Add chart to test report data to reflect the change made
                // to the report.
                await testReport.charts.push(usageTabCharts[0]);
            });
            await test.step('Save report', async () => {
                await reportGeneratorPage.saveReport();
            });
            await test.step('Return to "My Reports"', async () => {
                await reportGeneratorPage.returnToMyReports();
            });
            await test.step('Check report list', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                await expect(reportRows.length, '1 report in list').toEqual(1);
                const report = reportRows[0];
                const name = await report.getName();
                await expect(name, 'Name is correct').toEqual(testReport.name);
                const derivedFrom = await report.getDerivedFrom();
                await expect(derivedFrom, '"Derived From" is correct').toEqual(testReport.derivedFrom);
                const schedule = await report.getSchedule();
                await expect(schedule, 'Schedule is correct').toEqual(testReport.schedule);
                const deliveryform = await report.getDeliveryFormat();
                await expect(deliveryform, 'Delivery format is correct').toEqual(testReport.deliveryFormat);
                const numOfCharts = await report.getNumberOfCharts();
                await expect(numOfCharts, 'Number of charts of is correct').toEqual(testReport.charts.length);
                const numOfChartsPerPage = await report.getNumberOfChartsPerPage();
                await expect(numOfChartsPerPage, 'Number of charts per page is correct').toEqual(testReport.chartsPerPage);
            });
        });

        test('Create report and change options', async ({page}) => {
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
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['cd'].username, roles['cd'].password, (roles['cd'].givenname + " " + roles['cd'].surname));
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();

            await test.step('Create a new report', async () => {
                await reportGeneratorPage.createNewReport();
            });
            await test.step('Set report name', async () => {
                await reportGeneratorPage.setReportName(testReport.name);
            });
            await test.step('Set report title', async () => {
                await reportGeneratorPage.setReportTitle(testReport.title);
            });
            await test.step('Set header text', async () => {
                await reportGeneratorPage.setHeaderText(testReport.header);
            });
            await test.step('Set footer text', async () => {
                await reportGeneratorPage.setFooterText(testReport.footer);
            });
            await test.step('Set number of charts per page', async () => {
                await reportGeneratorPage.setNumberOfChartsPerPage(testReport.chartsPerPage);
            });
            await test.step('Set schedule', async () => {
                await reportGeneratorPage.setSchedule(testReport.schedule);
            });
            await test.step('Set delivery format', async () => {
                await reportGeneratorPage.setDeliveryFormat(testReport.deliveryFormat);
            });
            await test.step('Add chart to report', async () => {
                await reportGeneratorPage.addChartToReport(0);

                // Add chart to test report data to reflect the change made
                // to the report.
                testReport.charts.push(usageTabCharts[0]);
            });
            await test.step('Edit the timeframe of the chart', async () => {
                const charts = await reportGeneratorPage.getIncludedCharts();
                const chart:IncludedChart = charts[0];
                await chart.editTimeframe();
            });
            await test.step('Select "Specific" for the timeframe type', async () => {
                await reportGeneratorPage.selectSpecificChartTimeframe();
            });
            await test.step('Clear start and end date', async () => {
                await reportGeneratorPage.setSpecificChartTimeframeStartDate('');
                await reportGeneratorPage.setSpecificChartTimeframeEndDate('');
            });
            await test.step('Click the update button, expect error about start date', async () => {
                await reportGeneratorPage.confirmEditTimeframeOfSelectedCharts();
                const msg = await reportGeneratorPage.getEditChartTimeframeErrorMessage();
                await expect(msg, 'Start date error').toEqual('Valid start date required');
                // Give time for msg to go away on its own
                await page.locator('.overlay_message').waitFor({state:'detached'});
            });
            await test.step('Set a start date', async () => {
                await reportGeneratorPage.setSpecificChartTimeframeStartDate(startDate);
            });
            await test.step('Click the update button, expect error about end date', async () => {
                await reportGeneratorPage.confirmEditTimeframeOfSelectedCharts();
                const msg = await reportGeneratorPage.getEditChartTimeframeErrorMessage();
                await expect(msg, 'End date error').toEqual('Valid end date required');
                // Give time for msg to go away on its own
                await page.locator('.overlay_message').waitFor({state:'detached'});
            });
            await test.step('Set an end date', async () => {
                await reportGeneratorPage.setSpecificChartTimeframeEndDate(endDate);
            });
            await test.step('Click the update button', async () => {
                await reportGeneratorPage.confirmEditTimeframeOfSelectedCharts();
            });
            await test.step('Verify that the date has been changed', async () => {
                const charts = await reportGeneratorPage.getIncludedCharts();
                const chart:IncludedChart = charts[0];
                const timeframetype = await chart.getTimeframeType();
                await expect(timeframetype, 'Timeframe type').toEqual('User Defined');
                const date = await chart.getDateDescription();
                await expect(date, 'Date description').toEqual(startDate + ' to ' + endDate);
            });
            await test.step('Save report', async () => {
                await reportGeneratorPage.saveReport();
            });
            await test.step('Return to "My Reports"', async () => {
                await reportGeneratorPage.returnToMyReports();
                //Give time for msg to go away
                await page.locator(reportGeneratorPage.selectors.message.titleElement()).waitFor({state:'detached'});
            });
            await test.step('Check report list', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                await expect(reportRows.length, '2 reports in list').toEqual(2);
                const report = reportRows[1];
                const name = await report.getName();
                await expect(name, 'Name is correct').toEqual(testReport.name);
                const derivedfrom = await report.getDerivedFrom();
                await expect(derivedfrom, '"Derived From" is correct').toEqual(testReport.derivedFrom);
                const schedule = await report.getSchedule();
                await expect(schedule, 'Schedule is correct').toEqual(testReport.schedule);
                const deliveryform = await report.getDeliveryFormat();
                await expect(deliveryform, 'Delivery format is correct').toEqual(testReport.deliveryFormat);
                const numOfCharts = await report.getNumberOfCharts();
                await expect(numOfCharts, 'Number of charts of is correct').toEqual(testReport.charts.length);
                const numOfChartsPerPage = await report.getNumberOfChartsPerPage();
                await expect(numOfChartsPerPage, 'Number of charts per page is correct').toEqual(testReport.chartsPerPage);
            });
            await test.step('Edit report and compare values', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                const row = reportRows[1];
                await row.doubleClick();
                await page.isVisible(reportGeneratorPage.selectors.reportDisplay);
                const name = await reportGeneratorPage.getReportName();
                await expect(name, 'Report name is correct').toEqual(testReport.name);
                const title = await reportGeneratorPage.getReportTitle();
                await expect(title, 'Report title is correct').toEqual(testReport.title);
                const header = await reportGeneratorPage.getHeaderText();
                await expect(header, 'Header text is correct').toEqual(testReport.header);
                const footer = await reportGeneratorPage.getFooterText();
                await expect(footer, 'Footer text is correct').toEqual(testReport.footer);
                //reportGeneratorPage.getNumberOfChartsPerPage() method doesn't return proper value
                //sometimes despite set correctly and in image, so alternatively a check is done here
                if (testReport.chartsPerPage === 2){
                    await page.isChecked(reportGeneratorPage.selectors.reportEditor.chartLayout.twoChartsPerPageRadioButton());
                } else if (testReport.chartsPerPage === 1) {
                    await page.isChecked(reportGeneratorPage.selectors.reportEditor.chartLayout.oneChartPerPageRadioButton());
                } else {
                    throw new Error('No charts per page option selected');
                }
                const schedule = await reportGeneratorPage.getSchedule();
                await expect(schedule, 'Schedule is correct').toEqual(testReport.schedule);
                const deliveryform = await reportGeneratorPage.getDeliveryFormat();
                await expect(deliveryform, 'Delivery format is correct').toEqual(testReport.deliveryFormat);
                await reportGeneratorPage.returnToMyReports();
            });
        });

        test('Edit report and "Save As"', async ({page}) => {
            const testReport = {
                name: 'Copied Report',
                derivedFrom: '',
                schedule: '',
                deliveryFormat: '',
                numberOfCharts: 0,
                chartsPerPage: 0,
                header: '',
            };
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['cd'].username, roles['cd'].password, (roles['cd'].givenname + " " + roles['cd'].surname));
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Store data for report that will be copied', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                const selector = await reportGeneratorPage.getMyReportsRows();
                const reportRow:MyReportsRow = selector[1];
                testReport.derivedFrom = await reportRow.getDerivedFrom();
                testReport.schedule = await reportRow.getSchedule();
                testReport.deliveryFormat = await reportRow.getDeliveryFormat();
                testReport.numberOfCharts = await reportRow.getNumberOfCharts();
                testReport.chartsPerPage = await reportRow.getNumberOfChartsPerPage();
            });
            await test.step('Edit report', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                await reportRows[1].doubleClick();
            });
            await test.step('Click "Save As" and set report name', async () => {
                await reportGeneratorPage.saveReportAs(testReport.name);
            });
            await test.step('Click "Save" in "Save As" window', async () => {
                await reportGeneratorPage.confirmSaveReportAs();
            });
            await test.step('Check copied report name', async () => {
                const name = await reportGeneratorPage.getReportName();
                await expect(name, 'Report name is correct').toEqual(testReport.name);
                //Give time for msg window to go away
                await page.locator(reportGeneratorPage.selectors.message.titleElement()).waitFor({state:'detached'});
            });
            await test.step('Edit copied report', async () => {
                testReport.header = 'Header for copied report';
                await reportGeneratorPage.setHeaderText(testReport.header);
                testReport.chartsPerPage = testReport.chartsPerPage === 1 ? 2 : 1;
                await reportGeneratorPage.setNumberOfChartsPerPage(testReport.chartsPerPage);
                //Give time for msg window to go away
                await page.locator(reportGeneratorPage.selectors.message.titleElement()).waitFor({state:'detached'});
            });
            await test.step('Save report', async () => {
                await reportGeneratorPage.saveReport();
                //Give time for msg window to go away
                await page.locator(reportGeneratorPage.selectors.message.titleElement()).waitFor({state:'detached'});
            });
            await test.step('Return to "My Reports"', async () => {
                await reportGeneratorPage.returnToMyReports();
            });
            await test.step('Check report list', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                await expect(reportRows.length, '3 reports in list').toEqual(3);
                const report:MyReportsRow = reportRows[2];
                const name = await report.getName();
                await expect(name, 'Name is correct').toEqual(testReport.name);
                const derivedfrom = await report.getDerivedFrom();
                await expect(derivedfrom, '"Derived From" is correct').toEqual(testReport.derivedFrom);
                const schedule = await report.getSchedule();
                await expect(schedule, 'Schedule is correct').toEqual(testReport.schedule);
                const deliveryform = await report.getDeliveryFormat();
                await expect(deliveryform, 'Delivery format is correct').toEqual(testReport.deliveryFormat);
                const numOfCharts = await report.getNumberOfCharts();
                await expect(numOfCharts, 'Number of charts of is correct').toEqual(testReport.numberOfCharts);
                const numOfChartsPerPage = await report.getNumberOfChartsPerPage();
                await expect(numOfChartsPerPage, 'Number of charts per page is correct').toEqual(testReport.chartsPerPage);
            });
            await test.step('Edit copied report and compare values', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                await reportRows[2].doubleClick();
                const header = await reportGeneratorPage.getHeaderText();
                await expect(header, 'Header text is correct').toEqual(testReport.header);
                const numOfChartsPerPage = await reportGeneratorPage.getNumberOfChartsPerPage();
                await expect(numOfChartsPerPage, 'Number of charts per page is correct').toEqual(testReport.chartsPerPage);
            });
            await test.step('Return to "My Reports"', async () => {
                await reportGeneratorPage.returnToMyReports();
            });
        });

        test('Edit report and make changes', async ({page}) => {
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

            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['cd'].username, roles['cd'].password, (roles['cd'].givenname + " " + roles['cd'].surname));
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();

            await test.step('Open the report', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                const rows = await reportGeneratorPage.getMyReportsRows();
                const report:MyReportsRow = rows[reportIndex];
                await report.doubleClick();
            });
            await test.step('Set report name', async () => {
                await reportGeneratorPage.setReportName(testReport.name);
            });
            await test.step('Set report title', async () => {
                await reportGeneratorPage.setReportTitle(testReport.title);
            });
            await test.step('Set header text', async () => {
                await reportGeneratorPage.setHeaderText(testReport.header);
            });
            await test.step('Set footer text', async () => {
                await reportGeneratorPage.setFooterText(testReport.footer);
            });
            await test.step('Set number of charts per page', async () => {
                await reportGeneratorPage.setNumberOfChartsPerPage(testReport.chartsPerPage);
            });
            await test.step('Set schedule', async () => {
                await reportGeneratorPage.setSchedule(testReport.schedule);
            });
            await test.step('Set delivery format', async () => {
                await reportGeneratorPage.setDeliveryFormat(testReport.deliveryFormat);
            });
            await test.step('Add chart to report', async () => {
                await reportGeneratorPage.addChartToReport(1);

                // Add chart to test report data to reflect the change made
                // to the report.
                testReport.charts.push(usageTabCharts[1]);
            });
            await test.step('Save report', async () => {
                await reportGeneratorPage.saveReport();
            });
            await test.step('Return to "My Reports"', async () => {
                await reportGeneratorPage.returnToMyReports();
            });
            await test.step('Check report list', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                await expect(reportRows.length, '3 reports in list').toEqual(3);
                const reportRow:MyReportsRow = reportRows[reportIndex];
                const name = await reportRow.getName();
                await expect(name, 'Name is correct').toEqual(testReport.name);
                const derivedFrom = await reportRow.getDerivedFrom();
                await expect(derivedFrom, '"Derived From" is correct').toEqual(testReport.derivedFrom);
                const schedule = await reportRow.getSchedule();
                await expect(schedule, 'Schedule is correct').toEqual(testReport.schedule);
                const deliveryForm = await reportRow.getDeliveryFormat();
                await expect(deliveryForm, 'Delivery format is correct').toEqual(testReport.deliveryFormat);
                const numOfCharts = await reportRow.getNumberOfCharts();
                await expect(numOfCharts, 'Number of charts of is correct').toEqual(testReport.charts.length);
                const numOfChartsPerPage = await reportRow.getNumberOfChartsPerPage();
                await expect(numOfChartsPerPage, 'Number of charts per page is correct').toEqual(testReport.chartsPerPage);
            });
            await test.step('Edit report and compare values', async () => {
                const rows = await reportGeneratorPage.getMyReportsRows();
                await rows[reportIndex].doubleClick();
                const name = await reportGeneratorPage.getReportName();
                await expect(name, 'Report name is correct').toEqual(testReport.name);
                const title = await reportGeneratorPage.getReportTitle();
                await expect(title, 'Report title is correct').toEqual(testReport.title);
                const header = await reportGeneratorPage.getHeaderText();
                await expect(header, 'Header text is correct').toEqual(testReport.header);
                const footer = await reportGeneratorPage.getFooterText();
                await expect(footer, 'Footer text is correct').toEqual(testReport.footer);
                const numOfChartsPerPage = await reportGeneratorPage.getNumberOfChartsPerPage();
                await expect(numOfChartsPerPage, 'Number of charts per page is correct').toEqual(testReport.chartsPerPage);
                const schedule = await reportGeneratorPage.getSchedule();
                await expect(schedule, 'Schedule is correct').toEqual(testReport.schedule);
                const deliveryform = await reportGeneratorPage.getDeliveryFormat();
                await expect(deliveryform, 'Delivery format is correct').toEqual(testReport.deliveryFormat);
                await reportGeneratorPage.returnToMyReports();
            });
        });

        test('Create report from template', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();

            await test.step('Click "New Based On"', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.clickNewBasedOn();
                await page.locator(reportGeneratorPage.selectors.myReports.toolbar.newBasedOnMenu()).waitFor({state:'visible'});
            });
            await test.step('Check list of report templates', async () => {
                const reportTemplateNames = await reportGeneratorPage.getReportTemplateNames();
                let i = 0;
                for (const reportTemplateName of reportTemplateNames){
                    await expect(reportTemplateName, 'Report template ' + i).toEqual(centerDirectorReportTemplates[i].name);
                }
            });
            await test.step('Click "New Based On" to close menu', async () => {
                // Close the menu so that it can be re-opened below.
                await reportGeneratorPage.clickNewBasedOn();
                if (page.locator(reportGeneratorPage.selectors.myReports.toolbar.newBasedOnMenu()).isVisible()){
                    await reportGeneratorPage.clickNewBasedOn();
                }
                await expect(page.locator(reportGeneratorPage.selectors.myReports.toolbar.newBasedOnMenu())).toBeHidden();
                // mouse is stuck hovering over the "New Based On" button,
                // so another area on the page, or the "Report Generator"
                // tab is clicked and the delay is to give the page time
                await page.click(reportGeneratorPage.selectors.tab(), {delay:250});
            });
            let report_template_index = 0;
            let reportIndex = 3;
            const template = centerDirectorReportTemplates[0];
            await test.step('Click "New Based On"', async () => {
                await reportGeneratorPage.clickNewBasedOn();
                await page.locator(reportGeneratorPage.selectors.myReports.toolbar.newBasedOnMenu()).waitFor({state:'visible'});
            });
            await test.step(`Select "${template.name}"`, async () => {
                await reportGeneratorPage.selectNewBasedOnTemplate(template.name, expected.centerdirector.center);
            });
            await test.step('Check list of reports', async () => {
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                await expect(reportRows.length, 'New report added').toEqual(reportIndex + expected.centerdirector.report_templates[report_template_index].reports_created);
                const report:MyReportsRow = reportRows[reportIndex];
                const name = await report.getName();
                await expect(name, 'Name is correct').toEqual(expected.centerdirector.report_templates[report_template_index].created_name + ' 1');
                const derivedFrom = await report.getDerivedFrom();
                await expect(derivedFrom, '"Derived From" is correct').toEqual(template.name);
                const schedule = await report.getSchedule();
                await expect(schedule, 'Schedule is correct').toEqual(template.schedule);
                const deliveryform = await report.getDeliveryFormat();
                await expect(deliveryform, 'Delivery format is correct').toEqual(expected.centerdirector.report_templates[report_template_index].delivery_format);
                const numOfCharts = await report.getNumberOfCharts();
                await expect(numOfCharts, 'Number of charts of is correct').toEqual(expected.centerdirector.report_templates[report_template_index].created_reports_count);
                const numOfChartsPerPage = await report.getNumberOfChartsPerPage();
                await expect(numOfChartsPerPage, 'Number of charts per page is correct').toEqual(template.chartsPerPage);
            });
            await test.step('Edit report based on template', async () => {
                const rows = await reportGeneratorPage.getMyReportsRows();
                const row:MyReportsRow = rows[reportIndex];
                await row.doubleClick();
            });
            await test.step('Check charts', async () => {
                const templateCharts = await reportGeneratorPage.getCharts(
                    'centerdirector',
                    report_template_index,
                    {
                        startDate: startDate,
                        endDate: endDate,
                        previousMonthStartDate: previousMonthStartDate,
                        previousMonthEndDate: previousMonthEndDate,
                        previousQuarterStartDate: previousQuarterStartDate,
                        previousQuarterEndDate: previousQuarterEndDate,
                        previousYearStartDate: previousYearStartDate,
                        previousYearEndDate: previousYearEndDate,
                        yearToDateStartDate: yearToDateStartDate,
                        yearToDateEndDate: yearToDateEndDate
                    }
                );
                const reportCharts = await reportGeneratorPage.getIncludedCharts();
                let i = 0;
                for (const charts of reportCharts){
                    const chart:AvailableChart = charts[i];
                    const templateChart = templateCharts[i];
                    const title = await chart.getTitle();
                    await expect(title, 'Chart title').toEqual(templateChart.title);
                    const drillDetails = await chart.getDrillDetails();
                    await expect(drillDetails, 'Drill details').toEqual(templateChart.drillDetails);
                    const timeframetype = await chart.getTimeframeType();
                    await expect(timeframetype, 'Timeframe type').toEqual(templateChart.timeframeType);
                    const dateDescription = await chart.getDateDescription();
                    await expect(dateDescription, 'Date description').toEqual(templateChart.startDate + ' to ' + templateChart.endDate);
                    i +=1;
                }
            });
            await test.step('Return to "My Reports"', async () => {
                await reportGeneratorPage.returnToMyReports();
            });
            report_template_index += 1;
        });

        test('Preview report', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select a report', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                const rows = await reportGeneratorPage.getMyReportsRows();
                const report:MyReportsRow = rows[0];
                await report.click();
            });
            await test.step('Preview selected report', async () => {
                await reportGeneratorPage.previewSelectedReports();
            });
            await test.step('Return to reports overview', async () => {
                await reportGeneratorPage.returnToReportsOverview();
            });
            await test.step('Deselect reports', async () => {
                await reportGeneratorPage.deselectAllReports();
            });
        });

        test('Download report', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select a report', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                const rows = await reportGeneratorPage.getMyReportsRows();
                const report:MyReportsRow = rows[0];
                await report.click();
            });
            await test.step('Click "Download" button', async () => {
                await reportGeneratorPage.downloadSelectedReports();
            });
            await test.step('Click "As PDF"', async () => {
                await reportGeneratorPage.downloadSelectedReportsAsPdf();
            });
            await test.step('Close "Report Built" window', async () => {
                await reportGeneratorPage.closeReportBuiltWindow();
            });
            await test.step('Click "Download" button', async () => {
                await reportGeneratorPage.downloadSelectedReports();
            });
            await test.step('Click "As Word Document"', async () => {
                await reportGeneratorPage.downloadSelectedReportsAsWordDocument();
            });
            await test.step('Close "Report Built" window', async () => {
                await reportGeneratorPage.closeReportBuiltWindow();
            });
            await test.step('Deselect reports', async () => {
                await reportGeneratorPage.deselectAllReports();
            });
        });

        test('Select reports', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select all', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.selectAllReports();
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                let i = 0;
                for (const row:MyReportsRow of reportRows){
                    await expect(row.isSelected(), `Row ${i} is selected`).toBeTruthy();
                    i+=1;
                }
            });
            await test.step('Select none', async () => {
                await reportGeneratorPage.deselectAllReports();
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                let i = 0;
                for (const row:MyReportsRow of reportRows){
                    const isRowSelected = await row.isSelected();
                    await expect(isRowSelected, `Row ${i} is not selected`).toBeFalsy();
                    i+=1;
                }
            });
            await test.step('Invert selection', async () => {
                // Select one row then invert selection.
                const row:MyReportsRow = (await reportGeneratorPage.getMyReportsRows())[1];
                await row.toggleSelection();
                const first = await reportGeneratorPage.getMyReportsRows();
                const selectedStatus = await Promise.all(first.map(row => row.isSelected()));
                await reportGeneratorPage.invertReportSelection();
                const reportRows = await reportGeneratorPage.getMyReportsRows();
                let i = 0;
                for (const row:MyReportsRow of reportRows){
                    const isRowSelected = await row.isSelected();
                    await expect(isRowSelected, `Row ${i} has been inverted`).toEqual(!selectedStatus[i]);
                    i +=1;
                }
            });
        });

        test('Attempt to edit multiple reports from "My Reports"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select reports', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.selectAllReports();
            });
            await test.step('Edit reports (should not be possible)', async () => {
                const isReportEnabled = await reportGeneratorPage.isEditSelectedReportsEnabled();
                await expect(isReportEnabled, '"Edit" button is disabled').toBeFalsy();
            });
            await test.step('Deselect reports', async () => {
                await reportGeneratorPage.deselectAllReports();
            });
        });

        test('Attempt to preview multiple reports from "My Reports"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select reports', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.selectAllReports();
            });
            await test.step('Preview reports (should not be possible)', async () => {
                const isReportEnabled = await reportGeneratorPage.isPreviewSelectedReportsEnabled();
                await expect(isReportEnabled, '"Preview" button is disabled').toBeFalsy();
            });
            await test.step('Deselect reports', async () => {
                await reportGeneratorPage.deselectAllReports();
            });
        });

        test('Attempt to send multiple reports from "My Reports"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select reports', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.selectAllReports();
            });
            await test.step('Send reports (should not be possible)', async () => {
                const isReportEnabled = await reportGeneratorPage.isSendSelectedReportsEnabled();
                await expect(isReportEnabled, '"Send" button is disabled').toBeFalsy();
            });
            await test.step('Deselect reports', async () => {
                await reportGeneratorPage.deselectAllReports();
            });
        });

        test('Attempt to download multiple reports from "My Reports"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select reports', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.selectAllReports();
            });
            await test.step('Download reports (should not be possible)', async () => {
                const isReportEnabled = await reportGeneratorPage.isDownloadSelectedReportsEnabled();
                await expect(isReportEnabled, '"Download" button is disabled').toBeFalsy();
            });
            await test.step('Deselect reports', async () => {
                await reportGeneratorPage.deselectAllReports();
            });
        });

        test('Delete report from "My Reports"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            let reportCount;

            await test.step('Select report', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                const reports = await reportGeneratorPage.getMyReportsRows();
                reportCount = reports.length;
                await reports[0].click();
                await expect(reports[0].isSelected(), 'Report is selected').toBeTruthy();
            });
            await test.step('Click delete button', async () => {
                await reportGeneratorPage.deleteSelectedReports();
            });
            await test.step('Cancel deletion', async () => {
                await reportGeneratorPage.cancelDeleteSelectedReports();
                const reports = await reportGeneratorPage.getMyReportsRows();
                await expect(reports.length, 'Report count has not changed').toEqual(reportCount);
                await expect(reports[0].isSelected(), 'Report is still selected').toBeTruthy();
            });
            await test.step('Click delete button', async () => {
                await reportGeneratorPage.deleteSelectedReports();
            });
            await test.step('Confirm deletion', async () => {
                await reportGeneratorPage.confirmDeleteSelectedReports();
                reportCount-=1;
            });
            await test.step('Check list of reports', async () => {
                const first = await reportGeneratorPage.getMyReportsRows();
                await expect(first.length).toEqual(reportCount);
            });
        });

        test('Select charts listed in "Available Charts"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select all', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.selectAllAvailableCharts();
                const reportCharts = await reportGeneratorPage.getAvailableCharts();
                let i = 0;
                for (const chart:AvailableChart of reportCharts){
                    await expect(chart.isSelected(), `Chart ${i} is selected`).toBeTruthy();
                    i+=1;
                }
            });
            await test.step('Select none', async () => {
                await reportGeneratorPage.deselectAllAvailableCharts();
                const reportCharts = await reportGeneratorPage.getAvailableCharts();
                let i = 0;
                for (const chart:AvailableChart of reportCharts){
                    const isChartSelected = await chart.isSelected();
                    await expect(isChartSelected, `Chart ${i} is not selected`).toBeFalsy();
                    i+=1;
                }
            });
            await test.step('Invert selection', async () => {
                // Select one chart then invert selection.
                const charts = await reportGeneratorPage.getAvailableCharts();
                const chart:AvailableChart = charts[1];
                await chart.toggleSelection();
                const first = await reportGeneratorPage.getAvailableCharts();
                const selectedStatus = await Promise.all(first.map(chart => chart.isSelected()));
                await reportGeneratorPage.invertAvailableChartsSelection();
                const reportCharts = await reportGeneratorPage.getAvailableCharts();
                let i = 0;
                for (const chart:AvailableChart of reportCharts){
                    const isChartSelected = await chart.isSelected();
                    await expect(isChartSelected, `Chart ${i} selection has been inverted`).toEqual(!selectedStatus[i]);
                    i+=1;
                }
            });
        });

        // Removes all but the first chart.
        test('Remove charts from "Available Charts"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            let chartCount;
            await test.step('Select all charts', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                const first = await reportGeneratorPage.getAvailableCharts();
                chartCount = first.length;
                await reportGeneratorPage.selectAllAvailableCharts();
            });
            await test.step('Deselect first chart', async () => {
                const charts = await reportGeneratorPage.getAvailableCharts();
                const chart:AvailableChart = charts[0];
                await chart.toggleSelection();
            });
            await test.step('Click delete button', async () => {
                await reportGeneratorPage.deleteSelectedAvailableCharts();
            });
            await test.step('Cancel deletion', async () => {
                await reportGeneratorPage.cancelDeleteSelectedAvailableCharts();
            });
            await test.step('Confirm that no charts were removed', async () => {
                const first = await reportGeneratorPage.getAvailableCharts();
                await expect(first.length, 'Chart count not changed').toEqual(chartCount);
            });
            await test.step('Click delete button again', async () => {
                await reportGeneratorPage.deleteSelectedAvailableCharts();
            });
            await test.step('Confirm deletion', async () => {
                await reportGeneratorPage.confirmDeleteSelectedAvailableCharts();
            });
            await test.step('Confirm that charts were removed', async () => {
                const first = await reportGeneratorPage.getAvailableCharts();
                await expect(first.length, 'All but one chart removed').toEqual(1);
            });
        });

        // Removes the first chart.
        test('Remove chart from Report Generator from the Usage tab', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            const usagePage = new Usage(page, page.baseUrl);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await test.step('Click on the "Usage" tab', async () => {
                await usagePage.selectTab();
            });
            await test.step('Set date range', async () => {
                await page.click(reportGeneratorPage.selectors.configureTime.frameButton);
                await page.click(reportGeneratorPage.selectors.configureTime.byTimeFrameName("User Defined"));
                await usagePage.setStartDate(usageTabCharts[0].startDate);
                await usagePage.setEndDate(usageTabCharts[0].endDate);
                await usagePage.refresh();
            });
            await test.step('Make chart unavailable', async () => {
                await usagePage.makeCurrentChartUnavailableForReport();
            });
            await test.step('Select Report Generator tab', async () => {
                await reportGeneratorPage.selectTab();
                await reportGeneratorPage.waitForMyReportsPanelVisible();
            });
            await test.step('No available charts listed', async () => {
                const first = await reportGeneratorPage.getAvailableCharts();
                await expect(first.length, 'No charts in the list of available charts').toEqual(0);
            });
        });

        test('Delete multiple reports from "My Reports"', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('Select reports', async () => {
                //to ensure reportGeneratorPage fully loaded
                await reportGeneratorPage.fullyLoaded();
                //continue test
                await reportGeneratorPage.selectAllReports();
            });
            await test.step('Delete reports', async () => {
                await reportGeneratorPage.deleteSelectedReports();
                await reportGeneratorPage.confirmDeleteSelectedReports();
                const first = await reportGeneratorPage.getMyReportsRows();
                await expect(first.length).toEqual(0);
            });
        });

        // These tests confirm that the report generator state is the same
        // at the end of the tests as it was at the beginning.
        test('Confirm that there are no reports or available charts', async ({page}) => {
            //Generate pages
            const reportGeneratorPage = new ReportGenerator(page);
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await reportGeneratorPage.selectTab();
            await reportGeneratorPage.waitForMyReportsPanelVisible();
            await test.step('No reports listed', async () => {
                const first = await reportGeneratorPage.getMyReportsRows();
                await expect(first.length, 'No rows in the list of reports').toEqual(0);
            });
            await test.step('No available charts listed', async () => {
                const first = await reportGeneratorPage.getAvailableCharts();
                await expect(first.length, 'No charts in the list of available charts').toEqual(0);
            });
        });
    }
});
