/* eslint-env node, es6 */
class LoginPage {
    login(title, theUrl, loginName, loginPassword, displayName) {
        describe('General', function () {
            it('Verify Logo and Title', function () {
                browser.url(theUrl);
                expect(browser.getTitle()).to.equal(title);
                 // $(this.logo).waitForVisible(2000);
                $('#logo').waitForVisible(2000);
                var logoSize = browser.getElementSize('#logo');
                expect(logoSize.width).to.equal(93);
                expect(logoSize.height).to.equal(32);
            });
        });
        describe('Login', function login() {
            it('Click the login link', function clickLogin() {
                browser.waitForInvisible('.ext-el-mask-msg');
                $('a[href*=actionLogin]').click();
            });
            it('Should Login', function doLogin() {
                $('.x-window-header-text=Welcome To XDMoD').waitForVisible(20000);
                $('#wnd_login iframe').waitForVisible(20000);
                browser.frame($('#wnd_login iframe').value);
                $('#txt_login_username input').setValue(loginName);
                $('#txt_login_password input').setValue(loginPassword);
                $('#btn_sign_in .x-btn-mc').click();

                // Per: http://webdriver.io/api/protocol/frame.html#Usage
                // Resetting the server to the page's default content
                // This was causing issues with running the tests under
                // Ubuntu 16.04:Firefox
                browser.frame();
            });
            it('Display Logged in Users Name', function () {
                $('#welcome_message').waitForVisible(60000);
                expect($('#welcome_message').getText()).to.equal(displayName);

                $('#main_tab_panel__about_xdmod').waitForVisible();
            });
        });
    }
    logout() {
        describe('Logout', function logout() {
            it('Click the logout link', function clickLogout() {
                browser.pause(1000);
                $('#logout_link').waitForVisible();
                $('#logout_link').click();
            });
            it('Display Logged out State', function clickLogout() {
                browser.waitForInvisible('.ext-el-mask-msg');
                $('a[href*=actionLogin]').waitForVisible();
            });
        });
/*
        describe("Update Screenshot Repository", function screenshots() {
            it("Should upload screenshots", function screenshotsSync() {
                //return browser.sync();
            });
        });*/
    }
}
module.exports = new LoginPage();
