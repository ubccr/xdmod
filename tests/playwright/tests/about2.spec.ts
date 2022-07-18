import {test, expect, Page} from '@playwright/test';
import About from "../lib/about2.page";

const contextFile = './data/cd-state.json';

test.use({storageState: contextFile});

test.describe('About', async () => {
    test('Logged In Test', async ({page}) => {
        await page.goto('/');
        const about = new About(page, page.baseUrl);
        await page.locator('//button[contains(@class, "x-btn-text user_profile_16")]').click();
        await expect(page.locator(about.aboutSelectors.role)).toContainText('Center Director');
        await page.reload();

        await test.step('Verify About is the Last Tab', async () => {
            await expect(page.locator(about.aboutSelectors.tab)).toBeVisible();
            await expect(page.locator(about.aboutSelectors.last_tab)).toContainText('About');
        });

        await test.step('Select About Tab', async () => {
            await expect(page.locator(about.aboutSelectors.last_tab)).toBeVisible();
            await page.locator(about.aboutSelectors.last_tab).click();
            await expect(page.locator(about.aboutSelectors.container)).toBeVisible();
        });
        await test.step('Check Nav Entries', async () => {
            await test.step('XDMoD', async () => {
                await about.checkTab('XDMoD');
            });
            await test.step('Open XDMoD', async () => {
                await about.checkTab('Open XDMoD')
            });
            await test.step('SUPReMM', async () => {
                await about.checkTab('SUPReMM');
            });
            await test.step('Roadmap', async () => {
                await about.checkRoadMap();
            });
            await test.step('Team', async () => {
                await about.checkTab('Team');
            });
            await test.step('Publications', async () => {
                await about.checkTab('Publications');
            });
            await test.step('Presentations', async () => {
                await about.checkTab('Presentations');
            });
            await test.step('Links', async () => {
                await about.checkTab('Links');
            });
            await test.step('Release Notes', async () => {
                await about.checkTab('Release Notes');
            });
        });
    });

    test('Logged Out Tests', async ({page}) => {
        await page.goto('/');
        const about = new About(page, page.baseUrl);
        await test.step('Click the logout link', async () => {
            await expect(page.locator(about.aboutSelectors.logoutLink)).toBeVisible();
            await page.locator(about.aboutSelectors.logoutLink).click();
        });
        await test.step('Display Logged out State', async () => {
            //await expect('.ext-el-mask-msg');
            await expect(page.locator('//a[@id="sign_in_link"]')).toBeVisible();
        });
        await test.step('Verify About is the Last Tab', async () => {
            await expect(page.locator(about.aboutSelectors.tab)).toBeVisible();
            await expect(page.locator(about.aboutSelectors.last_tab)).toContainText('About');
        });

        await test.step('Select About Tab', async () => {
            await expect(page.locator(about.aboutSelectors.tab)).toBeVisible();
            await page.locator(about.aboutSelectors.last_tab).click();
            await expect(page.locator(about.aboutSelectors.container)).toBeVisible();
        });
        await test.step('Check Nav Entries', async () => {
            await test.step('XDMoD', async () => {
                await about.checkTab('XDMoD');
            });
            await test.step('Open XDMoD', async () => {
                await about.checkTab('Open XDMoD')
            });
            await test.step('SUPReMM', async () => {
                await about.checkTab('SUPReMM');
            });
            await test.step('Roadmap', async () => {
                await about.checkRoadMap();
            });
            await test.step('Team', async () => {
                await about.checkTab('Team');
            });
            await test.step('Publications', async () => {
                await about.checkTab('Publications');
            });
            await test.step('Presentations', async () => {
                await about.checkTab('Presentations');
            });
            await test.step('Links', async () => {
                await about.checkTab('Links');
            });
            await test.step('Release Notes', async () => {
                await about.checkTab('Release Notes');
            });
        });
    });
});
