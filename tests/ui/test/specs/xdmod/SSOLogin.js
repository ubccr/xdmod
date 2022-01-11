describe('Single Sign On Login', () => {
    it('Should have the Single Sign On option', () => {
        browser.url('https://');
        browser.waitForInvisible('.ext-el-mask-msg');
        browser.waitAndClick('a[href*=actionLogin]');
    });
    it('Should let us select the SSO Login button', () => {
        browser.waitForVisible('#SSOLoginLink');
        browser.waitAndClick('#SSOLoginLink');
    });
    it('Should goto the Single Sign On login page and login', () => {
        browser.waitForVisible('form[action="/signin"]', 30000);
        browser.submitForm('form[action="/signin"]');
    });
    it('Display Logged in Users Name', () => {
        browser.frame();
        $('#welcome_message').waitForVisible(60000);
        expect($('#welcome_message').getText()).to.equal('Saml Jackson');
        $('#main_tab_panel__about_xdmod').waitForVisible();
    });
    it('Should prompt with My Profile', () => {
        browser.waitForVisible('#xdmod-profile-editor button.general_btn_close', 30000);
        browser.waitAndClick('#xdmod-profile-editor button.general_btn_close');
        browser.waitForInvisible('#xdmod-profile-editor');
    });
    it('Logout', () => {
        browser.waitForInvisible('.ext-el-mask-msg');
        browser.waitAndClick('#logout_link');
        browser.waitForInvisible('.ext-el-mask-msg');
        $('a[href*=actionLogin]').waitForVisible();
        $('#main_tab_panel__about_xdmod').waitForVisible();
    });
});

describe('Single Sign On Login w/ deep link', () => {
    it('Should have the Single Sign On option', () => {
        browser.url('/#main_tab_panel:metric_explorer');
        browser.waitForVisible('#SSOLoginLink');
        browser.waitAndClick('#SSOLoginLink');
    });
    it('Should goto the Single Sign On login page and login', () => {
        browser.waitForExist('form[action="/signin"]');
        browser.submitForm('form[action="/signin"]');
    });
    it('Load Metric Explorer tab', () => {
        browser.frame();
        $('#welcome_message').waitForVisible(60000);
        expect($('#welcome_message').getText()).to.equal('Saml Jackson');
        $('#metric_explorer').waitForVisible();
    });
});
