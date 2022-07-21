import {test, expect} from '@playwright/test';
import {LoginPage} from "../../lib/login.page";
import MainToolbar from "../../lib/mainToolbar.page";

var mainTab;

const contextFile = './data/cd-state.json';

test.use({storageState: contextFile});

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
        for (var type in mTb.mTbSelectors.helpTypes){
            if (mTb.mTbSelectors.helpTypes.hasOwnProperty(type)){
                await test.step(type, async () => {
                    await mTb.helpFunc(type, mainTab);
                });
            }
        }
    });
});
