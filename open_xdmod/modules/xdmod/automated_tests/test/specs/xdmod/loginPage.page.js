/* eslint-env node, es6 */
var roles = require('./../../../../integration_tests/.secrets.json').role;
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
<<<<<<< HEAD
                browser.url(theUrl);
                expect(browser.getTitle()).to.equal(title);
=======
                browser.url('/');
                expect(browser.getTitle()).to.equal('Open XDMoD');
                 // $(this.logo).waitForVisible(2000);
>>>>>>> upstream/xdmod7.1
                $('#logo').waitForVisible(2000);
                var logoSize = browser.getElementSize('#logo');
                expect(logoSize.width).to.equal(93);
                expect(logoSize.height).to.equal(32);
            });
        });
        describe('Login', function login() {
            it('Click the login link', function clickLogin() {
                browser.waitForInvisible('.ext-el-mask-msg');
                browser.waitAndClick('a[href*=actionLogin]');
            });
            it('Should Login', function doLogin() {
<<<<<<< HEAD
                browser.waitForVisible('#txt_login_username');
                $('#txt_login_username').setValue(loginName);
                $('#txt_login_password').setValue(loginPassword);
=======
                $('.x-window-header-text=Welcome To XDMoD').waitForVisible(20000);
                $('#wnd_login iframe').waitForVisible(20000);
                browser.frame($('#wnd_login iframe').value);
                $('#txt_login_username input').setValue(username);
                $('#txt_login_password input').setValue(password);
>>>>>>> upstream/xdmod7.1
                $('#btn_sign_in .x-btn-mc').click();
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
                $('#main_tab_panel__about_xdmod').waitForVisible();
            });
        });
    }
}
module.exports = new LoginPage();
