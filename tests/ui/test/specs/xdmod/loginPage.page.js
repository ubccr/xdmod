/* eslint-env node, es6 */
var roles = require('./../../../../ci/testing.json').role;
var expected = global.testHelpers.artifacts.getArtifact('loginPage');
class LoginPage {

    login(desiredRole) {
        let username;
        let password;
        let displayName;
        let role;
        switch (desiredRole) {
            case 'cd':
            case 'centerdirector':
                role = 'cd';
                break;
            case 'usr':
            case 'user':
                role = 'usr';
                break;
            case 'pi':
            case 'principalinvestigator':
                role = 'pi';
                break;
            case 'cs':
            case 'centerstaff':
                role = 'cs';
                break;
            default:
                role = desiredRole;
        }
        username = roles[role].username;
        password = roles[role].password;
        displayName = roles[role].givenname + ' ' + roles[role].surname;
        displayName = displayName.trim();
        describe('General', function () {
            it('Verify Logo and Title', function () {
                browser.url('/');
                const actual = browser.getTitle();
                expect(actual).to.equal(expected.title);

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
                browser.waitForVisible('#btn_sign_in');
                $('#txt_login_username').setValue(username);
                $('#txt_login_password').setValue(password);
                browser.waitAndClick('#btn_sign_in');
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
                browser.clickSelectorAndWaitForMask('#logout_link');
            });
            it('Display Logged out State', function clickLogout() {
                browser.waitForInvisible('.ext-el-mask-msg');
                $('a[href*=actionLogin]').waitForVisible();
                $('#main_tab_panel__about_xdmod').waitForVisible();
            });
        });
    }
}
module.exports = new LoginPage();
