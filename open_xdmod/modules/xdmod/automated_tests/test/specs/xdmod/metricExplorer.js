var expected = global.testHelpers.artifacts.getArtifact('metricExplorer');
var logIn = require('./loginPage.page.js');
var me = require('./metricExplorer.page.js');
var reportGen = require('./reportGenerator.page');

var cheerio = require('cheerio');
describe('Metric Explorer', function metricExplorer() {
    var baselineDate = {
        start: '2016-12-22',
        end: '2017-01-01'
    };
    var $container;
    var actions = {
        chart: {
            load: function (chartNumber) {
                var mychartNumber = chartNumber || 0;
                it('Load Chart', function () {
                    me.actionLoadChart(mychartNumber);
                });
            },
            contextMenu: {
                open: function chartContextMenuOpen() {
                    it('Open Chart Context Menu', function () {
                        //  TODO: Find a better way to open this.  Currently there is a chance
                        //  that the click will open the dataseries menu
                        browser.waitAndClick('#hc-panelmetric_explorer', 10000);
                    });
                },
                addData: function () {
                    it('Should Display', function () {
                        browser.waitAndClick('#hc-panelmetric_explorer', 10000);
                        browser.waitAndClick(me.selectors.chart.contextMenu.addData, 10000);
                        browser.waitForVisible('#metric-explorer-chartoptions-add-data-menu', 10000);
                        browser.click('#logo');
                    });
                },
                addFilter: function () {
                    it('Should Display', function () {
                        browser.waitAndClick('#hc-panelmetric_explorer', 10000);
                        browser.waitAndClick(me.selectors.chart.contextMenu.addFilter, 10000);
                        browser.waitForVisible('#metric-explorer-chartoptions-add-filter-menu', 10000);
                        browser.click('#logo');
                    });
                },
                legend: function () {
                    it('Click Legend', function selectLegend() {
                        browser.waitAndClick(me.selectors.chart.contextMenu.legend, 10000);
                    });
                },
                setLegendPosition: function chartContextMenuLegendPosition(position) {
                    describe('Set Legend ' + position, function setLegend() {
                        actions.chart.contextMenu.open();
                        actions.chart.contextMenu.legend();
                        it('Click ' + position, function selectLegendPosition() {
                            var posId = me.selectors.chart.contextMenu.legend + '-' +
                                    position.toLowerCase().replace(/ /g, '-');
                            browser.waitAndClick(posId);
                            browser.waitForChart();
                        });
                    });
                }
            }
        }
    };
    var chartName = 'ME autotest chart ' + Date.now();
    logIn.login('centerdirector');
    describe('Select Tab', function xdmod() {
        it('Selected', function meSelect() {
            browser.waitForLoadedThenClick(me.selectors.tab);
            browser.waitForVisible(me.selectors.container, 3000);
            browser.waitForVisible(me.selectors.catalog.container, 10000);
            $container = cheerio.load(browser.getHTML(me.selectors.container));
            browser.waitUntilAnimEnd(me.selectors.catalog.collapseButton);
        });
    });
    describe('Create and save a chart', function () {
        it('Add data via metric catalog', function () {
            me.createNewChart(chartName, 'Timeseries', 'Line');
            me.setDateRange('2016-12-30', '2017-01-02');
            me.addDataViaCatalog('Jobs', 'Node Hours: Total', 'None');
            me.checkChart(chartName, 'Node Hours: Total', expected.legend);
            me.saveChanges();
            me.clear();
        });
    });
    describe('Basic Scenarios', function basicScenarios() {
        it('Add Filters in Toolbar', function () {
            me.loadExistingChartByName(chartName);
            me.addFiltersFromToolbar('PI');
            me.cancelFiltersFromToolbar();
        });
        it('Edit Filters in Toolbar', function () {
            me.editFiltersFromToolbar('Alpine');
            me.clear();
        });
        it('Add/Edit Filters in Data Series Definition', function () {
            me.clickLogoAndWaitForMask();
            me.loadExistingChartByName(chartName);
            me.addFiltersFromDataSeriesDefinition('PI', 'Alpine');
            me.cancelFiltersFromDataSeriesDefinition();
        });
        it('Edit Filters in Data Series Definition', function () {
            me.editFiltersFromDataSeriesDefinition('Alpine');
            me.clear();
        });
        it('Has Instructions', function meConfirmInstructions() {
            me.verifyInstructions();
        });
        it('Has three toolbars', function meConfirmToolbars() {
            expect($container('.x-toolbar').length).to.equal(3);
        });
        it('Has one canned Date Picker', function meConfirmDatePicker() {
            // TODO: Make Datepicker have a unique name
            expect($container('table[id^=canned_dates]').length).to.equal(1);
        });

        it('Set a known start date', function meSetStartEnd() {
            browser.waitForVisible(me.selectors.startDate, 10000);
            browser.click(me.selectors.startDate);
            browser.setValue(me.selectors.startDate, baselineDate.start);
        });

        it('Set a known end date', function meSetStartEnd() {
            browser.waitForVisible(me.selectors.endDate, 10000);
            browser.click(me.selectors.endDate);
            browser.setValue(me.selectors.endDate, baselineDate.end);
        });
        it("'Add Data' via toolbar", function meAddData1() {
            // click on CPU Hours: Total
            me.addDataViaMenu('.ext-el-mask-msg', 'CPU Hours: Total');
            me.addDataSeriesByDefinition();
        });
        it('Chart contains correct information', function () {
            me.checkChart('untitled query 1', 'CPU Hours: Total', expected.legend);
        });

        it("'Add Data' again via toolbar", function meAddData2() {
            me.waitForChartToChange(me.addDataViaToolbar);
        });
        it('Chart contains correct information', function () {
            me.checkChart('untitled query 1', 'CPU Hour', [expected.legend + ' [CPU Hours: Total]', expected.legend + ' [CPU Hours: Per Job]']);
        });

        it('Switch to aggregate chart (expect incompatible metric error)', function () {
            me.waitForChartToChange(me.switchToAggregate);
        });
        it('Check that error message is displayed', function () {
            me.checkChart('An error occurred while loading the chart.', null, null, false);
        });
        it('Undo Scratch Pad switch to aggregate', function () {
            me.waitForChartToChange(me.undoAggregateOrTrendLine, $container);
        });
        it('Check first undo works', function () {
            me.checkChart('untitled query 1', 'CPU Hour', [expected.legend + ' [CPU Hours: Total]', expected.legend + ' [CPU Hours: Per Job]']);
        });
        it('Undo Scratch Pad second source', function meUndoScratchPad() {
            me.waitForChartToChange(me.undoAggregateOrTrendLine, $container);
        });
        it('Check second undo works', function () {
            me.checkChart('untitled query 1', 'CPU Hours: Total', expected.legend);
        });
        it('Attempt Delete Scratchpad Chart', function meDeleteScratchPad() {
            me.attemptDeleteScratchpad();
        });
        it('Chart looks the same as previous run', function () {
            me.verifyInstructions();
        });
        it('Open chart from load dialog', function meOpenChart() {
            me.loadExistingChartByName(chartName);
        });
        it('Loaded chart looks the same as previous run', function () {
            me.checkChart(chartName, 'Node Hours: Total', expected.legend);
        });
        it('Open Chart Options', function meChartOptions() {
            browser.waitAndClick(me.selectors.options.button);
        });
        it('Chart options looks the same as previous run', function () {
            // TODO: Determine Pass case for this without using screenshot
            // browser.takeScreenshot(moduleName, me.selectors.container, "chart.options")
            // browser.pause(1000);
        });
        it('Show Trend Line via Chart Options', function meAddTrendLine() {
            me.waitForChartToChange(function () {
                browser.waitAndClick(me.selectors.options.trendLine);
                me.clickSelectorAndWaitForMask(me.selectors.options.button);
                browser.waitForInvisible(me.selectors.options.trendLine);
            });
        });
        it('Trend Line looks the same as previous run', function () {
            me.checkChart(chartName, 'Node Hours: Total', [expected.legend, 'Trend Line: ' + expected.legend + ' ' + expected.trend_line]);
        });
        it('Undo Trend Line', function meUndoTrendLine() {
            me.waitForChartToChange(me.undoAggregateOrTrendLine, $container);
        });
        it('Undo Trend Line looks the same as previous run', function () {
            me.checkChart(chartName, 'Node Hours: Total', expected.legend);
        });
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
    describe('Available For Report', function availableForReport() {
        var chartTitle = '';
        it('Start with the scratchpad', function startWithScratchPad() {
            me.clear();
            browser.waitForChart();
        });
        it('Select the first saved chart', function selectFirstSavedChart() {
            browser.waitForLoadedThenClick(me.selectors.load.button());
            browser.waitForLoadedThenClick(me.selectors.load.firstSaved);
            browser.waitForVisible(me.selectors.chart.title, 5000);
            var title = browser.getText(me.selectors.chart.title);
            expect(title).to.be.a('string');
            chartTitle = title;
        });

        it('Select "Available For Report"', function shouldBeEnabled() {
            var isSelected = browser.isSelected(me.selectors.availableForReport);
            if (isSelected === false) {
                browser.waitForLoadedThenClick(me.selectors.availableForReport, 5000);
                var nowSelected = browser.isSelected(me.selectors.availableForReport);
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
            expect(lastAvailable.getTitle()).to.equal(chartTitle.trim());
        });
        it('Select the Metric Explorer Tab again', function selectTabAgain() {
            browser.waitForLoadedThenClick(me.selectors.tab);
            browser.waitForVisible(me.selectors.container, 3000);
        });
        it('Select the first saved chart again', function selectFirstChartAgain() {
            browser.waitForChart();
            browser.click(me.selectors.load.button());
            browser.waitAndClick(me.selectors.load.firstSaved);
            browser.waitForVisible(me.selectors.chart.title, 5000);
            var title = browser.getText(me.selectors.chart.title);
            expect(title).to.be.a('string');
            expect(title).to.equal(chartTitle);
        });
        it('uncheck available for report', function uncheckAvailableForReport() {
            browser.waitForLoadedThenClick(me.selectors.availableForReport, 5000);
            var isSelected = browser.isSelected(me.selectors.availableForReport);
            expect(isSelected).to.equal(false);
        });
    });

    logIn.logout();
});
