import {test, expect, Page} from '@playwright/test';
import About from "../lib/about.page";

const contextFile = './data/cd-state.json';

test.use({storageState: contextFile});

async function checkTab(name:string, page:Page){
    var check = await About.navEntry(name);
    if (name == 'XDMoD'){
        check = '(' + check + ')[1]';
    }
    await expect(page.locator(check)).toBeVisible();
    await page.click(check);
    await page.waitForLoadState();
    await expect(page.locator(About.aboutSelectors.container)).toBeVisible();
    //Copied from js version:
    //TODO: Determine Pass case ffor this without using screenshot
    //browser.takeScreenshot(name.replace(' '. ''), this.center, "xdmod");
}

async function checkRoadMap(page:Page) {
    await expect(page.locator(await About.navEntry('Roadmap'))).toBeVisible();
    await page.locator(await About.navEntry('Roadmap')).click();
    await expect(page.locator('//iframe[@id="about_roadmap"]')).toBeVisible();
    await page.locator('//iframe[@id="about_roadmap"]', async function (err, result){
        await expect(err).toEqual(undefined);
        await expect(result).not.toEqual(null);
    });
    await expect(page.frameLocator('//iframe[@id="about_roadmap"]').locator('//div[contains(@class,"full-bleed-trello-board")]')).toBeVisible();
    await expect(page.frameLocator('//iframe[@id="about_roadmap"]').locator('//div[contains(@class,"full-bleed-trello-board")]').innerText()).not.toEqual(null);
}

test.describe('About', async () => {
    test('Logged In Test', async ({page}) => {
        await page.goto('/');
        await page.locator('//button[contains(@class, "x-btn-text user_profile_16")]').click();
        await expect(page.locator(About.aboutSelectors.role)).toContainText('Center Director');

        await test.step('Verify About is the Last Tab', async () => {
            await page.reload();
            await page.waitForLoadState();
            await expect(page.locator(About.aboutSelectors.tab)).toBeVisible();
            await expect(page.locator(About.aboutSelectors.last_tab)).toContainText('About');
        });

        await test.step('Select About Tab', async () => {
            await expect(page.locator(About.aboutSelectors.last_tab)).toBeVisible();
            await page.locator(About.aboutSelectors.last_tab).click();
            await expect(page.locator(About.aboutSelectors.container)).toBeVisible();
        });
        await test.step('Check Nav Entries', async () => {
            await test.step('XDMoD', async () => {
                await checkTab('XDMoD', page);
            });
            await test.step('Open XDMoD', async () => {
                await checkTab('Open XDMoD', page)
            });
            await test.step('SUPReMM', async () => {
                await checkTab('SUPReMM', page);
            });
            await test.step('Roadmap', async () => {
                await checkRoadMap(page);
            });
            await test.step('Team', async () => {
                await checkTab('Team', page);
            });
            await test.step('Publications', async () => {
                await checkTab('Publications', page);
            });
            await test.step('Presentations', async () => {
                await checkTab('Presentations', page);
            });
            await test.step('Links', async () => {
                await checkTab('Links', page);
            });
            await test.step('Release Notes', async () => {
                await checkTab('Release Notes', page);
            });
        });
    });

    test('Logged Out Tests', async ({page}) => {
        await page.goto('/');
        await test.step('Click the logout link', async () => {
            await expect(page.locator(About.aboutSelectors.logoutLink)).toBeVisible();
            await page.locator(About.aboutSelectors.logoutLink).click();
        });
        await test.step('Display Logged out State', async () => {
            //await expect('.ext-el-mask-msg');
            await expect(page.locator('//a[@id="sign_in_link"]')).toBeVisible();
        });
        await test.step('Verify About is the Last Tab', async () => {
            await expect(page.locator(About.aboutSelectors.tab)).toBeVisible();
            await expect(page.locator(About.aboutSelectors.last_tab)).toContainText('About');
        });

        await test.step('Select About Tab', async () => {
            await expect(page.locator(About.aboutSelectors.tab)).toBeVisible();
            await page.locator(About.aboutSelectors.last_tab).click();
            await expect(page.locator(About.aboutSelectors.container)).toBeVisible();
        });
        await test.step('Check Nav Entries', async () => {
            await test.step('XDMoD', async () => {
                await checkTab('XDMoD', page);
            });
            await test.step('Open XDMoD', async () => {
                await checkTab('Open XDMoD', page)
            });
            await test.step('SUPReMM', async () => {
                await checkTab('SUPReMM', page);
            });
            await test.step('Roadmap', async () => {
                await checkRoadMap(page);
            });
            await test.step('Team', async () => {
                await checkTab('Team', page);
            });
            await test.step('Publications', async () => {
                await checkTab('Publications', page);
            });
            await test.step('Presentations', async () => {
                await checkTab('Presentations', page);
            });
            await test.step('Links', async () => {
                await checkTab('Links', page);
            });
            await test.step('Release Notes', async () => {
                await checkTab('Release Notes', page);
            });
        });
        //make sure that the end result here is indeed logged out
    });
});
