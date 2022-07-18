import {test, expect} from '@playwright/test';

test('Single Sign On Login', async ({page}) =>{
    await test.step('Should have the Single Sign On option', async () => {
        await page.goto('/');
        await expect(page.locator('.ext-el-mask-msg')).toBeHidden();
        await expect(page.locator("//a[@id='sign_in_link']")).toBeVisible();
        await page.locator("//a[@id='sign_in_link']").click();
    	await page.screenshot({path:'SSO1present.png'});
    });
    await test.step('Should let us select the SSO Login button', async () => {
        //there is no such link in website that is currently being used
        await expect(page.locator('#SSOLoginLink')).toBeVisible();
        await page.locator('#SSOLoginLink').click();
	await page.screenshot({path:'SSO2select.png'});
    });
    await test.step('Should goto the Single Sign On login page and login', async () => {
        await expect(page.locator('form[action="/signin"]')).toBeVisible();
        await page.submitForm('form[action="/signin"]');
	await page.screenshot({path:'SSO3login.png'});
    });
    await test.step('Display Logged in Users Name', async () => {
        await page.mainFrame();
        await expect(page.locator('#welcome_message')).toBeVisible();
        await expect(page.locator('#welcome_message')).toContainText('Saml Jackson');
        await expect(page.locator('#main_tab_panel__about_xdmod')).toBeVisible();
	await page.screenshot({path:'SSO4display.png'});
    });
    await test.step('Should prompt with My Profile', async () => {
        await expect(page.locator('#xdmod-profile-editor button.general_btn_close')).toBeVisible();
        await expect(page.locator('#xdmod-profile-editor button.general_btn_close')).click();
        await expect(page.locator('#xdmod-profile-editor')).toBeHidden();
	await page.screenshot({path:'SSO5profile.png'});
    });
    await test.step('Logout', async () => {
        console.log(page.mask);
	await expect(page.mask).isHidden();
        await expect(page.locator('#logout_link')).click();
        await expect(page.mask).isHidden();
        await expect(page.locator('a[href*=actionLogin]')).toBeVisible();
        await expect(page.locator('#main_tab_panel__about_xdmod')).toBeVisible();
	await page.screenshot({path:'SSO6logout.png'});
    });
});

test('Single Sign On Login w/ deep link', async ({page}) => {
    await test.step('Should have the Single Sign On option', async () => {
        await page.goto('/#main_tab_panel:metric_explorer');
        //again, there is no such link in website
        await expect(page.locator('#SSOLoginLink')).toBeVisible();
        await expect(page.locator('#SSOLoginLink')).click();
    });
    await test.step('Should goto the Single Sign On login page and login', async () => {
        await expect(page.locator('form[action="/signin"]')).toBeVisible();
        await page.submitForm('form[action="/signin"]');
    });
    await test.step('Load Metric Explorer tab', async () => {
        await page.mainFrame();
        await expect(page.locator('#welcome_message')).toBeVisible();
        await expect(page.locator('#welcome_message')).toContainText('Saml Jackson');
        await expect(page.locator('#metric_explorer')).toBeVisible();
    });
});
