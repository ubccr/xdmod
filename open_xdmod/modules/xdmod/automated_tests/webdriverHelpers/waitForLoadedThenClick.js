/*
 *  Wait for loadingMask to not exist and selector to exist then click on selector
 *
 *	@param {string} selector - element to click on
 *	@param {Number} [maskMs=500] - Milliseconds to wait for mask to not exist
 *	@param {Number} [clickMs=500] - Milliseconds to wait for selector to be visible
 *
 *	@uses commands/waitForVisible, helpers/waitAndClick
 */
module.exports = function waitForLoadedThenClick(selector, maskMs, clickMs){
		maskMs = maskMs || 9000;
		clickMs = clickMs || 5000;
		browser.waitForVisible(".ext-el-mask-msg", maskMs, true);	
		return browser.click(selector);
};
