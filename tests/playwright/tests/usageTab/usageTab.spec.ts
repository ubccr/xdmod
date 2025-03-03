import {test, expect} from '@playwright/test';
import Usage from "../../lib/usageTab.page";
import {LoginPage} from "../../lib/login.page";
import artifacts from "../helpers/artifacts";
let expected = artifacts.getArtifact('usage');
let XDMOD_REALMS = process.env.XDMOD_REALMS;
import globalConfig from '../../playwright.config';
import testing from  '../../../ci/testing.json';
let roles = testing.role;

test.describe('Usage', async () => {
    const baselineDate={
        start: '2016-12-25',
        end: '2017-01-02'
    };
    // There are no tests for storage and cloud realms currently
    if (XDMOD_REALMS.includes('jobs')){
        test('(Center Director)', async ({page}) => {
            let baseUrl = globalConfig.use.baseURL;
            const usg = new Usage(page, baseUrl);
            const loginPage = new LoginPage(page, baseUrl, page.sso);
            await loginPage.login(roles['cd'].username, roles['cd'].password, (roles['cd'].givenname + " " + roles['cd'].surname), {timeout:10000});
            await test.step('Select "Usage" tab', async () => {
                await usg.selectTab();
                await expect(page.locator(usg.selectors.chart)).toBeVisible();
                await expect(page.locator(usg.selectors.mask)).toBeHidden();
                await expect(page.locator(usg.selectors.chartByTitle(expected.centerdirector.default_chart_title, true))).toBeVisible();

                // by refreshing we ensure that there are not stale legend-item elements
                // on the page.
                await page.reload();
                await expect(page.locator(usg.selectors.chartByTitle(expected.centerdirector.default_chart_title, true))).toBeVisible();
            });
            await test.step('Set a known start and end date', async () => {
                await usg.setStartDate(baselineDate.start);
                await usg.setEndDate(baselineDate.end);
                await usg.refresh();
                await expect(page.locator(usg.selectors.chartXAxisLabelByName(baselineDate.start))).toBeVisible();
            });
            await test.step('Select Job Size Min', async () =>{
                await expect(page.locator(usg.selectors.treeNodeByPath('Jobs Summary', 'Job Size: Min'))).toBeVisible();
                await page.locator(usg.selectors.treeNodeByPath('Jobs Summary', 'Job Size: Min')).click();
                await expect(page.locator(usg.selectors.chartByTitle('Job Size: Min (Core Count)', true))).toBeVisible();
                await usg.checkLegendText(expected.centerdirector.legend);

                //Check to make sure that the 'Std Err' display menu items are disabled.
                await expect(page.locator(usg.selectors.toolbarButtonByText('Display'))).toBeVisible();
                await page.locator(usg.selectors.toolbarButtonByText('Display')).click();
                const menuLabels = ['Std Err Bars', 'Std Err Labels'];
                for (const menuLabel of menuLabels){
                    await expect(page.locator(usg.selectors.displayMenuItemByText(menuLabel))).toBeVisible();
                    const check = await usg.toolbarMenuItemIsEnabled(menuLabel);
                    await expect(check).toBe(false);
                }
            });
              await test.step('View CPU Hours by System Username', async () => {
                await expect(page.locator(usg.selectors.unfoldTreeNodeByName('Jobs Summary'))).toBeVisible();
                await page.locator(usg.selectors.unfoldTreeNodeByName('Jobs Summary')).click();
                await expect(page.locator(usg.selectors.unfoldTreeNodeByName('Jobs by System Username'))).toBeVisible();
                await page.locator(usg.selectors.unfoldTreeNodeByName('Jobs by System Username')).click();
                await expect(page.locator(usg.selectors.treeNodeByPath('Jobs by System Username', 'CPU Hours: Per Job'))).toBeVisible();
                await page.locator(usg.selectors.treeNodeByPath('Jobs by System Username', 'CPU Hours: Per Job')).click();
                await expect(page.locator(usg.selectors.chartByTitle('CPU Hours: Per Job: by System Username', true))).toBeVisible();
            });
            await test.step('View CPU Hours: Per Job', async () => {
                await expect(page.locator(usg.selectors.unfoldTreeNodeByName('Jobs Summary', 'CPU Hours: Per Job'))).toBeVisible();
                await page.locator(usg.selectors.unfoldTreeNodeByName('Jobs Summary', 'CPU Hours: Per Job')).click();
                await expect(page.locator(usg.selectors.chartByTitle('CPU Hours: Per Job', true))).toBeVisible();

                ///Check to make sure that the 'Std Err' display menu items are disabled.
                await expect(page.locator(usg.selectors.toolbarButtonByText('Display'))).toBeVisible();
                await page.locator(usg.selectors.toolbarButtonByText('Display')).click();
                const menuLabels = ['Std Err Bars', 'Std Err Labels'];
                for (const menuLabel of menuLabels){
                    await expect(page.locator(usg.selectors.displayMenuItemByText(menuLabel))).toBeVisible();
                    const check = await usg.toolbarMenuItemIsEnabled(menuLabel);
                    await expect(check).toBe(true);
                }
            });
        });
        test('(Public User)', async ({page}) => {
            await page.goto('/');
            await page.waitForLoadState();
            const usg = new Usage(page, page.baseUrl);
            await page.locator(usg.selectors.signInLink).waitFor({state:'visible'});
            await test.step('Selected', async () => {
                await usg.selectTab();
                await page.locator(usg.selectors.chartByTitle(expected.centerdirector.default_chart_title, true)).waitFor({state:'visible'});

                // by refreshing we ensure that there are not stale legend-item elements
                // on the page.
                await page.reload();
                await expect(page.locator(usg.selectors.chartByTitle(expected.centerdirector.default_chart_title, true))).toBeVisible();
            });
            await test.step('Set a known start and end date', async () => {
                await usg.setStartDate(baselineDate.start);
                await usg.setEndDate(baselineDate.end);
                await usg.refresh();
                await expect(page.locator(usg.selectors.chartXAxisLabelByName(baselineDate.start))).toBeVisible();
            });
            await test.step('View Job Size Min', async () => {
                await expect(page.locator(usg.selectors.treeNodeByPath('Jobs Summary', 'Job Size: Min'))).toBeVisible();
                await page.locator(usg.selectors.treeNodeByPath('Jobs Summary', 'Job Size: Min')).click();
                await expect(page.locator(usg.selectors.chartByTitle('Job Size: Min (Core Count)', true))).toBeVisible();
                await usg.checkLegendText(expected.centerdirector.legend);
            });
            await test.step('Confirm System Username is not selectable', async () => {
                await expect(page.locator(usg.selectors.unfoldTreeNodeByName('Jobs Summary'))).toBeVisible();
                await page.locator(usg.selectors.unfoldTreeNodeByName('Jobs Summary')).click();
                await expect(page.locator(usg.selectors.topTreeNodeByName('Jobs by System Username'))).toBeVisible();
                await page.locator(usg.selectors.topTreeNodeByName('Jobs by System Username')).click();
                await expect(page.locator(usg.selectors.chartByTitle('Job Size: Min (Core Count)', true))).toBeVisible();
            });
        });
    }
});
