const page = require('./internalDashboard.page.js');
const settings = [
    {
        label: 'E-Mail Address',
        type: 'text',
        updated: 'btest@test.example.com',
        original: 'btest@example.com'
    },
    {
        label: 'User Type',
        type: 'dropdown',
        updated: 'Testing',
        original: 'External'
    },
    {
        label: 'Map To',
        type: 'dropdown',
        updated: 'Auk, Great',
        original: 'Unknown, Unknown'
    },
    {
        label: 'Institution',
        type: 'dropdown',
        updated: 'Unknown Organization',
        original: 'Screwdriver'
    }
];

describe('Internal Dashboard', function () {
    page.login('mgr');

    describe('Create a new user', function () {
        it('Select "User Management" Tab', function () {
            browser.waitForVisible(page.selectors.header.tabs.user_management());
            browser.waitAndClick(page.selectors.header.tabs.user_management());

            browser.waitForVisible(page.selectors.user_management.tabs.account_requests());
        });
        it('Click "Create & Manage Users"', function () {
            browser.waitAndClick(page.selectors.account_requests.toolbar.create_manage_users);

            browser.waitForVisible(page.selectors.create_manage_users.window);
        });
        it('Select the "New User" tab', function () {
            browser.waitAndClick(page.selectors.create_manage_users.tabs.new_user());

            browser.waitForVisible(page.selectors.create_manage_users.new_user.container());
        });
        it('Populate User Information', function () {
            browser.waitForVisible(page.selectors.create_manage_users.new_user.first_name());
            browser.waitForVisible(page.selectors.create_manage_users.new_user.lastName());
            browser.waitForVisible(page.selectors.create_manage_users.new_user.emailAddress());

            browser.setValue(page.selectors.create_manage_users.new_user.first_name(), 'Bob');
            browser.setValue(page.selectors.create_manage_users.new_user.lastName(), 'Test');
            browser.setValue(page.selectors.create_manage_users.new_user.emailAddress(), 'btest@example.com');
        });
        it('Populate User Details', function () {
            browser.waitForVisible(page.selectors.create_manage_users.new_user.username());
            browser.waitForVisible(page.selectors.create_manage_users.new_user.mapTo());
            browser.waitForVisible(page.selectors.create_manage_users.new_user.institution());

            // the institution drop down should be disabled by default.
            expect(browser.isEnabled(page.selectors.create_manage_users.new_user.institution())).to.equal(false);

            browser.setValue(page.selectors.create_manage_users.new_user.username(), 'btest');
            browser.setValue(page.selectors.create_manage_users.new_user.mapTo(), 'Unknown');

            browser.waitForVisible(page.selectors.combo.container);
            browser.waitForVisible(page.selectors.combo.itemByText('Unknown'));

            browser.waitAndClick(page.selectors.combo.itemByText('Unknown'));
            browser.waitForInvisible(page.selectors.combo.container);

            const mapTo = browser.getValue(page.selectors.create_manage_users.new_user.mapTo());
            expect(mapTo).to.equal('Unknown');

            // Wait for the institution combo to be enabled / have a value.
            browser.waitForEnabled(page.selectors.create_manage_users.new_user.institution());
            browser.waitForValue(page.selectors.create_manage_users.new_user.institution());

            // By selecting a person to map our user to the institution should
            // have been automatically populated.
            const institution = browser.getValue(page.selectors.create_manage_users.new_user.institution());
            expect(institution).to.equal('Unknown Organization');

            // Institution should also be enabled because the we're mapping the
            // 'Unknown' person.
            expect(browser.isEnabled(page.selectors.create_manage_users.new_user.institution())).to.equal(true);
        });
        it('Change Institution', function () {
            browser.waitAndClick(page.selectors.create_manage_users.new_user.institution_trigger());
            browser.waitForVisible(page.selectors.combo.container);
            browser.waitForVisible(page.selectors.combo.itemByText('Screwdriver'));

            browser.waitAndClick(page.selectors.combo.itemByText('Screwdriver'));
            browser.waitForInvisible(page.selectors.combo.container);

            const newInstitution = browser.getValue(page.selectors.create_manage_users.new_user.institution());
            expect(newInstitution).to.equal('Screwdriver');
        });
        it('Select Acls', function () {
            browser.waitAndClick(page.selectors.create_manage_users.new_user.aclByName('User'));
            const cls = browser.getAttribute(page.selectors.create_manage_users.new_user.aclByName('User'), 'class');
            expect(cls.indexOf('x-grid3-check-col-on')).to.not.equal(-1);
        });
        it('Save User', function () {
            browser.waitAndClick(page.selectors.create_manage_users.buttons.create_user());

            browser.waitForVisible(page.selectors.createSuccessNotification('btest'));
            browser.waitForInvisible(page.selectors.createSuccessNotification('btest'));
        });
        it('Close "Create & Manage Users"', function () {
            browser.waitAndClick(page.selectors.create_manage_users.buttons.close());
            browser.waitForInvisible(page.selectors.create_manage_users.window);
        });
        it('Select the "Existing Users" tab', function () {
            browser.waitForVisible(page.selectors.user_management.tabs.existing_users());
            browser.waitAndClick(page.selectors.user_management.tabs.existing_users());
        });
        it('Ensure that the "Existing Users" table is displayed', function () {
            browser.waitForVisible(page.selectors.existing_users.table.container);
        });
        it('Check that the username is displayed correctly', function () {
            const usernameCol = page.selectors.existing_users.table.col_for_user('btest', 'Username');
            browser.waitForValue(usernameCol);
            const username = browser.getText(usernameCol);
            expect(username).to.equal('btest');
        });
        it('Check that the first name is displayed correctly', function () {
            const firstNameCol = page.selectors.existing_users.table.col_for_user('btest', 'First Name');
            browser.waitForValue(firstNameCol);
            const firstName = browser.getText(firstNameCol);
            expect(firstName).to.equal('Bob');
        });
        it('Check that the last name is displayed correctly', function () {
            const lastNameCol = page.selectors.existing_users.table.col_for_user('btest', 'Last Name');
            browser.waitForValue(lastNameCol);
            const lastName = browser.getText(lastNameCol);
            expect(lastName).to.equal('Test');
        });
        it('Check that the email is displayed correctly', function () {
            const emailCol = page.selectors.existing_users.table.col_for_user('btest', 'E-Mail Address');
            browser.waitForValue(emailCol);
            const email = browser.getText(emailCol);
            expect(email).to.equal('btest@example.com');
        });
        it('Check that the role is displayed correctly', function () {
            const roleCol = page.selectors.existing_users.table.col_for_user('btest', 'Role(s)');
            browser.waitForValue(roleCol);
            const roles = browser.getText(roleCol);
            expect(roles).to.equal('User');
        });
    });
    describe('Make sure that updates to the newly created users Settings can be discarded', function () {
        settings.forEach(function (setting) {
            describe(`Checking: ${setting.label}`, function () {
                it('Select the "Existing Users" tab', function () {
                    browser.waitForVisible(page.selectors.user_management.tabs.existing_users());
                    browser.waitAndClick(page.selectors.user_management.tabs.existing_users());
                });
                it('Ensure that the "Existing Users" table is displayed', function () {
                    browser.waitForVisible(page.selectors.existing_users.table.container);
                });
                it('Double click the users row in the `Existing Users` table', function () {
                    const usernameCol = page.selectors.existing_users.table.col_for_user('btest', 'Username');
                    browser.waitForValue(usernameCol);
                    browser.doubleClick(usernameCol);

                    browser.waitForVisible(page.selectors.create_manage_users.window);
                    browser.waitForVisible(page.selectors.create_manage_users.current_users.container);
                    browser.waitForInvisible(page.selectors.create_manage_users.loading_mask);
                });
                it(`Change the "${setting.label}" to "${setting.updated}"`, function () {
                    browser.waitForInvisible(page.selectors.create_manage_users.current_users.settings.noUserSelectedModal());
                    if ('dropdown' === setting.type) {
                        const inputTrigger = page.selectors.create_manage_users.current_users.settings.dropDownTriggerByLabel(setting.label);
                        browser.waitForVisible(inputTrigger);
                        browser.click(inputTrigger);

                        const inputDropDown = page.selectors.combo.container;
                        browser.waitForVisible(inputDropDown);

                        const dropDownValue = page.selectors.combo.itemByText(setting.updated);
                        browser.waitForVisible(dropDownValue);
                        browser.waitAndClick(dropDownValue);

                        browser.waitForInvisible(inputDropDown);

                        const input = page.selectors.create_manage_users.current_users.settings.inputByLabel(setting.label, 'text');
                        const updatedValue = browser.getValue(input);
                        expect(updatedValue).to.equal(setting.updated);
                    } else if ('text' === setting.type) {
                        const input = page.selectors.create_manage_users.current_users.settings.inputByLabel(setting.label, setting.type);
                        browser.waitForVisible(input);
                        browser.setValue(input, setting.updated);
                        const updatedValue = browser.getValue(input);
                        expect(updatedValue).to.equal(setting.updated);
                    }
                });
                it('Ensure that the user dirty message is shown', function () {
                    const dirtyMessage = page.selectors.create_manage_users.bottom_bar.messageByText('unsaved changes');
                    browser.waitForVisible(dirtyMessage);
                });
                it('Click the Close button', function () {
                    const closeButton = page.selectors.create_manage_users.current_users.button('Close');
                    browser.waitAndClick(closeButton);
                });
                it('Ensure that the Unsaved Changes modal is presented', function () {
                    browser.waitForVisible(page.selectors.modal.containerByTitle('Unsaved Changes'));
                });
                it('Discard Changes', function () {
                    const noButton = page.selectors.modal.buttonByText('Unsaved Changes', 'No');
                    browser.waitForVisible(noButton);
                    browser.click(noButton);

                    // We expect that the modal dialog will disappear
                    browser.waitForInvisible(page.selectors.modal.containerByTitle('Unsaved Changes'));
                });
                it('Edit the User again', function () {
                    const usernameCol = page.selectors.existing_users.table.col_for_user('btest', 'Username');
                    browser.waitForValue(usernameCol);
                    browser.doubleClick(usernameCol);

                    browser.waitForVisible(page.selectors.create_manage_users.window);
                    browser.waitForVisible(page.selectors.create_manage_users.current_users.container);
                    browser.waitForInvisible(page.selectors.create_manage_users.loading_mask);
                });
                it(`Check that the ${setting.label} is back to ${setting.original}`, function () {
                    const inputType = 'dropdown' === setting.type ? 'text' : setting.type;
                    const inputElem = page.selectors.create_manage_users.current_users.settings.inputByLabel(setting.label, inputType);

                    // We need to wait for this mask to be hidden as that is the indicator that the user's information
                    // has been populated.
                    browser.waitForInvisible(page.selectors.create_manage_users.current_users.settings.noUserSelectedModal());

                    browser.waitUntil(function () {
                        return browser.getValue(inputElem) === setting.original;
                    }, 10000, `Expected that ${setting.label} would be back to ${setting.original} `);
                    const inputValue = browser.getValue(inputElem);
                    expect(inputValue).to.equal(setting.original);
                });
                it('Close the Edit Existing User Modal', function () {
                    const closeButton = page.selectors.create_manage_users.current_users.button('Close');
                    browser.waitForVisible(closeButton);
                    browser.click(closeButton);
                });
            });
        });
    });
    describe('Make sure that the newly created user can have its settings updated successfully', function () {
        settings.forEach(function (setting) {
            describe(`Checking: ${setting.label}`, function () {
                it('Select the "Existing Users" tab', function () {
                    browser.waitForVisible(page.selectors.user_management.tabs.existing_users());
                    browser.waitAndClick(page.selectors.user_management.tabs.existing_users());
                });
                it('Ensure that the "Existing Users" table is displayed', function () {
                    browser.waitForVisible(page.selectors.existing_users.table.container);
                });
                it('Double click the users row in the `Existing Users` table', function () {
                    const usernameCol = page.selectors.existing_users.table.col_for_user('btest', 'Username');
                    browser.waitForValue(usernameCol);
                    browser.doubleClick(usernameCol);

                    browser.waitForVisible(page.selectors.create_manage_users.window);
                    browser.waitForVisible(page.selectors.create_manage_users.current_users.container);
                    browser.waitForInvisible(page.selectors.create_manage_users.loading_mask);
                });
                it(`Change the "${setting.label}" to "${setting.updated}"`, function () {
                    browser.waitForInvisible(page.selectors.create_manage_users.current_users.settings.noUserSelectedModal());
                    if ('dropdown' === setting.type) {
                        const inputTrigger = page.selectors.create_manage_users.current_users.settings.dropDownTriggerByLabel(setting.label);
                        browser.waitForVisible(inputTrigger);
                        browser.click(inputTrigger);

                        const inputDropDown = page.selectors.combo.container;
                        browser.waitForVisible(inputDropDown);

                        const dropDownValue = page.selectors.combo.itemByText(setting.updated);
                        browser.waitForVisible(dropDownValue);
                        browser.waitAndClick(dropDownValue);

                        browser.waitForInvisible(inputDropDown);
                    } else if ('text' === setting.type) {
                        const input = page.selectors.create_manage_users.current_users.settings.inputByLabel(setting.label, setting.type);
                        browser.waitForVisible(input);
                        browser.setValue(input, setting.updated);
                        expect(browser.getValue(input)).to.equal(setting.updated);
                    }
                });
                it('Ensure that the user dirty message is shown', function () {
                    const dirtyMessage = page.selectors.create_manage_users.bottom_bar.messageByText('unsaved changes');
                    browser.waitForVisible(dirtyMessage);
                });
                it('Click the save button', function () {
                    const saveButton = page.selectors.create_manage_users.current_users.button('Save Changes');
                    browser.waitAndClick(saveButton);

                    const updateModal = page.selectors.updateSuccessNotification('btest');
                    browser.waitForVisible(updateModal);
                    browser.waitForInvisible(updateModal);
                });
                if ('User Type' === setting.label) {
                    it('Check that the user is not still selected.', function () {
                        const noUserSelectedModal = page.selectors.create_manage_users.current_users.settings.noUserSelectedModal();
                        browser.waitForVisible(noUserSelectedModal);
                    });
                    it('Check that the user is not listed in the Existing Users table', function () {
                        const updatedUser = page.selectors.create_manage_users.current_users.user_list.col_for_user('btest', 'Username');
                        browser.waitForInvisible(updatedUser);
                    });
                    it(`Change the Displayed User Type to: "${setting.updated}"`, function () {
                        const displayedUserType = page.selectors.create_manage_users.current_users.user_list.toolbar.buttonByLabel('Displaying', setting.original);
                        browser.waitForVisible(displayedUserType);
                        browser.waitAndClick(displayedUserType);

                        const newUserTypeItem = page.selectors.create_manage_users.current_users.user_list.dropDownItemByText(setting.updated);
                        browser.waitForVisible(newUserTypeItem);
                        browser.waitAndClick(newUserTypeItem);
                    });
                    it('Check that the user is listed in the Existing Users table.', function () {
                        const updatedUser = page.selectors.create_manage_users.current_users.user_list.col_for_user('btest', 'Username');
                        browser.waitForVisible(updatedUser);
                    });
                } else {
                    it(`Check that "${setting.label}" has been updated successfully to "${setting.updated}"`, function () {
                        const inputType = 'dropdown' === setting.type ? 'text' : setting.type;
                        const input = page.selectors.create_manage_users.current_users.settings.inputByLabel(setting.label, inputType);
                        browser.waitForVisible(input);
                        const updatedValue = browser.getValue(input);
                        expect(updatedValue).to.equal(setting.updated);
                    });
                }

                it('Close the edit user modal', function () {
                    const closeButton = page.selectors.create_manage_users.current_users.button('Close');
                    browser.waitAndClick(closeButton);
                });
            });
        });
    });
    describe('Remove the newly created User', function () {
        it('Ensure that were on the "Existing Users" tab', function () {
            browser.waitForVisible(page.selectors.user_management.tabs.existing_users());
            browser.waitAndClick(page.selectors.user_management.tabs.existing_users());
            browser.waitForVisible(page.selectors.existing_users.table.container);
        });
        it('Double click the newly created user', function () {
            const usernameCol = page.selectors.existing_users.table.col_for_user('btest', 'Username');
            browser.waitForValue(usernameCol);
            browser.doubleClick(usernameCol);
        });
        it('Ensure that the Create & Manage Users Dialog opens', function () {
            browser.waitForVisible(page.selectors.create_manage_users.window);
        });
        it('Ensure that the "Actions" button is visible and click it', function () {
            browser.waitForVisible(page.selectors.create_manage_users.current_users.settings.toolbar.actions.button());
            browser.waitForLoadedThenClick(page.selectors.create_manage_users.current_users.settings.toolbar.actions.button());
        });
        it('Ensure that the Actions menu has been displayed', function () {
            browser.waitForVisible(page.selectors.create_manage_users.current_users.settings.toolbar.actions.container);
        });
        it('Click the "Delete This User" menu item', function () {
            const deleteUserItem = page.selectors.create_manage_users.current_users.settings.toolbar.actions.itemWithText('Delete This Account');
            browser.waitForVisible(deleteUserItem);
            browser.click(deleteUserItem);
        });
        it('Confirm the deletion of the user', function () {
            const yesDelete = page.selectors.modal.buttonByText('Delete User', 'Yes');
            browser.waitAndClick(yesDelete);

            const deleteNotification = page.selectors.deleteSuccessNotification('btest');
            browser.waitForVisible(deleteNotification);
            browser.waitForInvisible(deleteNotification);
        });
        it('Close the User Management Dialog', function () {
            const close = page.selectors.create_manage_users.current_users.button('Close');
            browser.waitForVisible(close);
            browser.click(close);
        });
        it('Ensure that the "Existing Users" table is displayed', function () {
            browser.waitForVisible(page.selectors.existing_users.table.container);
        });
        it('Check that there is no username', function () {
            const usernameCol = page.selectors.existing_users.table.col_for_user('btest', 'Username');
            browser.waitForValue(usernameCol);
            const username = browser.getText(usernameCol);
            expect(username).to.not.equal('btest');
        });
        it('Check that there is no first name', function () {
            const firstNameCol = page.selectors.existing_users.table.col_for_user('btest', 'First Name');
            browser.waitForValue(firstNameCol);
            const firstName = browser.getText(firstNameCol);
            expect(firstName).to.not.equal('Bob');
        });
        it('Check that there is no name', function () {
            const lastNameCol = page.selectors.existing_users.table.col_for_user('btest', 'Last Name');
            browser.waitForValue(lastNameCol);
            const lastName = browser.getText(lastNameCol);
            expect(lastName).to.not.equal('Test');
        });
        it('Check that there is no email', function () {
            const emailCol = page.selectors.existing_users.table.col_for_user('btest', 'E-Mail Address');
            browser.waitForValue(emailCol);
            const email = browser.getText(emailCol);
            expect(email).to.not.equal('btest@example.com');
        });
        it('Check that there is no role', function () {
            const roleCol = page.selectors.existing_users.table.col_for_user('btest', 'Role(s)');
            browser.waitForValue(roleCol);
            const roles = browser.getText(roleCol);
            expect(roles).to.not.equal('User');
        });
    });

    page.logout();
});
