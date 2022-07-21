import {expect, Locator, Page} from '@playwright/test';
import {BasePage} from "./base.page";
import metricExplorerSelectors from './metricExplorer.selectors';
import {instructions} from '../tests/helpers/instructions';

class MetricExplorer extends BasePage{
    readonly metricExplorerSelectors = metricExplorerSelectors;

    readonly newChart = metricExplorerSelectors.newChart;
    readonly startDate = metricExplorerSelectors.startDate;
    readonly endDate = metricExplorerSelectors.endDate;
    readonly toolbar = metricExplorerSelectors.toolbar;
    readonly filterToolbar = metricExplorerSelectors.filters.toolbar;
    readonly catalog = metricExplorerSelectors.catalog;
    readonly svg = metricExplorerSelectors.chart.svg;
    readonly chart = metricExplorerSelectors.chart;
    readonly dSDef = metricExplorerSelectors.dataSeriesDefinition;
    readonly chartContextMenu = metricExplorerSelectors.chart.contextMenu;
    readonly catalogChartMenu = metricExplorerSelectors.catalog.addToChartMenu;
    readonly optionsTitle = metricExplorerSelectors.options.title;
    readonly optionsButton = metricExplorerSelectors.options.button;
    readonly optionsSwap = metricExplorerSelectors.options.swap;
    readonly optionsAggregate = metricExplorerSelectors.options.aggregate;
    readonly addDataButton = metricExplorerSelectors.addData.button;
    readonly load = metricExplorerSelectors.load;
    readonly container = metricExplorerSelectors.container;
    readonly dataInput = metricExplorerSelectors.data.modal.groupBy.input;
    readonly deleteChart = metricExplorerSelectors.deleteChart;
    readonly buttonMenuFirstLevel = metricExplorerSelectors.buttonMenu.firstLevel;
    readonly addDataSecondLevel= metricExplorerSelectors.addData.secondLevel;

    readonly containerLocator = this.page.locator(metricExplorerSelectors.container);
    readonly catalogContainerLocator = this.page.locator(metricExplorerSelectors.catalog.container);
    readonly catalogChartContainerLocator = this.page.locator(metricExplorerSelectors.catalog.addToChartMenu.container);
    readonly collapseButtonLocator = this.page.locator(metricExplorerSelectors.catalog.collapseButton);
    readonly startDateLocator = this.page.locator(metricExplorerSelectors.startDate);
    readonly endDateLocator = this.page.locator(metricExplorerSelectors.endDate);

    //for now this isn't used nor is it written
    //readonly meselectors = testHelpers.metricExplorer;
    readonly originalTitle:string = '(untitled query 2)';
    readonly newTitle:string = '<em>"& (untitled query) 2 &"</em>';
    readonly possible:string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'

    async undo(container){
        return '(//div[@id="metric_explorer"]//button[contains(@class, "x-btn-text-icon")])[1]';
    }

    async waitForChart() {
        return this.page.locator('.ext-el-mask-msg').waitFor({state:'visible', timeout:9000});
    };

    async createNewChart(chartName, datasetType, plotType){
        await this.waitForChart();
        await this.page.click(metricExplorerSelectors.toolbar.buttonByName('New Chart'));
        await expect(this.page.locator(this.newChart.topMenuByText(datasetType))).toBeVisible();
        await this.page.click(this.newChart.topMenuByText(datasetType));
        await expect(this.page.locator(this.newChart.subMenuByText(datasetType, plotType))).toBeVisible();
        await this.page.click(this.newChart.subMenuByText(datasetType, plotType));
        await expect(this.page.locator(this.newChart.modalDialog.textBox())).toBeVisible();
        await this.page.click(this.newChart.modalDialog.textBox());
        await this.page.fill(this.newChart.modalDialog.textBox(), chartName);
        await this.page.click(this.newChart.modalDialog.ok());
        await expect(this.page.locator(this.newChart.modalDialog.box)).toBeHidden();
        await expect(this.page.locator('//div[@class="x-grid-empty"]/b[text()="No data is available for viewing"]')).toBeVisible();
        await expect(this.page.locator('.ext-el-mask')).toBeHidden();
    }
    async waitForLoaded() {
        await expect(this.containerLocator).toBeVisible();
        await expect(this.catalogContainerLocator).toBeVisible();
        await expect(this.collapseButtonLocator).toBeVisible();
    }
    async setDateRange(start, end) {
        await this.clickSelectorAndWaitForMask(this.startDate);
        await this.page.fill(this.startDate, start);
        await expect(this.endDateLocator).toBeVisible();
        await this.endDateLocator.click();
        await this.page.fill(this.endDate, end);
        await this.page.click(this.toolbar.buttonByName('Refresh'));
        await this.page.locator('.ext-el-mask').isHidden();
    }
    async addDataViaCatalog(realm, statistic, groupby){
        await this.page.locator('.ext-el-mask').isHidden();
        await expect(this.catalogContainerLocator).toBeVisible();
        await this.page.click(this.catalog.rootNodeByName(realm));
        await this.page.click(this.catalog.nodeByPath(realm, statistic));
        await expect(this.catalogChartContainerLocator).toBeVisible();
        await this.page.click(this.catalogChartMenu.itemByName(groupby));
        await this.waitForChart();
    }
    async saveChanges() {
        await this.page.click(this.toolbar.buttonByName('Save'));
        await this.page.click('//span[@class="x-menu-item-text" and contains(text(),"Save Changes")]');
    }
    async addFiltersFromToolbar(filter){
        let filterByDialogBox = '//div[contains(@class,"x-panel-header")]/span[@class="x-panel-header-text" and contains(text(),"Filter by")]/ancestor::node()[4]';
        await this.clickSelectorAndWaitForMask(this.toolbar.buttonByName('Add Filter'));
        await this.page.click(`//div[@id="metric-explorer-chartoptions-add-filter-menu"]//span[@class="x-menu-item-text" and text() = "${filter}"]`);
        await expect(this.page.locator('('+filterByDialogBox + '//div[@class="x-grid3-check-col x-grid3-cc-checked"]'+')[1]')).toBeVisible();
        let checkboxes = await this.page.$$(filterByDialogBox + '//div[@class="x-grid3-check-col x-grid3-cc-checked"]');
        if (checkboxes.length !== 0){
            for await (const box of checkboxes) {
                await box.click();
            }
        }
        await this.waitForChartToChange(async () => {
            await this.page.click(filterByDialogBox + '//button[@class=" x-btn-text" and contains(text(), "Ok")]');
        });
        await expect(this.page.locator(this.svg + '//*[name()="text" and @class="undefinedsubtitle"]')).toBeVisible();
    }
    async editFiltersFromToolbar(name){
        let subtitleSelector = this.svg + '//*[name()="text" and @class="undefinedsubtitle"]';
        for (let i = 0; i < 100; i++){
            if (await this.page.isHidden('//div[@id="grid_filters_metric_explorer"]')){
                await this.clickSelectorAndWaitForMask(this.toolbar.buttonByName('Filters'));
            } else {
                await expect(this.page.locator('//div[@id="grid_filters_metric_explorer"] >> visible=true')).toBeVisible();
                break;
            }
        }
        await expect(this.page.locator(this.filterToolbar.byName(name))).toBeVisible();
        await this.page.locator(this.filterToolbar.byName(name)).click();
        await expect(this.page.locator(this.filterToolbar.byName(name))).toBeHidden();
        await this.waitForChartToChange(async () => {
            await this.page.click('//div[@id="grid_filters_metric_explorer"]//button[@class=" x-btn-text" and contains(text(), "Apply")]');
        });
        await expect(this.page.locator(subtitleSelector + `//*[contains(text(), "${name}")]`)).toBeHidden();
    }
    async cancelFiltersFromToolbar() {
        await this.clickLogoAndWaitForMask();
        let checkboxSelector = '//div[@id="grid_filters_metric_explorer"]//div[contains(@class, "x-grid3-check-col-on")]';
        await this.page.click(this.toolbar.buttonByName('Filters'));
        await expect(this.page.locator('(' + checkboxSelector + ')[1]')).toBeVisible();
        let checkboxes = await this.page.locator(checkboxSelector);
        if (checkboxes.length !== 0){
            for (let i = 0; i < 2; i++){
                await checkboxes.nth(i).click();
            }
        }
        await this.page.click('//div[@id="grid_filters_metric_explorer"]//button[@class=" x-btn-text" and contains(text(), "Cancel")]');
        await expect(this.page.locator(checkboxSelector).length).toEqual(checkboxes.length);
        await this.clickLogoAndWaitForMask();
    }
    async openDataSeriesDefinitionFromDataPoint() {
        await this.clickLogoAndWaitForMask();
        await this.clickFirstDataPoint();
        await this.page.click(this.chartContextMenu.menuItemByText('Data Series:', 'Edit Dataset'));
    }
    async addFiltersFromDataSeriesDefinition(filter, name) {
        await this.clickLogoAndWaitForMask();
        await this.openDataSeriesDefinitionFromDataPoint();
        await this.page.click(this.dSDef.dialogBox + '//button[contains(@class, "add_filter") and text() = "Add Filter"]');
        await this.page.click(`//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//li/a//span[text()="${filter}"]`);
        await expect(this.page.locator(`//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//div[contains(text(), "${name}")]`)).toBeVisible();
        /* let checkboxes = await this.page.$$('//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//div[@class="x-grid3-check-col x-grid3-cc-checked"]');
        if (checkboxes.length !== 0){
            for (let i = 0; i < checkboxes.length; i++){
                await checkboxes.nth(i).click();
            }
        }*/
        await this.page.click(`//div[contains(@class, "x-menu x-menu-floating") and contains(@style,     "visibility: visible;")]//div[contains(text(),"${name}")]`);
        await this.waitForChartToChange(async () => {
            await this.page.click('//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//button[@class=" x-btn-text" and contains(text(), "Ok")]');
            await this.page.click(this.dSDef.addButton);
        });
        await expect(this.page.locator(this.chart.legend() + `//*[contains(text(), "${name}")]`)).toBeVisible();
    }
    async editFiltersFromDataSeriesDefinition(name){
        await this.clickLogoAndWaitForMask();
        await this.openDataSeriesDefinitionFromDataPoint();
        await this.page.click(this.dSDef.dialogBox + '//button[contains(@class, "filter") and contains(text(), "Filters")]');
        await this.page.click('//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//td[contains(@class, "x-grid3-check-col-td")]');
        await this.page.click('//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//button[@class=" x-btn-text" and contains(text(), "Apply")]');
        await this.waitForChart();
        await this.page.click(this.dSDef.dialogBox + '//div[contains(@class, "x-panel-header")]');
        await this.page.click(this.dSDef.addButton);
        await this.page.locator(this.chart.legend() + `//*[contains(text(), "${name}")]`).waitFor({state:'detached'});
    }
    async cancelFiltersFromDataSeriesDefinition() {
        await this.clickLogoAndWaitForMask();
        let checkboxSelector = '//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//div[contains(@class, "x-grid3-check-col-on")]';
        await this.openDataSeriesDefinitionFromDataPoint();
        await this.page.locator((this.dSDef.dialogBox + '//button[contains(@class, "filter") and contains(text(), "Filters")]')).waitFor({state:'visible'});
        await this.page.click(this.dSDef.dialogBox + '//button[contains(@class, "filter") and contains(text(), "Filters")]');
        await this.page.locator(checkboxSelector).waitFor({state:'visible'});
        let checkboxes = await this.page.locator(checkboxSelector);
        if (checkboxes.length !== 0) {
            for (let i = 0; i < checkboxes.length; i++) {
                await checkboxes.nth(i).click();
            }
        }
        await this.page.click('//div[contains(@class, "x-menu x-menu-floating") and contains(@style,"visibility: visible;")]//button[@class=" x-btn-text" and contains(text(), "Cancel")]');
        await expect((await this.page.locator(checkboxSelector)).length).toEqual(checkboxes.length);
        await this.page.click(this.dSDef.dialogBox + '//div[contains(@class, "x-panel-header")]');
        await this.page.click(this.dSDef.addButton);
    }
    async clear() {
        await this.page.reload();
        await this.page.isVisible('#logout_link');
    }
    async generateTitle(size) {
        var result = '';
        for (var i = 0; i < size; i++) {
            result += this.possible.charAt(Math.floor(Math.random() * this.possible.length));
        }
        return result;
    }
    async setToPie() {
        await this.page.evaluate("return document.getElementsByName('display_type')[0];").click();
        await this.page.evaluate("return document.querySelector('div.x-layer.x-combo-list[style*=\"visibility: visible\"] div.x-combo-list-inner div.x-combo-list-item:last-child');").click();
    }

    async verifyError() {
        var invalidChart = await this.page.evaluate("return document.querySelectorAll('div.x-window.x-window-plain.x-window-dlg[style*=\"visibility: visible\"] span.x-window-header-text')[0];").textContent();
        await expect(invalidChart).toEqual('Invalid Chart Display Type');
        var errorText = await this.page.evaluate("return document.querySelectorAll('div.x-window.x-window-plain.x-window-dlg[style*=\"visibility: visible\"] span.ext-mb-text')[0];").textContent();
        await expect(errorText).toContainText('You cannot display timeseries data in a pie chart.');
        await expect(errorText).toContainText('Please change the dataset or display type.');
    }

    async arrowKeys() {
        await this.waitForChart();
        await this.page.locator(this.optionsButton).waitFor({state:'visible'}).click();
        await this.page.locator(this.optionsTitle).waitFor({state:'visible'}).click();
        var cursorPosition = await this.page.evaluate('return document.getElementById("me_chart_title").selectionStart;');
        await expect(cursorPosition._status).toEqual(0);
        await expect(cursorPosition.value).toEqual(this.originalTitle.length, 'Cursor Position not at end');
        await this.page.keyboard.press('ArrowUp');
        var newPosition = await this.page.evaluate('return document.getElementById("me_chart_title").selectionStart;');
        await expect(newPosition._status).toEqual(0);
        await expect(newPosition.value).toEqual(0, 'Cursor Position not at begining');
        await this.page.click(this.optionsButton);
    }
    async addDataViaMenu(maskName, n) {
        await this.page.locator(maskName).isHidden();
        await this.catalogContainerLocator.isVisible();
        await this.page.click(this.addDataButton);
        await this.page.click('//div[@id="metric-explorer-chartoptions-add-data-menu"]//span[contains(text(), "Jobs")]');
        await this.page.click("//div[contains(@class, 'x-menu')][contains(@class, 'x-menu-floating')][contains(@class, 'x-layer')][contains(@style, 'visibility: visible')]//span[contains(text(), '" + n + "')]");
        await expect(this.page.locator(this.dSDef.dialogBox)).toBeVisible();
    }
    async addDataSeriesByDefinition() {
        await this.page.click(this.dSDef.addButton);
        await expect(this.page.locator(this.dSDef.dialogBox)).toBeHidden();
    }
    async loadExistingChartByName(name) {
        await this.collapseButtonLocator.waitFor({state:'visible'});
        await this.page.locator(this.toolbar.buttonByName('Load Chart')).waitFor({state:'visible'});
        await this.page.click(this.toolbar.buttonByName('Load Chart'));
        await this.page.locator(this.load.dialog).waitFor({state:'visible'});
        await this.waitForChartToChange(async () => {
            await this.page.click(this.load.chartByName(name));
            await this.page.locator(this.load.dialog).waitFor({state:'hidden'});
        });
        await this.page.locator(this.catalog.expandButton).waitFor({state:'visible'});
    }
    async checkChart(chartTitle, yAxisLabel, legend, isValidChart = true) {
        await this.clickLogoAndWaitForMask();
        await this.page.locator(this.chart.titleByText(chartTitle)).waitFor({state:'visible'});
        var selToCheck;
        if (isValidChart) {
            selToCheck = this.chart.credits();
        } else {
            selToCheck = this.chart.titleByText(chartTitle);
        }
        await this.page.locator(selToCheck).waitFor({state:'visible'});
        for (let i = 0; i < 100; i++) {
            try {
                await this.page.click(selToCheck);
                break;
            } catch (e) {
                await this.page.isHidden('.ext-el-mask');
            }
        }

        if (yAxisLabel) {
            await this.page.locator(this.chart.yAxisTitle()).waitFor({state:'visible'});
            var yAxisElems = await this.page.$$(this.chart.yAxisTitle());
            if (typeof yAxisLabel === 'string') {
                await expect(yAxisElems.length).toEqual(1);
                const result = await Promise.all(yAxisElems.map((elem) => {
                    return elem.textContent();
                }));
                await expect(result[0]).toEqual(yAxisLabel);
            } else {
                await expect(yAxisElems.length).toEqual(yAxisLabel.length);
                for (let i = 0; i < legend.length; i++) {
                    await expect(yAxisElems[i]).toEqual(yAxisLabel[i]);
                }
            }
        }

        if (legend) {
            await this.page.locator('('+this.chart.legend()+')[1]').waitFor({state:'visible'});
            var legendElems = await this.page.locator(this.chart.legend());
            if (typeof legend === 'string') {
                await expect(legendElems).toBeVisible();
                const result = await legendElems.textContent();
                await expect(result).toEqual(legend);
            } else {
                const num = await legendElems.count();
                await expect(num).toEqual(legend.length);
                for (let i = 0; i < legend.length; i++) {
                    const computed = await legendElems.nth(i).textContent();
                    await expect(computed).toContain(legend[i]);
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
        var elem = this.page.locator(this.svg);
        if (arguments.length > 1) {
            action.apply(this, [].slice.call(arguments, 1));
        } else {
            action.call(this);
        }
        try {
            let i = 0;
            while (this.page.elementIdDisplayed(elem.value[0].ELEMENT) && i < 20) {
                //this.page.pause(100);
                i++;
            }
        } catch (err) {
            // OK the element has gone away
        }
    }
    async setTitleWithOptionsMenu(title) {
        await this.waitForChart();
        await this.page.click(this.optionsButton);
        await expect(this.page.locator(this.optionsTitle)).toBeVisible();
        await this.page.fill(this.optionsTitle, '');
        //await this.page.pause(500);
        await this.page.fill(this.optionsTitle, title);
    }
    async verifyHighChartsTitle(title) {
        var execReturn = await this.page.evaluate('return Ext.util.Format.htmlDecode(document.querySelector("' + this.chart.title + '").textContent);');
        await expect(execReturn._status).toEqual(0);
        await expect(typeof(execReturn.value)).toEqual('string');
        await expect(execReturn.value).toEqual(title);
    }
    async verifyEditChartTitle() {
        await this.page.click(this.chart.title);
        await expect(this.page.isVisible(this.chart.titleInput));
        var titleValue = await this.page.locator(this.chart.titleInput).inputValue();
        await expect(typeof(titleValue)).toEqual('string');
        await expect(titleValue).toEqual(this.newTitle);
        await this.page.click(this.chart.titleOkButton);
        await this.waitForChart();
    }
    async verifyInstructions() {
        await this.page.locator('//div[@id="metric_explorer"]//div[@class="x-grid-empty"]//b[text()="No data is available for viewing"]').waitFor({state:'visible'});
        const boo = await instructions(this.page, 'metricExplorer', this.container);
        await expect(boo).toBeTruthy();
    }
    async setChartTitleViaChart(title) {
        await this.waitForChart();
        await this.page.click(this.chart.title);
        await expect(this.page.locator(this.chart.titleInput)).isVisible();
        await this.page.clearElement(this.chart.titleInput);
        await this.page.fill(this.chart.titleInput, title);
        //await this.page.pause(500);
        await this.page.click(this.chart.titleOkButton);
    }
    async setGroupByToResource() {
        await this.page.click(this.dataInput);
        //await this.page.pause(1000);
        await this.page.evaluate("return document.querySelectorAll('div.x-layer.x-combo-list[style*=\"visibility: visible\"] .x-combo-list-item:nth-child(10)')[0];").click();
    }
    async axisSwap() {
        var axisFirstChildText = '';
        var axisSecondChildText = '';
        axisFirstChildText = await this.page.locator(this.chart.axis + ' text').textContent();
        await this.page.click(this.optionsButton);
        await this.page.click(this.optionsSwap);
        // await this.page.pause(1000);
        axisSecondChildText = await this.page.locator(this.chart.axis + ' text').textContent();
        // await this.waitForChart();
        //await this.page.pause(1000);
        await this.page.click(this.optionsButton);
        // browser.pause(1000);
        await expect(axisFirstChildText[1]).not.toEqual(axisSecondChildText[1]);
        //await this.page.pause(10000);
    }
    async chartTitleInOptionsUpdated() {
        await this.waitForChart();
        await this.page.click(this.optionsButton);
        await expect(this.page.locator(this.optionsTitle)).toBeVisible();
        var res1 = await this.page.locator(this.optionsTitle).textContent();
        await expect(typeof(res1)).toEqual('string');
        var res2 = await this.page.evaluate(function (text) {
            // TODO: Fix this withOut having to use EXT if Possible
            // eslint-disable-next-line no-undef
            return Ext.util.Format.htmlDecode(text);
        }, res1);
        await expect(res2.value).toEqual(this.newTitle);
        await this.waitForChart();
        await this.page.click(this.optionsButton);
    }
    async attemptDeleteScratchpad() {
        await expect(this.page.locator(this.toolbar.buttonByName('Delete'))).toBeVisible();
        await this.page.click(this.toolbar.buttonByName('Delete'));
        await expect(this.page.locator(this.deleteChart.dialogBox)).toBeVisible();
        await this.page.click(this.deleteChart.buttonByLabel('Yes'));
        await expect(this.page.locator(this.deleteChart.dialogBox)).toBeHidden();
        await expect(this.page.locator('.ext-el-mask')).toBeHidden();
    }
    async actionLoadChart(chartNumber) {
        //await this.page.pause(3000);
        await this.page.click(this.load.button());
        await this.page.click(this.load.chartNum(chartNumber));
        await this.waitForChart();
    }
    async addDataViaToolbar() {
        await this.page.click(this.addDataButton);
        await this.page.locator('//div[@id="metric-explorer-chartoptions-add-data-menu"]').waitFor({state:'visible'});
        await this.page.click(this.toolbar.addData('Jobs'));
        await this.page.locator('//div[@class="x-menu x-menu-floating x-layer"]').waitFor({state:'visible'});
        await this.page.click(this.toolbar.addDataGroupBy('CPU Hours: Per Job'));
        await this.addDataSeriesByDefinition();
    }
    async genericStartingPoint() {
        await this.page.click(this.addDataButton);
        // Click on Jobs (5 on original site)
        await this.page.click(this.buttonMenuFirstLevel + ' ul li:nth-child(3)');
        // click on CPU Hours: Total
        await this.page.click(this.addDataSecondLevel + ' ul li:nth-child(3)');
        await this.addDataSeriesByDefinition();
    }
    async confirmChartTitleChange(largeTitle) {
        await this.waitForChart();
        // await this.page.pause(2000);
        var titleChange = await this.page.evaluate('return document.querySelector("' + this.chart.title + '").textContent;');
        // expect(titleChange.state).to.equal('success');
        await expect(titleChange._status).toEqual(0);
        await expect(typeof(titleChange.value)).toEqual('string');
        await expect(titleChange.value).toEqual(largeTitle);
    }

    async switchToAggregate() {
        await this.page.click(this.optionsButton);
        await this.page.click(this.optionsAggregate);
        await this.clickLogoAndWaitForMask();
        await expect(this.page.locator(this.optionsAggregate)).toBeHidden();
        await expect(this.page.locator('.ext-el-mask')).toBeHidden();
    }

    async undoAggregateOrTrendLine(container) {
        const toClick = await this.undo(container);
        await this.page.click(toClick);
        // The mouse stays and causes a hover, lets move the mouse somewhere else
        await this.clickLogoAndWaitForMask();
    }

    async clickFirstDataPoint() {
        const tempMaskLocator = await this.page.locator('//div[contains(@class, "ext-el-mask-msg") and contains(., "Loading...")]');
        const maskHolder = await tempMaskLocator.isVisible();
        if (maskHolder){
            await tempMaskLocator.waitFor({state:"detached"});
        }
        var elems = await this.page.locator(this.chart.seriesMarkers(0));
        // Data points are returned in reverse order.
        // for some unknown reason the first point click gets intercepted by the series
        // menu.
        await elems.nth(0).click();
        const num = await elems.count();
        await elems.nth(num - 1).click();
    }

    /**
     * Best effort to try to wait until the load mask has been and gone.
     */
    async clickSelectorAndWaitForMask(selector) {
        await expect(this.page.locator(selector)).toBeVisible();
        await expect(this.page.locator('.ext-el-mask')).toBeHidden();

        for (let i = 0; i < 100; i++) {
            try {
                await this.page.click(selector);
                break;
            } catch (e) {
                await expect(this.page.locator('.ext-el-mask')).toBeHidden();
            }
        }
    }

    async clickLogoAndWaitForMask() {
        const tempMaskLocator = await this.page.locator('//div[contains(@class, "ext-el-mask-msg") and contains(., "Loading...")]');
        const maskHolder = await tempMaskLocator.isVisible();
        if (maskHolder){
            await tempMaskLocator.waitFor({state:"detached"});
        }
        await this.clickSelectorAndWaitForMask('.xtb-text.logo93');
    }
}
export default MetricExplorer;
