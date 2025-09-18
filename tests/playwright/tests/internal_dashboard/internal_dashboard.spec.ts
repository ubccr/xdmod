import {test, expect} from '@playwright/test';
import InternalDashboard from "../../lib/internal_dashboard.page";
import settings from '../../config/internal_dashboard/settings.json';
import globalConfig from '../../playwright.config';
import testing from  '../../../ci/testing.json';
let roles = testing.role;

test.describe('Internal Dashboard Tests', async () => {
    let baseUrl = globalConfig.use.baseURL;
    test('Create a new user', async ({page}) => {
        await page.goto('/internal_dashboard');
        const internalDash = new InternalDashboard(page, baseUrl, page.sso);
        await internalDash.login(roles['mgr'].username, roles['mgr'].password, (roles['mgr'].givenname + " " + roles['mgr'].surname));

        await test.step('Select User Management tab', async () => {
            await expect(page.locator(InternalDashboard.selectors.header.tabs.user_management())).toBeVisible();
            await page.click(InternalDashboard.selectors.header.tabs.user_management());
            await expect(page.locator(InternalDashboard.selectors.user_management.tabs.account_requests())).toBeVisible();
        });

        await test.step('Click "Create and Manage Users"', async () => {
            await expect(page.locator(InternalDashboard.selectors.account_requests.toolbar.create_manage_users)).toBeVisible();
            await page.click(InternalDashboard.selectors.account_requests.toolbar.create_manage_users);

            await expect(page.locator(InternalDashboard.selectors.create_manage_users.window)).toBeVisible();
        });

        await test.step('Select the "New User" tab', async () => {
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.tabs.new_user())).toBeVisible();
            await page.click(InternalDashboard.selectors.create_manage_users.tabs.new_user());

            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.container())).toBeVisible();
        });

        await test.step('Populate User Information', async () => {
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.first_name())).toBeVisible();
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.lastName())).toBeVisible();
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.emailAddress())).toBeVisible();

            await page.fill(InternalDashboard.selectors.create_manage_users.new_user.first_name(), 'Bob');
            await page.fill(InternalDashboard.selectors.create_manage_users.new_user.lastName(), 'Test');
            await page.fill(InternalDashboard.selectors.create_manage_users.new_user.emailAddress(), 'btest@example.com');
        });

        await test.step('Populate User Details', async () => {
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.username())).toBeVisible();
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.mapTo())).toBeVisible();
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.institution())).toBeVisible();

            // the institution drop down should be disabled by default.
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.institution())).toBeDisabled();

            await page.type(InternalDashboard.selectors.create_manage_users.new_user.username(), 'btest', {delay: 100});
            await page.type(InternalDashboard.selectors.create_manage_users.new_user.mapTo(), 'Unknown', {delay: 100});

            await expect(page.locator(InternalDashboard.selectors.combo.container)).toBeVisible();
            await expect(page.locator(InternalDashboard.selectors.combo.itemByText('Unknown'))).toBeVisible();

            await page.locator(InternalDashboard.selectors.combo.itemByText('Unknown')).click();
            await expect(page.locator(InternalDashboard.selectors.combo.container)).toBeHidden();

            const mapTo = await page.inputValue(InternalDashboard.selectors.create_manage_users.new_user.mapTo());
            expect(mapTo).toEqual('Unknown');

            // Wait for the institution combo to be enabled / have a value.
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.institution())).toBeEnabled();

            // By selecting a person to map our user to the institution should be populated automatically.
            //
            const institution = await page.inputValue(InternalDashboard.selectors.create_manage_users.new_user.institution());
            expect(institution).toEqual('Unknown Organization');

            // Institution should also be enabled because we're mapping the 'Unknown' person.
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.institution())).toBeEnabled();
        });

        await test.step('Change Institution', async () => {
            await page.click(InternalDashboard.selectors.create_manage_users.new_user.institution_trigger());
            await expect(page.locator(InternalDashboard.selectors.combo.container)).toBeVisible();
            await expect(page.locator(InternalDashboard.selectors.combo.itemByText('Screwdriver'))).toBeVisible();

            await page.click(InternalDashboard.selectors.combo.itemByText('Screwdriver'));
            await expect(page.locator(InternalDashboard.selectors.combo.container)).toBeHidden();

            const newInstitution = await page.inputValue(InternalDashboard.selectors.create_manage_users.new_user.institution());
            expect(newInstitution).toEqual('Screwdriver');
        });

        await test.step('Select Acls', async () => {
            await page.click(InternalDashboard.selectors.create_manage_users.new_user.aclByName('User'));
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.new_user.aclByName('User'))).toHaveClass(/x-grid3-check-col-on/);
        });

        await test.step('Select User Type', async () => {
            const userTypeTrigger = page.locator(InternalDashboard.selectors.create_manage_users.new_user.userTypeTrigger());
            await expect(userTypeTrigger).toBeVisible();
            await userTypeTrigger.click();

            const externalComboOptionLocator = page.locator(InternalDashboard.selectors.combo.itemByText('External'));
            await expect(externalComboOptionLocator).toBeVisible();
            await externalComboOptionLocator.click();
            await expect(externalComboOptionLocator).toBeHidden();
        });

        await test.step('Save User', async () => {
            await page.locator(InternalDashboard.selectors.create_manage_users.buttons.create_user()).click();

            await expect(page.locator(InternalDashboard.selectors.createSuccessNotification('btest'))).toBeVisible();
            await expect(page.locator(InternalDashboard.selectors.createSuccessNotification('btest'))).toBeHidden();
        });

        await test.step('Close "Create and Manage Users"', async () => {
            await page.click(InternalDashboard.selectors.create_manage_users.buttons.close());
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.window)).toBeHidden();
        })

        await test.step('Select the "Existing Users" tab', async () => {
            await expect(page.locator(InternalDashboard.selectors.user_management.tabs.existing_users())).toBeVisible();
            await page.click(InternalDashboard.selectors.user_management.tabs.existing_users());
            await expect(page.locator(InternalDashboard.selectors.existing_users.table.container)).toBeVisible();
        });

        await test.step('Check that the username is displayed correctly', async () => {
            let selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'Username');
            await expect(page.locator(selector)).toBeVisible({timeout: 30000});
            await expect(page.locator(selector)).toContainText('btest');
        });

        await test.step('Check that the first name is displayed correctly', async () => {
            let column = page.locator('xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'First Name'));
            await expect(column).toBeVisible();
            await expect(column).toContainText('Bob');
        });

        await test.step('Check that the last name is displayed correctly', async () => {
            let column = page.locator('xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'Last Name'));
            await expect(column).toBeVisible();
            await expect(column).toContainText('Test');
        });

        await test.step('Check that the E-Mail Address is displayed correctly', async () => {
            let column = page.locator('xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'E-Mail Address'));
            await expect(column).toBeVisible();
            await expect(column).toContainText('btest@example.com');
        });

        await test.step('Check that the role is displayed correctly', async () => {
            let column = page.locator('xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'Role(s)'));
            await expect(column).toBeVisible();
            await expect(column).toContainText('User');
        });
    });

    test('Test that settings can be discarded.', async ({page}) => {
        await page.goto('/internal_dashboard');
        const internalDash = new InternalDashboard(page, baseUrl, page.sso);
        await internalDash.login(roles['mgr'].username, roles['mgr'].password, (roles['mgr'].givenname + " " + roles['mgr'].surname));

        await test.step('Select User Management tab', async () => {
            await expect(page.locator(InternalDashboard.selectors.header.tabs.user_management())).toBeVisible();
            await page.click(InternalDashboard.selectors.header.tabs.user_management());
            await expect(page.locator(InternalDashboard.selectors.user_management.tabs.account_requests())).toBeVisible();
        });

        for (const index in settings) {
            const setting = settings[index];
            await test.step(`${setting.label}: Selecting the "Existing Users" tab`, async () => {
                await expect(page.locator(InternalDashboard.selectors.user_management.tabs.existing_users())).toBeVisible();
                await page.click(InternalDashboard.selectors.user_management.tabs.existing_users());
                await expect(page.locator(InternalDashboard.selectors.existing_users.table.container)).toBeVisible();
            });

            await test.step(`${setting.label}: Double click the users row in the "Existing Users" table`, async () => {
                const selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', "Username");
                const column = page.locator(selector);
                await expect(column).toBeVisible();
                await page.dblclick(selector);

                await expect(page.locator(InternalDashboard.selectors.create_manage_users.window)).toBeVisible();
                await expect(page.locator(InternalDashboard.selectors.create_manage_users.current_users.container)).toBeVisible();
                await expect(page.locator(InternalDashboard.selectors.create_manage_users.loading_mask)).toBeHidden();
            });

            await test.step(`${setting.label}: Change the "${setting.label}" to "${setting.updated}"`, async () => {
                await expect(page.locator(InternalDashboard.selectors.create_manage_users.current_users.settings.noUserSelectedModal())).toBeHidden();
                if ('dropdown' === setting.type) {
                    let inputSelector = InternalDashboard.selectors.create_manage_users.current_users.settings.dropDownTriggerByLabel(setting.label);
                    let inputTrigger = page.locator(inputSelector);
                    await expect(inputTrigger).toBeVisible();
                    await page.click(inputSelector);

                    let inputDropDown = page.locator(InternalDashboard.selectors.combo.container);
                    await expect(inputDropDown).toBeVisible();

                    let dropDownValueSelector = InternalDashboard.selectors.combo.itemByText(setting.updated);
                    let dropDownValue = page.locator(dropDownValueSelector);
                    await expect(dropDownValue).toBeVisible();
                    await dropDownValue.click();
                    await expect(inputDropDown).toBeHidden();
                } else if ('text' === setting.type) {
                    const inputSelector = InternalDashboard.selectors.create_manage_users.current_users.settings.inputByLabel(setting.label, setting.type);
                    const input = page.locator(inputSelector);
                    await expect(input).toBeVisible();
                    await page.fill(inputSelector, setting.updated);
                    await page.keyboard.press('Tab');
                }
            });

            await test.step(`${setting.label}: Ensure that the user dirty message is shown`, async () => {
                await expect(page.locator(InternalDashboard.selectors.create_manage_users.bottom_bar.messageByText('unsaved changes'))).toBeVisible();
            });

            await test.step(`${setting.label}: Click the save button`, async () => {
                let saveButtonSelector = InternalDashboard.selectors.create_manage_users.current_users.button('Save Changes');
                let saveButton = page.locator(saveButtonSelector);
                await expect(saveButton).toBeVisible();
                await page.click(saveButtonSelector);

                let updateModal = page.locator(InternalDashboard.selectors.updateSuccessNotification('btest'));
                await expect(updateModal).toBeVisible();
                await expect(updateModal).toBeHidden();
            });

            if ('User Type' === setting.label) {

                await test.step(`${setting.label}: Check that the user is not still selected`, async () => {
                    await expect(page.locator(InternalDashboard.selectors.create_manage_users.current_users.settings.noUserSelectedModal())).toBeVisible();
                });

                await test.step(`${setting.label}: Check that the user is not listed in the Existing Users table`, async () => {
                    await expect(page.locator(InternalDashboard.selectors.create_manage_users.current_users.user_list.col_for_user('btest'))).toBeHidden();
                });

                await test.step(`${setting.label}: Change the Displayed User Type to: ${setting.updated}`, async () => {
                    const displayedUserTypeSelector = InternalDashboard.selectors.create_manage_users.current_users.user_list.toolbar.buttonByLabel('Displaying', setting.original);
                    const displayedUserType = page.locator(displayedUserTypeSelector);
                    await expect(displayedUserType).toBeVisible();
                    await displayedUserType.click();

                    const newUserTypeItemSelector = InternalDashboard.selectors.create_manage_users.current_users.user_list.dropDownItemByText(setting.updated);
                    const newUserTypeItem = page.locator(newUserTypeItemSelector);
                    await expect(newUserTypeItem).toBeVisible();
                    await newUserTypeItem.click();
                });

                await test.step(`${setting.label}: Check that the user is listed in the Existing Users table`, async () => {
                    await expect(page.locator(InternalDashboard.selectors.create_manage_users.current_users.user_list.col_for_user('btest'))).toBeVisible();
                });
            } else {
                await test.step(`${setting.label}: Make sure that the user is selected`, async() => {
                    const userSelector = page.locator(InternalDashboard.selectors.create_manage_users.current_users.user_list.col_for_user('btest'));
                    await userSelector.click();
                    const userDetailsLocator = page.locator(InternalDashboard.selectors.create_manage_users.current_users.settings.toolbar.details_header('Bob Test'));
                    await expect(userDetailsLocator).toBeVisible();
                });
                await test.step(`${setting.label}: Check that ${setting.label} has been updated successfully to "${setting.updated}"`, async () => {
                    const inputType = 'dropdown' === setting.type ? 'text' : setting.type;
                    const selector = InternalDashboard.selectors.create_manage_users.current_users.settings.inputByLabel(setting.label, inputType);
                    const input = page.locator(selector);
                    await expect(input).toBeVisible();
                    const updated = await page.inputValue(selector);
                    await expect(updated).toEqual(setting.updated);
                });
            }

            await test.step(`${setting.label}: Close the edit user modal`, async () => {
                const closeButtonSelector = InternalDashboard.selectors.create_manage_users.current_users.button('Close');
                await expect(page.locator(closeButtonSelector)).toBeVisible();
                await page.click(closeButtonSelector);
            });
        }
    });

    test('Remove the newly created user', async ({page}) => {
        await page.goto('/internal_dashboard');
        const internalDash = new InternalDashboard(page, baseUrl, page.sso);
        await internalDash.login(roles['mgr'].username, roles['mgr'].password, (roles['mgr'].givenname + " " + roles['mgr'].surname));

        await test.step('Select User Management tab', async () => {
            await expect(page.locator(InternalDashboard.selectors.header.tabs.user_management())).toBeVisible();
            await page.click(InternalDashboard.selectors.header.tabs.user_management());
            await expect(page.locator(InternalDashboard.selectors.user_management.tabs.account_requests())).toBeVisible();
        });

        await test.step('Ensure that were on the "Existing Users" Tab', async () => {
            const selector = InternalDashboard.selectors.user_management.tabs.existing_users();
            await expect(page.locator(selector)).toBeVisible();
            await page.click(selector);
            await expect(page.locator(InternalDashboard.selectors.existing_users.table.container)).toBeVisible();
        });

        await test.step('Double click the newly created user', async () => {
            const selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'Username');
            await expect(page.locator(selector)).toBeVisible();
            await page.dblclick(selector);
            await expect(page.locator(InternalDashboard.selectors.create_manage_users.window)).toBeVisible();
        });

        await test.step('Ensure that the "Actions" button is visible and click it', async () => {
            const selector = InternalDashboard.selectors.create_manage_users.current_users.settings.toolbar.actions.button();
            await expect(page.locator(selector)).toBeVisible();
            await page.click(selector);

            await expect(page.locator(InternalDashboard.selectors.create_manage_users.current_users.settings.toolbar.actions.container)).toBeVisible();
        });

        await test.step('Click the "Delete This User" menu item', async () => {
            const selector = InternalDashboard.selectors.create_manage_users.current_users.settings.toolbar.actions.itemWithText('Delete This Account');
            await expect(page.locator(selector)).toBeVisible();
            await page.click(selector);
        });

        await test.step('Confirm the deletion of the user', async () => {
            const selector = InternalDashboard.selectors.modal.buttonByText('Delete User', 'Yes');
            await expect(page.locator(selector)).toBeVisible();
            await page.click(selector);

            const deleteNotification = page.locator(InternalDashboard.selectors.deleteSuccessNotification('btest'));
            await expect(deleteNotification).toBeVisible();
            await expect(deleteNotification).toBeHidden();
        });

        await test.step('Close the User Management Dialog', async () => {
            const selector = InternalDashboard.selectors.create_manage_users.current_users.button('Close');
            await expect(page.locator(selector)).toBeVisible();
            await page.click(selector);
        });

        await test.step('Ensure that the "Existing Users" table is displayed', async () => {
            await expect(page.locator(InternalDashboard.selectors.existing_users.table.container)).toBeVisible();
        });

        await test.step('Check that there is not a username', async () => {
            let selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'Username');
            let locator = page.locator(selector);
            await expect(locator).toBeVisible();
            const value = await page.textContent(selector);
            expect(value).toMatch(/^((?!btest).)*$/);
        });

        await test.step('Check that there is no First Name', async () => {
            let selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'First Name');
            let locator = page.locator(selector);
            await expect(locator).toBeVisible();
            const value = await page.textContent(selector);
            expect(value).toMatch(/^((?!Bob).)*$/);
        });

        await test.step('Check that there is no Last Name', async () => {
            let selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'Last Name');
            let locator = page.locator(selector);
            await expect(locator).toBeVisible();
            const value = await page.textContent(selector);
            expect(value).toMatch(/^((?!Test).)*$/);
        });

        await test.step('Check that there is no E-Mail Address', async () => {
            let selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'E-Mail Address');
            let locator = page.locator(selector);
            await expect(locator).toBeVisible();
            const value = await page.textContent(selector);
            expect(value).toMatch(/^((?!btest@example.com).)*$/);
        });

        await test.step('Check that there are no Role(s)', async () => {
            let selector = 'xpath=' + InternalDashboard.selectors.existing_users.table.col_for_user('btest', 'Role(s)');
            let locator = page.locator(selector);
            await expect(locator).toBeVisible();
            const value = await page.textContent(selector);
            const matches = value === 'User';
            expect(matches).toEqual(false);
        });
    });
});
