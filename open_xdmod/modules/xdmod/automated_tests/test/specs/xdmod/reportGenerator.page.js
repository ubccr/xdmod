class ReportGenerator {
    constructor() {
        this.name = 'Report Generator';
        this.contents = "//div[@id='report_generator']";
        this.dateEditorcmp = "//div[contains(@class, 'chart_date_editor')]";
        this.radial = {
            periodic: 'Periodic',
            specific: 'Specific'
        };
        this.items = [
            'Once',
            'Daily',
            'Weekly',
            'Monthly',
            'Quarterly',
            'Semi-annually',
            'Annually'
        ];
        this.mask = "//div[contains(@class, 'ext-el-mask')]";
    }

    radioButton(value) {
        return this.dateEditorcmp + "//input[@type='radio' and contains(@class, 'x-form-radio') and @value='" + value + "']";
    }

    startDateField() {
        return this.dateEditorcmp + "//input[@type='text' and @id='report_generator_edit_date_start_date_field']";
    }

    endDateField() {
        return this.dateEditorcmp + "//input[@type='text' and @id='report_generator_edit_date_end_date_field']";
    }

    updateButton() {
        return this.dateEditorcmp + "//button[contains(@class, 'chart_date_editor_update_button')]";
    }

    errorMessageContains(message) {
        return this.dateEditorcmp + "//div[contains(@class, 'overlay_message') and text()[contains(., '" + message + "')]]";
    }

    cmp() {
        return this.contents + "//input[@type='text' and @id='report_generator_report_schedule']";
    }

    selectionByName(name) {
        return "//div[contains(@class, 'x-combo-list') and contains(@style, 'visibility: visible')]//div[contains(@class, 'x-combo-list-item') and text()[contains(.,'" + name + "')]]";
    }

    nextValue(value) {
        var values = this.items;
        var index = values.indexOf(value) + 1;
        if (index === values.length) {
            index = 0;
        }
        return values[index];
    }

    tabForName(name) {
        return "//div[contains(@class, 'x-tab-panel-header')]//span[contains(@class, 'x-tab-strip-text') and text()[contains(., '" + name + "')]]";
    }

    tableRowForColumnText(columnIndex, text) {
        return this.contents + "//div[contains(@class, 'x-grid3-row')]//table[contains(@class, 'x-grid3-row-table')]//div[contains(@class, 'x-grid3-cell-inner') and contains(@class, x-grid3-col-" + columnIndex + ") and text()[contains(., '" + text + "')]]";
    }

    tableRowByRowIndex(rowIndex) {
        var myrowIndex;
        if (typeof rowIndex !== 'number') {
            myrowIndex = 1;
        } else if (rowIndex < 1) {
            myrowIndex = 1;
        } else {
            myrowIndex = rowIndex;
        }
        return '(' + this.contents + "//div[@id='CurrentNewchartbaseTab_queueGrid']//div[contains(@class, 'x-grid3-row')])[" + myrowIndex + ']';
    }

    editDateForRowByIndex(rowIndex, byImage) {
        var newbyImage = byImage !== undefined ? byImage : false;
        if (newbyImage === true) {
            return this.tableRowByRowIndex(rowIndex) + "//a[@id='report_generator_timeframe_selector_img']";
        }
        return this.tableRowByRowIndex(rowIndex) + "//a[@id='report_generator_timeframe_selector']";
    }

    chartUpdateInProgressByIndex(rowIndex) {
        return this.tableRowByRowIndex(rowIndex) + "//img[contains(@src, 'report_gen_thumbnail_progress')]";
    }

    saveReportButton() {
        return this.contents + "//button[contains(@class, 'btn_save') and text()[contains(.,'Save')]]";
    }

    saveReportMessage() {
        return "//div[contains(@class, 'x-window') and @id='report_generator_message' and contains(@style, 'visibility: visible')]";
    }

    returnToMyReportsButton() {
        return this.contents + "//button[contains(@class, 'btn_return_to_overview')]";
    }

    saveTheReport() {
        browser.click(this.saveReportButton());
        browser.waitForVisible(this.saveReportMessage(), 50000);
        browser.waitForVisible(this.saveReportMessage(), 50000, true);
    }
    pressUpdate() {
        browser.pause(4000);
        browser.click(this.updateButton());
        browser.pause(4000);
        browser.waitForVisible(this.dateEditorcmp, 50000, true);
        browser.waitForVisible(this.chartUpdateInProgressByIndex(1), 50000, true);
    }
    verifyDateChange(startDate, endDate) {
        var dateChange = browser.getText(this.editDateForRowByIndex(1));
        var hasStartDate = dateChange.indexOf(startDate) >= 0;
        var hasEndDate = dateChange.indexOf(endDate) >= 0;
        expect(hasStartDate).to.equal(true);
        expect(hasEndDate).to.equal(true);
    }
    pressUpdateExpectError(error) {
        browser.click(this.updateButton());
        browser.waitForVisible(this.errorMessageContains(error), 60000);
        browser.waitForVisible(this.errorMessageContains(error), 60000, true);
    }
}
module.exports = new ReportGenerator();
