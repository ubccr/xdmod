/*
 *  Wait for chart to load
 *  TODO: Find a better way to determine if chart is rendered
 *
 *  @param {Number} [ms=9000] - Milliseconds to wait for chat to be visible
 *
 *  @uses commands/waitForVisible, protocol/pause
 */

module.exports = function waitForChart(ms) {
    var timeOut = ms || 9000;

    return this.waitForVisible('.ext-el-mask-msg', timeOut, true);
};
