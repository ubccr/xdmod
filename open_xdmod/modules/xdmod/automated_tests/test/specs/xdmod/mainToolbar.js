var logIn = require('./loginPage.page.js');
var loginName = testHelpers.auth.roles.po.username;
var loginPassword = testHelpers.auth.roles.po.password;
var displayName = testHelpers.auth.roles.po.display;
var mTb = require('./mainToolbar.page.js');
var mainTab;

describe('Main Toolbar', function () {
    logIn.login('Open XDMoD', '/', loginName, loginPassword, displayName);
    describe('Check Tab', function xdmod() {
        it('Get Browser Tab ID', function () {
            mainTab = browser.getCurrentTabId();
        });
        it("About should change 'Tabs'", function () {
            mTb.checkAbout();
        });
    });
    describe('Contact Us', function () {
        for (var type in mTb.contactTypes) {
            if (mTb.contactTypes.hasOwnProperty(type)) {
                it(type, function () {
                    mTb.contactFunc(type);
                });
            }
        }
    });
    describe('Help', function () {
        for (var type in mTb.helpTypes) {
            if (mTb.helpTypes.hasOwnProperty(type)) {
                it(type, function () {
                    mTb.helpFunc(type, mainTab);
                    browser.pause(1500);
                });
            }
        }
    });
    logIn.logout();
});
