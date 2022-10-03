/**
 * Report generator test classes.
 */
const expected = global.testHelpers.artifacts.getArtifact('reportGenerator');
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

var xdmod = require('./xdmod.page.js');

/**
 * A single row in the list of reports.
 */
class MyReportsRow {

    /**
     * @param {String} selector XPath selector for a "My Reports" row.
     */
    constructor(selector) {
        this.selector = selector;

        this.selectors = {
            name: selector + '//tr/td[position()=2]//div',
            derivedFrom: selector + '//tr/td[position()=3]//div',
            schedule: selector + '//tr/td[position()=4]//div',
            deliveryFormat: selector + '//tr/td[position()=5]//div[position()=2]',
            numberOfCharts: selector + '//tr/td[position()=6]//div',
            numberOfChartsPerPage: selector + '//tr/td[position()=6]//div/span'
        };
    }

    /**
     * Get the name of the chart.
     *
     * @return {String}
     */
    getName() {
        return browser.getText(this.selectors.name);
    }

    /**
     * Get the name of the template this report is derived from or "Manual" if
     * the report was created manually..
     *
     * @return {String}
     */
    getDerivedFrom() {
        return browser.getText(this.selectors.derivedFrom);
    }

    /**
     * Get the schedule (frequency) of the report.
     *
     * @return {String}
     */
    getSchedule() {
        return browser.getText(this.selectors.schedule);
    }

    /**
     * Get the delivery format (PDF or Word Document) of the report.
     *
     * @return {String}
     */
    getDeliveryFormat() {
        return browser.getText(this.selectors.deliveryFormat);
    }

    /**
     * Get the number of charts in the report.
     *
     * @return {Number}
     */
    getNumberOfCharts() {
        return parseInt(browser.getText(this.selectors.numberOfCharts).trim(), 10);
    }

    /**
     * Get the number of charts per page in the report.
     *
     * @return {Number}
     */
    getNumberOfChartsPerPage() {
        const chartsPerPage = browser.getText(this.selectors.numberOfChartsPerPage);
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
    isSelected() {
        return browser.getAttribute(this.selector, 'class').match(/(^| )x-grid3-row-selected($| )/) !== null;
    }

    /**
     * Click the row.
     */
    click() {
        browser.click(this.selector);
    }

    /**
     * Double click the row.
     */
    doubleClick() {
        browser.doubleClick(this.selector);
    }

    /**
     * Toggle the row selection using Control+Click.
     */
    toggleSelection() {
        browser.keys('Control');
        browser.click(this.selector);
        browser.keys('Control');
    }
}

/**
 * A chart in the "Available Charts" list.
 */
class AvailableChart {

    /**
     * @param {String} selector XPath selector for an "Available Chart".
     */
    constructor(selector) {
        this.selector = selector;
        const baseSelector = selector + '//tr/td[position()=2]/div/div';
        this.selectors = {
            titleAndDrillDetails: baseSelector + '/div[position()=4]/span',
            dateDescription: baseSelector + '/div[position()=5]',
            timeframeType: baseSelector + '/div[position()=6]'
        };
    }

    /**
     * Get the combined title and drill-down details of the chart.
     *
     * Contains "<br>" between the title and drill details.
     *
     * @return {String}
     */
    getTitleAndDrillDetails() {
        return browser.getHTML(this.selectors.titleAndDrillDetails, false);
    }

    /**
     * Get the title of the chart.
     *
     * @return {String}
     */
    getTitle() {
        return this.getTitleAndDrillDetails().split('<br>')[0].trim();
    }

    /**
     * Get the drill-down details of the chart.
     *
     * @return {String}
     */
    getDrillDetails() {
        const drillDetails = this.getTitleAndDrillDetails().split('<br>')[1].trim();
        return drillDetails === '&nbsp;' ? '' : drillDetails;
    }

    /**
     * Get the date description of the chart.
     *
     * @return {String}
     */
    getDateDescription() {
        return browser.getText(this.selectors.dateDescription);
    }

    /**
     * Get the timeframe of the chart.
     *
     * @return {String}
     */
    getTimeframeType() {
        return browser.getText(this.selectors.timeframeType);
    }

    /**
     * Check if the chart is selected.
     *
     * @return {Boolean}
     */
    isSelected() {
        return browser.getAttribute(this.selector, 'class').match(/(^| )x-grid3-row-selected($| )/) !== null;
    }

    /**
     * Click the chart.
     */
    click() {
        browser.click(this.selector);
    }

    /**
     * Toggle the chart selection using Control+Click.
     */
    toggleSelection() {
        browser.keys('Control');
        browser.click(this.selector);
        browser.keys('Control');
    }
}

/**
 * A chart in the "Included Charts" list.
 */
class IncludedChart {

    /**
     * @param {String} selector XPath selector for an "Included Chart".
     */
    constructor(selector) {
        this.selector = selector;
        const baseSelector = selector + '//tr/td[position()=2]/div/div';
        this.selectors = {
            titleAndDrillDetails: baseSelector + '/div[position()=4]/span',
            dateDescription: baseSelector + '/div[position()=6]',
            timeframeEditIcon: baseSelector + '/div[position()=5]/a[position()=1]',
            timeframeType: baseSelector + '/div[position()=7]/span',
            timeframeResetIcon: baseSelector + '/div[position()=5]/a[position()=2]'
        };
    }

    /**
     * Get the combined title and drill-down details of the chart.
     *
     * Contains "<br>" between the title and drill details.
     *
     * @return {String}
     */
    getTitleAndDrillDetails() {
        return browser.getHTML(this.selectors.titleAndDrillDetails, false);
    }

    /**
     * Get the title of the chart.
     *
     * @return {String}
     */
    getTitle() {
        return this.getTitleAndDrillDetails().split('<br>')[0].trim();
    }

    /**
     * Get the drill-down details of the chart.
     *
     * @return {String}
     */
    getDrillDetails() {
        const drillDetails = this.getTitleAndDrillDetails().split('<br>')[1].trim();
        return drillDetails === '&nbsp;' ? '' : drillDetails;
    }

    /**
     * Get the date description of the chart.
     *
     * @return {String}
     */
    getDateDescription() {
        return browser.getText(this.selectors.dateDescription);
    }

    /**
     * Get the timeframe of the chart.
     *
     * @return {String}
     */
    getTimeframeType() {
        return browser.getText(this.selectors.timeframeType);
    }

    /**
     * Check if the row is selected.
     *
     * @return {Boolean}
     */
    isSelected() {
        return browser.getAttribute(this.selector, 'class').match(/(^| )x-grid3-row-selected($| )/) !== null;
    }

    /**
     * Click the chart.
     */
    click() {
        browser.click(this.selector);
    }

    /**
     * Click the date range to edit timeframe.
     */
    editTimeframe() {
        browser.click(this.selectors.timeframeEditIcon);
    }

    /**
     * Click the timeframe reset icon.
     */
    resetTimeframe() {
        browser.click(this.selectors.timeframeResetIcon);
    }

    /**
     * Toggle the chart selection using Control+Click.
     */
    toggleSelection() {
        browser.keys('Control');
        browser.click(this.selector);
        browser.keys('Control');
    }
}

/**
 * Report generator page.
 */
class ReportGenerator {
    constructor() {
        this.tabName = 'Report Generator';

        this.selectors = {
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
                    newBasedOnButton: (enabled = false) => this.selectors.myReports.toolbar.panel() + `//button[text()="New Based On" and not(ancestor::td[contains(@class,"x-hide-display")]) ${enabled ? 'and not(ancestor::table[contains(@class, "x-item-disabled")])' : ''}]`,
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
    }

    /**
     * Determine if the report generator page is enabled.
     *
     * The report generator is considered to be enabled if the report
     * generator tab exists.
     *
     * @return {Boolean} True if the report generator page is enabled.
     */
    isEnabled() {
        return browser.isExisting(this.selectors.tab());
    }

    /**
     * Check if the "New Based On" button in the "My Reports" toolbar is
     * enabled.
     *
     * There are two separate "New Based On" buttons.  Only one should be
     * visible at a time.  This method returns true if the visible button is
     * enabled.
     *
     * @param buttonExpected {Boolean} Whether or not the caller expects there to be an enabled "New Based On" button or not.
     *
     * @return {Boolean} True if an enabled button was expected, else false.
     */
    isNewBasedOnEnabled(buttonExpected = false) {
        const selector = this.selectors.myReports.toolbar.newBasedOnButton(true);
        try {
            browser.waitUntil(function () {
                let buttons = $$(selector);
                return (buttonExpected === true && buttons.length === 1) ||
                    (buttonExpected === false && buttons.length === 0);
            }, 5000, `Expected ${buttonExpected ? 'only one' : 'no'} enabled "New Based On" buttons.`);
        } catch (e) {
            return false;
        }

        // if we've gotten to this point in the code then the `browser.waitUntil` function call did not throw an
        // exception. Which in turn means that the expected conditions were fulfilled and as such can be returned.
        return buttonExpected;
    }

    /**
     * Check if the "Edit" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    isEditSelectedReportsEnabled() {
        try {
            browser.waitUntil(function () {
                const visibleButtons = $$(this.selectors.myReports.toolbar.editButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
                return visibleButtons[0].getAttribute('class').match(/(^| )x-item-disabled($| )/) === null;
            }, 5000, 'Expected the "Edit" button in the "My Reports" toolbar to be enabled');
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Check if the "Preview" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    isPreviewSelectedReportsEnabled() {
       try {
           browser.waitForEnabled(function () {
               const visibleButtons = $$(this.selectors.myReports.toolbar.previewButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
               return visibleButtons[0].getAttribute('class').match(/(^| )x-item-disabled($| )/) === null;
           }, 5000, 'Expected the "Preview" button in the "My Reports" toolbar to be enabled.');
           return true;
       } catch (e) {
           return false;
       }
    }

    /**
     * Check if the "Send Now" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    isSendSelectedReportsEnabled() {
        // There are two separate "Send Now" buttons in the "My Reports" panel.
        // Only one should be visible at a time.
        try {
            browser.waitUntil(function () {
                const visibleButtons = $$(this.selectors.myReports.toolbar.sendNowButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
                expect(visibleButtons.length, 'One "Send Now" button is visible').to.be.equal(1);
                return visibleButtons[0].getAttribute('class').match(/(^| )x-item-disabled($| )/) === null;
            }, 5000, 'Expected the "Send Now" button in the "My Reports" toolbar to be enabled');
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Check if the "Download" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    isDownloadSelectedReportsEnabled() {
        try {
            browser.waitUntil(function () {
                const visibleButtons = $$(this.selectors.myReports.toolbar.downloadButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
                expect(visibleButtons.length, 'One "New Based On" button is visible').to.be.equal(1);
                return visibleButtons[0].getAttribute('class').match(/(^| )x-item-disabled($| )/) === null;
            }, 5000, 'Expected the "Download" button in the "My Reports" toolbar to be enabled');
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Check if the "Delete" button in the "My Reports" toolbar is enabled.
     *
     * @return {Boolean} True if the button is enabled.
     */
    isDeleteSelectedReportsEnabled() {
        try {
            browser.waitUntil(function () {
                const visibleButtons = $$(this.selectors.myReports.toolbar.deleteButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
                return visibleButtons[0].getAttribute('class').match(/(^| )x-item-disabled($| )/) === null;
            }, 5000, 'Expected the "Delete" button in the "My Reports" toolbar to be enabled.');
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Wait for the "My Reports" panel to be visible.
     */
    waitForMyReportsPanelVisible() {
        browser.waitForVisible(this.selectors.myReports.panel());
    }

    /**
     * Wait for the "Report Preview" panel to be visible.
     */
    waitForReportPreviewPanelVisible() {
        browser.waitForVisible(this.selectors.reportPreview.panel());
    }

    /**
     * Wait for the "Report Editor" panel to be visible.
     */
    waitForReportEditorPanelVisible() {
        browser.waitForVisible(this.selectors.reportEditor.panel());
    }

    /**
     * Wait for the "Included Charts" panel to be visible.
     */
    waitForIncludedChartsPanelVisible() {
        this.waitForReportEditorPanelVisible();
        browser.waitForVisible(this.selectors.reportEditor.includedCharts.panel());
    }

    /**
     * Wait for the "Available Charts" panel to be visible.
     */
    waitForAvailableChartsPanelVisible() {
        browser.waitForVisible(this.selectors.availableCharts.panel());
    }

    /**
     * Wait for the "Message" window to be visible.
     */
    waitForMessageWindowVisible() {
        browser.waitForVisible(this.selectors.message.window());
    }

    /**
     * Wait for the "Delete Selected Report" window to be visible.
     */
    waitForDeleteSelectedReportsWindowVisible() {
        browser.waitForVisible(this.selectors.deleteSelectedReports.window());
    }

    /**
     * Wait for the "Delete Selected Report" window to not be visible.
     */
    waitForDeleteSelectedReportsWindowNotVisible() {
        browser.waitForInvisible(this.selectors.deleteSelectedReports.window(), 500);
    }

    /**
     * Wait for the "Delete Selected Chart" window to be visible.
     */
    waitForDeleteSelectedChartsWindowVisible() {
        browser.waitForVisible(this.selectors.deleteSelectedCharts.window());
    }

    /**
     * Wait for the "Delete Selected Chart" window to not be visible.
     */
    waitForDeleteSelectedChartsWindowNotVisible() {
        browser.waitForInvisible(this.selectors.deleteSelectedCharts.window(), 500);
    }

    /**
     * Wait for the "Remove Selected Chart" window to be visible.
     */
    waitForRemoveSelectedChartsWindowVisible() {
        browser.waitForVisible(this.selectors.removeSelectedCharts.window());
    }

    /**
     * Wait for the "Remove Selected Chart" window to not be visible.
     */
    waitForRemoveSelectedChartsWindowNotVisible() {
        browser.waitForInvisible(this.selectors.removeSelectedCharts.window(), 500);
    }

    /**
     * Wait for the "Edit Chart Timeframe" window to be visible.
     *
     * Also waits for the error message to not be visible.
     */
    waitForEditChartTimeframeWindowVisible() {
        browser.waitForVisible(this.selectors.editChartTimeframe.window());
        browser.waitForInvisible(this.selectors.editChartTimeframe.errorMessage(), 2500);
    }

    /**
     * Wait for the "Save Report As" window to be visible.
     */
    waitForSaveReportAsWindowVisible() {
        browser.waitForVisible(this.selectors.saveReportAs.window());
    }

    /**
     * Wait for the "Report Built" window to be visible.
     */
    waitForReportBuiltWindowVisible() {
        browser.waitForVisible(this.selectors.reportBuilt.window());
    }

    /**
     * Wait for the "Report Built" window to not be visible.
     */
    waitForReportBuiltWindowNotVisible() {
        browser.waitForInvisible(this.selectors.reportBuilt.window(), 500);
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
    getMyReportsRows() {
        this.waitForMyReportsPanelVisible();
        const selector = this.selectors.myReports.reportList.rows();
        return $$(selector).map((element, i) => new MyReportsRow(`${selector}[${i + 1}]`));
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
    getIncludedCharts() {
        this.waitForIncludedChartsPanelVisible();
        const selector = this.selectors.reportEditor.includedCharts.chartList.rows();
        return $$(selector).map((element, i) => new IncludedChart(`${selector}[${i + 1}]`));
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
    getAvailableCharts() {
        this.waitForAvailableChartsPanelVisible();
        const selector = this.selectors.availableCharts.chartList.rows();
        var elemCount = browser.elements(selector).length;
        var lastCount = -1;
        while (elemCount !== lastCount) {
            lastCount = elemCount;
            browser.pause(100);
            elemCount = browser.elements(selector).length;
        }
        return $$(selector).map((element, i) => new AvailableChart(`${selector}[${i + 1}]`));
    }

    /**
     * Get the title of the "Message" window.
     *
     * @return {String}
     */
    getMessageWindowTitle() {
        this.waitForMessageWindowVisible();
        return browser.getText(this.selectors.message.titleElement());
    }

    /**
     * Get the message text of the "Message" window.
     *
     * @return {String}
     */
    getMessage() {
        this.waitForMessageWindowVisible();
        return browser.getText(this.selectors.message.textElement());
    }

    /**
     * Select the "Report Generator" tab by clicking it.
     */
    selectTab() {
        xdmod.selectTab('report_generator');
    }

    /**
     * Select a report in the "My Reports" panel by clicking the row in the list
     * of reports.
     *
     * @param {String} reportName The name of the report.
     */
    selectReportByName(reportName) {
        this.waitForMyReportsPanelVisible();

        let foundReport = false;

        this.getMyReportsRows().forEach(row => {
            if (row.getName() === reportName) {
                row.click();
                foundReport = true;
            }
        });

        if (!foundReport) {
            throw new Error(`No report named "${reportName}" found`);
        }
    }

    /**
     * Select all reports in the "My Reports" panel by clicking the "Select"
     * button then clicking "All Reports".
     */
    selectAllReports() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.selectButton());
        browser.waitForVisible(this.selectors.myReports.toolbar.selectAllReportsButton());
        browser.click(this.selectors.myReports.toolbar.selectAllReportsButton());
        browser.waitForInvisible(this.selectors.myReports.toolbar.selectMenu(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Deselect all reports in the "My Reports" panel by clicking the "Select"
     * button then clicking "No Reports".
     */
    deselectAllReports() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.selectButton());
        browser.waitForVisible(this.selectors.myReports.toolbar.selectNoReportsButton());
        browser.click(this.selectors.myReports.toolbar.selectNoReportsButton());
        browser.waitForInvisible(this.selectors.myReports.toolbar.selectMenu(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Invert the report selection in the "My Reports" panel by clicking the
     * "Select" button then clicking "Invert Selection".
     */
    invertReportSelection() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.selectButton());
        browser.waitForVisible(this.selectors.myReports.toolbar.invertSelectionButton());
        // Multiple buttons match the "Invert Selection" selector, but only one should be visible.
        const visibleButtons = $$(this.selectors.myReports.toolbar.invertSelectionButton()).filter(button => button.isVisible());
        expect(visibleButtons.length, 'One "Invert Selection" button is visible').to.be.equal(1);
        visibleButtons[0].click();
        browser.waitForInvisible(this.selectors.myReports.toolbar.selectMenu(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Create a new report from the "My Reports" panel by clicking the "New"
     * button.
     */
    createNewReport() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.newButton());
    }

    /**
     * Click the "New Based On" button.
     *
     * Must click the name of the report template separately.
     */
    clickNewBasedOn() {
        this.waitForMyReportsPanelVisible();
        // There are two separate "New Based On" buttons.  Only one should be
        // visible at a time.
        const visibleButtons = $$(this.selectors.myReports.toolbar.newBasedOnButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
        expect(visibleButtons.length, 'One "New Based On" button is visible').to.be.equal(1);
        visibleButtons[0].click();
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        // Ext seems to also be "slow" to add events to the button, wait to make sure events get added
        browser.pause(750);
    }

    /**
     * Get the report template names.
     *
     * The list of report template names must be visible.
     *
     * @return {String[]} Report template names.
     */
    getReportTemplateNames() {
        browser.waitForVisible(this.selectors.myReports.toolbar.newBasedOnTemplateRows());
        return $$(this.selectors.myReports.toolbar.newBasedOnTemplateRows()).map(row => row.$(`//a[./img[${classContains('btn_report_template')}]]//b`).getText());
    }

    /**
     * Select a template from the "New Based On" menu.
     *
     * Must click the "New Based On" button before selecting the template.
     *
     * @param {String} templateName The full name of the template.
     * @param {String} center       The name of the center to select [optional].
     */
    selectNewBasedOnTemplate(templateName, center) {
        browser.waitForVisible(this.selectors.myReports.toolbar.newBasedOnMenu());
        const reportCount = this.getMyReportsRows().length;
        if (!center) {
            browser.waitAndClick(this.selectors.myReports.toolbar.newBasedOnTemplate(templateName));
        } else {
            // move the mouse to the middle of the menu so that the center selection menu appears
            browser.moveToObject(this.selectors.myReports.toolbar.newBasedOnTemplate(templateName));

            // wait for the new menu to be visible
            browser.waitForVisible(this.selectors.myReports.toolbar.newBasedOnTemplateWithCenter(center));

            // Select the option that corresponds with the center argument
            browser.click(this.selectors.myReports.toolbar.newBasedOnTemplateWithCenter(center));
        }
        // There is no visible indicator that the reports are being
        // updated, so wait for the number of rows to increase
        // specifically look for there to be at least one more item
        // this seems to be do the the fact that elements and selectors get cached
        browser.waitForVisible(this.selectors.myReports.reportList.rowByIndex(reportCount + 1));
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
    selectNewBasedOnReport(reportName) {
        browser.waitForVisible(this.selectors.myReports.toolbar.newBasedOnMenu());
        browser.click(this.selectors.myReports.toolbar.newBasedOnReport(reportName));
    }

    /**
     * Edit the selected report in the "My Reports" panel by clicking the
     * "Edit" button.
     */
    editSelectedReports() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.editButton());
    }

    /**
     * Edit a report in the "My Reports" panel by double clicking the row for
     * that report.
     *
     * @param {String} reportName Name of the report.
     */
    editReportByName(reportName) {
        this.waitForMyReportsPanelVisible();

        let foundReport = false;

        this.getMyReportsRows().forEach(row => {
            if (row.getName() === reportName) {
                row.doubleClick();
                foundReport = true;
            }
        });

        if (!foundReport) {
            throw new Error(`No report named "${reportName}" found`);
        }
    }

    /**
     * Preview the selected report in the "My Reports" panel by clicking the
     * "Preview" button.
     */
    previewSelectedReports() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.previewButton());
    }

    /**
     * Return to the "My Reports" panel from the "Report Preview" by clicking
     * the "Return To Reports Overview" button.
     */
    returnToReportsOverview() {
        this.waitForReportPreviewPanelVisible();
        browser.click(this.selectors.reportPreview.toolbar.returnToReportsOverviewButton());
    }

    /**
     * Click the "Send Now" button in the "My Reports" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    sendSelectedReportsNow() {
        this.waitForMyReportsPanelVisible();
        // There are two separate "Send Now" buttons in the "My Reports" panel.
        // Only one should be visible at a time.
        const visibleButtons = $$(this.selectors.myReports.toolbar.sendNowButton() + `/ancestor::table[${classContains('x-btn')}]`).filter(button => button.isVisible());
        expect(visibleButtons.length, 'One "Send Now" button is visible').to.be.equal(1);
        visibleButtons[0].click();
    }

    /**
     * Select the "As PDF" option from the "Send Now" menu in the "My Reports"
     * panel.
     */
    sendSelectedReportsAsPdfNow() {
        browser.waitForVisible(this.selectors.myReports.toolbar.sendNowAsPdfButton());
        browser.click(this.selectors.myReports.toolbar.sendNowAsPdfButton());
    }

    /**
     * Select the "As Word Document" option from the "Send Now" menu in the "My Reports"
     * panel.
     */
    sendSelectedReportsAsWordDocumentNow() {
        browser.waitForVisible(this.selectors.myReports.toolbar.sendNowAsWordDocumentButton());
        browser.click(this.selectors.myReports.toolbar.sendNowAsWordDocumentButton());
    }

    /**
     * Click the "Download" button in the "My Reports" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    downloadSelectedReports() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.downloadButton());
    }

    /**
     * Select the "As PDF" option from the "Download" menu in the "My Reports"
     * panel.
     */
    downloadSelectedReportsAsPdf() {
        browser.waitForVisible(this.selectors.myReports.toolbar.downloadAsPdfButton());
        browser.click(this.selectors.myReports.toolbar.downloadAsPdfButton());
        // Wait for check mark image to appear and disappear.
        browser.waitForVisible(this.selectors.checkmarkMask(), 60000);
        browser.waitForInvisible(this.selectors.checkmarkMask(), 60000);
    }

    /**
     * Select the "As Word Document" option from the "Download" menu in the
     * "My Reports" panel.
     */
    downloadSelectedReportsAsWordDocument() {
        browser.waitForVisible(this.selectors.myReports.toolbar.downloadAsWordDocumentButton());
        browser.click(this.selectors.myReports.toolbar.downloadAsWordDocumentButton());
        // Wait for check mark image to appear and disappear.
        browser.waitForVisible(this.selectors.checkmarkMask());
        browser.waitForInvisible(this.selectors.checkmarkMask(), 3500);
    }

    /**
     * Close the "Report Built" window.
     */
    closeReportBuiltWindow() {
        this.waitForReportBuiltWindowVisible();
        browser.click(this.selectors.reportBuilt.closeButton());
        this.waitForReportBuiltWindowNotVisible();
    }

    /**
     * Delete selected reports from "My Reports" by clicking the delete button.
     *
     * Does not confirm deletion of report, that button must be clicked
     * separately.
     */
    deleteSelectedReports() {
        this.waitForMyReportsPanelVisible();
        browser.click(this.selectors.myReports.toolbar.deleteButton());
    }

    /**
     * Click the "Yes" button in the "Delete Selected Report" window.
     */
    confirmDeleteSelectedReports() {
        const reportCount = this.getMyReportsRows().length;
        this.waitForDeleteSelectedReportsWindowVisible();
        browser.click(this.selectors.deleteSelectedReports.yesButton());
        this.waitForDeleteSelectedReportsWindowNotVisible();
        // There is no visible indicator that the reports are being
        // updated, so wait for the number of rows to change.
        browser.waitUntil(() => reportCount !== this.getMyReportsRows().length, 2000, 'Expect number of reports to change');
    }

    /**
     * Click the "Yes" button in the "Delete Selected Report" window.
     */
    cancelDeleteSelectedReports() {
        this.waitForDeleteSelectedReportsWindowVisible();
        browser.click(this.selectors.deleteSelectedReports.noButton());
        this.waitForDeleteSelectedReportsWindowNotVisible();
    }

    /**
     * Save the report currently being edited.
     */
    saveReport() {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.toolbar.saveButton());
        browser.waitForVisible(this.selectors.message.window());
        expect(this.getMessageWindowTitle(), 'Message window title is correct').to.be.equal('Report Editor');
        browser.waitForInvisible(this.selectors.message.window(), 5000);
    }

    /**
     * Save a report with a different name.
     *
     * Does not confirm saving the report.
     *
     * @param {String} reportName The new name to give the report (optional).
     */
    saveReportAs(reportName = undefined) {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.toolbar.saveAsButton());
        this.waitForSaveReportAsWindowVisible();
        if (reportName !== undefined) {
            /*
             * There is a "timing" issue when setting the value of the input box
             * and then clicking on save to quickly, this forces it to wait for
             * the invalid input to not be present
             */
            browser.setValue(this.selectors.saveReportAs.reportNameInput(), reportName);
            browser.waitUntilNotExist(this.selectors.saveReportAs.reportNameInputInvalid());
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
    confirmSaveReportAs(expectError = false) {
        this.waitForSaveReportAsWindowVisible();
        browser.click(this.selectors.saveReportAs.saveButton());

        if (!expectError) {
            expect(this.getMessageWindowTitle(), 'Message window title is correct').to.be.equal('Report Editor');
            expect(this.getMessage(), 'Message is correct').to.be.equal('Report successfully saved as a copy');
            browser.waitForInvisible(this.selectors.message.window(), 5000);
        }
    }

    /**
     * Close "Save Report As" window by clicking the "Close" button.
     */
    closeSaveReportAs() {
        this.waitForSaveReportAsWindowVisible();
        browser.click(this.selectors.saveReportAs.closeButton());
    }

    /**
     * Preview the report currently being edited.
     */
    previewCurrentReport() {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.toolbar.previewButton());
    }

    /**
     * Click the "Send Now" button in the "Report Editor" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    sendCurrentlyBeingEditedReportNow() {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.toolbar.sendNowButton());
    }

    /**
     * Select the "As PDF" option from the "Send Now" menu in the
     * "Report Editor" panel.
     */
    sendCurrentlyBeingEditedReportAsPdfNow() {
        this.waitForReportEditorPanelVisible();
        browser.waitForVisible(this.selectors.reportEditor.toolbar.sendNowAsPdfButton());
        browser.click(this.selectors.reportEditor.toolbar.sendNowAsPdfButton());
    }

    /**
     * Select the "As PDF" option from the "Send Now" menu in the
     * "Report Editor" panel.
     */
    sendCurrentlyBeingEditedReportAsWordDocumentNow() {
        this.waitForReportEditorPanelVisible();
        browser.waitForVisible(this.selectors.reportEditor.toolbar.sendNowAsWordDocumentButton());
        browser.click(this.selectors.reportEditor.toolbar.sendNowAsWordDocumentButton());
    }

    /**
     * Click the "Download" button in the "Report Editor" panel.
     *
     * It may be necessary to click the "As PDF" or "As Word Document" option
     * after clicking this button.
     */
    downloadCurrentlyBeingEditedReport() {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.toolbar.downloadButton());
    }

    /**
     * Select the "As PDF" option from the "Download" menu in the
     * "Report Editor" panel.
     */
    downloadCurrentlyBeingEditedReportAsPdf() {
        browser.waitForVisible(this.selectors.reportEditor.toolbar.downloadAsPdfButton());
        browser.click(this.selectors.reportEditor.toolbar.downloadAsPdfButton());
    }

    /**
     * Select the "As Word Document" option from the "Download" menu in the
     * "Report Editor" panel.
     */
    downloadCurrentlyBeingEditedReportAsWordDocument() {
        browser.waitForVisible(this.selectors.reportEditor.toolbar.downloadAsWordDocumentButton());
        browser.click(this.selectors.reportEditor.toolbar.downloadAsWordDocumentButton());
    }

    /**
     * Return to "My Reports" by clicking the "Return to My Reports" button.
     */
    returnToMyReports() {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.toolbar.returnToMyReportsButton());
    }

    /**
     * Get the name of the report currently being edited.
     *
     * @return {String}
     */
    getReportName() {
        this.waitForReportEditorPanelVisible();
        return browser.getValue(this.selectors.reportEditor.generalInformation.reportNameInput());
    }

    /**
     * Set the name of the report currently being edited.
     *
     * @param {String} name
     */
    setReportName(name) {
        this.waitForReportEditorPanelVisible();
        return browser.setValue(this.selectors.reportEditor.generalInformation.reportNameInput(), name);
    }

    /**
     * Get the title of the report currently being edited.
     *
     * @return {String}
     */
    getReportTitle() {
        this.waitForReportEditorPanelVisible();
        return browser.getValue(this.selectors.reportEditor.generalInformation.reportTitleInput());
    }

    /**
     * Set the title of the report currently being edited.
     *
     * @param {String} reportTitle
     */
    setReportTitle(reportTitle) {
        this.waitForReportEditorPanelVisible();
        return browser.setValue(this.selectors.reportEditor.generalInformation.reportTitleInput(), reportTitle);
    }

    /**
     * Get the header text of the report currently being edited.
     *
     * @return {String}
     */
    getHeaderText() {
        this.waitForReportEditorPanelVisible();
        return browser.getValue(this.selectors.reportEditor.generalInformation.headerTextInput());
    }

    /**
     * Set the header text of the report currently being edited.
     *
     * @param {String} headerText
     */
    setHeaderText(headerText) {
        this.waitForReportEditorPanelVisible();
        return browser.setValue(this.selectors.reportEditor.generalInformation.headerTextInput(), headerText);
    }

    /**
     * Get the footer text of the report currently being edited.
     *
     * @return {String}
     */
    getFooterText() {
        this.waitForReportEditorPanelVisible();
        return browser.getValue(this.selectors.reportEditor.generalInformation.footerTextInput());
    }

    /**
     * Set the footer text of the report currently being edited.
     *
     * @param {String} footerText
     */
    setFooterText(footerText) {
        this.waitForReportEditorPanelVisible();
        return browser.setValue(this.selectors.reportEditor.generalInformation.footerTextInput(), footerText);
    }

    /**
     * Get the number of charts per page for the report currently being edited.
     *
     * @return {Number}
     */
    getNumberOfChartsPerPage() {
        this.waitForReportEditorPanelVisible();
        if (browser.isSelected(this.selectors.reportEditor.chartLayout.oneChartPerPageRadioButton())) {
            return 1;
        } else if (browser.isSelected(this.selectors.reportEditor.chartLayout.twoChartsPerPageRadioButton())) {
            return 2;
        }

        throw new Error('No charts per page option selected');
    }

    /**
     * Set the number of charts per page for the report currently being edited.
     *
     * @param {Number} chartsPerPage Must be 1 or 2.
     */
    setNumberOfChartsPerPage(chartsPerPage) {
        this.waitForReportEditorPanelVisible();

        if (chartsPerPage === 1) {
            browser.click(this.selectors.reportEditor.chartLayout.oneChartPerPageRadioButton());
        } else if (chartsPerPage === 2) {
            browser.click(this.selectors.reportEditor.chartLayout.twoChartsPerPageRadioButton());
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
    getSchedule() {
        this.waitForReportEditorPanelVisible();
        return browser.getValue(this.selectors.reportEditor.scheduling.scheduleInput());
    }

    /**
     * Set the schedule (delivery frequency) of the report currently being
     * edited.
     *
     * @param {String} frequency
     */
    setSchedule(frequency) {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.scheduling.scheduleInput());
        browser.waitForVisible(this.selectors.reportEditor.scheduling.scheduleOption(frequency));
        browser.click(this.selectors.reportEditor.scheduling.scheduleOption(frequency));
    }

    /**
     * Get the delivery format ('PDF" or "Word Document') of the report
     * currently being edited.
     *
     * @return {String}
     */
    getDeliveryFormat() {
        this.waitForReportEditorPanelVisible();
        return browser.getValue(this.selectors.reportEditor.scheduling.deliveryFormatInput());
    }

    /**
     * Set the delivery format ('PDF" or "Word Document') of the report
     * currently being edited.
     *
     * @param {String} format
     */
    setDeliveryFormat(format) {
        this.waitForReportEditorPanelVisible();
        browser.click(this.selectors.reportEditor.scheduling.deliveryFormatInput());
        browser.waitForVisible(this.selectors.reportEditor.scheduling.deliveryFormatOption(format));
        browser.click(this.selectors.reportEditor.scheduling.deliveryFormatOption(format));
    }

    /**
     * Select all charts in the "Included Charts" panel by clicking the "Select"
     * button then clicking "All Charts".
     */
    selectAllIncludedCharts() {
        this.waitForIncludedChartsPanelVisible();
        browser.click(this.selectors.reportEditor.includedCharts.toolbar.selectButton());
        browser.waitForVisible(this.selectors.reportEditor.includedCharts.toolbar.selectAllChartsButton());
        browser.click(this.selectors.reportEditor.includedCharts.toolbar.selectAllChartsButton());
        browser.waitForInvisible(this.selectors.reportEditor.includedCharts.toolbar.selectAllChartsButton(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Deselect all charts in the "Included Charts" panel by clicking the
     * "Select" button then clicking "No Charts".
     */
    deselectAllIncludedCharts() {
        this.waitForIncludedChartsPanelVisible();
        browser.click(this.selectors.reportEditor.includedCharts.toolbar.selectButton());
        browser.waitForVisible(this.selectors.reportEditor.includedCharts.toolbar.selectNoChartsButton());
        browser.click(this.selectors.reportEditor.includedCharts.toolbar.selectNoChartsButton());
        browser.waitForInvisible(this.selectors.reportEditor.includedCharts.toolbar.selectNoChartsButton(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Invert the chart selection in the "Included Charts" panel by clicking the
     * "Select" button then clicking "Invert Selection".
     */
    invertIncludedChartsSelection() {
        this.waitForIncludedChartsPanelVisible();
        browser.click(this.selectors.reportEditor.includedCharts.toolbar.selectButton());
        browser.waitForVisible(this.selectors.reportEditor.includedCharts.toolbar.invertSelectionButton());
        // Multiple buttons match the "Invert Selection" selector, but only one should be visible.
        const visibleButtons = $$(this.selectors.myReports.toolbar.invertSelectionButton()).filter(button => button.isVisible());
        expect(visibleButtons.length, 'One "Invert Selection" button is visible').to.be.equal(1);
        visibleButtons[0].click();
        browser.waitForInvisible(this.selectors.reportEditor.includedCharts.toolbar.invertSelectionButton(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Click the "Edit Timeframe of Selected Charts" button in the "Included
     * Charts" panel.
     */
    editTimeframeOfSelectedCharts() {
        browser.click(this.selectors.reportEditor.includedCharts.toolbar.editTimeframeButton());
    }

    /**
     * Click the "Specific" radio button in the "Edit Chart Timeframe" window.
     */
    selectSpecificChartTimeframe() {
        this.waitForEditChartTimeframeWindowVisible();
        browser.click(this.selectors.editChartTimeframe.specificRadioButton());
    }

    /**
     * Set the start date in the "Edit Chart Timeframe" window.
     *
     * @param {String} startDate
     */
    setSpecificChartTimeframeStartDate(startDate) {
        this.waitForEditChartTimeframeWindowVisible();
        browser.setValue(this.selectors.editChartTimeframe.startDateInput(), startDate);
    }

    /**
     * Set the end date in the "Edit Chart Timeframe" window.
     *
     * @param {String} endDate
     */
    setSpecificChartTimeframeEndDate(endDate) {
        this.waitForEditChartTimeframeWindowVisible();
        browser.setValue(this.selectors.editChartTimeframe.endDateInput(), endDate);
    }

    /**
     * Click the "Periodic" radio button in the "Edit Chart Timeframe" window
     * and pick a duration.
     *
     * @param {String} duration Name of the periodic duration.
     */
    selectPeriodicChartTimeframe(duration) {
        this.waitForEditChartTimeframeWindowVisible();
        browser.click(this.selectors.editChartTimeframe.periodicRadioButton());
        browser.waitForVisible(this.selectors.editChartTimeframe.periodicInput());
        browser.click(this.selectors.editChartTimeframe.periodicInput());
        browser.waitForVisible(this.selectors.editChartTimeframe.periodicOption(duration));
        browser.click(this.selectors.editChartTimeframe.periodicOption(duration));
        browser.waitForInvisible(this.selectors.editChartTimeframe.periodicOption(duration), 500);
    }

    /**
     * Click the "Cancel" button in the "Edit Chart Timeframe" window.
     */
    cancelEditTimeframeOfSelectedCharts() {
        this.waitForEditChartTimeframeWindowVisible();
        browser.click(this.selectors.editChartTimeframe.cancelButton());
    }

    /**
     * Click the "Update" button in the "Edit Chart Timeframe" window.
     */
    confirmEditTimeframeOfSelectedCharts() {
        this.waitForEditChartTimeframeWindowVisible();
        browser.click(this.selectors.editChartTimeframe.updateButton());
    }

    /**
     * Get the error message displayed in the "Edit Chart Timeframe" window.
     *
     * This method must be called while the error message is still visible (or
     * directly before the message appears) or it will fail.
     *
     * @return {String} The text of the error message.
     */
    getEditChartTimeframeErrorMessage() {
        browser.waitForVisible(this.selectors.editChartTimeframe.errorMessage());
        return browser.getText(this.selectors.editChartTimeframe.errorMessage());
    }

    /**
     * Remove selected charts from the "Included Charts" panel by clicking the
     * "Remove" button.
     */
    removeSelectedIncludedCharts() {
        this.waitForIncludedChartsPanelVisible();
        browser.click(this.selectors.reportEditor.includedCharts.toolbar.deleteButton());
    }

    /**
     * Click the "No" button in the "Remove Selected Chart" window.
     */
    cancelRemoveSelectedIncludedCharts() {
        this.waitForRemoveSelectedChartsWindowVisible();
        browser.click(this.selectors.removeSelectedCharts.noButton());
        this.waitForRemoveSelectedChartsWindowNotVisible();
    }

    /**
     * Click the "Yes" button in the "Remove Selected Chart" window.
     */
    confirmRemoveSelectedIncludedCharts() {
        this.waitForRemoveSelectedChartsWindowVisible();
        const chartCount = this.getIncludedCharts().length;
        browser.click(this.selectors.removeSelectedCharts.yesButton());
        this.waitForRemoveSelectedChartsWindowNotVisible();
        // There is no visible indicator that the charts are being
        // updated, so wait for the number of rows to change.
        browser.waitUntil(() => chartCount !== this.getIncludedCharts().length, 2000, 'Expect number of charts to change');
    }

    /**
     * Select all charts in the "Available Charts" panel by clicking the
     * "Select" button then clicking "All Charts".
     */
    selectAllAvailableCharts() {
        this.waitForAvailableChartsPanelVisible();
        browser.click(this.selectors.availableCharts.toolbar.selectButton());
        browser.waitForVisible(this.selectors.availableCharts.toolbar.selectAllChartsButton());
        browser.click(this.selectors.availableCharts.toolbar.selectAllChartsButton());
        browser.waitForInvisible(this.selectors.availableCharts.toolbar.selectAllChartsButton(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Deselect all charts in the "Available Charts" panel by clicking the
     * "Select" button then clicking "No Charts".
     */
    deselectAllAvailableCharts() {
        this.waitForAvailableChartsPanelVisible();
        browser.click(this.selectors.availableCharts.toolbar.selectButton());
        browser.waitForVisible(this.selectors.availableCharts.toolbar.selectNoChartsButton());
        browser.click(this.selectors.availableCharts.toolbar.selectNoChartsButton());
        browser.waitForInvisible(this.selectors.availableCharts.toolbar.selectNoChartsButton(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Invert the chart selection in the "Available Charts" panel by clicking
     * the "Select" button then clicking "Invert Selection".
     */
    invertAvailableChartsSelection() {
        this.waitForAvailableChartsPanelVisible();
        browser.click(this.selectors.availableCharts.toolbar.selectButton());
        browser.waitForVisible(this.selectors.availableCharts.toolbar.invertSelectionButton());
        // Multiple buttons match the "Invert Selection" selector, but only one should be visible.
        const visibleButtons = $$(this.selectors.myReports.toolbar.invertSelectionButton()).filter(button => button.isVisible());
        expect(visibleButtons.length, 'One "Invert Selection" button is visible').to.be.equal(1);
        visibleButtons[0].click();
        browser.waitForInvisible(this.selectors.availableCharts.toolbar.invertSelectionButton(), 500);
        // Ext.Button ignores clicks for 250ms after the menu is hidden so pause
        // in case the menu is used multiple times in a row.
        browser.pause(500);
    }

    /**
     * Delete selected charts from "Available Charts" by clicking the
     * delete button.
     *
     * Does not confirm deletion of carts, that button must be clicked
     * separately.
     *
     */
    deleteSelectedAvailableCharts() {
        this.waitForAvailableChartsPanelVisible();
        browser.click(this.selectors.availableCharts.toolbar.deleteButton());
    }

    /**
     * Click the "Yes" button in the "Delete Selected Charts" window.
     */
    confirmDeleteSelectedAvailableCharts() {
        this.waitForDeleteSelectedChartsWindowVisible();
        const chartCount = this.getAvailableCharts().length;
        browser.click(this.selectors.deleteSelectedCharts.yesButton());
        // There is no visible indicator that the charts are being
        // updated, so wait for the number of rows to change.
        browser.waitUntil(() => chartCount !== this.getAvailableCharts().length, 2000, 'Expect number of charts to change');
    }

    /**
     * Click the "No" button in the "Delete Selected Charts" window.
     */
    cancelDeleteSelectedAvailableCharts() {
        this.waitForDeleteSelectedChartsWindowVisible();
        browser.click(this.selectors.deleteSelectedCharts.noButton());
    }

    /**
     * Add a chart to the report that is currently being edited.
     *
     * @param {Number} index The 0-based index of the chart in the list of
     *   available charts.
     */
    addChartToReport(index) {
        this.waitForAvailableChartsPanelVisible();
        this.waitForIncludedChartsPanelVisible();
        const charts = this.getAvailableCharts();
        const includedChartCountBefore = this.getIncludedCharts().length;
        expect(index, 'Index is valid').to.be.below(charts.length);
        browser.dragAndDrop(this.selectors.availableCharts.chartList.rows() + `[${index + 1}]`, this.selectors.reportEditor.includedCharts.chartList.panel());
        browser.waitForExist(this.selectors.reportEditor.includedCharts.chartList.rows() + `[${includedChartCountBefore + 1}]`);
    }

    getCharts(user, report_template_index, options) {
        var charts = expected[user].report_templates[report_template_index].charts;
        charts.forEach(function (chart, i) {
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

module.exports = new ReportGenerator();
