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
                browser.waitUntilNotExist('.ext-el-mask-msg');
                $('a[href*=actionLogin]').click();
                $('a[href*=switchLoginView]').click();
            });
            it('Should Login', function doLogin() {
                $('.x-window-header-text=Sign in Locally').waitForExist(20000);
                $('#txt_login_username input').setValue(loginName);
                $('#txt_login_password input').setValue(loginPassword);
                $('#btn_sign_in .x-btn-mc').click();
            });
            it('Display Logged in Users Name', function () {
                $('#welcome_message').waitForExist(60000);
                expect($('#welcome_message').getText()).to.equal(displayName);
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
                browser.waitUntilNotExist('.ext-el-mask-msg');
                $('a[href*=actionLogin]').waitForExist();
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
