/*
 *  Wait for selector to be visible then click on it
 *
 *  @param {string} selector - element to click on
 *  @param {Number} [ms=9000] - Milliseconds to wait for element to be visible
 *
 *  @uses commands/waitForExist
 */

module.exports = function waitUntilNotExist(selector, ms) {
    var timeOut = ms || 9000;
    return this.waitForExist(selector, timeOut, true);
};
