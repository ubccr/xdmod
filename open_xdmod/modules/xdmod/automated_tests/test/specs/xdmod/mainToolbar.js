var logIn = require('./loginPage.page.js');
var mTb = require('./mainToolbar.page.js');
var mainTab;

describe('Main Toolbar', function () {
    logIn.login('centerdirector');
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
                // TODO: Fix this with Page Objects?
                // eslint-disable-next-line no-loop-func
                it(type, function () {
                    mTb.contactFunc(type);
                });
            }
        }
    });
    describe('Help', function () {
        for (var type in mTb.helpTypes) {
            if (mTb.helpTypes.hasOwnProperty(type)) {
                // TODO: Fix this with Page Objects?
                // eslint-disable-next-line no-loop-func
                it(type, function () {
                    mTb.helpFunc(type, mainTab);
                    browser.pause(1500);
                });
            }
        }
    });
    logIn.logout();
});
