class XDMoD {
    constructor() {
        this.selectors = {
            mask: '.ext-el-mask',
            exportDialog: {
                window: '//body/div[contains(@class,"x-window")]//span[contains(@class, "x-window-header-text") and text() = "Export"]/ancestor::node()[5]',
                cancelButton: function () {
                    return module.exports.selectors.exportDialog.window + '//button[contains(text(), "Cancel")]';
                },
                formElement: function (formElementName) {
                    return module.exports.selectors.exportDialog.window + '//input[@name="' + formElementName + '"]';
                },
                dropDown: function (formElementName) {
                    return module.exports.selectors.exportDialog.formElement(formElementName) + '/../img[contains(@class,"x-form-arrow-trigger")]';
                },
                formatDropdown: function () {
                    return module.exports.selectors.exportDialog.dropDown('format_type');
                },
                showTitleCheckbox: function () {
                    return module.exports.selectors.exportDialog.formElement('show_title');
                },
                imageSizeDropdown: function () {
                    return module.exports.selectors.exportDialog.dropDown('image_size');
                },
                widthInput: function () {
                    return module.exports.selectors.exportDialog.formElement('width_inches');
                },
                heightInput: function () {
                    return module.exports.selectors.exportDialog.formElement('height_inches');
                },
                fontInput: function () {
                    return module.exports.selectors.exportDialog.formElement('font_pt');
                },
                comboList: '//div[contains(@class, "x-combo-list")]',
                comboListItems: '//div[contains(@style, "visibility: visible")]//div[contains(@class, "x-combo-list-item")]',
                comboListItemByName: function (name) {
                    return '//div[contains(@style, "visibility: visible")]//div[contains(@class, "x-combo-list-item") and contains(text(), "' + name + '")]';
                }
            }
        };
    }
    selectTab(tabId) {
        var tab = '#main_tab_panel__' + tabId;
        var panel = '//div[@id="' + tabId + '"]';

        browser.waitForVisible(tab);
        for (let i = 0; i < 100; i++) {
            try {
                browser.click(tab);
                break;
            } catch (e) {
                browser.waitForAllInvisible(this.selectors.mask);
            }
        }
        browser.waitForVisible(panel);
        browser.waitForAllInvisible(this.selectors.mask);
    }
}
module.exports = new XDMoD();
