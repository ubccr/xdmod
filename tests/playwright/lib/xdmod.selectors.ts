const xdmodSelectors ={
    mask: '.ext-el-mask',
    exportDialog: {
        window: '//body/div[contains(@class,"x-window")]//span[contains(@class, "x-window-header-text") and text() = "Export"]/ancestor::node()[5]',
        cancelButton: function () {
            return xdmodSelectors.exportDialog.window + '//button[contains(text(), "Cancel")]';
        },
        formElement: function (formElementName) {
            return xdmodSelectors.exportDialog.window + '//input[@name="' + formElementName + '"]';
        },
        dropDown: function (formElementName) {
            return xdmodSelectors.exportDialog.formElement(formElementName) + '/../img[contains(@class,"x-form-arrow-trigger")]';
        },
        formatDropdown: function () {
            return xdmodSelectors.exportDialog.dropDown('format_type');
        },
        showTitleCheckbox: function () {
            return xdmodSelectors.exportDialog.formElement('show_title');
        },
        imageSizeDropdown: function () {
            return xdmodSelectors.exportDialog.dropDown('image_size');
        },
        widthInput: function () {
            return xdmodSelectors.exportDialog.formElement('width_inches');
        },
        heightInput: function () {
            return xdmodSelectors.exportDialog.formElement('height_inches');
        },
        fontInput: function () {
            return xdmodSelectors.exportDialog.formElement('font_pt');
        },
        comboList: '//div[contains(@class, "x-combo-list")]',
        comboListItems: '//div[contains(@style, "visibility: visible")]//div[contains(@class, "x-combo-list-item")]',
        comboListItemByName: function (name) {
            return '//div[contains(@style, "visibility: visible")]//div[contains(@class, "x-combo-list-item") and contains(text(), "' + name + '")]';
        }
    }
}
export default xdmodSelectors;
