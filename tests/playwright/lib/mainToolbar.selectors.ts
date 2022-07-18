const mTbSelectors= {
    helpTypes: {
        //Manual: '#global-toolbar-help-user-manual'
	Manual: '//div[contains(@class, "x-menu x-menu-floating x-layer")]//a[@id="global-toolbar-help-user-manual"]'
    },
    contactTypes: {
        'Send Message': '//div[contains(@class, "x-menu x-menu-floating x-layer") and contains(@style,"visibility: visible")]//a[contains(., "Send Message")]',
        'Request Feature': '//div[contains(@class, "x-menu x-menu-floating x-layer") and contains(@style,"visibility: visible")]//a[contains(., "Request Feature")]',
	'Submit Support Request': '//div[contains(@class, "x-menu x-menu-floating x-layer") and contains(@style,"visibility: visible")]//a[contains(., "Submit Support Request")]'
    },
    toolbarClose: '.x-window .x-tool-close',
    //toolbarAbout: '#global-toolbar-about',
    toolbarAbout: '//table[@id="global-toolbar-about"]//button',
    //contactus: '#global-toolbar-contact-us',
    contactus:'//table[@id="global-toolbar-contact-us"]//button',
    //help: '#help_button',
    help: '//div[contains(@class, "x-toolbar x-small-editor x-toolbar-layout-ct")]//table[@id="help_button"]',
    about: '#about_xdmod',
    container: '.x-window',
    header: '.x-window .x-window-header .x-window-header-text',
    //floatlayer: 'div.x-menu.x-menu-floating.x-layer',
    floatlayer: '//div[contains(@class, "x-menu x-menu-floating x-layer") and contains(@style,"visibility: visible")]',
    note: '.x-window.x-notification',
    role: '//span[@id="profile_editor_most_privileged_role"]',
    logoutLink: '//a[@id="logout_link"]'
}
export default mTbSelectors;
