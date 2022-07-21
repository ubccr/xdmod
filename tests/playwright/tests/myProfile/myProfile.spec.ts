import {test, expect} from '@playwright/test';
import {LoginPage} from "../../lib/login.page";
import myProfile from '../../lib/myProfile.page';
let selectors = myProfile.myProfileSelectors;
import things from  '../../../ci/testing.json';
import artifacts from "../helpers/artifacts";
import globalConfig from '../../playwright.config';
var roles = things.role;
var expected = artifacts.getArtifact('myProfile');

test.describe('My Profile Tests', async () => {
    let keys = Object.keys(roles);
    for (let key in keys) {
        if (keys.hasOwnProperty(key)) {
            let role = keys[key];
            test(`${role} Tests`, async ({page}) => {
		        let baseUrl = globalConfig.use.baseURL;
                const loginPage = new LoginPage(page, baseUrl, page.sso);
		        await loginPage.login(roles[role].username, roles[role].password, (roles[role].givenname + " " + roles[role].surname));
                await test.step('Click the `My Profile` button', async () => {
                    await page.isVisible(myProfile.toolbarButton);
                    await page.waitForLoadState()
                    await page.click(myProfile.toolbarButton);
                    await page.isVisible(myProfile.container);
                });

                await test.step('Check User Information', async () => {
                    await test.step('First Name', async () => {
                        // the normal user does not have a first name so the value returned from
                        // the first name field is the default empty text ( 1 min, 50 max ).
                        let givenname = role !== 'usr' ? roles[role].givenname : '1 min, 50 max';
                        let firstNameControl = selectors.general.user_information.first_name();
                        
                        await page.isVisible(firstNameControl);
                        const computed = await page.locator(firstNameControl).inputValue();
                        await expect(computed).toEqual(givenname);
                    });
                    await test.step('Last Name', async () => {
                        let surname = roles[role].surname;
                        let lastNameControl = selectors.general.user_information.last_name();

                        await page.isVisible(lastNameControl);
                        const computed = await page.locator(lastNameControl).inputValue();
                        await expect(computed).toEqual(surname);
                    });
                    await test.step('E-Mail Address', async () => {
                        let username = roles[role].username;
                        // the admin user has a different email format than the rest of 'um.
                        let email = role !== 'mgr' ? `${username}@example.com` : `${username}@localhost`;
                        let emailControl = selectors.general.user_information.email_address();

                        await page.isVisible(emailControl);
                        const computed = await page.locator(emailControl).inputValue();
                        await expect(computed).toEqual(email);
                    });
                    await test.step('Top Role', async () => {
                        // We need to account for the different displays for users
                        // with center related acls and the others.
                        let expectedValue = role === 'cd' || role === 'cs' ? `${expected.top_roles[role]} - ${expected.organization.name}` : expected.top_roles[role];
                        let topRoleControl = selectors.general.user_information.top_role();

                        await page.isVisible(topRoleControl);
                        const computed = await page.textContent(topRoleControl);
                        await expect(computed).toEqual(expectedValue);
                    });
                    await test.step('Click the `Close` button', async () => {
                        const profile = new myProfile(page, page.baseUrl);
                        let closeButton = await profile.button(selectors.buttons.close);

                        await page.isVisible(closeButton);
                        await page.waitForLoadState();
                        await page.click(closeButton);
                        await page.isHidden(myProfile.container);
                    });
                });
	    });
        }
    }
});
