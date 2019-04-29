let expected = global.testHelpers.artifacts.getArtifact('myProfile');
let roles = require('./../../../../ci/testing.json').role;
let logIn = require('./loginPage.page.js');
let myProfile = require('./myProfile.page.js');
let selectors = myProfile.selectors;

describe('My Profile Tests', function generalTests() {
    let keys = Object.keys(roles);
    for (let key in keys) {
        if (keys.hasOwnProperty(key)) {
            let role = keys[key];
            logIn.login(role);

            describe(`${role} Tests`, function perUserTests() {
                it('Click the `My Profile` button', function clickMyProfile() {
                    browser.waitForAllInvisible('.ext-el-mask');
                    browser.waitForVisible(myProfile.toolbarButton, 3000);
                    browser.waitForLoadedThenClick(myProfile.toolbarButton);
                    browser.waitForVisible(myProfile.container, 20000);
                });

                describe('Check User Information', function checkUserInformation() {
                    it('First Name', function checkFirstName() {
                        // the normal user does not have a first name so the value returned from
                        // the first name field is the default empty text ( 1 min, 50 max ).
                        let givenname = role !== 'usr' ? roles[role].givenname : '1 min, 50 max';
                        let firstNameControl = selectors.general.user_information.first_name();

                        browser.waitForVisible(firstNameControl);
                        expect(browser.getValue(firstNameControl)).to.equal(givenname);
                    });
                    it('Last Name', function checkLastName() {
                        let surname = roles[role].surname;
                        let lastNameControl = selectors.general.user_information.last_name();

                        browser.waitForVisible(lastNameControl);
                        expect(browser.getValue(lastNameControl)).to.equal(surname);
                    });
                    it('E-Mail Address', function checkEmailAddress() {
                        let username = roles[role].username;
                        // the admin user has a different email format than the rest of 'um.
                        let email = role !== 'mgr' ? `${username}@example.com` : `${username}@localhost`;
                        let emailControl = selectors.general.user_information.email_address();

                        browser.waitForVisible(emailControl);
                        expect(browser.getValue(emailControl)).to.equal(email);
                    });
                    it('Top Role', function checkTopRole() {
                        // We need to account for the different displays for users
                        // with center related acls and the others.
                        let expectedValue = role === 'cd' || role === 'cs' ? `${expected.top_roles[role]} - ${expected.organization.name}` : expected.top_roles[role];
                        let topRoleControl = selectors.general.user_information.top_role();

                        browser.waitForVisible(topRoleControl);
                        expect(browser.getText(topRoleControl)).to.equal(expectedValue);
                    });
                    it('Click the `Close` button', function closeMyProfile() {
                        let closeButton = myProfile.button(selectors.buttons.close);

                        browser.waitForVisible(closeButton);
                        browser.waitForLoadedThenClick(closeButton);
                        browser.waitForInvisible(myProfile.container, 20000);
                    });
                });
            });

            logIn.logout();
        }
    }
});
