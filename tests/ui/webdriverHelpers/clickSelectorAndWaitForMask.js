

module.exports = function clickSelectorAndWaitForMask(selector, ms) {
    var thisMS = ms || 5000;

    browser.waitForVisible(selector, thisMS);

    browser.waitForInvisible('.ext-el-mask', thisMS);
    for (let i = 0; i < 100; i++) {
        try {
            browser.click(selector);
            break;
        } catch (e) {
            browser.waitForInvisible('.ext-el-mask', thisMS);
        }
    }
};
