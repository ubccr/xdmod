/*
 *  Wait for all elements that match a selector to be invisible.
 *
 *  @param {string} selector - elements to check
 *  @param {Number} [ms=1000] - Milliseconds to wait for elements to be invisible
 *
 *  @uses commands/waitForVisible
 */

module.exports = function waitForAllInvisible(selector, ms) {
    var timeOut = ms || 1000;
    browser.waitUntil(() => $$(selector).filter(el => el.isVisible()).length === 0);
};
