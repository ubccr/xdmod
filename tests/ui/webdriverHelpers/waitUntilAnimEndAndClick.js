/*
 *  Wait for selector to stop animating then click on it
 *
 *  @param {string} selector - element to click on
 *  @param {string} [button=left] which mouse key to use ["left", "middle", "right"]
 *  @param {Number} [ms=9000] - Milliseconds to wait for element to stop moving
 *  @param {Number} [interval=250] - interval between element location checks
 *
 *  @uses waitUntilAnimEnd commands/buttonPress
 */

module.exports = function waitUntilAnimEndAndClick(selector, button, ms, interval) {
    var clickButton = button || 'left';
    var timeOut = ms || 9000;
    var checkInterval = interval || 250;
    this.waitUntilAnimEnd(selector, timeOut, checkInterval);
    this.moveToObject(selector);
    return this.buttonPress(clickButton);
};
