import {test, expect} from '@playwright/test';
import {LoginPage} from "../lib/login.page";
import MetricExplorer from '../lib/metricExplorer.page';
//import cheerio from '@cheerio';
import artifacts from "./helpers/artifacts";
var expected = artifacts.getArtifact('metricExplorer');
var XDMOD_REALMS = process.env.XDMOD_REALMS;
import globalConfig from '../playwright.config';
import XDMoD from '../lib/xdmod.page';

test.describe('Metric Explorer', async () => {
    var baselineDate = {
        start: '2016-12-22',
        end: '2017-01-01'
    };
    var container;
    var actions = {
        chart: {
            load: async (chartNumber) => {
                var mychartNumber = chartNumber || 0;
                await test.step('Load Chart', async () => {
                    await me.actionLoadChart(mychartNumber);
                });
            },
            contextMenu: {
                open: async () => {
                    await test.step('Open Chart Context Menu', async () => {
                        //  TODO: Find a better way to open this.  Currently there is a chance
                        //  that the click will open the dataseries menu
                        await page.click('#hc-panelmetric_explorer');
                    });
                },
                addData: async () => {
                    await test.step('Should Display', async () => {
                        await page.click('#hc-panelmetric_explorer');
                        await page.click(me.metricExplorerSelectors.chart.contextMenu.addData);
                        await page.isVisible('#metric-explorer-chartoptions-add-data-menu');
                        await page.click('#logo');
                    });
                },
                addFilter: async () => {
                    await test.step('Should Display', async () => {
                        await page.click('#hc-panelmetric_explorer');
                        await page.click(me.metricExplorerSelectors.chart.contextMenu.addFilter);
                        await page.isVisible('#metric-explorer-chartoptions-add-filter-menu');
                        await page.click('#logo');
                    });
                },
                legend: async () => {
                    await test.step('Click Legend', async () => {
                        await page.click(me.metricExplorerSelectors.chart.contextMenu.legend);
                    });
                },
                setLegendPosition: async (position) => {
                    await test.step('Set Legend ' + position, async () => {
                        await actions.chart.contextMenu.open();
                        await actions.chart.contextMenu.legend();
                        await test.step('Click ' + position, async () => {
                            var posId = me.metricExplorerSelectors.chart.contextMenu.legend + '-' +
                                await position.toLowerCase().replace(/ /g, '-');
                            await page.click(posId);
                            await page.waitForChart();
                        });
                    });
                }
            }
        }
    };
    var chartName = 'ME autotest chart ' + Date.now();
    test('Select Tab', async ({page}) => {
        let baseUrl = globalConfig.use.baseURL;
        const loginPage = new LoginPage(page, baseUrl, page.sso);
        const me = new MetricExplorer(page, baseUrl);
        await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
        await test.step('Selected', async () => {
            await page.click(me.metricExplorerSelectors.tab);
            await page.isVisible(me.metricExplorerSelectors.container);
            await page.isVisible(me.metricExplorerSelectors.catalog.container);
            //$container = cheerio.load(page.content(me.metricExplorerSelectors.container));
            container = page.content(me.metricExplorerSelectors.container);
            await page.click(me.metricExplorerSelectors.catalog.collapseButton);
        });
    });
    // TODO: Add tests for storage and cloud realms
    if (XDMOD_REALMS.includes('jobs')) {
        test('Create and save a chart', async ({page}) => {
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            const me = new MetricExplorer(page, baseUrl);
            const xdmod = new XDMoD(page, baseUrl);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await xdmod.selectTab('metric_explorer');
            await test.step('Add data via metric catalog', async () => {
                await me.createNewChart(chartName, 'Timeseries', 'Line');
                await me.setDateRange('2016-12-30', '2017-01-02');
                await me.addDataViaCatalog('Jobs', 'Node Hours: Total', 'None');
                await me.checkChart(chartName, 'Node Hours: Total', expected.legend);
                await page.screenshot({path:'chart.png'});
                //await page.click(me.toolbar.buttonByName('Save'));
                await me.saveChanges();/*
                await page.screenshot({path:'chart2.png'});
                await page.click('//span[@class="x-menu-item-text" and contains(text(),"Save Changes")]');
                await page.screenshot({path:'chart3.png'});
                await page.screenshot({path:'chart4.png'});
                await me.clear();
                await page.screenshot({path:'chart5.png'});
                await page.screenshot({path:'chart6.png'});
                await me.loadExistingChartByName(chartName);
                await page.screenshot({path:'chart7.png'});*/
            });
        });
        test('Basic Scenarios', async ({page}) => {
            let baseUrl = globalConfig.use.baseURL;
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            const me = new MetricExplorer(page, baseUrl);
            const xdmod = new XDMoD(page, baseUrl);
            await loginPage.login('centerdirector', 'centerdirector', 'Reed Bunting');
            await xdmod.selectTab('metric_explorer');/*
            await test.step('Add Filters in Toolbar', async () => {
                await me.loadExistingChartByName(chartName);
                await page.screenshot({path:'chart2.png'});
                await me.addFiltersFromToolbar('PI');
                await me.cancelFiltersFromToolbar();
            });
            await test.step('Edit Filters in Toolbar', async () => {
                await me.editFiltersFromToolbar('Alpine');
                await page.screenshot({path:'chart3.png'});
                await me.clear();
            });
            await test.step('Add/Edit Filters in Data Series Definition', async () => {
                await me.clickLogoAndWaitForMask();
                await me.loadExistingChartByName(chartName);
                await me.addFiltersFromDataSeriesDefinition('PI', 'Alpine');
                await me.cancelFiltersFromDataSeriesDefinition();
            });
            await test.step('Edit Filters in Data Series Definition', async () => {
                await me.editFiltersFromDataSeriesDefinition('Alpine');
                await me.clear();
            });
            await test.step('Has Instructions', async () => {
                await me.verifyInstructions();
            });
            await test.step('Has three toolbars', async () => {
                const toolbars = await page.locator(container('.x-toolbar'));
                await expect(toolbars.length).toEqual(3);
            });
            await test.step('Has one canned Date Picker', async () => {
                // TODO: Make Datepicker have a unique name
                await expect(container('table[id^=canned_dates]').length).toEqual(1);
            });

            await test.step('Set a known start date', async () => {
                await expect(page.locator(me.metricExplorerSelectors.startDate)).toBeVisible();
                await page.click(me.metricExplorerSelectors.startDate);
                await page.fill(me.metricExplorerSelectors.startDate, baselineDate.start);
            });

            await test.step('Set a known end date', async () => {
                await expect(page.locator(me.metricExplorerSelectors.endDate)).toBeVisible();
                await page.click(me.metricExplorerSelectors.endDate);
                await page.fill(me.metricExplorerSelectors.endDate, baselineDate.end);
            });
            await test.step("'Add Data' via toolbar", async () => {
                // click on CPU Hours: Total
                await me.addDataViaMenu('.ext-el-mask-msg', 'CPU Hours: Total');
                await me.addDataSeriesByDefinition();
            });
            await test.step('Chart contains correct information', async () => {
                await me.checkChart('untitled query 1', 'CPU Hours: Total', expected.legend);
            });

            await test.step("'Add Data' again via toolbar", async () => {
                await me.waitForChartToChange(me.addDataViaToolbar);
            });
            await test.step('Chart contains correct information', async () => {
                await me.checkChart('untitled query 1', 'CPU Hour', [expected.legend + ' [CPU Hours: Total]', expected.legend + ' [CPU Hours: Per Job]']);
            });

            await test.step('Switch to aggregate chart', async () => {
                await me.waitForChartToChange(me.switchToAggregate);
            });
            await test.step('Chart contains correct information', async () => {
                await me.checkChart('untitled query 1', 'CPU Hour', ['CPU Hours: Total', 'CPU Hours: Per Job']);
            });
            await test.step('Undo Scratch Pad switch to aggregate', async () => {
                await me.waitForChartToChange(me.undoAggregateOrTrendLine, $container);
            });
            await test.step('Check first undo works', async () => {
                await me.checkChart('untitled query 1', 'CPU Hour', [expected.legend + ' [CPU Hours: Total]', expected.legend + ' [CPU Hours: Per Job]']);
            });
            await test.step('Undo Scratch Pad second source', async () => {
                await me.waitForChartToChange(me.undoAggregateOrTrendLine, $container);
            });
            await test.step('Check second undo works', async () => {
                await me.checkChart('untitled query 1', 'CPU Hours: Total', expected.legend);
            });
            await test.step('Attempt Delete Scratchpad Chart', async () => {
                await me.attemptDeleteScratchpad();
            });
            await test.step('Chart looks the same as previous run', async () => {
                await me.verifyInstructions();
            });
            await test.step('Open chart from load dialog', async () => {
                await me.loadExistingChartByName(chartName);
            });
            await test.step('Loaded chart looks the same as previous run', async () => {
                await me.checkChart(chartName, 'Node Hours: Total', expected.legend);
            });
            await test.step('Open Chart Options', async () => {
                await page.click(me.metricExplorerSelectors.options.button);
            });
            await test.step('Chart options looks the same as previous run', async () => {
                // TODO: Determine Pass case for this without using screenshot
                // browser.takeScreenshot(moduleName, me.selectors.container, "chart.options")
                // browser.pause(1000);
            });
            await test.step('Show Trend Line via Chart Options', async () => {
                await me.waitForChartToChange(async () => {
                    await page.click(me.metricExplorerSelectors.options.trendLine);
                    await me.clickSelectorAndWaitForMask(me.metricExplorerSelectors.options.button);
                    await expect(page.locator(me.metricExplorerSelectors.options.trendLine)).toBeHidden();
                });
            });
            await test.step('Trend Line looks the same as previous run', async () => {
                await me.checkChart(chartName, 'Node Hours: Total', [expected.legend, 'Trend Line: ' + expected.legend + ' ' + expected.trend_line]);
            });
            await test.step('Undo Trend Line', async () => {
                await me.waitForChartToChange(me.undoAggregateOrTrendLine, $container);
                });
            await test.step('Undo Trend Line looks the same as previous run', async () => {
                await me.checkChart(chartName, 'Node Hours: Total', expected.legend);
                });*/
        });
        /* The following tests are disabled until such a time as they can be changed to work
         * reliably without browser.pause()

        describe('Context Menu', function contextMenu() {
            it('Start with scratchpad', function () {
                browser.refresh();
                browser.pause(10000);
                me.clear();
                browser.pause(2000);
            });

            it('Attempt Delete Scratchpad Chart', function meDeleteScratchPad() {
                me.attemptDeleteScratchpad();
            });

            it('Set a known start date', function meSetStartEnd() {
                browser.waitForVisible('#metric_explorer input[id^=start_field_ext]', 10000);
                browser.setValue('#metric_explorer input[id^=start_field_ext]', baselineDate.start);
            });

            it('Set a known end date', function meSetStartEnd() {
                browser.waitForVisible('#metric_explorer input[id^=end_field_ext]', 10000);
                browser.setValue('#metric_explorer input[id^=end_field_ext]', baselineDate.end);
            });

            it('Wait For Metric Catalog', function () {
                browser.pause(3000);
                browser.waitUntilNotExist('.ext-el-mask-msg');
                browser.waitForVisible(me.selectors.catalog.container, 10000);
            });
            it('Generic Starting Point', function () {
                me.genericStartingPoint();
            });

            describe('Add Data Menu', function () {
                actions.chart.contextMenu.addData();
            });
            describe('Add Filter Menu', function () {
                actions.chart.contextMenu.addFilter();
            });
            describe('Legend Menus', function () {
                actions.chart.contextMenu.setLegendPosition('Top Left');
                actions.chart.contextMenu.setLegendPosition('Top Right');
                browser.pause(5000);
                describe('Verify after load', function () {
                    actions.chart.load(1);
                    actions.chart.contextMenu.open();
                    actions.chart.contextMenu.legend();
                    it('legend Items set Properly', function () {
                        browser.waitForVisible('#metric-explorer-chartoptions-legend-options', 2000);
                        var legendText = browser.getText('#metric-explorer-chartoptions-legend-options .x-menu-item-checked');
                        expect(legendText).to.equal('Bottom Center (Default)', 'Loaded chart has non default legend');
                    });
                });
            });
        });
        describe('Pie Charts', function pieCharts() {
            describe("Can't use timeseries data", function noTimeSeries() {
                it('Start with scratchpad', function () {
                    me.clear();
                });
                it("Add Data via the 'Add Data' Menu", function addData() {
                    // 'Add Data' via the 'Add Data' menu:
                    // 'Add Data' -> Jobs -> CPU Hours: Total -> 'Add'
                    // wait for the global mask to disapear
                    me.addDataViaMenu('.ext-el-mask-msg', '2');
                });
                it("Set 'Group By' to resource", function findGroupBy() {
                    me.setGroupByToResource();
                });
                it("'Add' data", function add() {
                    browser.waitAndClick('#adp_submit_button');
                    browser.waitUntilNotExist('.ext-el-mask');
                });
                it('Verify that the chart has been rendered', function chartRendered() {
                    // Attempt to ascertain if the HighChartPanel ( the panel that contains the chart contents ) is hidden or not.
                    // Pause so we can determine if the chart was actually loaded.
                    browser.waitForVisible('#hc-panelmetric_explorer', 3000);
                    // If the chart was successfully loaded / rendered this should return false.
                    var execReturn = browser.execute('return CCR.xdmod.ui.metricExplorer.chartViewPanel.items.get(0).hidden;').value;
                    expect(execReturn).to.equal(false);
                });
                it('Chart looks the same as previous run', function () {
                    // TODO: Determine Pass case for this without using screenshot
                    // browser.takeScreenshot(moduleName, me.selectors.container, "pie.loaded");
                });
                it("Select the 'Data' toolbar button", function selectData() {
                    browser.click(me.selectors.data.button);
                });
                it('Click the First Row', function jobsRow() {
                    browser.waitForVisible('.x-menu-floating:not(.x-hide-offsets)');
                    browser.execute('return CCR.xdmod.ui.metricExplorer.datasetsGridPanel.getView().getRow(0)').doubleClick();
                });
                it("Set 'Display Type' to 'Pie'", function clickDisplayType() {
                    me.setToPie();
                });
                it('Verify error message', function checkErrorMessage() {
                    me.verifyError();
                });
            });
            describe('Aggregate data', function useAggregateData() {
                it('Start with scratchpad', function freshChart() {
                    me.clear();
                });
            }); // Aggregate data
        }); // Pie Charts
        describe('Chart Interactions', function chartInteractions() {
            it('Should start with a new chart', function newChart() {
                me.clear();
            });
            describe('Should start start with a dataset', function dataset() {
                it("Add Data via the 'Add Data' Menu", function addData() {
                    // 'Add Data' via the 'Add Data' menu:
                    // 'Add Data' -> Jobs -> CPU Hours: Total -> 'Add'
                    // wait for the global mask to disapear
                    me.addDataViaMenu('div.x-panel.x-masked-relative.x-masked', '2');
                });
                it("Set 'Group By' to resource", function findGroupBy() {
                    me.setGroupByToResource();
                });
                it("'Add' data", function add() {
                    browser.waitAndClick('#adp_submit_button');
                    browser.waitUntilNotExist('.ext-el-mask');
                });
                it('Verify that the chart has been rendered', function chartRendered() {
                    // Attempt to ascertain if the HighChartPanel ( the panel that contains the chart contents ) is hidden or not.
                    // Pause so we can determine if the chart was actually loaded.
                    browser.waitForChart();
                    browser.pause(750);
                        // If the chart was successfully loaded / rendered this should return false.
                    var execReturn = browser.execute('return CCR.xdmod.ui.metricExplorer.chartViewPanel.items.get(0).hidden;');
                    expect(execReturn.value).to.equal(false);
                });
                it('Chart looks the same as previous run', function () {
                    // TODO: Determine Pass case for this without using screenshot
                    // browser.takeScreenshot(moduleName, me.selectors.container, "highcharts.loaded")
                });
            }); // Should start with a dataset.

            describe('Chart Titles', function chartTitle() {
                describe('Update the Chart Title with a value that contains html entities', function editChartTitleDialog() {
                    it('Click the Chart Title element', function clickChartTitleElement() {
                        browser.waitAndClick(me.selectors.chart.title);
                        browser.waitForVisible(me.selectors.chart.titleInput, 3000);
                    });

                    it('Update the Chart Title input element', function editChartTitle() {
                        browser.pause(2000);
                        browser.clearElement(me.selectors.chart.titleInput);
                        browser.setValue(me.selectors.chart.titleInput, me.newTitle);
                    });

                    it('Click the Ok element', function clickOkElement() {
                        browser.pause(2000);
                        browser.waitAndClick(me.selectors.chart.titleOkButton);
                    });

                    it('Chart Title element should be updated', function checkChartTitle() {
                        browser.pause(2500);
                        me.verifyHighChartsTitle(me.newTitle);
                    });

                    it('Chart Title in Chart Options should be updated', function verifyChartOptions() {
                        me.chartTitleInOptionsUpdated();
                    });
                });

                describe('Set the title to a value that does not include html entities', function noHtmlEntities() {
                    it('click the Chart Title', function clickChartTitle() {
                        browser.waitForVisible(me.selectors.chart.title, 3000);
                        browser.click(me.selectors.chart.title);
                        browser.waitForVisible(me.selectors.chart.titleInput, 3000);
                    });

                    it('clear the title field', function clearTitleField() {
                        browser.clearElement(me.selectors.chart.titleInput);
                    });

                    it('set the title field to a value that does not contain html entities.', function setWithNoEntities() {
                        browser.pause(500);
                        browser.setValue(me.selectors.chart.titleInput, me.originalTitle);
                        browser.pause(2000);
                    });

                    it("click 'ok' to confirm the change", function confirmTheChange() {
                        browser.click(me.selectors.chart.titleOkButton);
                        browser.waitForVisible(me.selectors.chart.title, 3000);
                    });

                    it('get the new value from the chart title to verify that it has in fact changed', function checkNewValue() {
                        browser.pause(500);
                        var execReturn = browser.execute('return document.querySelector("' + me.selectors.chart.title + '").textContent;');
                        expect(execReturn.value).to.be.a('string');
                        expect(execReturn.value).to.equal(me.originalTitle);
                    });
                });

                describe('Arrow Keys', function () {
                    it('Arrow keys can be used in chart options title', function () {
                        me.arrowKeys();
                    });
                });
                describe('Accept html entity input via the Chart Options -> Chart Title element.', function editChartTitleChartOptions() {
                    it('set the Chart Options -> Chart Title value with html entities', function setTitleWithHtmlEntities() {
                        browser.pause(2000);
                        me.setTitleWithOptionsMenu(me.newTitle);
                        browser.pause(2000);
                    });
                    it('click the Chart Options button again', function clickChartOptions() {
                        browser.waitForChart();
                        browser.waitAndClick(me.selectors.options.button);
                        browser.waitForChart();
                        browser.pause(3000);
                    });
                    it('verify that the HighCharts chart title has the new value', function highChartsTitle() {
                        me.verifyHighChartsTitle(me.newTitle);
                    });
                    it('verify that the Edit Chart Title Modal has the new value', function chartTitleModal() {
                        me.verifyEditChartTitle();
                    });
                });

                describe('Providing an empty title value', function shouldAcceptEmptyTitle() {
                    it('Set the chart title to an empty value', function setTitleEmpty() {
                        me.setChartTitleViaChart('');
                    });

                    it('Get the new chart title and confirm that no svg element was generated', function confirmEmptyChartTitle() {
                        browser.pause(500);
                        var titleChange = browser.execute('return document.querySelector("' + me.selectors.chart.title + '");');
                        // expect(titleChange.state).to.equal('success');
                        expect(titleChange._status).to.equal(0);
                        expect(titleChange.value).to.equal(null);
                    });
                });

                describe('Providing an exceptionally large title should be ok', function shouldNotAcceptLargeTitle() {
                    var largeTitle = me.generateTitle(900);
                    it('Set the chart title to an exceptionally large value (' + largeTitle.length + ')', function setALargeTitle() {
                        me.setTitleWithOptionsMenu(largeTitle);
                        browser.waitAndClick(me.selectors.options.button);
                    });

                    it('Confirm that the chart title has been changed', function confirmTheTitleWasSet() {
                        me.confirmChartTitleChange(largeTitle);
                    });
                });
            });
        });
        */
    }
    //loginPage.logout();
});
