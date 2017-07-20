/*
 *  Wait for selector to stop animating, where animation is defined
 *  as moving in the view.
 *
 *  @param {string} selector - element to click on
 *  @param {Number} [ms=9000] - Milliseconds to wait for element to be visible
 *  @param {Number} [interval=250] - interval between element location checks
 *
 *  @uses commands/waitUntil commands/getLocationInView
 */

module.exports = function waitUntilAnimEnd(selector, ms, interval) {
    var timeOut = ms || 9000;
    var checkInterval = interval || 250;

    var initialLoc = { x: -1, y: -1 };

    this.waitForVisible(selector, timeOut);
    return this.waitUntil(function () {
        var loc = this.getLocationInView(selector);
        if (loc.x === initialLoc.x && loc.y === initialLoc.y) {
            return true;
        }

        initialLoc = loc;

        return false;
    }, timeOut, checkInterval);
};
