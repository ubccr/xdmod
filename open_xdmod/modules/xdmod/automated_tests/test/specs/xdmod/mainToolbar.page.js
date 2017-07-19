class MainToolbar {
    constructor() {
        this.helpTypes = {
            Manual: '#global-toolbar-help-user-manual',
            'You Tube': '#global-toolbar-help-youtube'
        };
        this.contactTypes = {
            'Submit Support Request': '#global-toolbar-contact-us-submit-support-request',
            'Send Message': '#global-toolbar-contact-us-send-message',
            'Request Feature': '#global-toolbar-contact-us-request-feature'
        };
        this.toolbarClose = '.x-window .x-tool-close';
        this.toolbarAbout = '#global-toolbar-about';
        this.contactus = '#global-toolbar-contact-us';
        this.help = '#help_button';
        this.about = '#about_xdmod';
        this.container = '.x-window';
        this.header = '.x-window .x-window-header .x-window-header-text';
        this.floatlayer = 'div.x-menu.x-menu-floating.x-layer';
        this.note = '.x-window.x-notification';
    }
    helpFunc(type, mainTab) {
        $(this.help).click();
        browser.waitForExist(this.floatlayer);
        browser.waitForExist(this.helpTypes[type]);
        browser.click(this.helpTypes[type]);
        var ids = browser.windowHandles().value;
        var id = ids.length;
        expect(id).to.equal(2);
        while (id--) {
            if (ids[id] !== mainTab) {
                browser.window(ids[id]);
                browser.close();
            }
        }
        browser.window(mainTab);
    }
    contactFunc(type) {
        browser.waitUntilNotExist(this.note);
        $(this.contactus).click();
        browser.waitForExist(this.floatlayer);
        browser.waitForExist(this.contactTypes[type]);
        browser.click(this.contactTypes[type]);
        browser.waitForExist(this.container);
        expect(browser.getText(this.header)).to.be.equal(type);
        browser.pause(500);
        browser.waitForExist(this.toolbarClose);
        browser.click(this.toolbarClose);
    }
    checkAbout() {
        browser.waitForExist(this.toolbarAbout);
        browser.pause(5000);
        browser.click(this.toolbarAbout);
        browser.waitForExist(this.about);
    }
}
module.exports = new MainToolbar();
