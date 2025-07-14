/**
 * Metric Explorer test class
 */
import {expect} from '@playwright/test';
import {BasePage} from "./base.page";
import selectors from './metricExplorer.selectors';
import {instructions} from '../tests/helpers/instructions';

class MetricExplorer extends BasePage{
    readonly selectors = selectors;

    readonly mask = selectors.mask;
    readonly logoutLink = selectors.logoutLink;
    readonly logo = selectors.logo;
    readonly newChart = selectors.newChart;
    readonly startDate = selectors.startDate;
    readonly endDate = selectors.endDate;
    readonly toolbar = selectors.toolbar;
    readonly container = selectors.container;
    readonly load = selectors.load;
    readonly filterToolbar = selectors.filters.toolbar;
    readonly dataSeriesDef = selectors.dataSeriesDefinition;
    readonly filterMenu = selectors.filterMenu;
    readonly filterByDialogBox = selectors.filterMenu.filterByDialogBox;
    readonly selectedCheckboxes = selectors.filterMenu.selectedCheckboxes();
    readonly firstSelectedCheckbox = selectors.filterMenu.firstSelectedCheckbox();
    readonly okButton = selectors.filterMenu.okButton();
    readonly deleteChart = selectors.deleteChart;
    readonly addData = selectors.addData;
    readonly addDataButton = selectors.addData.button;
    readonly addDataSecondLevel = selectors.addData.secondLevel;
    readonly addDataSecondLevelChild = selectors.addData.secondLevelChild();
    readonly dataInput = selectors.data.modal.groupBy.input;
    readonly optionsAggregate = selectors.options.aggregate;
    readonly optionsButton = selectors.options.button;
    readonly optionsSwap = selectors.options.swap;
    readonly optionsTitle = selectors.options.title;
    readonly chart = selectors.chart;
    readonly svg = selectors.chart.svg;
    readonly chartContextMenu = selectors.chart.contextMenu;
    readonly subtitle = selectors.chart.subtitle();
    readonly catalog = selectors.catalog;
    readonly catalogChartMenu = selectors.catalog.addToChartMenu;
    readonly buttonMenuFirstLevel = selectors.buttonMenu.firstLevel;
    readonly buttonMenuFirstLevelChild = selectors.buttonMenu.firstLevelChild();
    readonly grid = selectors.filters.grid;
    readonly apply = selectors.filters.toolbar.apply;
    readonly checkbox = selectors.filters.toolbar.checkBox;
    readonly firstCheckbox = selectors.filters.toolbar.firstCheckBox;
    readonly cancel = selectors.filters.toolbar.cancel;
    readonly undo = selectors.undo;

    readonly containerLocator = this.page.locator(selectors.container);
    readonly catalogContainerLocator = this.page.locator(selectors.catalog.container);
    readonly catalogChartContainerLocator = this.page.locator(selectors.catalog.addToChartMenu.container);
    readonly collapseButtonLocator = this.page.locator(selectors.catalog.collapseButton);
    readonly startDateLocator = this.page.locator(selectors.startDate);
    readonly endDateLocator = this.page.locator(selectors.endDate);

    readonly originalTitle:string = '(untitled query 2)';
    readonly newTitle:string =  '<em>"& (untitled query) 2 &"</em>';
    readonly possible:string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'

    /**
     * Create a new Chart based on a given dataset and plot type
     *
     * @param {String} chartName    name for new chart
     * @param {String} datasetType  dataset type for new chart
     * @param {String} plotType     plot type for new chart
     */
    async createNewChart(chartName, datasetType, plotType){
        await this.page.click(this.toolbar.buttonByName('New Chart'));
        await expect(this.page.locator(this.newChart.topMenuByText(datasetType))).toBeVisible();
        await this.page.click(this.newChart.topMenuByText(datasetType));
        await expect(this.page.locator(this.newChart.subMenuByText(datasetType, plotType))).toBeVisible();
        await this.page.click(this.newChart.subMenuByText(datasetType, plotType));
        await expect(this.page.locator(this.newChart.modalDialog.textBox())).toBeVisible();
        await this.page.click(this.newChart.modalDialog.textBox());
        await this.page.fill(this.newChart.modalDialog.textBox(), chartName);
        await this.page.click(this.newChart.modalDialog.ok());
        await expect(this.page.locator(this.newChart.modalDialog.box)).toBeHidden();
        await expect(this.page.locator(this.newChart.modalDialog.noDataMessage)).toBeVisible();
        await expect(this.page.locator(this.mask)).toBeHidden();
    }

    /**
     * Waits for catalog container and collapse button to be visible
     */
    async waitForLoaded() {
        await expect(this.containerLocator).toBeVisible();
        await expect(this.catalogContainerLocator).toBeVisible();
        await expect(this.collapseButtonLocator).toBeVisible();
        await this.clickSelector(this.logo);
    }

    /**
     * Sets the date of a chart
     *
     * @param {String} start    the start date for the chart
     * @param {String} end      the end date for the chart
     */
    async setDateRange(start, end) {
        await this.clickSelector(this.startDate);
        await this.page.fill(this.startDate, start);
        await expect(this.endDateLocator).toBeVisible();
        await this.endDateLocator.click();
        await this.page.fill(this.endDate, end);
        await this.page.click(this.toolbar.buttonByName('Refresh'));
        await this.page.locator(this.mask).isHidden();
    }

    /**
     * Adds data from catalog option
     *
     * @param {String} realm        the realm of data source
     * @param {String} statistic    the category name under the realm
     * @param {String} groupby      the name of the group filter for the data
     */
    async addDataViaCatalog(realm, statistic, groupby){
        await expect(this.catalogContainerLocator).toBeVisible();
        await this.page.click(this.catalog.rootNodeByName(realm));
        await this.page.click(this.catalog.nodeByPath(realm, statistic));
        await expect(this.catalogChartContainerLocator).toBeVisible();
        await this.page.click(this.catalogChartMenu.itemByName(groupby));
    }

    /**
     * Clicks on the save button
     */
    async saveChanges() {
        await this.page.click(this.toolbar.buttonByName('Save'));
        await this.page.click(this.toolbar.saveChanges);
    }

    /**
     * Selects a filter from the toolbar
     *
     * @param {String} filter   name of filter option in filter menu
     */
    async addFiltersFromToolbar(filter){
        await this.clickSelector(this.toolbar.buttonByName('Add Filter'));
        await this.page.click(this.filterMenu.addFilterMenuOption(filter));
        await expect(this.page.locator(this.firstSelectedCheckbox)).toBeVisible();
        let checkboxes = await this.page.$$(this.selectedCheckboxes);
        if (checkboxes.length !== 0){
            for (const box of checkboxes) {
                await box.click();
            }
        }
        await this.page.click(this.okButton);
        await expect(this.page.locator(this.subtitle)).toBeVisible();
    }

    /**
     * Selects a filter by name from toolbar
     *
     * @param {String} name     Name of intended filter to edit
     */
    async editFiltersFromToolbar(name){
        for (let i = 0; i < 100; i++){
            if (await this.page.isHidden(this.grid)){
                await this.clickSelector(this.toolbar.buttonByName('Filters'));
            } else {
                await expect(this.page.locator(this.grid)).toBeVisible();
                break;
            }
        }
        await expect(this.page.locator(this.filterToolbar.byName(name))).toBeVisible();
        await this.page.locator(this.filterToolbar.byName(name)).click();
        await expect(this.page.locator(this.filterToolbar.byName(name))).toBeHidden();
        await this.page.click(this.apply);
        await expect(this.page.locator(this.chart.subtitleName(name))).toBeHidden();
    }

    /**
     * Select filters from toolbar menu and then cancel
     */
    async cancelFiltersFromToolbar() {
        await this.clickLogo();
        await this.page.click(this.toolbar.buttonByName('Filters'));
        await expect(this.page.locator(this.firstCheckbox)).toBeVisible();
        let checkboxes = await this.page.locator(this.checkbox);
        if (checkboxes.length !== 0){
            for (let i = 0; i < 2; i++){
                await checkboxes.nth(i).click();
            }
        }
        await this.page.click(this.cancel);
        await expect(this.page.locator(this.checkbox).length).toEqual(checkboxes.length);
        await this.clickLogo();
    }

    /**
     * Select first data point in chart and open data information
     */
    async openDataSeriesDefinitionFromDataPoint() {
        await this.clickLogo();
        await this.clickFirstDataPoint();

        // Wait for the context menu to be visible.
        await expect(this.page.locator('//div[contains(@class, "x-menu-floating") and contains(@style, "visible")]//span[@class="menu-title"]')).toBeVisible();
        await this.page.locator('//div[contains(@class, "x-menu x-menu-floating") and contains(@style    , "visibility: visible;")]//ul//li/a//span[text()="Edit Dataset"]//ancestor::a').click({clickCount: 2})
        await expect(this.page.locator('//div[contains(@class, "x-window-body")]//div[contains(@class, "x-panel-header")]//span[@class="x-panel-header-text"]')).toBeVisible();
    }

    /**
     * Add a name from filter list from a data series of a data point
     *
     * @param {String} filter   name of category filter from list of filters
     * @param {String} name     name of filter to add
     */
    async addFiltersFromDataSeriesDefinition(filter, name) {
        await this.clickLogo();
        await this.openDataSeriesDefinitionFromDataPoint();

        let addFilter = this.page.locator(this.dataSeriesDef.addFilter())
        await addFilter.isVisible();
        await this.page.screenshot({path: 'add_filter_visible.png'});
        await addFilter.click();
        await this.page.click(this.dataSeriesDef.filter(filter));
        await expect(this.page.locator(this.dataSeriesDef.name(name))).toBeVisible();
        await this.page.click(this.dataSeriesDef.name(name));
        await this.page.click(this.dataSeriesDef.ok);
        await this.page.click(this.dataSeriesDef.addButton);
        await expect(this.page.locator(this.chart.legendContent(name))).toBeVisible();
    }

    /**
     * Edit filter from data series from data point
     *
     * @param {String} name     name of filter from list of filters
     */
    async editFiltersFromDataSeriesDefinition(name){
        await this.clickLogo();
        await this.openDataSeriesDefinitionFromDataPoint();
        await this.page.click(this.dataSeriesDef.filterButton());
        await this.page.click(this.dataSeriesDef.box);
        await this.page.click(this.dataSeriesDef.apply);
        await this.page.click(this.dataSeriesDef.header());
        await this.page.click(this.dataSeriesDef.addButton);
        await this.page.locator(this.chart.legendContent(name)).waitFor({state:'detached'});
    }

    /**
     * Select filter from data series from data point and then cancel
     */
    async cancelFiltersFromDataSeriesDefinition() {
        await this.clickLogo();
        await this.openDataSeriesDefinitionFromDataPoint();
        await this.page.locator(this.dataSeriesDef.filterButton()).waitFor({state:'visible'});
        await this.page.click(this.dataSeriesDef.filterButton());
        await this.page.locator(this.dataSeriesDef.checkbox).waitFor({state:'visible'});
        let checkboxes = await this.page.locator(this.dataSeriesDef.checkbox);
        if (checkboxes.length !== 0) {
            for (let i = 0; i < checkboxes.length; i++) {
                await checkboxes.nth(i).click();
            }
        }
        await this.page.click(this.dataSeriesDef.cancel);
        const checkboxLoc = await this.page.locator(this.dataSeriesDef.checkbox);
        await expect(checkboxLoc.length).toEqual(checkboxes.length);
        await this.page.click(this.dataSeriesDef.header());
        await this.page.click(this.dataSeriesDef.addButton);
    }

    /**
     * Refresh the page
     */
    async clear() {
        await this.page.reload();
        await this.page.isVisible(this.logoutLink);
    }

    /**
     * Helper function to generate a unique title for chart
     *
     * @param {Number} size         length of resulting title
     * @return {String} result      a randomly generated string for a title
     */
    generateTitle(size) {
        let result = '';
        for (let i = 0; i < size; i++) {
            result += this.possible.charAt(Math.floor(Math.random() * this.possible.length));
        }
        return result;
    }

    /**
     * Set the display type to pie for the chart
     */
    async setToPie() {
        await this.page.evaluate("return document.getElementsByName('display_type')[0];").click();
        await this.page.evaluate("return document.querySelector('div.x-layer.x-combo-list[style*=\"visibility: visible\"] div.x-combo-list-inner div.x-combo-list-item:last-child');").click();
    }

    /**
     * Ensure that the error received/displayed is as expected
     */
    async verifyError() {
        const invalidChart = await this.page.evaluate("return document.querySelectorAll('div.x-window.x-window-plain.x-window-dlg[style*=\"visibility: visible\"] span.x-window-header-text')[0];").textContent();
        await expect(invalidChart).toEqual('Invalid Chart Display Type');
        const errorText = await this.page.evaluate("return document.querySelectorAll('div.x-window.x-window-plain.x-window-dlg[style*=\"visibility: visible\"] span.ext-mb-text')[0];").textContent();
        await expect(errorText).toContainText('You cannot display timeseries data in a pie chart.');
        await expect(errorText).toContainText('Please change the dataset or display type.');
    }

    /**
     * Select options and check cursor position to check pressing the up arrow key
     */
    async arrowKeys() {
        await this.page.locator(this.optionsButton).waitFor({state:'visible'}).click();
        await this.page.locator(this.optionsTitle).waitFor({state:'visible'}).click();
        const cursorPosition = await this.page.evaluate('return document.getElementById("me_chart_title").selectionStart;');
        await expect(cursorPosition._status).toEqual(0);
        await expect(cursorPosition.value).toEqual(this.originalTitle.length, 'Cursor Position not at end');
        await this.page.keyboard.press('ArrowUp');
        const newPosition = await this.page.evaluate('return document.getElementById("me_chart_title").selectionStart;');
        await expect(newPosition._status).toEqual(0);
        await expect(newPosition.value).toEqual(0, 'Cursor Position not at begining');
        await this.page.click(this.optionsButton);
    }

    /**
     * Add data from a specificed name under Jobs
     *
     * @param {String} maskName     selector for mask to ensure hidden status
     * @param {String} n            name of type for data to be grouped by
     */
    async addDataViaMenu(maskName, n) {
        await this.page.locator(maskName).isHidden();
        await this.catalogContainerLocator.isVisible();
        await this.page.click(this.addDataButton);
        await this.page.click(this.toolbar.addData('Jobs'));
        await this.page.click(this.toolbar.addDataGroupBy(n));
        await expect(this.page.locator(this.dataSeriesDef.dialogBox)).toBeVisible();
    }

    /**
     * Select add Button from data series
     */
    async addDataSeriesByDefinition() {
        await this.page.click(this.dataSeriesDef.addButton);
        await expect(this.page.locator(this.dataSeriesDef.dialogBox)).toBeHidden();
    }

    /**
     * Load an existing chart based on a given name
     *
     * @param {String} name     title of chart
     */
    async loadExistingChartByName(name) {
        await this.collapseButtonLocator.waitFor({state:'visible'});
        await expect(this.collapseButtonLocator).toBeVisible();
        await this.page.locator(this.toolbar.buttonByName('Load Chart')).waitFor({state:'visible'});
        await expect(this.page.locator(this.toolbar.buttonByName('Load Chart'))).toBeVisible();
        await this.page.click(this.toolbar.buttonByName('Load Chart'), {delay:250});
        await this.page.locator(this.load.dialog).waitFor({state:'visible'});
        await expect(this.page.locator(this.load.dialog)).toBeVisible();
        await this.page.click(this.load.chartByName(name));
        await this.page.locator(this.load.dialog).waitFor({state:'hidden'});
        await expect(this.page.locator(this.load.dialog)).toBeHidden();
        await this.page.locator(this.catalog.expandButton).waitFor({state:'visible', timeout: 10000});
        await expect(this.page.locator(this.catalog.expandButton)).toBeVisible();
    }

    /**
     * Check if the chart matches a title, y-axis, legend, and its validity
     *
     * @param {String} chartTitle       name to match the chart to
     * @param {String} yAxisLabel       name to match the y-axis label to
     * @param {String} legend           name to match the legend to
     * @param {Boolean} isValidChart    determine if the chart is valid or not
     */
    async checkChart(chartTitle, yAxisLabel, legend, isValidChart = true) {
        await this.clickLogo();
        await this.page.locator(this.chart.titleByText(chartTitle)).waitFor({state:'visible'});
        let selToCheck;
        if (isValidChart) {
            selToCheck = this.chart.credits();
        } else {
            selToCheck = this.chart.titleByText(chartTitle, true);
        }
        await this.page.locator(selToCheck).waitFor({state:'visible'});
        await this.page.click(selToCheck);

        if (yAxisLabel) {
            await this.checkChartYAxisLabel(yAxisLabel);
        }

        if (legend) {
            await this.checkChartLegend(legend);
        }
    }

    /**
     * Helper function for checkChart() for the yAxisLabel
     *
     * @param {String} yAxisLabel   name to match the y-axis label to
     */
    async checkChartYAxisLabel(yAxisLabel) {
        await this.page.locator(this.chart.yAxisTitle()).waitFor({state:'visible'});
        const yAxisElems = await this.page.$$(this.chart.yAxisTitle());
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

    /**
     * Helper function for checkChart() for the legend
     *
     * @param {String} legend   name to match the legend to
     */
    async checkChartLegend(legend) {
        await this.page.locator(this.chart.firstLegendContent()).waitFor({state:'visible'});
        const legendElems = await this.page.locator(this.chart.legend());
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

    /**
     * Set the name of the chart from the options menu
     *
     * @param {String} title    name of chart to set to
     */
    async setTitleWithOptionsMenu(title) {
        await this.page.click(this.optionsButton);
        await expect(this.page.locator(this.optionsTitle)).toBeVisible();
        await this.page.fill(this.optionsTitle, '');
        await this.page.fill(this.optionsTitle, title);
    }

    /**
     * Check if the name of the chart matches a given title
     *
     * @param {String} title    name to check if chart matches
     */
    async verifyHighChartsTitle(title) {
        const execReturn = await this.page.evaluate('return Ext.util.Format.htmlDecode(document.querySelector("' + this.chart.title() + '").textContent);');
        await expect(execReturn._status).toEqual(0);
        await expect(typeof(execReturn.value)).toEqual('string');
        await expect(execReturn.value).toEqual(title);
    }

    /**
     * Check if the chart matches the readonly newTitle variable
     */
    async verifyEditChartTitle() {
        await this.page.click(this.chart.title());
        await expect(this.page.isVisible(this.chart.titleInput));
        const titleValue = await this.page.locator(this.chart.titleInput).inputValue();
        await expect(typeof(titleValue)).toEqual('string');
        await expect(titleValue).toEqual(this.newTitle);
        await this.page.click(this.chart.titleOkButton);
    }

    /**
     * Check if the instructions when there is no data matches
     * the instructions helper class
     */
    async verifyInstructions() {
        await this.page.locator(this.newChart.modalDialog.noDataMessage).waitFor({state:'visible'});
        const boo = await instructions(this.page, 'metricExplorer', this.container);
        await expect(boo).toBeTruthy();
    }

    /**
     * Clears and sets the title of the chart
     *
     * @param {String} title    name of chart to set title to
     */
    async setChartTitleViaChart(title) {
        await this.page.click(this.chart.title());
        await expect(this.page.locator(this.chart.titleInput)).isVisible();
        await this.page.clearElement(this.chart.titleInput);
        await this.page.fill(this.chart.titleInput, title);
        await this.page.click(this.chart.titleOkButton);
    }

    /**
     * Sets group by option to resource
     */
    async setGroupByToResource() {
        await this.page.click(this.dataInput);
        await this.page.evaluate("return document.querySelectorAll('div.x-layer.x-combo-list[style*=\"visibility: visible\"] .x-combo-list-item:nth-child(10)')[0];").click();
    }

    /**
     * Swap the axes of the chart
     */
    async axisSwap() {
        let axisFirstChildText = '';
        let axisSecondChildText = '';
        axisFirstChildText = await this.page.locator(this.chart.axisText()).textContent();
        await this.page.click(this.optionsButton);
        await this.page.click(this.optionsSwap);
        axisSecondChildText = await this.page.locator(this.chart.axisText()).textContent();
        await this.page.click(this.optionsButton);
        await expect(axisFirstChildText[1]).not.toEqual(axisSecondChildText[1]);
    }

    /**
     * Update title of chart in options
     */
    async chartTitleInOptionsUpdated() {
        await this.page.click(this.optionsButton);
        await expect(this.page.locator(this.optionsTitle)).toBeVisible();
        const optionsTitle1 = await this.page.locator(this.optionsTitle).textContent();
        await expect(typeof(optionsTitle1)).toEqual('string');
        const optionsTitle2 = await this.page.evaluate(function (text) {
            // For now, EXT will be used
            // eslint-disable-next-line no-undef
            return Ext.util.Format.htmlDecode(text);
        }, optionsTitle1);
        await expect(optionsTitle2.value).toEqual(this.newTitle);
        await this.page.click(this.optionsButton);
    }

    /**
     * Attempt to delete chart
     */
    async attemptDeleteScratchpad() {
        const title = this.page.locator(this.chart.title()).textContent();
        await expect(this.page.locator(this.toolbar.buttonByName('Delete'))).toBeVisible();
        await this.page.click(this.toolbar.buttonByName('Delete'));
        await expect(this.page.locator(this.deleteChart.dialogBox)).toBeVisible();
        await this.page.click(this.deleteChart.buttonByLabel('Yes'));
        await expect(this.page.locator(this.deleteChart.dialogBox)).toBeHidden();
        await this.collapseButtonLocator.waitFor({state:'visible'});
        await this.page.locator(this.toolbar.buttonByName('Load Chart')).waitFor({state:'visible'});
        await this.page.click(this.toolbar.buttonByName('Load Chart'));
        await this.page.locator(this.load.dialog).waitFor({state:'visible'});
        await expect(this.page.locator(this.load.chartByName(title)).waitFor({state:'detached'})).toBeTruthy();
    }

    /**
     * Load a chart based on given chart number
     *
     * @param {Number} chartNumber      chart to load based on number
     */
    async actionLoadChart(chartNumber) {
        await this.page.click(this.load.button());
        await this.page.click(this.load.chartNum(chartNumber));
    }

    /**
     * Add data from "Jobs", grouped by "CPU Hours: Per Job"
     */
    async addDataViaToolbar() {
        await this.page.click(this.addDataButton);
        await this.page.locator(this.toolbar.addDataMenu).waitFor({state:'visible'});
        await this.page.click(this.toolbar.addData('Jobs'));
        await this.page.locator(this.toolbar.groupByMenu).waitFor({state:'visible'});
        await this.page.click(this.toolbar.addDataGroupBy('CPU Hours: Per Job'));
        await this.addDataSeriesByDefinition();
    }

    /**
     * Add data from "CPU Hours: Total" under "Jobs"
     */
    async genericStartingPoint() {
        await this.page.click(this.addDataButton);
        // Click on Jobs (5 on original site)
        await this.page.click(this.buttonMenuFirstLevelChild);
        // click on CPU Hours: Total
        await this.page.click(this.addDataSecondLevelChild);
        await this.addDataSeriesByDefinition();
    }

    /**
     * Check if the chart title was correctly changed based on a given title
     *
     * @param {String} largeTitle   name for chart title to match to
     */
    async confirmChartTitleChange(largeTitle) {
        const titleChange = await this.page.evaluate('return document.querySelector("' + this.chart.title() + '").textContent;');
        await expect(titleChange._status).toEqual(0);
        await expect(typeof(titleChange.value)).toEqual('string');
        await expect(titleChange.value).toEqual(largeTitle);
    }

    /**
     * Switch chart options to aggregate
     */
    async switchToAggregate() {
        await this.page.click(this.optionsButton);
        await this.page.click(this.optionsAggregate);
        await this.clickLogo();
        await expect(this.page.locator(this.optionsAggregate)).toBeHidden();
        await expect(this.page.locator(this.mask)).toBeHidden();
    }

    /**
     * Undo Aggregate option from chart
     *
     * @param {String} container    selector for Metric Explorer page
     */
    async undoAggregateOrTrendLine(container) {
        await this.page.click(this.undo);
        // The mouse stays and causes a hover, lets move the mouse somewhere else
        await this.clickLogo();
    }

    /**
     * Click on the first data point
     */
    async clickFirstDataPoint() {
        const elems = await this.page.locator(this.chart.seriesMarkers(0));
        // Data points are returned in reverse order.
        // for some unknown reason the first point click gets intercepted by the series
        // menu.
        await elems.nth(0).click({force: true});
        // const num = await elems.count();
        // await elems.nth(num - 1).click({force: true});
    }

    /**
     * Best effort to try to wait until the load mask has been and gone.
     * Then click on a given selector
     *
     * @param {String} selector     Selector to wait for and click
     */
    async clickSelector(selector) {
        await expect(this.page.locator(selector)).toBeVisible();
        await this.page.click(selector, {delay:500});
    }

    /**
     * Click on the page's logo
     */
    async clickLogo() {
        await this.clickSelector(this.logo);
    }
}
export default MetricExplorer;
