/*
 *  Wait for selector to stop animating then click on it
 *
 *	@param {string} selector - element to click on
 *	@param {string} [button=left] which mouse key to use ["left", "middle", "right"]
 *	@param {Number} [ms=9000] - Milliseconds to wait for element to stop moving
 *	@param {Number} [interval=250] - interval between element location checks
 *
 *	@uses waitUntilAnimEnd commands/buttonPress
 */

module.exports = function waitUntilAnimEndAndClick(selector, button, ms, interval) {
	button = button || 'left';
	ms = ms || 9000;
	interval = interval || 250;
    this.waitUntilAnimEnd(selector, ms,interval);
    this.moveToObject(selector);
    return this.buttonPress(button);
};
