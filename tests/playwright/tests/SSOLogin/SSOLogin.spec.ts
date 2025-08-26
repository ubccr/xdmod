import {test, expect} from '@playwright/test';

test('Single Sign On Login', async ({page}) => {

    await test.step('Should have the Single Sign On option', async () => {
        await page.goto('/');
        await expect(page.locator("//a[@id='sign_in_link']")).toBeVisible();
        await page.locator("//a[@id='sign_in_link']").click();
    });
    await test.step('Should let us select the SSO Login button', async () => {
        await expect(page.locator('#SSOLoginLink')).toBeVisible();
        await page.locator('#SSOLoginLink').click();
    });
    await test.step('Should goto the Single Sign On login page and login', async () => {
        const signInButton = '//button[@id="btn-sign-in"]';
        await expect(page.locator(signInButton)).toBeVisible();
        await page.screenshot({path: '/tmp/sso_login.png'})
        await page.click(signInButton);
    });
    await test.step('Display Logged in Users Name', async () => {
        await expect(page.locator('#welcome_message')).toBeVisible({timeout: 10000});
        const msg = await page.locator('#welcome_message').textContent();
        await expect(msg).toEqual('Saml Jackson');
        await expect(page.locator('#main_tab_panel__about_xdmod')).toBeVisible();
    });
    await test.step('Might prompt with My Profile', async () => {
        await expect(page.locator('#xdmod-profile-editor')).toBeVisible();
        await expect(page.locator('#xdmod-profile-editor button.general_btn_close')).toBeVisible();
        await page.locator('#xdmod-profile-editor button.general_btn_close').click();
        await expect(page.locator('#xdmod-profile-editor')).toBeHidden();
    });
    await test.step('Logout', async () => {
        await expect(page.locator('#logout_link')).toBeVisible();
        await page.locator('#logout_link').click();
        await expect(page.locator('a[href*=actionLogin]')).toBeVisible();
        await expect(page.locator('#main_tab_panel__about_xdmod')).toBeVisible();
    });
});

test('Single Sign On Login w/ deep link', async ({page}) => {
    await test.step('Should have the Single Sign On option', async () => {
        await page.goto('/#main_tab_panel:metric_explorer');
        await expect(page.locator('#SSOLoginLink')).toBeVisible();
        await page.locator('#SSOLoginLink').click();
    });
    await test.step('Should goto the Single Sign On login page and login', async () => {
        // For js idp test
        const signInButton = '//button[@id="btn-sign-in"]';
        await expect(page.locator(signInButton)).toBeVisible();
        await page.click(signInButton);
    });
    await test.step('Load Metric Explorer tab', async () => {
        await expect(page.locator('#welcome_message')).toBeVisible({timeout: 10000});
        await expect(page.locator('#welcome_message')).toContainText('Saml Jackson');
        await expect(page.locator('#metric_explorer')).toBeVisible();
    });
});
