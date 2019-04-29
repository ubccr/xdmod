var logIn = require('./loginPage.page.js');
var Abt = require('./about.page.js');

describe('About', function about() {
    logIn.login('centerdirector');
    describe('Logged In Test', function loggedInTests() {
        it('Verify About is the Last Tab', function aboutIsTheLastTab() {
            browser.waitForAllInvisible('.ext-el-mask');
            browser.waitForVisible(Abt.tab, 30000);
            expect(browser.getText(Abt.last_tab)).to.equal('About');
        });

        it('Select About Tab', function selectTab() {
            browser.waitForLoadedThenClick(Abt.tab, 50000);
            browser.waitForVisible(Abt.container, 20000);
        });
        describe('Check Nav Entries', function checkNavEntries() {
            it('XDMoD', function checkNavEntryXDMoD() {
                Abt.checkTab('XDMoD');
            });
            it('Open XDMoD', function checkNavEntryXDMoD() {
                Abt.checkTab('Open XDMoD');
            });
            it('SUPReMM', function checkNavEntrySUPReMM() {
                Abt.checkTab('SUPReMM');
            });
            it('Roadmap', function checkNavEntryXDMoD() {
                Abt.checkRoadmap();
            });
            it('Team', function checkNavEntryXDMoD() {
                Abt.checkTab('Team');
            });
            it('Publications', function checkNavEntryXDMoD() {
                Abt.checkTab('Publications');
            });
            it('Presentations', function checkNavEntryXDMoD() {
                Abt.checkTab('Presentations');
            });
            it('Links', function checkNavEntryXDMoD() {
                Abt.checkTab('Links');
            });
            it('Release Notes', function checkNavEntryXDMoD() {
                Abt.checkTab('Release Notes');
            });
        });
    });

    describe('Logged Out Tests', function loggedInTests() {
        it('Click the logout link', function clickLogout() {
            browser.waitForLoadedThenClick('#logout_link', 50000);
        });
        it('Display Logged out State', function clickLogout() {
            browser.waitUntilNotExist('.ext-el-mask-msg');
            $('a[href*=actionLogin]').waitForExist();
        });

        it('Verify About is the Last Tab', function aboutIsTheLastTab() {
            browser.waitForVisible(Abt.tab, 30000);
            expect(browser.getText(Abt.last_tab)).to.equal('About');
        });

        it('Select Tab', function selectTab() {
            browser.waitForLoadedThenClick(Abt.tab, 50000);
            browser.waitForVisible(Abt.container, 20000);
        });
        describe('Check Nav Entries', function checkNavEntries() {
            it('XDMoD', function checkNavEntryXDMoD() {
                Abt.checkTab('XDMoD');
            });
            it('Open XDMoD', function checkNavEntryXDMoD() {
                Abt.checkTab('Open XDMoD');
            });
            it('SUPReMM', function checkNavEntrySUPReMM() {
                Abt.checkTab('SUPReMM');
            });
            it('Roadmap', function checkNavEntryXDMoD() {
                Abt.checkRoadmap();
            });
            it('Team', function checkNavEntryXDMoD() {
                Abt.checkTab('Team');
            });
            it('Publications', function checkNavEntryXDMoD() {
                Abt.checkTab('Publications');
            });
            it('Presentations', function checkNavEntryXDMoD() {
                Abt.checkTab('Presentations');
            });
            it('Links', function checkNavEntryXDMoD() {
                Abt.checkTab('Links');
            });
            it('Release Notes', function checkNavEntryXDMoD() {
                Abt.checkTab('Release Notes');
            });
        });
    });
    logIn.login('cd');
    logIn.logout();
});
