/*
 *  Wait for chart to load
 *	TODO: Find a better way to determine if chart is rendered
 *
 *	@param {Number} [ms=3000] - Milliseconds to wait for chat to be visible
 *
 *	@uses commands/waitForVisible, protocol/pause
 */

module.exports = function waitForChart(ms) {
	ms = ms || 3000;

    return this.waitForVisible(".ext-el-mask-msg", 9000, true)
};
