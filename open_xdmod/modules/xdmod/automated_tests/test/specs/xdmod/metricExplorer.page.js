class MetricExplorer {
    constructor() {
        this.meselectors = testHelpers.metricExplorer;
        this.originalTitle = '(untitled query 2)';
        this.newTitle = '<em>"& (untitled query) 2 &"</em>';
        this.possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        this.selectors = {
            tab: '#main_tab_panel__metric_explorer',
            startDate: '#metric_explorer input[id^=start_field_ext]',
            endDate: '#metric_explorer input[id^=end_field_ext]',
            toolbar: {
                buttonByName: function (name) {
                    return '//div[@id="metric_explorer"]//table[@class="x-toolbar-ct"]//button[text()="' + name + '"]/ancestor::node()[5]';
                }
            },
            container: '#metric_explorer',
            load: {
                button: function meLoadButtonId() {
                    return 'button=Load Chart';
                },
                firstSaved: '.x-menu-floating:not(.x-hide-offsets) .x-grid3-body .x-grid3-row-first',
                chartNum: function meChartByIndex(number) {
                    var mynumber = number + 1;
                    return '.x-menu-floating:not(.x-hide-offsets) .x-grid3-body > div:nth-child(' + mynumber + ')';
                },
                dialog: '//div[contains(@class,"x-grid3-header-inner")]//div[contains(@class,"x-grid3-hd-name") and text() = "Chart Name"]/ancestor::node()[8]',
                chartByName: function (name) {
                    return module.exports.selectors.load.dialog + '//div[contains(@class,"x-grid3-cell-inner") and text() = "' + name + '"]';
                }
            },
            newChart: {
                topMenuByText: function (name) {
                    return '//div[@id="me_new_chart_menu"]//span[@class="x-menu-item-text" and text() = "' + name + '"]';
                },
                subMenuByText: function (topText, name) {
                    return '//div[@id="me_new_chart_submenu_' + topText + '"]//span[@class="x-menu-item-text" and contains(text(),"' + name + '")]';
                },
                modalDialog: {
                    box: '//span[@class="x-window-header-text" and text() = "New Chart"]/ancestor::node()[5]',
                    textBox: function () {
                        return module.exports.selectors.newChart.modalDialog.box + '//input[contains(@class,"x-form-text")]';
                    },
                    checkBox: function () {
                        return module.exports.selectors.newChart.modalDialog.box + '//input[contains(@class,"x-form-checkbox")]';
                    },
                    ok: function () {
                        return module.exports.selectors.newChart.modalDialog.box + '//button[text() = "Ok"]';
                    },
                    cancel: function () {
                        return module.exports.selectors.newChart.modalDialog.box + '//button[text() = "Cancel"]';
                    }
                }
            },
            dataSeriesDefinition: {
                dialogBox: '//div[contains(@class,"x-panel-header")]/span[@class="x-panel-header-text" and contains(text(),"Data Series Definition")]/ancestor::node()[4]',
                addButton: '#adp_submit_button'
            },
            deleteChart: {
                dialogBox: '//div[contains(@class,"x-window-header")]/span[@class="x-window-header-text" and contains(text(),"Delete Selected Chart")]/ancestor::node()[5]',
                buttonByLabel: function (label) {
                    return module.exports.selectors.deleteChart.dialogBox + '//button[text()="' + label + '"]';
                }
            },
            addData: {
                button: '.x-btn-text.add_data',
                secondLevel: '.x-menu-floating:not(.x-hide-offsets):not(.x-menu-nosep)'
            },
            data: {
                button: 'button=Data',
                container: '',
                modal: {
                    updateButton: 'button=Update',
                    groupBy: {
                        input: 'input[name=dimension]'
                    }
                }
            },
            options: {
                aggregate: '#aggregate_cb',
                button: '#metric_explorer button.chartoptions',
                trendLine: '#me_trend_line',
                swap: '#me_chart_swap_xy',
                title: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"] #me_chart_title'
            },
            chart: {
                svg: '//div[@id="metric_explorer"]//div[@class="highcharts-container"]//*[local-name() = "svg"]',
                titleByText: function (title) {
                    return module.exports.selectors.chart.svg + '/*[name()="text" and contains(@class, "title")]/*[name()="tspan" and contains(text(),"' + title + '")]';
                },
                yAxisTitle: function () {
                    return module.exports.selectors.chart.svg + '//*[name() = "g" and contains(@class, "highcharts-axis")]/*[name() = "text" and contains(@class,"highcharts-yaxis-title")]';
                },
                legend: function () {
                    return module.exports.selectors.chart.svg + '//*[name() = "g" and contains(@class, "highcharts-legend-item")]/*[name()="text"]';
                },
                title: '#hc-panelmetric_explorer svg .undefinedtitle',
                titleInput: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"] input[type=text]',
                titleOkButton: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"] table.x-btn.x-btn-noicon.x-box-item:first-child button',
                titleCancelButton: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"] table.x-btn.x-btn-noicon.x-box-item:last-child button',
                contextMenu: {
                    container: '#metric-explorer-chartoptions-context-menu',
                    legend: '#metric-explorer-chartoptions-legend',
                    addData: '#metric-explorer-chartoptions-add-data',
                    addFilter: '#metric-explorer-chartoptions-add-filter'

                },
                axis: '#metric_explorer .highcharts-yaxis-labels'
            },
            catalog: {
                container: '#metric_explorer > div > .x-panel-body-noborder > .x-border-panel:not(.x-panel-noborder)',
                tree: '#metric_explorer > div > .x-panel-body-noborder > .x-border-panel:not(.x-panel-noborder) .x-tree-root-ct',
                rootNodeByName: function (name) {
                    return '//div[@id="metric_explorer"]//div[@class="x-tree-root-node"]/li/div[contains(@class,"x-tree-node-el")]//span[text() = "' + name + '"]';
                },
                nodeByPath: function (topname, childname) {
                    return module.exports.selectors.catalog.rootNodeByName(topname) + '/ancestor::node()[3]//span[text() = "' + childname + '"]';
                },
                addToChartMenu: {
                    container: '//span[@class="x-menu-text"]/span[contains(text(),"Add To Chart:")]/ancestor::node()[3]',
                    itemByName: function (name) {
                        return module.exports.selectors.catalog.addToChartMenu.container + '//span[@class="x-menu-item-text" and text() = "' + name + '"]';
                    }
                }
            },
            buttonMenu: {
                firstLevel: '.x-menu-floating:not(.x-hide-offsets)'
            }
        };
    }
    undo($container) {
        return '#' + $container('button.x-btn-text-icon')
            .closest('table')
            .attr('id');
    }
    createNewChart(chartName, datasetType, plotType) {
        browser.waitForChart();
        browser.click(this.selectors.toolbar.buttonByName('New Chart'));
        browser.waitAndClick(this.selectors.newChart.topMenuByText(datasetType));
        browser.waitAndClick(this.selectors.newChart.subMenuByText(datasetType, plotType));
        browser.waitForVisible(this.selectors.newChart.modalDialog.box);
        browser.setValue(this.selectors.newChart.modalDialog.textBox(), chartName);
        browser.click(this.selectors.newChart.modalDialog.ok());
        browser.waitForInvisible(this.selectors.newChart.modalDialog.box);
        browser.waitUntilNotExist('.ext-el-mask');
        browser.waitForVisible('//div[@class="x-grid-empty"]/b[text()="No data is available for viewing"]');
    }
    setDateRange(start, end) {
        browser.waitUntilNotExist('.ext-el-mask');
        browser.waitAndClick(this.selectors.startDate);
        browser.setValue(this.selectors.startDate, start);
        browser.waitAndClick(this.selectors.endDate);
        browser.setValue(this.selectors.endDate, end);
        browser.click(this.selectors.toolbar.buttonByName('Refresh'));
        browser.waitUntilNotExist('.ext-el-mask');
    }
    addDataViaCatalog(realm, statistic, groupby) {
        browser.waitForVisible(this.selectors.catalog.container, 10000);
        browser.waitUntilAnimEndAndClick(this.selectors.catalog.rootNodeByName(realm));
        browser.waitUntilAnimEndAndClick(this.selectors.catalog.nodeByPath(realm, statistic));
        browser.waitForVisible(this.selectors.catalog.addToChartMenu.container);
        browser.waitAndClick(this.selectors.catalog.addToChartMenu.itemByName(groupby));
        browser.waitForChart();
    }
    saveChanges() {
        browser.click(this.selectors.toolbar.buttonByName('Save'));
        browser.waitAndClick('//span[@class="x-menu-item-text" and contains(text(),"Save Changes")]');
    }
    clear() {
        browser.refresh();
        browser.waitForVisible('#logout_link', 3000);
    }
    generateTitle(size) {
        var result = '';
        for (var i = 0; i < size; i++) {
            result += this.possible.charAt(Math.floor(Math.random() * this.possible.length));
        }
        return result;
    }
    setToPie() {
        browser.execute("return document.getElementsByName('display_type')[0];").click();
        browser.execute("return document.querySelector('div.x-layer.x-combo-list[style*=\"visibility: visible\"] div.x-combo-list-inner div.x-combo-list-item:last-child');").click();
    }

    verifyError() {
        var invalidChart = browser.execute("return document.querySelectorAll('div.x-window.x-window-plain.x-window-dlg[style*=\"visibility: visible\"] span.x-window-header-text')[0];").getText();
        expect(invalidChart).to.equal('Invalid Chart Display Type');
        var errorText = browser.execute("return document.querySelectorAll('div.x-window.x-window-plain.x-window-dlg[style*=\"visibility: visible\"] span.ext-mb-text')[0];").getText();
        expect(errorText).to.contain('You cannot display timeseries data in a pie chart.');
        expect(errorText).to.contain('Please change the dataset or display type.');
    }

    arrowKeys() {
        browser.waitForChart();
        browser.waitForLoadedThenClick(this.selectors.options.button);
        browser.waitForLoadedThenClick(this.selectors.options.title);
        var cursorPosition = browser.execute('return document.getElementById("me_chart_title").selectionStart;');
        expect(cursorPosition._status).to.equal(0);
        expect(cursorPosition.value).to.equal(this.originalTitle.length, 'Cursor Position not at end');
        browser.keys('Arrow_Up');
        var newPosition = browser.execute('return document.getElementById("me_chart_title").selectionStart;');
        expect(newPosition._status).to.equal(0);
        expect(newPosition.value).to.equal(0, 'Cursor Position not at begining');
        browser.waitAndClick(this.selectors.options.button);
    }
    addDataViaMenu(maskName, n) {
        browser.waitUntilNotExist(maskName);
        browser.waitForVisible(this.selectors.catalog.container, 5000);
        browser.waitAndClick(this.selectors.addData.button);
        browser.waitAndClick(this.selectors.buttonMenu.firstLevel + ' ul li:nth-child(3)');
        browser.waitAndClick(this.selectors.addData.secondLevel + ' ul li:nth-child(' + n + ')');
        browser.waitForVisible(this.selectors.dataSeriesDefinition.dialogBox);
    }
    addDataSeriesByDefinition() {
        browser.waitAndClick(this.selectors.dataSeriesDefinition.addButton);
        browser.waitForInvisible(this.selectors.dataSeriesDefinition.dialogBox);
    }
    loadExistingChartByName(name) {
        browser.waitForVisible(this.selectors.toolbar.buttonByName('Load Chart'));
        browser.click(this.selectors.toolbar.buttonByName('Load Chart'));
        browser.waitForVisible(this.selectors.load.dialog);
        browser.waitAndClick(this.selectors.load.chartByName(name));
        browser.waitForInvisible(this.selectors.load.dialog);
    }
    checkChart(chartTitle, yAxisLabel, legend) {
        browser.waitForVisible(this.selectors.chart.titleByText(chartTitle));

        if (yAxisLabel) {
            browser.waitForExist(this.selectors.chart.yAxisTitle());
            var yAxisElems = browser.elements(this.selectors.chart.yAxisTitle());
            if (typeof yAxisLabel === 'string') {
                expect(yAxisElems.value.length).to.equal(1);
                expect(browser.elementIdText(yAxisElems.value[0].ELEMENT).value).to.equal(yAxisLabel);
            } else {
                expect(yAxisElems.value.length).to.equal(yAxisLabel.length);
                for (let i = 0; i < legend.length; i++) {
                    expect(browser.elementIdText(yAxisElems.value[i].ELEMENT).value).to.equal(yAxisLabel[i]);
                }
            }
        }

        if (legend) {
            browser.waitForExist(this.selectors.chart.legend());
            var legendElems = browser.elements(this.selectors.chart.legend());
            if (typeof legend === 'string') {
                expect(legendElems.value.length).to.equal(1);
                expect(browser.elementIdText(legendElems.value[0].ELEMENT).value).to.equal(legend);
            } else {
                expect(legendElems.value.length).to.equal(legend.length);
                for (let i = 0; i < legend.length; i++) {
                    expect(browser.elementIdText(legendElems.value[i].ELEMENT).value).to.equal(legend[i]);
                }
            }
        }
    }
    /**
     * Call the action function then wait until the current loaded Highcharts chart
     * disappears. This function should only be called if there is an active highcharts
     * chart and the action should result in a chart change.
     *
     * @params function() action
     */
    waitForChartToChange(action) {
        var elem = browser.elements(this.selectors.chart.svg);
        if (arguments.length > 1) {
            action.apply(this, [].slice.call(arguments, 1));
        } else {
            action.call(this);
        }
        try {
            let i = 0;
            while (browser.elementIdDisplayed(elem.value[0].ELEMENT) && i < 20) {
                browser.pause(100);
                i++;
            }
        } catch (err) {
            // OK the element has gone away
        }
    }
    setTitleWithOptionsMenu(title) {
        browser.waitForChart();
        browser.waitAndClick(this.selectors.options.button);
        browser.waitForVisible(this.selectors.options.title, 5000);
        browser.clearElement(this.selectors.options.title);
        browser.pause(500);
        browser.setValue(this.selectors.options.title, title);
    }
    verifyHighChartsTitle(title) {
        var execReturn = browser.execute('return Ext.util.Format.htmlDecode(document.querySelector("' + this.selectors.chart.title + '").textContent);');
        expect(execReturn._status).to.equal(0);
        expect(execReturn.value).to.be.a('string');
        expect(execReturn.value).to.equal(title);
    }
    verifyEditChartTitle() {
        browser.waitAndClick(this.selectors.chart.title);
        browser.waitForVisible(this.selectors.chart.titleInput);
        var titleValue = browser.getValue(this.selectors.chart.titleInput);
        expect(titleValue).to.be.a('string');
        expect(titleValue).to.equal(this.newTitle);
        browser.waitAndClick(this.selectors.chart.titleOkButton);
        browser.waitForChart();
    }
    verifyInstructions() {
        browser.waitForVisible('//div[@id="metric_explorer"]//div[@class="x-grid-empty"]//b[text()="No data is available for viewing"]');
        testHelpers.instructions(browser, 'metricExplorer', this.selectors.container);
    }
    setChartTitleViaChart(title) {
        browser.waitForChart();
        browser.waitAndClick(this.selectors.chart.title);
        browser.waitForVisible(this.selectors.chart.titleInput);
        browser.clearElement(this.selectors.chart.titleInput);
        browser.setValue(this.selectors.chart.titleInput, title);
        browser.pause(500);
        browser.waitAndClick(this.selectors.chart.titleOkButton);
    }
    setGroupByToResource() {
        browser.click(this.selectors.data.modal.groupBy.input);
        browser.pause(1000);
        browser.execute("return document.querySelectorAll('div.x-layer.x-combo-list[style*=\"visibility: visible\"] .x-combo-list-item:nth-child(10)')[0];").click();
    }
    axisSwap() {
        var axisFirstChildText = '';
        var axisSecondChildText = '';
        axisFirstChildText = browser.getText(this.selectors.chart.axis + ' text');
        browser.waitAndClick(this.selectors.options.button);
        browser.waitAndClick(this.selectors.options.swap);
        // browser.pause(1000);
        axisSecondChildText = browser.getText(this.selectors.chart.axis + ' text');
        // browser.waitForChart();
        browser.pause(1000);
        browser.waitAndClick(this.selectors.options.button);
        // browser.pause(1000);
        expect(axisFirstChildText[1]).to.not.equal(axisSecondChildText[1]);
        browser.pause(10000);
    }
    chartTitleInOptionsUpdated() {
        browser.waitForChart();
        browser.waitAndClick(this.selectors.options.button);
        browser.waitForVisible(this.selectors.options.title, 3000);
        var res1 = browser.getValue(this.selectors.options.title);
        expect(res1).to.be.a('string');
        var res2 = browser.execute(function (text) {
            // TODO: Fix this withOut having to use EXT if Possible
            // eslint-disable-next-line no-undef
            return Ext.util.Format.htmlDecode(text);
        }, res1);
        expect(res2.value).to.equal(this.newTitle);
        browser.waitForChart();
        browser.waitAndClick(this.selectors.options.button);
    }
    attemptDeleteScratchpad() {
        browser.waitAndClick(this.selectors.toolbar.buttonByName('Delete'));
        browser.waitForVisible(this.selectors.deleteChart.dialogBox);
        browser.waitAndClick(this.selectors.deleteChart.buttonByLabel('Yes'));
        browser.waitForInvisible(this.selectors.deleteChart.dialogBox);
        browser.waitUntilNotExist('#ext-el-mask');
    }
    actionLoadChart(chartNumber) {
        browser.pause(3000);
        browser.waitAndClick(this.selectors.load.button(), 2000);
        browser.waitAndClick(this.selectors.load.chartNum(chartNumber), 2000);
        browser.waitForChart();
    }
    addDataViaToolbar() {
        browser.click(this.selectors.addData.button);
        // Click on Jobs
        browser.waitAndClick(this.selectors.buttonMenu.firstLevel + ' ul li:nth-child(3)');
        // click on CPU Hours: Per Job
        browser.waitAndClick(this.selectors.addData.secondLevel + ' ul li:nth-child(2)');
        this.addDataSeriesByDefinition();
    }
    genericStartingPoint() {
        browser.waitAndClick(this.selectors.addData.button);
            // Click on Jobs (5 on original site)
        browser.waitAndClick(this.selectors.buttonMenu.firstLevel + ' ul li:nth-child(3)');
            // click on CPU Hours: Total
        browser.waitAndClick(this.selectors.addData.secondLevel + ' ul li:nth-child(3)', 1000);
        this.addDataSeriesByDefinition();
    }
    confirmChartTitleChange(largeTitle) {
        browser.waitForChart();
        browser.pause(2000);
        var titleChange = browser.execute('return document.querySelector("' + this.selectors.chart.title + '").textContent;');
        // expect(titleChange.state).to.equal('success');
        expect(titleChange._status).to.equal(0);
        expect(titleChange.value).to.be.a('string');
        expect(titleChange.value).to.equal(largeTitle);
    }

    switchToAggregate() {
        browser.waitAndClick(this.selectors.options.button);
        browser.waitAndClick(this.selectors.options.aggregate);
        browser.waitAndClick('.xtb-text.logo93');
        browser.waitForInvisible(this.selectors.options.aggregate);
        browser.waitUntilNotExist('.ext-el-mask');
    }

    undoAggregateOrTrendLine($container) {
        browser.waitAndClick(this.undo($container));
            // The mouse stays and causes a hover, lets move the mouse somewhere else
        browser.waitAndClick('.xtb-text.logo93');
    }

    clickFirstDataPoint() {
        var seriesIndex = 0;
        var pointIndex = 0;
        browser.moveToObject('#hc-panelmetric_explorer', 0, 0);
        var pointLocation = browser.getChartSeriesPointLocation('hc-panelmetric_explorer', seriesIndex, pointIndex).value;
        expect(pointLocation.left).to.be.a('number');
        expect(pointLocation.top).to.be.a('number');
        browser.moveTo(null, pointLocation.left, pointLocation.top);
        browser.buttonDown();
        browser.buttonUp();
        browser.execute('return document.querySelectorAll("svg g.highcharts-series-group g.highcharts-markers.highcharts-tracker path")[' + seriesIndex + '];').click();
        browser.pause(10000);
    }
    saveChartTitle() {
        browser.pause(5000);
        browser.waitForLoadedThenClick(this.meselectors.options.button);
        browser.waitForVisible(this.meselectors.options.title, 30000);
        expect(browser.getValue(this.meselectors.options.title)).to.be.a('string');
        var chartTitle = browser.getValue(this.meselectors.options.title);
        return (chartTitle);
    }
}

module.exports = new MetricExplorer();
