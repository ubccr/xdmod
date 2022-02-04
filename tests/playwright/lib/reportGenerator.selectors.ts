const selectors = {
    tab: () => `//div[${classContains('x-tab-panel-header')}]//span[${classContains('x-tab-strip-text')} and text()='Report Generator']`,
    panel: () => '//div[@id="report_generator"]',
    mask: () => `//div[${classContains('ext-el-mask')}]`,
    configureTime: {
        frameButton: "(//div[@id='main_tab_panel']//div[@id='tg_usage']//table[@class='x-toolbar-ct']/tbody/tr/td[@class='x-toolbar-left']/table/tbody/tr[@class='x-toolbar-left-row']//tbody[@class='x-btn-small x-btn-icon-small-left'])[1]",
        byTimeFrameName: function(name) {
            return '//div[@class="x-menu x-menu-floating x-layer x-menu-nosep"]//ul//li//a//span[text()="' + name + '"]';
        }
    },
    myReports: {
        panel: () => selectors.panel() + `//div[${classContains('report_overview')}]`,
        toolbar: {
            panel: () => selectors.myReports.panel() + `//div[${classContains('x-panel-tbar')}]`,
            selectButton: () => selectors.myReports.toolbar.panel() + '//button[text()="Select"]',
            selectMenu: () => `//div[${classContains('x-menu-floating')} and contains(@style, "visibility: visible")]`,
            selectAllReportsButton: () => selectors.myReports.toolbar.selectMenu() + '//span[text()="All Reports"]/ancestor::a',
            selectNoReportsButton: () => selectors.myReports.toolbar.selectMenu() + '//span[text()="No Reports"]/ancestor::a',
            invertSelectionButton: () => selectors.myReports.toolbar.selectMenu() + '//span[text()="Invert Selection"]/ancestor::a',
            newButton: () => selectors.myReports.toolbar.panel() + '//button[text()="New"]',
            newBasedOnButton: () => selectors.myReports.toolbar.panel() + '//button[text()="New Based On"]',
            newBasedOnVisibleButton: () => selectors.myReports.toolbar.newBasedOnButton() + `/ancestor::table[${classContains('x-btn')}]`,
            numNewBasedOnVisibleButton: (num) => '(' + selectors.myReports.toolbar.newBasedOnVisibleButton() + ')[' + num + ']',
            newBasedOnMenu: () => `//div[${classContains('x-menu-floating')} and .//img[${classContains('btn_selected_report')} or ${classContains('btn_report_template')}]]`,
            newBasedOnRows: () => selectors.myReports.toolbar.newBasedOnMenu() + `//li[not(${classContains('x-menu-sep-li')})]`,
            newBasedOnTemplateRows: () => selectors.myReports.toolbar.newBasedOnMenu() + `//li[.//img[${classContains('btn_report_template')}]]`,
            newBasedOnTemplateRowsButton: () => selectors.myReports.toolbar.newBasedOnTemplateRows() + `//a[./img[${classContains('btn_report_template')}]]//b`,
            newBasedOnReportRows: () => selectors.myReports.toolbar.newBasedOnMenu() + `//li[.//img[${classContains('btn_selected_report')}]]`,
            newBasedOnTemplate: name => selectors.myReports.toolbar.newBasedOnTemplateRows() + `//a[.//b[text()="${name}"]]`,
            newBasedOnTemplateWithCenter: center => `//div[${classContains('x-menu-floating')}]//a[.//img[${classContains('btn_resource_provider')}] and .//span[contains(text(), "${center}")]]`,
            newBasedOnReport: name => selectors.myReports.toolbar.newBasedOnReportRows() + `//a[./img[.//b[text()="${name}"]]`,
            editButton: () => selectors.myReports.toolbar.panel() + '//button[text()="Edit"]',
            editButtonInClass: () => selectors.myReports.toolbar.editButton() + `/ancestor::table[${classContains('x-btn')}]`,
            previewButton: () => selectors.myReports.toolbar.panel() + '//button[text()="Preview"]',
            previewButtonInClass: () => selectors.myReports.toolbar.previewButton() + `/ancestor::table[${classContains('x-btn')}]`,
            sendNowButton: () => selectors.myReports.toolbar.panel() + '//button[text()="Send Now"]',
            sendNowButtonInClass: () => selectors.myReports.toolbar.sendNowButton() + `/ancestor::table[${classContains('x-btn')}]`,
            firstSendNowButton: () => '(' + selectors.myReports.toolbar.sendNowButton() + `/ancestor::table[${classContains('x-btn')}]` + ')[1]',
            sendNowAsPdfButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As PDF"]/ancestor::a`,
            sendNowAsWordDocumentButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As Word Document"]/ancestor::a`,
            downloadButton: () => selectors.myReports.toolbar.panel() + '//button[text()="Download"]',
            downloadButtonInClass: () => selectors.myReports.toolbar.downloadButton() + `/ancestor::table[${classContains('x-btn')}]`,
            firstDownloadButton: () => '(' + selectors.myReports.toolbar.downloadButton() + `/ancestor::table[${classContains('x-btn')}]` + ')[1]',
            downloadAsPdfButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As PDF"]/ancestor::a`,
            downloadAsWordDocumentButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="As Word Document"]/ancestor::a`,
            deleteButton: () => selectors.myReports.toolbar.panel() + '//button[text()="Delete"]',
            deleteButtonInClass: () => selectors.myReports.toolbar.deleteButton() + `/ancestor::table[${classContains('x-btn')}]`
        },
        reportList: {
            panel: () => selectors.myReports.panel() + `//div[${classContains('x-panel-body-noheader')}]`,
            rows: () => selectors.myReports.reportList.panel() + `//div[${classContains('x-grid3-row')}]`,
            rowByIndex: index => selectors.myReports.reportList.panel() + `//div[${classContains('x-grid3-row')} and position()=${index}]`
        }
    },
    reportPreview: {
        panel: () => selectors.panel() + `//div[${classContains('report_preview')}]`,
        toolbar: {
            panel: () => selectors.reportPreview.panel() + `//div[${classContains('x-panel-tbar')}]`,
            sendNowButton: () => selectors.reportPreview.toolbar.panel() + '//button[text()="Send Now"]',
            downloadButton: () => selectors.reportPreview.toolbar.panel() + '//button[text()="Download"]',
            returnToReportsOverviewButton: () => selectors.reportPreview.toolbar.panel() + `//button[${classContains('btn_return_to_previous')}]`
        }
    },
    reportEditor: {
        panel: () => selectors.panel() + `//div[${classContains('report_edit')}]`,
        toolbar: {
            panel: () => selectors.reportEditor.panel() + `//div[${classContains('x-panel-tbar')} and .//button[text()="Save"]]`,
            saveButton: () => selectors.reportEditor.toolbar.panel() + '//button[text()="Save"]',
            saveAsButton: () => selectors.reportEditor.toolbar.panel() + '//button[text()="Save As"]',
            previewButton: () => selectors.reportEditor.toolbar.panel() + '//button[text()="Preview"]',
            sendNowButton: () => selectors.reportEditor.toolbar.panel() + '//button[text()="Send Now"]',
            downloadButton: () => selectors.reportEditor.toolbar.panel() + '//button[text()="Download"]',
            returnToMyReportsButton: () => selectors.reportEditor.toolbar.panel() + `//button[${classContains('btn_return_to_overview')}]`
        },
        generalInformation: {
            panel: () => selectors.reportEditor.panel() + `//div[${classContains('x-panel')} and .//span[text()="General Information"]]`,
            reportNameInput: () => selectors.reportEditor.generalInformation.panel() + '//input[@name="report_name"]',
            reportTitleInput: () => selectors.reportEditor.generalInformation.panel() + '//input[@name="report_title"]',
            headerTextInput: () => selectors.reportEditor.generalInformation.panel() + '//input[@name="report_header"]',
            footerTextInput: () => selectors.reportEditor.generalInformation.panel() + '//input[@name="report_footer"]'
        },
        chartLayout: {
            panel: () => selectors.reportEditor.panel() + `//div[${classContains('x-panel')} and .//span[text()="Chart Layout"]]`,
            oneChartPerPageRadioButton: () => selectors.reportEditor.chartLayout.panel() + '//input[@value="1_up"]',
            twoChartsPerPageRadioButton: () => selectors.reportEditor.chartLayout.panel() + '//input[@value="2_up"]'
        },
        scheduling: {
            panel: () => selectors.reportEditor.panel() + `//div[${classContains('x-panel')} and .//span[text()="Scheduling"]]`,
            scheduleInput: () => selectors.reportEditor.scheduling.panel() + '//input[@name="report_generator_report_schedule"]',
            scheduleOption: name => `//div[${classContains('x-combo-list-item')} and text()="${name}"]`,
            deliveryFormatInput: () => selectors.reportEditor.scheduling.panel() + '//div[./label[text()="Delivery Format:"]]//input',
            deliveryFormatOption: name => `//div[${classContains('x-combo-list-item')} and text()="${name}"]`
        },
        includedCharts: {
            panel: () => selectors.reportEditor.panel() + '//div[@id="ReportCreatorGrid"]',
            toolbar: {
                panel: () => selectors.reportEditor.includedCharts.panel() + `//div[${classContains('x-panel-tbar')}]`,
                selectButton: () => selectors.reportEditor.includedCharts.toolbar.panel() + '//button[text()="Select"]',
                selectAllChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="All Charts"]/ancestor::a`,
                selectNoChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="No Charts"]/ancestor::a`,
                invertSelectionButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="Invert Selection"]/ancestor::a`,
                editTimeframeButton: () => selectors.reportEditor.includedCharts.toolbar.panel() + '//button[text()="Edit Timeframe of Selected Charts"]',
                removeButton: () => selectors.reportEditor.includedCharts.toolbar.panel() + '//button[text()="Remove"]'
            },
            chartList: {
                panel: () => selectors.reportEditor.includedCharts.panel() + '//div[@class="x-panel-body" and .//div[text()="Chart"]]',
                rows: () => selectors.reportEditor.includedCharts.chartList.panel() + `//div[${classContains('x-grid3-row')}]`
            }
        }
    },
    availableCharts: {
        panel: () => selectors.panel() + '//div[@id="chart_pool_panel"]',
        toolbar: {
            panel: () => selectors.availableCharts.panel() + '//div[@class="x-panel-tbar"]',
            selectButton: () => selectors.availableCharts.toolbar.panel() + '//button[text()="Select"]',
            selectAllChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="All Charts"]/ancestor::a`,
            selectNoChartsButton: () => `//div[${classContains('x-menu-floating')}]//span[text()="No Charts"]/ancestor::a`,
            invertSelectionButton: () => `//div[${classContains('x-menu-floating')}]//a[.//span[text()="Invert Selection"]]`,
            deleteButton: () => selectors.availableCharts.toolbar.panel() + '//button[text()="Delete"]'
        },
        chartList: {
            panel: () => selectors.availableCharts.panel() + '//div[@class="x-panel-body" and .//div[text()="Chart"]]',
            rows: () => selectors.availableCharts.chartList.panel() + `//div[${classContains('x-grid3-row')}]`
        }
    },
    message: {
        window: () => '//div[@id="report_generator_message"]',
        titleElement: () => selectors.message.window() + `//span[${classContains('x-window-header-text')}]`,
        textElement: () => selectors.message.window() + '//b'
    },
    deleteSelectedReports: {
        window: () => `//div[${classContains('x-window')} and .//span[text()="Delete Selected Report" or text()="Delete Selected Reports"]]`,
        yesButton: () => selectors.deleteSelectedReports.window() + '//button[text()="Yes"]',
        noButton: () => selectors.deleteSelectedReports.window() + '//button[text()="No"]'
    },
    unsavedChanges: {
        window: () => `//div[${classContains('x-window')} and .//span[text()="Unsaved Changes"]]`,
        yesButton: () => selectors.unsavedChanges.window() + '//button[text()="Yes"]',
        noButton: () => selectors.unsavedChanges.window() + '//button[text()="No"]',
        cancelButton: () => selectors.unsavedChanges.window() + '//button[text()="Cancel"]'
    },
    deleteSelectedCharts: {
        window: () => `//div[${classContains('x-window')} and .//span[text()="Delete Selected Chart" or text()="Delete Selected Charts"]]`,
        yesButton: () => selectors.deleteSelectedCharts.window() + '//button[text()="Yes"]',
        noButton: () => selectors.deleteSelectedCharts.window() + '//button[text()="No"]'
    },
    removeSelectedCharts: {
        window: () => `//div[${classContains('x-window')} and .//span[text()="Remove Selected Chart" or text()="Remove Selected Charts"]]`,
        yesButton: () => selectors.removeSelectedCharts.window() + '//button[text()="Yes"]',
        noButton: () => selectors.removeSelectedCharts.window() + '//button[text()="No"]'
    },
    saveReportAs: {
        window: () => `//div[${classContains('x-window')} and .//span[text()="Save Report As"]]`,
        reportNameInput: () => selectors.saveReportAs.window() + '//input[@name="report_name"]',
        saveButton: () => selectors.saveReportAs.window() + '//button[text()="Save"]',
        closeButton: () => selectors.saveReportAs.window() + '//button[text()="Close"]',
        reportNameInputInvalid: () => selectors.saveReportAs.window() + `//input[@name="report_name" and ${classContains('x-form-invalid')}]`
    },
    reportBuilt: {
        window: () => `//div[${classContains('x-window')} and .//span[text()="Report Built"]]`,
        viewReportButton: () => selectors.reportBuilt.window() + '//button[text()="View Report"]',
        closeButton: () => selectors.reportBuilt.window() + `//div[${classContains('x-tool-close')}]`
    },
    editChartTimeframe: {
        window: () => `//div[${classContains('chart_date_editor')}]`,
        specificRadioButton: () => selectors.editChartTimeframe.window() + '//input[@name="report_creator_chart_entry" and @value="Specific"]',
        periodicRadioButton: () => selectors.editChartTimeframe.window() + '//input[@name="report_creator_chart_entry" and @value="Periodic"]',
        periodicInput: () => selectors.editChartTimeframe.window() + '//table[contains(@class,"menu")]//button',
        periodicOption: name => `//div[${classContains('x-menu-floating')}]//a[starts-with(text(),"${name}')]`,
        startDateInput: () => selectors.editChartTimeframe.window() + '//input[@id="report_generator_edit_date_start_date_field"]',
        endDateInput: () => selectors.editChartTimeframe.window() + '//input[@id="report_generator_edit_date_end_date_field"]',
        updateButton: () => selectors.editChartTimeframe.window() + `//button[${classContains('chart_date_editor_update_button')}]`,
        cancelButton: () => selectors.editChartTimeframe.window() + `//button[${classContains('chart_date_editor_cancel_button')}]`,
        errorMessage: () => selectors.editChartTimeframe.window() + `//div[${classContains('overlay_message')}]`
    },
    // The mask with the check mark image that is displayed after a report is ready for download or has been emailed.
    checkmarkMask: () => `//div[${classContains('ext-el-mask-msg')} and .//img[@src="gui/images/checkmark.png"]]`,
    reportDisplay: '//div[@class="x-panel report_overview x-hide-display"]'
};

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

export default selectors;
