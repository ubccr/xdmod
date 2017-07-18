/*
 *  Generate a screenshot name based on the base url and the capabilities
 *  TODO: Update options to have this configurable instead of assuming
 *
 *  @param {object} browser - the browser object since it has the options
 *	@param {string} screenshotCategory - element to click on
 *
 *  @returns string
 */
module.exports = function getScreenshotTitle(browser, screenshotCategory) {
	return [
		screenshotCategory,
		browser.desiredCapabilities.browserName.replace(" ", "."),
		browser.options.baseUrl.replace(/https?/, "").replace(/\//g, "").replace(":", "")
	].join(".");
};
