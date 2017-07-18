/*
 *  Wait for loadingMask to not exist and selector to exist then click on selector
 *
 *  @param {string} selector - element to click on
 *  @param {Number} [maskMs=500] - Milliseconds to wait for mask to not exist
 *
 *  @uses commands/waitForVisible, helpers/waitAndClick
 */
module.exports = function waitForLoadedThenClick(selector, maskMs) {
    var maskTimeOut = maskMs || 9000;
    browser.waitForVisible('.ext-el-mask-msg', maskTimeOut, true);
    return browser.click(selector);
};
