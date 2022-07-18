const aboutSelectors = {
	tab: '//ul[contains(@class, "x-tab-strip x-tab-strip-top")]',
	container: '//div[@id="about_xdmod"]',
	center: '//div[@id="about_xdmod"]//div[contains(@class, "x-panel-body") and contains(@class, "x-border-layout-ct")]/div[contains(@class,"x-panel") and contains(@class,"x-panel-reset") and contains(@class,"x-border-panel")]',
	last_tab: '//ul/li[@id="main_tab_panel__about_xdmod"]',
	role: '//span[@id="profile_editor_most_privileged_role"]',
	logoutLink: '//a[@id="logout_link"]'
};
export default aboutSelectors;
