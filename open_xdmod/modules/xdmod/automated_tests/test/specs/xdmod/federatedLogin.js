describe('Federated Login', () => {
    it('Should have the federated option', () => {
        browser.url('/');
        browser.waitForInvisible('.ext-el-mask-msg');
        browser.waitAndClick('a[href*=actionLogin]');
        $('.x-window-header-text=Welcome To XDMoD').waitForVisible(20000);
        $('#wnd_login iframe').waitForVisible(20000);
        browser.frame($('#wnd_login iframe').value);
        browser.waitAndClick('a[href*="as_login"]');
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
