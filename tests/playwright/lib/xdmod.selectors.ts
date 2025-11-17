const selectors ={
    mask: '//body/div[@class="ext-el-mask"]',
    tab: function(tabId) {
        return '//div[contains(@class, "x-tab-strip-wrap")]//li[@id="main_tab_panel__' + tabId + '"]';
    },
    panel: function(tabId) {
        return '//div[@id="' + tabId + '"]';
    },
    exportDialog: {
        window: '//body/div[contains(@class,"x-window")]//span[contains(@class, "x-window-header-text") and text() = "Export"]/ancestor::node()[5]',
        cancelButton: function () {
            return selectors.exportDialog.window + '//button[contains(text(), "Cancel")]';
        },
        formElement: function (formElementName) {
            return selectors.exportDialog.window + '//input[@name="' + formElementName + '"]';
        },
        dropDown: function (formElementName) {
            return selectors.exportDialog.formElement(formElementName) + '/../img[contains(@class,"x-form-arrow-trigger")]';
        },
        formatDropdown: function () {
            return selectors.exportDialog.dropDown('format_type');
        },
        showTitleCheckbox: function () {
            return selectors.exportDialog.formElement('show_title');
        },
        imageSizeDropdown: function () {
            return selectors.exportDialog.dropDown('image_size');
        },
        widthInput: function () {
            return selectors.exportDialog.formElement('width_inches');
        },
        heightInput: function () {
            return selectors.exportDialog.formElement('height_inches');
        },
        fontInput: function () {
            return selectors.exportDialog.formElement('font_pt');
        },
        comboList: '//div[contains(@class, "x-combo-list")]',
        comboListItemByNum: function(num) {
            return '(' + selectors.exportDialog.comboList + ')[' + num + ']';
        },
        comboListItems: '//div[contains(@style, "visibility: visible")]//div[contains(@class, "x-combo-list-item")]',
        comboListItemByName: function (name) {
            return '//div[contains(@style, "visibility: visible")]//div[contains(@class, "x-combo-list-item") and contains(text(), "' + name + '")]';
        }
    }
}
export default selectors;
