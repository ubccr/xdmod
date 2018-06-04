describe('Single Sign On Login', () => {
    it('Should have the Single Sign On option', () => {
        browser.url('/');
        browser.waitForInvisible('.ext-el-mask-msg');
        browser.waitAndClick('a[href*=actionLogin]');
        browser.waitForVisible('#SSOLoginLink');
        browser.waitAndClick('#SSOLoginLink');
    });
    it('Should goto the Single Sign On login page and login', () => {
        browser.waitForExist('form[action="/signin"]');
        browser.submitForm('form[action="/signin"]');
    });
    it('Display Logged in Users Name', () => {
        browser.frame();
        $('#welcome_message').waitForVisible(60000);
        expect($('#welcome_message').getText()).to.equal('Saml Jackson');
        $('#main_tab_panel__about_xdmod').waitForVisible();
    });
});
