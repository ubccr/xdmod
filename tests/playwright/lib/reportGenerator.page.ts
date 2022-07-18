/**
 * Report generator test classes.
 */
import artifacts from "../tests/helpers/artifacts";
const expected = artifacts.getArtifact('reportGenerator');
/**
 * Helper function for creating XPath expression predicates to accurately
 * determine if an element has a class.
 *
 * This prevents incorrect matching of class names where the desired class name
 * is a substring of other class names.
 *
 * @param {String} className CSS class name.
 *
 * @return {String} XPath expression predicate.
 */
function classContains(className) {
    return `contains(concat(" ",normalize-space(@class)," ")," ${className} ")`;
}

import XDMoD from './xdmod.page';
import {expect, Locator, Page} from '@playwright/test';

/**
 * A single row in the list of reports.
 */
export class MyReportsRow {
    readonly page: Page;
    readonly name: string;
    readonly derivedFrom: string;
    readonly schedule: string;
    readonly deliveryFormat: string;
    readonly numberOfCharts: string;
    readonly numberOfChartsPerPage: string;
    readonly selector:string;

    /**
     * @param {String} selector XPath selector for a "My Reports" row.
     */
    constructor(selector: string, page: Page) {
        this.page = page;
        this.selector = selector;
        this.name= selector + '//tr/td[position()=2]//div';
        this.derivedFrom= selector + '//tr/td[position()=3]//div';
        this.schedule= selector + '//tr/td[position()=4]//div';
        this.deliveryFormat= selector + '//tr/td[position()=5]//div[position()=2]';
        this.numberOfCharts = selector + '//tr/td[position()=6]//div';
        this.numberOfChartsPerPage = selector + '//tr/td[position()=6]//div/span'
    }

    /**
     * Get the name of the chart.
     *
     * @return {String}
     */
    async getName() {
        return this.page.textContent(this.name);
    }

    /**
     * Get the name of the template this report is derived from or "Manual" if
     * the report was created manually..
     *
     * @return {String}
     */
    async getDerivedFrom() {
        return this.page.textContent(this.derivedFrom);
    }

    /**
     * Get the schedule (frequency) of the report.
     *
     * @return {String}
     */
    async getSchedule() {
        return this.page.textContent(this.schedule);
    }

    /**
     * Get the delivery format (PDF or Word Document) of the report.
     *
     * @return {String}
     */
    async getDeliveryFormat() {
        return this.page.textContent(this.deliveryFormat);
    }

    /**
     * Get the number of charts in the report.
     *
     * @return {Number}
     */
    async getNumberOfCharts() {
        const text = await this.page.textContent(this.numberOfCharts);
        return parseInt(text.trim(), 10);
    }

    /**
     * Get the number of charts per page in the report.
     *
     * @return {Number}
     */
    async getNumberOfChartsPerPage() {
        const chartsPerPage = await this.page.textContent(this.numberOfChartsPerPage);
        const matches = chartsPerPage.match(/\((\d+) per page\)/);
        if (matches === null) {
            throw new Error(`Failed to determine number of charts from text "${chartsPerPage}"`);
        }
        return parseInt(matches[1], 10);
    }

    /**
     * Check if the row is selected.
     *
     * @return {Boolean}
     */
    async isSelected() {
        const att = await this.page.getAttribute(this.selector, 'class');
        return att.match(/(^| )x-grid3-row-selected($| )/) !== null;
    }

    /**
     * Click the row.
     */
    async click() {
        await this.page.click(this.selector);
    }

    /**
     * Double click the row.
     */
    async doubleClick() {
        await this.page.dblclick(this.selector);
    }

    /**
     * Toggle the row selection using Control+Click.
     */
    async toggleSelection() {
        await this.page.keyboard.press('Control');
        await this.page.click(this.selector);
        await this.page.keyboard.up('Control');
    }
}

/**
 * A chart in the "Available Charts" list.
 */
export class AvailableChart {
    readonly page:Page;
    readonly selector:string;
    readonly titleAndDrillDetails:string;
    readonly dateDescription:string;
    readonly timeframeType:string;

    /**
     * @param {String} selector XPath selector for an "Available Chart".
     */
    constructor(selector, page) {
        this.page = page;
        this.selector = selector;
        const baseSelector = selector + '//tr/td[position()=2]/div/div';
        this.titleAndDrillDetails = baseSelector + '/div[position()=4]/span';
        this.dateDescription = baseSelector + '/div[position()=5]';
        this.timeframeType = baseSelector + '/div[position()=6]';
    }

    /**
     * Get the combined title and drill-down details of the chart.
     *
     * Contains "<br>" between the title and drill details.
     *
     * @return {String}
     */
    async getTitleAndDrillDetails() {
        return this.page.innerHTML(this.titleAndDrillDetails);
    }

    /**
     * Get the title of the chart.
     *
     * @return {String}
     */
    async getTitle() {
        const first = await this.getTitleAndDrillDetails();
        return first.split('<br>')[0].trim();
    }

    /**
     * Get the drill-down details of the chart.
     *
     * @return {String}
     */
    async getDrillDetails() {
        const first = await this.getTitleAndDrillDetails();
        const drillDetails = first.split('<br>')[1].trim();
        return drillDetails === '&nbsp;' ? '' : drillDetails;
    }

    /**
     * Get the date description of the chart.
     *
     * @return {String}
     */
    async getDateDescription() {
        return this.page.locator(this.dateDescription).textContent();
    }

    /**
     * Get the timeframe of the chart.
     *
     * @return {String}
     */
    async getTimeframeType() {
        return this.page.locator(this.timeframeType).textContent();
    }

    /**
     * Check if the chart is selected.
     *
     * @return {Boolean}
     */
    async isSelected() {
        return this.page.getAttribute(this.selector, 'class').match(/(^| )x-grid3-row-selected($| )/) !== null;
    }

    /**
     * Click the chart.
     */
    async click() {
        await this.page.click(this.selector);
    }

    /**
     * Toggle the chart selection using Control+Click.
     */
    async toggleSelection() {
        await this.page.keyboard.press('Control');
        await this.page.click(this.selector);
        await this.page.keyboard.up('Control');
    }
}

/**
 * A chart in the "Included Charts" list.
 */
export class IncludedChart {

    readonly page:Page;
    readonly selector: string;
    readonly titleAndDrillDetails: string;
    readonly dateDescription:string;
    readonly timeframeEditIcon:string;
    readonly timeframeType:string;
    readonly timeframeResetIcon:string;

    /**
     * @param {String} selector XPath selector for an "Included Chart".
     */
    constructor(selector, page) {
        this.page = page;
        this.selector = selector;
        const baseSelector = selector + '//tr/td[position()=2]/div/div';
        this.titleAndDrillDetails = baseSelector + '/div[position()=4]/span';
        this.dateDescription = baseSelector + '/div[position()=6]';
        this.timeframeEditIcon =  baseSelector + '/div[position()=5]/a[position()=1]';
        this.timeframeType = baseSelector + '/div[position()=7]/span';
        this.timeframeResetIcon = baseSelector + '/div[position()=5]/a[position()=2]'
    }

    /**
     * Get the combined title and drill-down details of the chart.
     *
     * Contains "<br>" between the title and drill details.
     *
     * @return {String}
     */
    async getTitleAndDrillDetails() {
        return this.page.innerHTML(this.titleAndDrillDetails, false);
    }

    /**
     * Get the title of the chart.
     *
     * @return {String}
     */
    async getTitle() {
        const first = await this.getTitleAndDrillDetails();
        return first.split('<br>')[0].trim();
    }

    /**
     * Get the drill-down details of the chart.
     *
     * @return {String}
     */
    async getDrillDetails() {
        const first = await this.getTitleAndDrillDetails();
        const drillDetails = first.split('<br>')[1].trim();
        return drillDetails === '&nbsp;' ? '' : drillDetails;
    }

    /**
     * Get the date description of the chart.
     *
     * @return {String}
     */
    async getDateDescription() {
        return this.page.locator(this.dateDescription).textContent();
    }

    /**
     * Get the timeframe of the chart.
     *
     * @return {String}
     */
    async getTimeframeType() {
        return this.page.locator(this.timeframeType).textContent();
    }

    /**
     * Check if the row is selected.
     *
     * @return {Boolean}
     */
    async isSelected() {
        return this.page.getAttribute(this.selector, 'class').match(/(^| )x-grid3-row-selected($| )/) !== null;
    }

    /**
     * Click the chart.
     */
    async click() {
        await this.page.click(this.selector);
    }

    /**
     * Click the date range to edit timeframe.
     */
    async editTimeframe() {
        await this.page.click(this.timeframeEditIcon);
    }

    /**
     * Click the timeframe reset icon.
     */
    async resetTimeframe() {
        await this.page.click(this.timeframeResetIcon);
    }

    /**
     * Toggle the chart selection using Control+Click.
     */
    async toggleSelection() {
        await this.page.keyboard.press('Control');
        await this.page.click(this.selector);
        await this.page.keyboard.up('Control');
    }
}

/**
 * Report generator page.
 */
export class ReportGenerator {
    readonly page:Page;
    readonly xdmod:XDMoD;
    readonly tabName:string;
    readonly selectors = {
        tab: () => `//div[${classContains('x-tab-panel-header')}]//span[${classContains('x-tab-strip-text')} and text()="${this.tabName}"]`,
        panel: () => '//div[@id="report_generator"]',
        mask: () => `//div[${classContains('ext-el-mask')}]`,
        myReports: {
            panel: () => this.selectors.panel() + `//div[${classContains('report_overview')}]`,
            toolbar: {
                panel: () => this.selectors.myReports.panel() + `//div[${classContains('x-panel-tbar')}]`,
                selectButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="Select"]',
                selectMenu: () => `//div[${classContains('x-menu-floating')}]`,
                selectAllReportsButton: () => this.selectors.myReports.toolbar.selectMenu() + '//span[text()="All Reports"]/ancestor::a',
                selectNoReportsButton: () => this.selectors.myReports.toolbar.selectMenu() + '//span[text()="No Reports"]/ancestor::a',
                invertSelectionButton: () => this.selectors.myReports.toolbar.selectMenu() + '//span[text()="Invert Selection"]/ancestor::a',
                newButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="New"]',
                newBasedOnButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="New Based On"]',
                newBasedOnMenu: () => `//div[${classContains('x-menu-floating')} and .//img[${classContains('btn_selected_report')} or ${classContains('btn_report_template')}]]`,
                newBasedOnRows: () => this.selectors.myReports.toolbar.newBasedOnMenu() + `//li[not(${classContains('x-menu-sep-li')})]`,
                newBasedOnTemplateRows: () => this.selectors.myReports.toolbar.newBasedOnMenu() + `//li[.//img[${classContains('btn_report_template')}]]`,
                newBasedOnReportRows: () => this.selectors.myReports.toolbar.newBasedOnMenu() + `//li[.//img[${classContains('btn_selected_report')}]]`,
                newBasedOnTemplate: name => this.selectors.myReports.toolbar.newBasedOnTemplateRows() + `//a[.//b[text()="${name}"]]`,
                newBasedOnTemplateWithCenter: center => `//div[${classContains('x-menu-floating')}]//a[.//img[${classContains('btn_resource_provider')}] and .//span[contains(text(), "${center}")]]`,
                newBasedOnReport: name => this.selectors.myReports.toolbar.newBasedOnReportRows() + `//a[./img[.//b[text()="${name}"]]`,
                editButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="Edit"]',
                previewButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="Preview"]',
                sendNowButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="Send Now"]',
                sendNowAsPdfButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As PDF"]/ancestor::a`,
                sendNowAsWordDocumentButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As Word Document"]/ancestor::a`,
                downloadButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="Download"]',
                downloadAsPdfButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As PDF"]/ancestor::a`,
                downloadAsWordDocumentButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As Word Document"]/ancestor::a`,
                deleteButton: () => this.selectors.myReports.toolbar.panel() + '//button[text()="Delete"]'
            },
            reportList: {
                panel: () => this.selectors.myReports.panel() + `//div[${classContains('x-panel-body-noheader')}]`,
                rows: () => this.selectors.myReports.reportList.panel() + `//div[${classContains('x-grid3-row')}]`,
                rowByIndex: index => this.selectors.myReports.reportList.panel() + `//div[${classContains('x-grid3-row')} and position()=${index}]`
            }
        },
        reportPreview: {
            panel: () => this.selectors.panel() + `//div[${classContains('report_preview')}]`,
            toolbar: {
                panel: () => this.selectors.reportPreview.panel() + `//div[${classContains('x-panel-tbar')}]`,
                sendNowButton: () => this.selectors.reportPreview.toolbar.panel() + '//button[text()="Send Now"]',
                downloadButton: () => this.selectors.reportPreview.toolbar.panel() + '//button[text()="Download"]',
                returnToReportsOverviewButton: () => this.selectors.reportPreview.toolbar.panel() + `//button[${classContains('btn_return_to_previous')}]`
            }
        },
        reportEditor: {
            panel: () => this.selectors.panel() + `//div[${classContains('report_edit')}]`,
            toolbar: {
                panel: () => this.selectors.reportEditor.panel() + `//div[${classContains('x-panel-tbar')} and .//button[text()="Save"]]`,
                saveButton: () => this.selectors.reportEditor.toolbar.panel() + '//button[text()="Save"]',
                saveAsButton: () => this.selectors.reportEditor.toolbar.panel() + '//button[text()="Save As"]',
                previewButton: () => this.selectors.reportEditor.toolbar.panel() + '//button[text()="Preview"]',
                sendNowButton: () => this.selectors.reportEditor.toolbar.panel() + '//button[text()="Send Now"]',
                downloadButton: () => this.selectors.reportEditor.toolbar.panel() + '//button[text()="Download"]',
                returnToMyReportsButton: () => this.selectors.reportEditor.toolbar.panel() + `//button[${classContains('btn_return_to_overview')}]`
            },
            generalInformation: {
                panel: () => this.selectors.reportEditor.panel() + `//div[${classContains('x-panel')} and .//span[text()="General Information"]]`,
                reportNameInput: () => this.selectors.reportEditor.generalInformation.panel() + '//input[@name="report_name"]',
                reportTitleInput: () => this.selectors.reportEditor.generalInformation.panel() + '//input[@name="report_title"]',
                headerTextInput: () => this.selectors.reportEditor.generalInformation.panel() + '//input[@name="report_header"]',
                footerTextInput: () => this.selectors.reportEditor.generalInformation.panel() + '//input[@name="report_footer"]'
            },
            chartLayout: {
                panel: () => this.selectors.reportEditor.panel() + `//div[${classContains('x-panel')} and .//span[text()="Chart Layout"]]`,
                oneChartPerPageRadioButton: () => this.selectors.reportEditor.chartLayout.panel() + '//input[@value="1_up"]',
                twoChartsPerPageRadioButton: () => this.selectors.reportEditor.chartLayout.panel() + '//input[@value="2_up"]'
            },
            scheduling: {
                panel: () => this.selectors.reportEditor.panel() + `//div[${classContains('x-panel')} and .//span[text()="Scheduling"]]`,
                scheduleInput: () => this.selectors.reportEditor.scheduling.panel() + '//input[@name="report_generator_report_schedule"]',
                scheduleOption: name => `//div[${classContains('x-combo-list-item')} and text()="${name}"]`,
                deliveryFormatInput: () => this.selectors.reportEditor.scheduling.panel() + '//div[./label[text()="Delivery Format:"]]//input',
                deliveryFormatOption: name => `//div[${classContains('x-combo-list-item')} and text()="${name}"]`
            },
            includedCharts: {
                panel: () => this.selectors.reportEditor.panel() + '//div[@id="ReportCreatorGrid"]',
                toolbar: {
                    panel: () => this.selectors.reportEditor.includedCharts.panel() + `//div[${classContains('x-panel-tbar')}]`,
                    selectButton: () => this.selectors.reportEditor.includedCharts.toolbar.panel() + '//button[text()="Select"]',
                    selectAllChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="All Charts"]/ancestor::a`,
                    selectNoChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="No Charts"]/ancestor::a`,
                    invertSelectionButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="Invert Selection"]/ancestor::a`,
                    editTimeframeButton: () => this.selectors.reportEditor.includedCharts.toolbar.panel() + '//button[text()="Edit Timeframe of Selected Charts"]',
                    removeButton: () => this.selectors.reportEditor.includedCharts.toolbar.panel() + '//button[text()="Remove"]'
                },
                chartList: {
                    panel: () => this.selectors.reportEditor.includedCharts.panel() + '//div[@class="x-panel-body" and .//div[text()="Chart"]]',
                    rows: () => this.selectors.reportEditor.includedCharts.chartList.panel() + `//div[${classContains('x-grid3-row')}]`
                }
            }
        },
        availableCharts: {
            panel: () => this.selectors.panel() + '//div[@id="chart_pool_panel"]',
            toolbar: {
                panel: () => this.selectors.availableCharts.panel() + '//div[@class="x-panel-tbar"]',
                selectButton: () => this.selectors.availableCharts.toolbar.panel() + '//button[text()="Select"]',
                selectAllChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="All Charts"]/ancestor::a`,
                selectNoChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="No Charts"]/ancestor::a`,
                invertSelectionButton: () => `//div[${classContains('x-menu-floating')}]//a[.//span[text()="Invert Selection"]]`,
                deleteButton: () => this.selectors.availableCharts.toolbar.panel() + '//button[text()="Delete"]'
            },
            chartList: {
                panel: () => this.selectors.availableCharts.panel() + '//div[@class="x-panel-body" and .//div[text()="Chart"]]',
                rows: () => this.selectors.availableCharts.chartList.panel() + `//div[${classContains('x-grid3-row')}]`
            }
        },
        message: {
            window: () => '//div[@id="report_generator_message"]',
            titleElement: () => this.selectors.message.window() + `//span[${classContains('x-window-header-text')}]`,
            textElement: () => this.selectors.message.window() + '//b'
        },
        deleteSelectedReports: {
            window: () => `//div[${classContains('x-window')} and .//span[text()="Delete Selected Report" or text()="Delete Selected Reports"]]`,
            yesButton: () => this.selectors.deleteSelectedReports.window() + '//button[text()="Yes"]',
            noButton: () => this.selectors.deleteSelectedReports.window() + '//button[text()="No"]'
        },
        unsavedChanges: {
            window: () => `//div[${classContains('x-window')} and .//span[text()="Unsaved Changes"]]`,
            yesButton: () => this.selectors.unsavedChanges.window() + '//button[text()="Yes"]',
            noButton: () => this.selectors.unsavedChanges.window() + '//button[text()="No"]',
            cancelButton: () => this.selectors.unsavedChanges.window() + '//button[text()="Cancel"]'
        },
        deleteSelectedCharts: {
            window: () => `//div[${classContains('x-window')} and .//span[text()="Delete Selected Chart" or text()="Delete Selected Charts"]]`,
            yesButton: () => this.selectors.deleteSelectedCharts.window() + '//button[text()="Yes"]',
            noButton: () => this.selectors.deleteSelectedCharts.window() + '//button[text()="No"]'
        },
        removeSelectedCharts: {
            window: () => `//div[${classContains('x-window')} and .//span[text()="Remove Selected Chart" or text()="Remove Selected Charts"]]`,
            yesButton: () => this.selectors.removeSelectedCharts.window() + '//button[text()="Yes"]',
            noButton: () => this.selectors.removeSelectedCharts.window() + '//button[text()="No"]'
        },
        saveReportAs: {
            window: () => `//div[${classContains('x-window')} and .//span[text()="Save Report As"]]`,
            reportNameInput: () => this.selectors.saveReportAs.window() + '//input[@name="report_name"]',
            saveButton: () => this.selectors.saveReportAs.window() + '//button[text()="Save"]',
            closeButton: () => this.selectors.saveReportAs.window() + '//button[text()="Close"]',
            reportNameInputInvalid: () => this.selectors.saveReportAs.window() + `//input[@name="report_name" and ${classContains('x-form-invalid')}]`
        },
        reportBuilt: {
            window: () => `//div[${classContains('x-window')} and .//span[text()="Report Built"]]`,
            viewReportButton: () => this.selectors.reportBuilt.window() + '//button[text()="View Report"]',
            closeButton: () => this.selectors.reportBuilt.window() + `//div[${classContains('x-tool-close')}]`
        },
        editChartTimeframe: {
            window: () => `//div[${classContains('chart_date_editor')}]`,
            specificRadioButton: () => this.selectors.editChartTimeframe.window() + '//input[@name="report_creator_chart_entry" and @value="Specific"]',
            periodicRadioButton: () => this.selectors.editChartTimeframe.window() + '//input[@name="report_creator_chart_entry" and @value="Periodic"]',
            periodicInput: () => this.selectors.editChartTimeframe.window() + '//table[contains(@class,"menu")]//button',
            periodicOption: name => `//div[${classContains('x-menu-floating')}]//a[starts-with(text(),"${name}')]`,
            startDateInput: () => this.selectors.editChartTimeframe.window() + '//input[@id="report_generator_edit_date_start_date_field"]',
            endDateInput: () => this.selectors.editChartTimeframe.window() + '//input[@id="report_generator_edit_date_end_date_field"]',
            updateButton: () => this.selectors.editChartTimeframe.window() + `//button[${classContains('chart_date_editor_update_button')}]`,
            cancelButton: () => this.selectors.editChartTimeframe.window() + `//button[${classContains('chart_date_editor_cancel_button')}]`,
            errorMessage: () => this.selectors.editChartTimeframe.window() + `//div[${classContains('overlay_message')}]`
        },
        // The mask with the check mark image that is displayed after a report is ready for download or has been emailed.
        checkmarkMask: () => `//div[${classContains('ext-el-mask-msg')} and .//img[@src="gui/images/checkmark.png"]]`
    };

    constructor(page:Page) {
        const xdmod = new XDMoD(page, page.baseUrl);
        this.page = page;
        this.xdmod = xdmod;
        this.tabName = 'Report Generator';
    }

    /**
     * Determine if the report generator page is enabled.
     *
     * The report generator is considered to be enabled if the report
     * generator tab exists.
     *
     * @return {Boolean} True if the report generator page is enabled.
     */
    async isEnabled() {
        return this.page.isVisible(this.selectors.tab());
    }

    /**
     * Check if the "New Based On" button in the "My Reports" toolbar is
     * enabled.
     *
     * There are two separate "New Based On" buttons.  Only one should be
     * visible at a time.  This method returns true if the visible button is
     * enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    async isNewBasedOnEnabled() {
        const visibleButtons = await this.page.$$(this.selectors.myReports.toolbar.newBasedOnButton() + `/ancestor::table[${classContains('x-btn')}]`);
        await expect(visibleButtons.length, 'Two "New Based On" button are present').toEqual(2);
        const first = '(' + this.selectors.myReports.toolbar.newBasedOnButton() + `/ancestor::table[${classContains('x-btn')}]` + ')[1]';
        const hold = await this.page.getAttribute(first, 'class');
        const boo = hold.match(/(^| )x-item-disabled($| )/) === null;
        return boo;
    }

    /**
     * Check if the "Edit" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    async isEditSelectedReportsEnabled() {
        const att = await this.page.getAttribute(this.selectors.myReports.toolbar.editButton() + `/ancestor::table[${classContains('x-btn')}]`, 'class');
        const result = att.match(/(^| )x-item-disabled($| )/) === null;
        return result;
    }

    /**
     * Check if the "Preview" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    async isPreviewSelectedReportsEnabled() {
        const att = await this.page.getAttribute(this.selectors.myReports.toolbar.previewButton() + `/ancestor::table[${classContains('x-btn')}]`, 'class');
        const result = att.match(/(^| )x-item-disabled($| )/) === null;
        return result;
    }

    /**
     * Check if the "Send Now" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    async isSendSelectedReportsEnabled() {
        // There are two separate "Send Now" buttons in the "My Reports" panel.
        // Only one should be visible at a time.
        const visibleButtons = await this.page.$$(this.selectors.myReports.toolbar.sendNowButton() + `/ancestor::table[${classContains('x-btn')}]`);
        await expect(visibleButtons.length, 'Two "Send Now" button are present').toEqual(2);
        const first = '(' + this.selectors.myReports.toolbar.sendNowButton() + `/ancestor::table[${classContains('x-btn')}]` + ')[1]';
        const hold = await this.page.getAttribute(first, 'class');
        return hold.match(/(^| )x-item-disabled($| )/) === null;
    }

    /**
     * Check if the "Download" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    async isDownloadSelectedReportsEnabled() {
        const visibleButtons = await this.page.$$(this.selectors.myReports.toolbar.downloadButton() + `/ancestor::table[${classContains('x-btn')}]`);
        await expect(visibleButtons.length, 'Two "New Based On" button are present').toEqual(2);
        const first = '(' + this.selectors.myReports.toolbar.downloadButton() + `/ancestor::table[${classContains('x-btn')}]` + ')[1]';
        const hold = await this.page.getAttribute(first, 'class');
        return hold.match(/(^| )x-item-disabled($| )/) === null;
    }

    /**
     * Check if the "Delete" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    async isDeleteSelectedReportsEnabled() {
        const att = await this.page.getAttribute(this.selectors.myReports.toolbar.deleteButton() + `/ancestor::table[${classContains('x-btn')}]`, 'class');
        const result = att.match(/(^| )x-item-disabled($| )/) === null;
        return result;
    }

    /**
     * Wait for the "My Reports" panel to be visible.
     */
    async waitForMyReportsPanelVisible() {
        await this.page.isVisible(this.selectors.myReports.panel());
    }

    /**
     * Wait for the "Report Preview" panel to be visible.
     */
    async waitForReportPreviewPanelVisible() {
        await this.page.isVisible(this.selectors.reportPreview.panel());
    }

    /**
     * Wait for the "Report Editor" panel to be visible.
     */
    async waitForReportEditorPanelVisible() {
        await this.page.isVisible(this.selectors.reportEditor.panel());
    }

    /**
     * Wait for the "Included Charts" panel to be visible.
     */
    async waitForIncludedChartsPanelVisible() {
        await this.waitForReportEditorPanelVisible();
        await this.page.isVisible(this.selectors.reportEditor.includedCharts.panel());
    }

    /**
     * Wait for the "Available Charts" panel to be visible.
     */
    async waitForAvailableChartsPanelVisible() {
        await this.page.isVisible(this.selectors.availableCharts.panel());
    }

    /**
     * Wait for the "Message" window to be visible.
     */
    async waitForMessageWindowVisible() {
        await this.page.isVisible(this.selectors.message.window());
    }

    /**
     * Wait for the "Delete Selected Report" window to be visible.
     */
    async waitForDeleteSelectedReportsWindowVisible() {
        await this.page.isVisible(this.selectors.deleteSelectedReports.window());
    }

    /**
     * Wait for the "Delete Selected Report" window to not be visible.
     */
    async waitForDeleteSelectedReportsWindowNotVisible() {
        await this.page.isHidden(this.selectors.deleteSelectedReports.window());
    }

    /**
     * Wait for the "Delete Selected Chart" window to be visible.
     */
    async waitForDeleteSelectedChartsWindowVisible() {
        await this.page.isVisible(this.selectors.deleteSelectedCharts.window());
    }

    /**
     * Wait for the "Delete Selected Chart" window to not be visible.
     */
    async waitForDeleteSelectedChartsWindowNotVisible() {
        await this.page.isHidden(this.selectors.deleteSelectedCharts.window());
    }

    /**
     * Wait for the "Remove Selected Chart" window to be visible.
     */
    async waitForRemoveSelectedChartsWindowVisible() {
        await this.page.isVisible(this.selectors.removeSelectedCharts.window());
    }

    /**
     * Wait for the "Remove Selected Chart" window to not be visible.
     */
    async waitForRemoveSelectedChartsWindowNotVisible() {
        await this.page.isHidden(this.selectors.removeSelectedCharts.window());
    }

    /**
     * Wait for the "Edit Chart Timeframe" window to be visible.
     *
     * Also waits for the error message to not be visible.
     */
    async waitForEditChartTimeframeWindowVisible() {
        await this.page.isVisible(this.selectors.editChartTimeframe.window());
        await this.page.isHidden(this.selectors.editChartTimeframe.errorMessage());
    }

    /**
     * Wait for the "Save Report As" window to be visible.
     */
    async waitForSaveReportAsWindowVisible() {
        await this.page.isVisible(this.selectors.saveReportAs.window());
    }

    /**
     * Wait for the "Report Built" window to be visible.
     */
    async waitForReportBuiltWindowVisible() {
        await this.page.isVisible(this.selectors.reportBuilt.window());
    }

    /**
     * Wait for the "Report Built" window to not be visible.
     */
    async waitForReportBuiltWindowNotVisible() {
        await this.page.isHidden(this.selectors.reportBuilt.window());
    }

    /**
     * Convenience method to convert the rows in the "My Reports" list into
     * objects.
     *
     * The list of reports must be visible or this method will throw an
     * exception.
     *
     * @return {MyReportsRow[]}
     */
    async getMyReportsRows() {
        await this.waitForMyReportsPanelVisible();
        const selector = this.selectors.myReports.reportList.rows();
        const computed = await this.page.$$(selector);
        const result = await Promise.all(computed.map(async (element, i) => new MyReportsRow(`(${selector})[${i + 1}]`, this.page)));
        return result;
    }

    /**
     * Convenience method to convert the charts in the "Included Charts" list
     * into objects.
     *
     * The list of included charts must be visible or this method will throw an
     * exception.
     *
     * @return {IncludedChart[]}
     */
    async getIncludedCharts() {
        await this.waitForIncludedChartsPanelVisible();
        const selector = this.selectors.reportEditor.includedCharts.chartList.rows();
        const computed = await this.page.$$(selector);
        const result = await Promise.all(computed.map((element, i) => new IncludedChart(`(${selector})[${i + 1}]`, this.page)));
        return result;
    }

    /**
     * Convenience method to convert the charts in the "Available Charts" list
     * into objects.
     *
     * The list of available charts must be visible or this method will throw an
     * exception.
     *
     * @return {IncludedChart[]}
     */
    async getAvailableCharts() {
        await this.waitForAvailableChartsPanelVisible();
        const selector = this.selectors.availableCharts.chartList.rows();
        var elemCount = this.page.$(selector).length;
        var lastCount = -1;
        while (elemCount !== lastCount) {
            lastCount = elemCount;
            elemCount = this.page.$(selector).length;
        }
        const computed = await this.page.$$(selector);
        const result = await Promise.all(computed.map((element, i) => new AvailableChart(`(${selector})[${i + 1}]`, this.page)));
        return result;
    }

    /**
     * Get the title of the "Message" window.
     *
     * @return {String}
     */
    async getMessageWindowTitle() {
        await this.waitForMessageWindowVisible();
        const thing = await this.page.locator(this.selectors.message.titleElement()).textContent();
        return this.page.locator(this.selectors.message.titleElement()).textContent();
    }

    /**
     * Get the message text of the "Message" window.
     *
     * @return {String}
     */
    async getMessage() {
        await this.waitForMessageWindowVisible();
        return this.page.locator(this.selectors.message.textElement()).textContent();
    }

    /**
     * Select the "Report Generator" tab by clicking it.
     */
    async selectTab() {
        await this.xdmod.selectTab('report_generator');
    }

    /**
     * Select a report in the "My Reports" panel by clicking the row in the list
     * of reports.
     *
     * @param {String} reportName The name of the report.
     */
    async selectReportByName(reportName) {
        await this.waitForMyReportsPanelVisible();

        let foundReport = false;

        const rows = await this.getMyReportsRows();
        for (const row of rows){
            if (await row.getName() === reportName){
                await row.click();
                foundReport = true;
            }
        }

        if (!foundReport) {
            throw new Error(`No report named "${reportName}" found`);
        }
    }

    /**
     * Select all reports in the "My Reports" panel by clicking the "Select"
     * button then clicking "All Reports".
     */
    async selectAllReports() {
        await this.waitForMyReportsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.selectButton());
        await this.page.isVisible(this.selectors.myReports.toolbar.selectAllReportsButton());
        await this.page.click(this.selectors.myReports.toolbar.selectAllReportsButton());
        await this.page.isHidden(this.selectors.myReports.toolbar.selectMenu());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Deselect all reports in the "My Reports" panel by clicking the "Select"
     * button then clicking "No Reports".
     */
    async deselectAllReports() {
        await this.waitForMyReportsPanelVisible();
        console.log(this.selectors.myReports.toolbar.selectButton());
        console.log(this.selectors.myReports.toolbar.selectNoReportsButton());
        console.log('(' + this.selectors.myReports.toolbar.selectMenu() + ')[5]');
        await this.page.click(this.selectors.myReports.toolbar.selectButton());
        await this.page.locator(this.selectors.myReports.toolbar.selectNoReportsButton()).waitFor({state:'visible'});
        await this.page.click(this.selectors.myReports.toolbar.selectNoReportsButton());
        await this.page.locator('(' + this.selectors.myReports.toolbar.selectMenu() + ')[5]').waitFor({state:'hidden'});
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Invert the report selection in the "My Reports" panel by clicking the
     * "Select" button then clicking "Invert Selection".
     */
    async invertReportSelection() {
        await this.waitForMyReportsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.selectButton());
        await this.page.isVisible(this.selectors.myReports.toolbar.invertSelectionButton());
        // Multiple buttons match the "Invert Selection" selector, but only one should be visible.
        const buttons = await this.page.$$(this.selectors.myReports.toolbar.invertSelectionButton());
        const visibleButtons = await Promise.all(buttons.filter((button) => {
            return button.isVisible();
        }));
        await expect(visibleButtons.length, 'One "Invert Selection" button is visible').toEqual(1);
        await visibleButtons[0].click();
        await this.page.isHidden(this.selectors.myReports.toolbar.selectMenu());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Create a new report from the "My Reports" panel by clicking the "New"
     * button.
     */
    async createNewReport() {
        await this.waitForMyReportsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.newButton());
    }

    /**
     * Click the "New Based On" button.
     *
     * Must click the name of the report template separately.
     */
    async clickNewBasedOn() {
        await this.waitForMyReportsPanelVisible();
        const tempMaskLocator = this.page.locator('//div[contains(@class, "ext-el-mask-msg") and contains(., "Loading...")]');
        const maskHolder = await tempMaskLocator.isVisible();
        if (maskHolder){
            await tempMaskLocator.waitFor({state:"detached"});
        }
        // There are two separate "New Based On" buttons.  Only one should be
        // visible at a time.
        const buttons = await this.page.$$(this.selectors.myReports.toolbar.newBasedOnButton() + `/ancestor::table[${classContains('x-btn')}]`);
        /*const visibleButtons = await Promise.all(buttons.filter((button) => {
            return button.isVisible();
        }));*/
        await expect(buttons.length, 'Two "New Based On" button are present').toEqual(2);
        const button1 = await this.page.locator('(' + this.selectors.myReports.toolbar.newBasedOnButton() + `/ancestor::table[${classContains('x-btn')}]` + ')[1]');
        const button2 = await this.page.locator('(' + this.selectors.myReports.toolbar.newBasedOnButton() + `/ancestor::table[${classContains('x-btn')}]` + ')[2]');
        if (await button1.isVisible()) {
            await button1.click();
        } else if (await button2.isVisible()){
            await button2.click();
        } else {
            console.log('There is no button');
        }

        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        // Ext seems to also be "slow" to add events to the button, wait to make sure events get added
        //await this.page.pause(750);
    }

    /**
     * Get the report template names.
     *
     * The list of report template names must be visible.
     *
     * @return {String[]} Report template names.
     */
    async getReportTemplateNames() {
        await this.page.isVisible(this.selectors.myReports.toolbar.newBasedOnTemplateRows());
        //const computed = await this.page.$$(this.selectors.myReports.toolbar.newBasedOnTemplateRows());
        const computed = await this.page.locator(this.selectors.myReports.toolbar.newBasedOnTemplateRows() + `//a[./img[${classContains('btn_report_template')}]]//b`);
        /*const result = await Promise.all(computed.map(async (row) => {
            return await row.textContent();
        }));*/
        const result = await computed.allTextContents();
        await expect(computed).toBeVisible();
        return result;
    }

    /**
     * Select a template from the "New Based On" menu.
     *
     * Must click the "New Based On" button before selecting the template.
     *
     * @param {String} templateName The full name of the template.
     * @param {String} center       The name of the center to select [optional].
     */
    async selectNewBasedOnTemplate(templateName, center) {
        const menuLoc = await this.page.locator(this.selectors.myReports.toolbar.newBasedOnMenu() + '>> visible=true');
        await expect(menuLoc).toBeVisible();
        const first = await this.getMyReportsRows();
        const reportCount = first.length;
        if (!center) {
            await this.page.click(this.selectors.myReports.toolbar.newBasedOnTemplate(templateName));
        } else {
            // move the mouse to the middle of the menu so that the center selection menu appears
            await this.page.locator(this.selectors.myReports.toolbar.newBasedOnTemplate(templateName)).hover();

            // wait for the new menu to be visible
            await this.page.isVisible(this.selectors.myReports.toolbar.newBasedOnTemplateWithCenter(center));

            // Select the option that corresponds with the center argument
            await this.page.click(this.selectors.myReports.toolbar.newBasedOnTemplateWithCenter(center));
        }
        // There is no visible indicator that the reports are being
        // updated, so wait for the number of rows to increase
        // specifically look for there to be at least one more item
        // this seems to be do the the fact that elements and selectors get cached
        await expect(this.page.locator(this.selectors.myReports.reportList.rowByIndex(reportCount + 1))).toBeVisible();
    }

    /**
     * Select a report from the "New Based On" menu.
     *
     * Must select the same report in the "My Reports list (and no other
     * reports) and click the "New Based On" button before selecting the report
     * in the "New Based On" menu.
     *
     * @param {String} reportName The full name of the template.
     */
    async selectNewBasedOnReport(reportName) {
        await this.page.isVisible(this.selectors.myReports.toolbar.newBasedOnMenu());
        await this.page.click(this.selectors.myReports.toolbar.newBasedOnReport(reportName));
    }

    /**
     * Edit the selected report in the "My Reports" panel by clicking the
     * "Edit" button.
     */
    async editSelectedReports() {
        await this.waitForMyReportsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.editButton());
    }

    /**
     * Edit a report in the "My Reports" panel by double clicking the row for
     * that report.
     *
     * @param {String} reportName Name of the report.
     */
    async editReportByName(reportName) {
        await this.waitForMyReportsPanelVisible();

        let foundReport = false;

        const rows = await this.getMyReportsRows();
        for (const row of rows){
            if (await row.getName() === reportName){
                await row.dblclick();
                foundReport = true;
            }
        }

        if (!foundReport) {
            throw new Error(`No report named "${reportName}" found`);
        }
    }

    /**
     * Preview the selected report in the "My Reports" panel by clicking the
     * "Preview" button.
     */
    async previewSelectedReports() {
        await this.waitForMyReportsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.previewButton());
    }

    /**
     * Return to the "My Reports" panel from the "Report Preview" by clicking
     * the "Return To Reports Overview" button.
     */
    async returnToReportsOverview() {
        await this.waitForReportPreviewPanelVisible();
        await this.page.click(this.selectors.reportPreview.toolbar.returnToReportsOverviewButton());
    }

    /**
     * Click the "Send Now" button in the "My Reports" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    async sendSelectedReportsNow() {
        await this.waitForMyReportsPanelVisible();
        // There are two separate "Send Now" buttons in the "My Reports" panel.
        // Only one should be visible at a time.
        const visibleButtons = this.page.$$(this.selectors.myReports.toolbar.sendNowButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
        await expect(visibleButtons.length, 'One "Send Now" button is visible').toEqual(1);
        await visibleButtons[0].click();
    }

    /**
     * Select the "As PDF" option from the "Send Now" menu in the "My Reports"
     * panel.
     */
    async sendSelectedReportsAsPdfNow() {
        await this.page.isVisible(this.selectors.myReports.toolbar.sendNowAsPdfButton());
        await this.page.click(this.selectors.myReports.toolbar.sendNowAsPdfButton());
    }

    /**
     * Select the "As Word Document" option from the "Send Now" menu in the "My Reports"
     * panel.
     */
    async sendSelectedReportsAsWordDocumentNow() {
        await this.page.isVisible(this.selectors.myReports.toolbar.sendNowAsWordDocumentButton());
        await this.page.click(this.selectors.myReports.toolbar.sendNowAsWordDocumentButton());
    }

    /**
     * Click the "Download" button in the "My Reports" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    async downloadSelectedReports() {
        await this.waitForMyReportsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.downloadButton());
    }

    /**
     * Select the "As PDF" option from the "Download" menu in the "My Reports"
     * panel.
     */
    async downloadSelectedReportsAsPdf() {
        await this.page.isVisible(this.selectors.myReports.toolbar.downloadAsPdfButton());
        await this.page.click(this.selectors.myReports.toolbar.downloadAsPdfButton());
        // Wait for check mark image to appear and disappear.
        await this.page.isVisible(this.selectors.checkmarkMask());
        await this.page.isHidden(this.selectors.checkmarkMask());
    }

    /**
     * Select the "As Word Document" option from the "Download" menu in the
     * "My Reports" panel.
     */
    async downloadSelectedReportsAsWordDocument() {
        await this.page.isVisible(this.selectors.myReports.toolbar.downloadAsWordDocumentButton());
        await this.page.click(this.selectors.myReports.toolbar.downloadAsWordDocumentButton());
        // Wait for check mark image to appear and disappear.
        await this.page.isVisible(this.selectors.checkmarkMask());
        await this.page.isHidden(this.selectors.checkmarkMask());
    }

    /**
     * Close the "Report Built" window.
     */
    async closeReportBuiltWindow() {
        await this.waitForReportBuiltWindowVisible();
        await this.page.click(this.selectors.reportBuilt.closeButton());
        await this.waitForReportBuiltWindowNotVisible();
    }

    /**
     * Delete selected reports from "My Reports" by clicking the delete button.
     *
     * Does not confirm deletion of report, that button must be clicked
     * separately.
     */
    async deleteSelectedReports() {
        await this.waitForMyReportsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.deleteButton());
    }

    /**
     * Click the "Yes" button in the "Delete Selected Report" window.
     */
    async confirmDeleteSelectedReports() {
        const rows = await this.getMyReportsRows();
        const reportCount = rows.length;
        await this.waitForDeleteSelectedReportsWindowVisible();
        await this.page.click(this.selectors.deleteSelectedReports.yesButton());
        await this.waitForDeleteSelectedReportsWindowNotVisible();
        // There is no visible indicator that the reports are being
        // updated, so wait for the number of rows to change.
        const maskLocator = this.page.locator('(//div[@class="x-panel-body"]//div[@class="x-grid3-scroller"])[1]//div[contains(., "Loading...")]');
        const maskHolder = await maskLocator.isVisible();
        if (maskHolder){
            await maskLocator.waitFor({state:'detached'});
        }   
        for (let i = 0; i < 100; i++){
            try {
                const second = await this.getMyReportsRows();
                const reportCount2 = second.length;
                await expect(reportCount2 !== reportCount).toBeTruthy();
                break;
            } catch (e) {
                //console.log(i);
                await maskLocator.waitFor({state:'detached'});
            }
        }
        //await this.page.waitForFunction(async () => reportCount !== (await this.getMyReportsRows()).length);
    }

    /**
     * Click the "Yes" button in the "Delete Selected Report" window.
     */
    async cancelDeleteSelectedReports() {
        await this.waitForDeleteSelectedReportsWindowVisible();
        await this.page.click(this.selectors.deleteSelectedReports.noButton());
        await this.waitForDeleteSelectedReportsWindowNotVisible();
    }

    /**
     * Save the report currently being edited.
     */
    async saveReport() {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.toolbar.saveButton());
        await this.page.isVisible(this.selectors.message.window());
        await expect(await this.getMessageWindowTitle(), 'Message window title is correct').toEqual('Report Editor');
        await this.page.isHidden(this.selectors.message.window());
    }

    /**
     * Save a report with a different name.
     *
     * Does not confirm saving the report.
     *
     * @param {String} reportName The new name to give the report (optional).
     */
    async saveReportAs(reportName = undefined) {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.toolbar.saveAsButton());
        await this.waitForSaveReportAsWindowVisible();
        if (reportName !== undefined) {
            /*
             * There is a "timing" issue when setting the value of the input box
             * and then clicking on save to quickly, this forces it to wait for
             * the invalid input to not be present
             */
            await this.page.locator(this.selectors.saveReportAs.reportNameInput()).fill(reportName);
            await this.page.isHidden(this.selectors.saveReportAs.reportNameInputInvalid());
        }
    }

    /**
     * Confirm saving a report by clicking the "Save" button.
     *
     * @param {Boolean} expectError True, if an error is expected
     *   (defaults to false).  If no error is expected then this method
     *   does not return until the confirmation message is no longer
     *   visible.
     */
    async confirmSaveReportAs(expectError = false) {
        await this.waitForSaveReportAsWindowVisible();
        await this.page.click(this.selectors.saveReportAs.saveButton());

        if (!expectError) {
            await expect(await this.getMessageWindowTitle(), 'Message window title is correct').toEqual('Report Editor');
            await expect(await this.getMessage(), 'Message is correct').toEqual('Report successfully saved as a copy');
            await this.page.isHidden(this.selectors.message.window());
        }
    }

    /**
     * Close "Save Report As" window by clicking the "Close" button.
     */
    async closeSaveReportAs() {
        await this.waitForSaveReportAsWindowVisible();
        await this.page.click(this.selectors.saveReportAs.closeButton());
    }

    /**
     * Preview the report currently being edited.
     */
    async previewCurrentReport() {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.toolbar.previewButton());
    }

    /**
     * Click the "Send Now" button in the "Report Editor" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    async sendCurrentlyBeingEditedReportNow() {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.toolbar.sendNowButton());
    }

    /**
     * Select the "As PDF" option from the "Send Now" menu in the
     * "Report Editor" panel.
     */
    async sendCurrentlyBeingEditedReportAsPdfNow() {
        await this.waitForReportEditorPanelVisible();
        await this.page.isVisible(this.selectors.myReports.toolbar.sendNowAsPdfButton());
        await this.page.click(this.selectors.myReports.toolbar.sendNowAsPdfButton());
        //await this.page.isVisible(this.selectors.reportEditor.toolbar.sendNowAsPdfButton());
        //await this.page.click(this.selectors.reportEditor.toolbar.sendNowAsPdfButton());
    }

    /**
     * Select the "As PDF" option from the "Send Now" menu in the
     * "Report Editor" panel.
     */
    async sendCurrentlyBeingEditedReportAsWordDocumentNow() {
        await this.waitForReportEditorPanelVisible();
        await this.page.isVisible(this.selectors.myReports.toolbar.sendNowAsWordDocumentButton());
        await this.page.click(this.selectors.myReports.toolbar.sendNowAsWordDocumentButton());
        //await this.page.isVisible(this.selectors.reportEditor.toolbar.sendNowAsWordDocumentButton());
        //await this.page.click(this.selectors.reportEditor.toolbar.sendNowAsWordDocumentButton());
    }

    /**
     * Click the "Download" button in the "Report Editor" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    async downloadCurrentlyBeingEditedReport() {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.toolbar.downloadButton());
    }

    /**
     * Select the "As PDF" option from the "Download" menu in the
     * "Report Editor" panel.
     */
    async downloadCurrentlyBeingEditedReportAsPdf() {
        await this.page.isVisible(this.selectors.myReports.toolbar.downloadAsPdfButton());
        await this.page.click(this.selectors.myReports.toolbar.downloadAsPdfButton());
        //await this.page.isVisible(this.selectors.reportEditor.toolbar.downloadAsPdfButton());
        //await this.page.click(this.selectors.reportEditor.toolbar.downloadAsPdfButton());
    }

    /**
     * Select the "As Word Document" option from the "Download" menu in the
     * "Report Editor" panel.
     */
    async downloadCurrentlyBeingEditedReportAsWordDocument() {
        await this.page.isVisible(this.selectors.myReports.toolbar.downloadAsWordDocumentButton());
        await this.page.click(this.selectors.myReports.toolbar.downloadAsWordDocumentButton());
        //await this.page.isVisible(this.selectors.reportEditor.toolbar.downloadAsWordDocumentButton());
        //await this.page.click(this.selectors.reportEditor.toolbar.downloadAsWordDocumentButton());
    }

    /**
     * Return to "My Reports" by clicking the "Return to My Reports" button.
     */
    async returnToMyReports() {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.toolbar.returnToMyReportsButton());
    }

    /**
     * Get the name of the report currently being edited.
     *
     * @return {String}
     */
    async getReportName() {
        await this.waitForReportEditorPanelVisible();
        return this.page.locator(this.selectors.reportEditor.generalInformation.reportNameInput()).inputValue();
    }

    /**
     * Set the name of the report currently being edited.
     *
     * @param {String} name
     */
    async setReportName(name) {
        await this.waitForReportEditorPanelVisible();
        await this.page.locator(this.selectors.reportEditor.generalInformation.reportNameInput()).fill(name); 
    }

    /**
     * Get the title of the report currently being edited.
     *
     * @return {String}
     */
    async getReportTitle() {
        await this.waitForReportEditorPanelVisible();
        return this.page.locator(this.selectors.reportEditor.generalInformation.reportTitleInput()).inputValue();
    }

    /**
     * Set the title of the report currently being edited.
     *
     * @param {String} reportTitle
     */
    async setReportTitle(reportTitle) {
        await this.waitForReportEditorPanelVisible();
        await this.page.locator(this.selectors.reportEditor.generalInformation.reportTitleInput()).fill(reportTitle);
    }

    /**
     * Get the header text of the report currently being edited.
     *
     * @return {String}
     */
    async getHeaderText() {
        await this.waitForReportEditorPanelVisible();
        return this.page.locator(this.selectors.reportEditor.generalInformation.headerTextInput()).inputValue();
    }

    /**
     * Set the header text of the report currently being edited.
     *
     * @param {String} headerText
     */
    async setHeaderText(headerText) {
        await this.waitForReportEditorPanelVisible();
        await this.page.locator(this.selectors.reportEditor.generalInformation.headerTextInput()).fill(headerText);
    }

    /**
     * Get the footer text of the report currently being edited.
     *
     * @return {String}
     */
    async getFooterText() {
        await this.waitForReportEditorPanelVisible();
        return this.page.locator(this.selectors.reportEditor.generalInformation.footerTextInput()).inputValue();
    }

    /**
     * Set the footer text of the report currently being edited.
     *
     * @param {String} footerText
     */
    async setFooterText(footerText) {
        await this.waitForReportEditorPanelVisible();
        await this.page.locator(this.selectors.reportEditor.generalInformation.footerTextInput()).fill(footerText);
    }

    /**
     * Get the number of charts per page for the report currently being edited.
     *
     * @return {Number}
     */
    async getNumberOfChartsPerPage() {
        await this.waitForReportEditorPanelVisible();
        if (this.page.isChecked(this.selectors.reportEditor.chartLayout.twoChartsPerPageRadioButton())) {
            return 1;
        } else if (this.page.isChecked(this.selectors.reportEditor.chartLayout.oneChartPerPageRadioButton())) {
            return 2;
        }

        throw new Error('No charts per page option selected');
    }

    /**
     * Set the number of charts per page for the report currently being edited.
     *
     * @param {Number} chartsPerPage Must be 1 or 2.
     */
    async setNumberOfChartsPerPage(chartsPerPage) {
        await this.waitForReportEditorPanelVisible();
        if (chartsPerPage === 1) {
            await this.page.click(this.selectors.reportEditor.chartLayout.oneChartPerPageRadioButton());
        } else if (chartsPerPage === 2) {
            await this.page.click(this.selectors.reportEditor.chartLayout.twoChartsPerPageRadioButton());
        } else {
            throw new Error(`Invalid number of charts per page: "${chartsPerPage}"`);
        }
    }

    /**
     * Get the schedule (delivery frequency) of the report currently being
     * edited.
     *
     * @return {String}
     */
    async getSchedule() {
        await this.waitForReportEditorPanelVisible();
        return this.page.locator(this.selectors.reportEditor.scheduling.scheduleInput()).inputValue();
    }

    /**
     * Set the schedule (delivery frequency) of the report currently being
     * edited.
     *
     * @param {String} frequency
     */
    async setSchedule(frequency) {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.scheduling.scheduleInput());
        await this.page.isVisible(this.selectors.reportEditor.scheduling.scheduleOption(frequency));
        await this.page.click(this.selectors.reportEditor.scheduling.scheduleOption(frequency));
    }

    /**
     * Get the delivery format ('PDF" or "Word Document') of the report
     * currently being edited.
     *
     * @return {String}
     */
    async getDeliveryFormat() {
        await this.waitForReportEditorPanelVisible();
        return this.page.locator(this.selectors.reportEditor.scheduling.deliveryFormatInput()).inputValue();
    }

    /**
     * Set the delivery format ('PDF" or "Word Document') of the report
     * currently being edited.
     *
     * @param {String} format
     */
    async setDeliveryFormat(format) {
        await this.waitForReportEditorPanelVisible();
        await this.page.click(this.selectors.reportEditor.scheduling.deliveryFormatInput());
        await this.page.isVisible(this.selectors.reportEditor.scheduling.deliveryFormatOption(format));
        await this.page.click(this.selectors.reportEditor.scheduling.deliveryFormatOption(format));
    }

    /**
     * Select all charts in the "Included Charts" panel by clicking the "Select"
     * button then clicking "All Charts".
     */
    async selectAllIncludedCharts() {
        await this.waitForIncludedChartsPanelVisible();
        await this.page.click(this.selectors.reportEditor.includedCharts.toolbar.selectButton());
        await this.page.isVisible(this.selectors.reportEditor.includedCharts.toolbar.selectAllChartsButton());
        await this.page.click(this.selectors.reportEditor.includedCharts.toolbar.selectAllChartsButton());
        await this.page.isHidden(this.selectors.reportEditor.includedCharts.toolbar.selectAllChartsButton());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Deselect all charts in the "Included Charts" panel by clicking the
     * "Select" button then clicking "No Charts".
     */
    async deselectAllIncludedCharts() {
        await this.waitForIncludedChartsPanelVisible();
        await this.page.click(this.selectors.reportEditor.includedCharts.toolbar.selectButton());
        await this.page.isVisible(this.selectors.reportEditor.includedCharts.toolbar.selectNoChartsButton());
        await this.page.click(this.selectors.reportEditor.includedCharts.toolbar.selectNoChartsButton());
        await this.page.isHidden(this.selectors.reportEditor.includedCharts.toolbar.selectNoChartsButton());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Invert the chart selection in the "Included Charts" panel by clicking the
     * "Select" button then clicking "Invert Selection".
     */
    async invertIncludedChartsSelection() {
        await this.waitForIncludedChartsPanelVisible();
        await this.page.click(this.selectors.reportEditor.includedCharts.toolbar.selectButton());
        await this.page.isVisible(this.selectors.reportEditor.includedCharts.toolbar.invertSelectionButton());
        // Multiple buttons match the "Invert Selection" selector, but only one should be visible.
        const visibleButtons = this.page.$$(this.selectors.myReports.toolbar.invertSelectionButton()).filter(button => button.isVisible());
        await expect(visibleButtons.length, 'One "Invert Selection" button is visible').toEqual(1);
        await visibleButtons[0].click();
        await this.page.isHidden(this.selectors.reportEditor.includedCharts.toolbar.invertSelectionButton());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Click the "Edit Timeframe of Selected Charts" button in the "Included
     * Charts" panel.
     */
    async editTimeframeOfSelectedCharts() {
        await this.page.click(this.selectors.reportEditor.includedCharts.toolbar.editTimeframeButton());
    }

    /**
     * Click the "Specific" radio button in the "Edit Chart Timeframe" window.
     */
    async selectSpecificChartTimeframe() {
        await this.waitForEditChartTimeframeWindowVisible();
        await this.page.click(this.selectors.editChartTimeframe.specificRadioButton());
    }

    /**
     * Set the start date in the "Edit Chart Timeframe" window.
     *
     * @param {String} startDate
     */
    async setSpecificChartTimeframeStartDate(startDate) {
        await this.waitForEditChartTimeframeWindowVisible();
        await this.page.locator(this.selectors.editChartTimeframe.startDateInput()).fill(startDate);
    }

    /**
     * Set the end date in the "Edit Chart Timeframe" window.
     *
     * @param {String} endDate
     */
    async setSpecificChartTimeframeEndDate(endDate) {
        await this.waitForEditChartTimeframeWindowVisible();
        await this.page.locator(this.selectors.editChartTimeframe.endDateInput()).fill(endDate);
    }

    /**
     * Click the "Periodic" radio button in the "Edit Chart Timeframe" window
     * and pick a duration.
     *
     * @param {String} duration Name of the periodic duration.
     */
    async selectPeriodicChartTimeframe(duration) {
        await this.waitForEditChartTimeframeWindowVisible();
        await this.page.click(this.selectors.editChartTimeframe.periodicRadioButton());
        await this.page.isVisible(this.selectors.editChartTimeframe.periodicInput());
        await this.page.click(this.selectors.editChartTimeframe.periodicInput());
        await this.page.isVisible(this.selectors.editChartTimeframe.periodicOption(duration));
        await this.page.click(this.selectors.editChartTimeframe.periodicOption(duration));
        await this.page.isHidden(this.selectors.editChartTimeframe.periodicOption(duration));
    }

    /**
     * Click the "Cancel" button in the "Edit Chart Timeframe" window.
     */
    async cancelEditTimeframeOfSelectedCharts() {
        await this.waitForEditChartTimeframeWindowVisible();
        await this.page.click(this.selectors.editChartTimeframe.cancelButton());
    }

    /**
     * Click the "Update" button in the "Edit Chart Timeframe" window.
     */
    async confirmEditTimeframeOfSelectedCharts() {
        await this.waitForEditChartTimeframeWindowVisible();
        await this.page.click(this.selectors.editChartTimeframe.updateButton());
    }

    /**
     * Get the error message displayed in the "Edit Chart Timeframe" window.
     *
     * This method must be called while the error message is still visible (or
     * directly before the message appears) or it will fail.
     *
     * @return {String} The text of the error message.
     */
    async getEditChartTimeframeErrorMessage() {
        await this.page.isVisible(this.selectors.editChartTimeframe.errorMessage());
        return this.page.textContent(this.selectors.editChartTimeframe.errorMessage());
    }

    /**
     * Remove selected charts from the "Included Charts" panel by clicking the
     * "Remove" button.
     */
    async removeSelectedIncludedCharts() {
        await this.waitForIncludedChartsPanelVisible();
        await this.page.click(this.selectors.myReports.toolbar.deleteButton());
//        await this.page.click(this.selectors.reportEditor.includedCharts.toolbar.deleteButton());
    }

    /**
     * Click the "No" button in the "Remove Selected Chart" window.
     */
    async cancelRemoveSelectedIncludedCharts() {
        await this.waitForRemoveSelectedChartsWindowVisible();
        await this.page.click(this.selectors.removeSelectedCharts.noButton());
        await this.waitForRemoveSelectedChartsWindowNotVisible();
    }

    /**
     * Click the "Yes" button in the "Remove Selected Chart" window.
     */
    async confirmRemoveSelectedIncludedCharts() {
        await this.waitForRemoveSelectedChartsWindowVisible();
        const first = await this.getIncludedCharts();
        const chartCount = first.length;
        await this.page.click(this.selectors.removeSelectedCharts.yesButton());
        this.waitForRemoveSelectedChartsWindowNotVisible();
        // There is no visible indicator that the charts are being
        // updated, so wait for the number of rows to change.
        await this.page.waitForFunction(async () => chartCount !== (await this.getIncludedCharts()).length);
    }

    /**
     * Select all charts in the "Available Charts" panel by clicking the
     * "Select" button then clicking "All Charts".
     */
    async selectAllAvailableCharts() {
        await this.waitForAvailableChartsPanelVisible();
        await this.page.click(this.selectors.availableCharts.toolbar.selectButton());
        await this.page.isVisible(this.selectors.availableCharts.toolbar.selectAllChartsButton());
        await this.page.click(this.selectors.availableCharts.toolbar.selectAllChartsButton());
        await this.page.isHidden(this.selectors.availableCharts.toolbar.selectAllChartsButton());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Deselect all charts in the "Available Charts" panel by clicking the
     * "Select" button then clicking "No Charts".
     */
    async deselectAllAvailableCharts() {
        await this.waitForAvailableChartsPanelVisible();
        await this.page.click(this.selectors.availableCharts.toolbar.selectButton());
        await this.page.isVisible(this.selectors.availableCharts.toolbar.selectNoChartsButton());
        await this.page.click(this.selectors.availableCharts.toolbar.selectNoChartsButton());
        await this.page.isHidden(this.selectors.availableCharts.toolbar.selectNoChartsButton());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Invert the chart selection in the "Available Charts" panel by clicking
     * the "Select" button then clicking "Invert Selection".
     */
    async invertAvailableChartsSelection() {
        await this.waitForAvailableChartsPanelVisible();
        await this.page.click(this.selectors.availableCharts.toolbar.selectButton());
        await this.page.isVisible(this.selectors.availableCharts.toolbar.invertSelectionButton());
        // Multiple buttons match the "Invert Selection" selector, but only one should be visible.
        const visibleButtons = this.page.$$(this.selectors.myReports.toolbar.invertSelectionButton()).filter(button => button.isVisible());
        await expect(visibleButtons.length, 'One "Invert Selection" button is visible').to.be.equal(1);
        await visibleButtons[0].click();
        await this.page.isHidden(this.selectors.availableCharts.toolbar.invertSelectionButton());
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        //await this.page.pause(500);
    }

    /**
     * Delete selected charts from "Available Charts" by clicking the
     * delete button.
     *
     * Does not confirm deletion of carts, that button must be clicked
     * separately.
     *
     */
    async deleteSelectedAvailableCharts() {
        await this.waitForAvailableChartsPanelVisible();
        await this.page.click(this.selectors.availableCharts.toolbar.deleteButton());
    }

    /**
     * Click the "Yes" button in the "Delete Selected Charts" window.
     */
    async confirmDeleteSelectedAvailableCharts() {
        await this.waitForDeleteSelectedChartsWindowVisible();
        const first = await this.getAvailableCharts();
        const chartCount = first.length;
        await this.page.click(this.selectors.deleteSelectedCharts.yesButton());
        // There is no visible indicator that the charts are being
        // updated, so wait for the number of rows to change.
        const maskLocator = this.page.locator('//div[@id="chart_pool_panel"]//div[@class="x-grid3-scroller"]//div[contains(., "Loading...")]');
        const maskHolder = await maskLocator.isVisible();
        if (maskHolder){
            await maskLocator.waitFor({state:'detached'});
        }
        for (let i = 0; i < 100; i++) {
            try {
                const second = await this.getAvailableCharts();
                const chartCount2 = second.length;
                await expect(chartCount2 !== chartCount).toBeTruthy();
                break;
            } catch (e) {
                //console.log(i);
                await maskLocator.waitFor({state:'detached'});
            }
        }        
 
        //await this.page.waitForFunction(async () => chartCount !== (await this.getAvailableCharts()).length);
    }

    /**
     * Click the "No" button in the "Delete Selected Charts" window.
     */
    async cancelDeleteSelectedAvailableCharts() {
        await this.waitForDeleteSelectedChartsWindowVisible();
        await this.page.click(this.selectors.deleteSelectedCharts.noButton());
    }

    /**
     * Add a chart to the report that is currently being edited.
     *
     * @param {Number} index The 0-based index of the chart in the list of
     *   available charts.
     */
    async addChartToReport(index) {
        await this.waitForAvailableChartsPanelVisible();
        await this.waitForIncludedChartsPanelVisible();
        const charts = await this.getAvailableCharts();
        const first = await this.getIncludedCharts();
        const includedChartCountBefore = first.length;
        await expect(index, 'Index is valid').toBeLessThanOrEqual(charts.length);
        await this.page.dragAndDrop(this.selectors.availableCharts.chartList.rows() + `[${index + 1}]`, this.selectors.reportEditor.includedCharts.chartList.panel());
        await this.page.isVisible(this.selectors.reportEditor.includedCharts.chartList.rows() + `[${includedChartCountBefore + 1}]`);
    }

    async getCharts(user, report_template_index, options) {
        var charts = expected[user].report_templates[report_template_index].charts;
        await charts.forEach(function (chart, i) {
            if (chart.startDate in options) {
                charts[i].startDate = options[chart.startDate];
            }
            if (chart.endDate in options) {
                charts[i].endDate = options[chart.endDate];
            }
        });
        return charts;
    }
}
export default {MyReportsRow, AvailableChart, IncludedChart, ReportGenerator};
