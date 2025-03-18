const selectors= {
  helpTypes: {
    Manual: '//div[contains(@class, "x-menu x-menu-floating x-layer")]//a[@id="global-toolbar-help-user-manual"]'
  },
  contactTypes: {
    'Send Message': '//div[contains(@class, "x-menu x-menu-floating x-layer") and contains(@style,"visibility: visible")]//a[contains(., "Send Message")]',
    'Request Feature': '//div[contains(@class, "x-menu x-menu-floating x-layer") and contains(@style,"visibility: visible")]//a[contains(., "Request Feature")]',
    'Submit Support Request': '//div[contains(@class, "x-menu x-menu-floating x-layer") and contains(@style,"visibility: visible")]//a[contains(., "Submit Support Request")]'
  },
  toolbarClose: '//div[contains(@class, "x-window")]//div//div//div//div[contains(@class,"x-window-header x-unselectable x-panel-icon ")]//div',
  toolbarAbout: '//table[@id="global-toolbar-about"]//button',
  contactus:'//table[@id="global-toolbar-contact-us"]//button',
  help: '//div[contains(@class, "x-toolbar x-small-editor x-toolbar-layout-ct")]//table[@id="help_button"]',
  about: '//ul/li[@id="main_tab_panel__about_xdmod"]',
  container: '//div[contains(@class, "x-window") and contains(@style, "visibility: visible")]',
  header: '//div[contains(@class, "x-window")]//div//div//div//div[contains(@class,"x-window-header x-unselectable x-panel-icon ")]//span',
    floatlayer: '//div[@class="x-menu x-menu-floating x-layer"]',
  note: '.x-window.x-notification',
  role: '//span[@id="profile_editor_most_privileged_role"]',
  logoutLink: '//a[@id="logout_link"]',
}
export default selectors;
