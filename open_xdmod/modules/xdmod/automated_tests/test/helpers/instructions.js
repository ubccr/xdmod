//TODO: Look into moving these to files...
var sections = {
		jobViewer: {
			instructions: '<div class="x-grid-empty"><b style="font-size: 150%">No job is selected for viewing</b><br><br>Please refer to the instructions below:<br><br><img src="gui/images/job_viewer_instructions.png"><br><br><div style="background-image: url(\'gui/images/user_manual.png\'); background-repeat: no-repeat; height: 36px; padding-left: 40px; padding-top: 10px">For more information, please refer to the <a href="javascript:void(0)" onclick="CCR.xdmod.ui.userManualNav(\'job+viewer\')">User Manual</a></div></div>'
		},
		metricExplorer: {
			instructions: '<div class="x-grid-empty"><b style="font-size: 150%">No data is available for viewing</b><br><br>Please refer to the instructions below:<br><br><img src="gui/images/metric_explorer_instructions.png"><br><br><div style="background-image: url(\'gui/images/user_manual.png\'); background-repeat: no-repeat; height: 36px; padding-left: 40px; padding-top: 10px">For more information, please refer to the <a href="javascript:void(0)" onclick="CCR.xdmod.ui.userManualNav(\'metric+explorer\')">User Manual</a></div></div>'
		}
	};
	//cheerio = require("cheerio");

module.exports = function instructions(browser, section, selector) {
	expect(browser.getHTML(selector + " .x-grid-empty")).to.equal(sections[section].instructions);
};
