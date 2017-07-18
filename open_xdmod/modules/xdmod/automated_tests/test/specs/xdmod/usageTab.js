var logIn = require('./loginPage.page.js');
var usg = require('./usageTab.page.js');

describe('Usage', function () {
    var loginName = testHelpers.auth.roles.po.username;
    var loginPassword = testHelpers.auth.roles.po.password;
    var displayName = testHelpers.auth.roles.po.display;
    logIn.login('Open XDMoD', '/', loginName, loginPassword, displayName);
    var baselineDate = {
        start: '2016-12-25',
        end: '2017-01-02'
    };
    describe('Usage Tab', function xdmod() {
        it('Selected', function () {
            browser.waitForLoadedThenClick(usg.tab).call();
        });
        it('Set a known start and end date', function meSetStartEnd() {
            browser.setValue(usg.startField, baselineDate.start);
            browser.setValue(usg.endField, baselineDate.end);
        });
        it('Select Job Size Min', function () {
            browser.waitForLoadedThenClick(usg.jobSizeMin);
        });
        it('Legend Text is Correct', function () {
            usg.checkLegendText('Screwdriver');
        });
    });
    logIn.logout();
});
