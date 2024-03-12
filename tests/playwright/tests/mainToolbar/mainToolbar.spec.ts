import {test} from '@playwright/test';
import MainToolbar from "../../lib/mainToolbar.page";

let mainTab;

test.describe('Main Toolbar', async () => {
    test('Check Tab', async ({page}) => {
        await page.goto('/');
        await page.waitForLoadState();
        const mTb = new MainToolbar(page, page.baseUrl);

        await test.step('Get Browswer Tab ID', async () => {
            mainTab = await page.evaluateHandle(() => Promise.resolve(window));
        });

        await test.step("About should change 'Tabs'", async () =>{
            await mTb.checkAbout();
        });
    });

    test('Contact Us - Send Message', async ({page}) => {
        await page.goto('/');
        await page.waitForLoadState();
        const mTb = new MainToolbar(page, page.baseUrl);
        await mTb.contactFunc('Send Message');
    });

    test('Contact Us - Request Feature', async ({page}) => {
        await page.goto('/');
        await page.waitForLoadState();
        const mTb = new MainToolbar(page, page.baseUrl);
        await mTb.contactFunc('Request Feature');
    });

    test('Contact Us - Submit Support Request', async ({page}) => {
        await page.goto('/');
        await page.waitForLoadState();
        const mTb = new MainToolbar(page, page.baseUrl);
        await mTb.contactFunc('Submit Support Request');
    });

    test('Help', async ({page}) =>{
        await page.goto('/');
        await page.waitForLoadState();
        const mTb = new MainToolbar(page, page.baseUrl);
        for (let type in mTb.selectors.helpTypes){
            if (mTb.selectors.helpTypes.hasOwnProperty(type)){
                await mTb.helpFunc(type, mainTab);
            }
        }
    });
});
