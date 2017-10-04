class XDMoD {
    constructor() {
        this.selectors = {
            mask: '.ext-el-mask'
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
                browser.waitForAllInvisible(this.selectors.mask, 5000);
            }
        }
        browser.waitForVisible(panel);
        browser.waitForAllInvisible(this.selectors.mask, 5000);
    }
}
module.exports = new XDMoD();
