import {expect} from '@playwright/test';
import XDMoD from './xdmod.page';
import {BasePage} from "./base.page";
import selectors from './usageTab.selectors'

class Usage extends BasePage{
    readonly selectors = selectors;

    readonly legendTextLocator = this.page.locator(selectors.legendText());
    readonly chartLocator = this.page.locator(selectors.chart);
    readonly maskLocator = this.page.locator(selectors.mask);
    readonly durationButtonLocator = this.page.locator(selectors.durationButton());
    readonly durationMenuLocator = this.page.locator(selectors.durationMenu);
    readonly startFieldLocator = this.page.locator(selectors.startField);
    readonly endFieldLocator = this.page.locator(selectors.endField);
    readonly refreshButtonLocator = this.page.locator(selectors.refreshButton);
    readonly availableForReportCheckboxLocator = this.page.locator(selectors.availableForReportCheckbox);

    async checkLegendText(text){
        await expect(this.legendTextLocator).toBeVisible();
        await expect(this.legendTextLocator).toContainText(text);
    }

    /**
     * Select the "Usage" tab by clicking it.
     */
    async selectTab(){
        const xdmod = new XDMoD(this.page, this.page.baseUrl);
        await xdmod.selectTab('tg_usage');
        await expect(this.chartLocator).toBeVisible();
        await expect(this.maskLocator).toBeHidden();
    }

    /**
     * Select a duration from the list of preset options.
     *
     * @param {String} name The name of the duration preset.
     */
    async selectDuration(name){
        await this.durationButtonLocator.click();
        await expect(this.durationMenuLocator).toBeVisible();
        await this.page.locator(selectors.durationMenuItem(name)).click();
        await expect(this.maskLocator).toBeHidden();

        // The chart automatically refreshes after a new duration is
        // selected, but the menu remains open. Clicking the refresh
        // button will close the menu.
        await this.refresh();
    }

    /**
     * Set the start date.
     *
     * @param {String} date Start date.
     */
    async setStartDate(date:string){
        await this.startFieldLocator.fill(date);
    }

    /**
     * Set the end date.
     *
     * @param {String} date End date.
     */
    async setEndDate(date:string){
        await this.endFieldLocator.fill(date);
    }

    /**
     * Refresh current chart by clicking the "Refresh" button.
     */
    async refresh(){
        await this.refreshButtonLocator.click();
        await this.maskLocator.waitFor({state:"detached"});
    }

    /**
     * Make the current chart available for use in the report generator by
     * clicking the "Available for Report" checkbox.
     *
     * Preconditions:
     * - The "Available for Report" checkbox is visible, enabled and not checked.
     *
     * Postconditions:
     * - The "Available for Report" checkbox is checked.
     */
    async makeCurrentChartAvailableForReport(){
        await expect(this.availableForReportCheckboxLocator.isVisible(), '"Available for Report" checkbox is visible').toBeTruthy();
        await expect(this.availableForReportCheckboxLocator.isEnabled(), '"Available for Report" checkbox is enabled').toBeTruthy();
        const checkbox = await this.page.$eval(selectors.availableForReportCheckbox, node => node.checked);
        await expect(checkbox, '"Available for Report" checkbox is not checked').toBeFalsy();
        await this.availableForReportCheckboxLocator.click();
        await expect(this.availableForReportCheckboxLocator.isChecked(), '"Available for Report" checkbox is checked').toBeTruthy();
    }

    /**
     * Remove the current chart from the report generator by clicking the
     * "Available for Report" checkbox.
     *
     * Preconditions:
     * - The "Available for Report" checkbox is visible, enabled and checked.
     *
     * Postconditions:
     * - The "Available for Report" checkbox is not checked.
     */
    async makeCurrentChartUnavailableForReport(){
        await expect(this.availableForReportCheckboxLocator.isVisible(), '"Available for Report" checkbox is visible').toBeTruthy();
        await expect(this.availableForReportCheckboxLocator.isEnabled(), '"Available for Report" checkbox is enabled').toBeTruthy();
        await expect(this.availableForReportCheckboxLocator.isChecked(), '"Available for Report" checkbox is checked').toBeTruthy();
        await this.availableForReportCheckboxLocator.click();
        const checkbox = await this.page.$eval(selectors.availableForReportCheckbox, node => node.checked);
        await expect(checkbox, '"Available for Report" checkbox is not checked').toBeFalsy();
    }

    /**
     * Check if a top-level tree node is expanded.
     *
     * @param {String} name The name of the tree node.
     *
     * @return {Boolean} True if the node is expanded.
     */
    async isTreeNodeExpanded(name){
        const unfoldTreeSelector = selectors.unfoldTreeNodeByName(name);
        const unfoldTreeClass = await this.page.getAttribute(unfoldTreeSelector, 'class');
        return unfoldTreeClass.match(/[$ ]x-tree-node-plus[^ ]/) === null;
    }

    /**
     * Expand a top-level node in the metrics tree by clicking the
     * plus/minus icon.
     *
     * @param {String} name The name of the tree node.
     */
    async expandTreeNode(name){
        await expect(this.isTreeNodeExpanded(name), 'Tree node is collpased').toBeFalsy();
        await this.page.locator(selectors.unfoldTreeNodeByName(name)).click();
    }

    /**
     * Collapse a top-level node in the metrics tree by clicking the
     * plus/minus icon.
     *
     * @param {String} name The name of the tree node.
     */
    async collapseTreeNode(name){
        await expect(this.isTreeNodeExpanded(name), 'Tree node is expanded').toBeTruthy();
        await this.page.locator(selectors.unfoldTreeNodeByName(name)).click();
    }

    /**
     * Select a top-level tree node.
     *
     * @param {String} name The name of the tree node.
     */
    async selectTreeNode(name){
        await this.page.locator(selectors.topTreeNodeByName(name)).click();
        await expect(this.maskLocator).toBeHidden();
    }

    /**
     * Select a child node in the metrics tree by clicking.
     *
     * @param {String} topName The name of the top-level tree node.
     * @param {String} childName The name of the child tree node.
     */
    async selectChildTreeNode(topName, childName){
        const check = await this.isTreeNodeExpanded(topName);
        if (!check){
            await this.expandTreeNode(topName);
        }
        await this.page.locator(selectors.treeNodeByPath(topName, childName)).click();
    }

    /**
     * Check if the menu item element that contains the text in `display` is enabled.
     *
     * @param display
     * @returns {boolean}
     */
    async toolbarMenuItemIsEnabled(display){
        const item = selectors.displayMenuItemByText(display);
        await this.page.locator(item).isVisible();
        const itemClass = await this.page.getAttribute(item, 'class');
        const itemIsDisabled = itemClass.includes('x-item-disabled');
        return !(itemIsDisabled);
    }
}
export default Usage;
