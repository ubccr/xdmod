/*
 *  Wait for selector to be visible then click on it
 *
 *  @param {string} selector - element to click on
 *  @param {Number} [ms=5000] - Milliseconds to wait for element to be visible
 *
 *  @uses commands/waitForVisible, commands/click
 */
module.exports = function waitAndClick(selector, ms) {
    var thisMs = ms || 5000;

    this.waitForVisible(selector, thisMs);
    return this.click(selector);
};
