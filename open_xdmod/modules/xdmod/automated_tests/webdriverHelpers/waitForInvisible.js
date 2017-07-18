/*
 *  Wait for selector to be invisible
 *
 *	@param {string} selector - element to check
 *	@param {Number} [ms=500] - Milliseconds to wait for element to be invisible
 *
 *	@uses commands/waitForVisible
 */

module.exports = function waitForInvisible(selector, ms) {
	ms = ms || 1000;
	return this.waitForVisible(selector, ms, true);
};
