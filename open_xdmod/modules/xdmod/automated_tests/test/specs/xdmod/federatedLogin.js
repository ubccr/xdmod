describe('Federated Login', () => {
    it('Should have the federated option', () => {
        browser.url('/');
        browser.waitForInvisible('.ext-el-mask-msg');
        browser.waitAndClick('a[href*=actionLogin]');
        browser.waitForVisible('#federatedLoginLink');
        browser.waitAndClick('#federatedLoginLink');
    });
    it('Should goto the federated login page and login', () => {
        browser.waitForExist('form[action="/sso"]');
        browser.submitForm('form[action="/sso"]');
    });
    it('Display Logged in Users Name', () => {
        browser.frame();
        $('#welcome_message').waitForVisible(60000);
        expect($('#welcome_message').getText()).to.equal('Saml Jackson');
        $('#main_tab_panel__about_xdmod').waitForVisible();
    });
});
