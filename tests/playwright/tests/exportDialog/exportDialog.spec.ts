import {test, expect} from '@playwright/test';
import Usage from '../../lib/usageTab.page';
import XDMoD from '../../lib/xdmod.page';

test('Export Dialog', async ({page}) => {
    await page.goto('/');
    await page.waitForLoadState();
    const usage = new Usage(page, page.baseUrl);
    const xdmod = new XDMoD(page, page.baseUrl);
    await test.step('Select "Usage" tab', async () => {
        await usage.selectTab();
    });
    await test.step('Bring up export dialog', async () => {
        await expect(page.locator(usage.selectors.toolbar.exportButton)).toBeVisible();
        await page.click(usage.selectors.toolbar.exportButton);
        await expect(page.locator(xdmod.selectors.exportDialog.window)).toBeVisible();
    });
    await test.step('Check format list', async () => {
        await expect(page.locator(xdmod.selectors.exportDialog.formatDropdown())).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.formatDropdown());
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(1))).toBeVisible();
        const expected = [
            'PNG - Portable Network Graphics',
            'SVG - Scalable Vector Graphics',
            'CSV - Comma Separated Values',
            'XML - Extensible Markup Language',
            'PDF - Portable Document Format'
        ];
        const computed = await page.$$(xdmod.selectors.exportDialog.comboListItems);
        const innerTexts = await Promise.all(computed.map(async (ele, i) => {
            return await ele.innerText();
        }));
        await expect(innerTexts).toEqual(expected);
        await expect(page.locator(xdmod.selectors.exportDialog.formatDropdown())).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.formatDropdown());
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(1))).toBeHidden();
    });
    await test.step('Check Image Sizes', async () => {
        await expect(page.locator(xdmod.selectors.exportDialog.imageSizeDropdown())).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.imageSizeDropdown());
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(8))).toBeVisible();
        const expected = [
            'Small',
            'Medium',
            'Large',
            'Poster',
            'Custom'
        ];
        const computed = await page.$$(xdmod.selectors.exportDialog.comboListItems);
        const innerTexts = await Promise.all(computed.map(async (ele, i) => {
            return await ele.innerText();
        }));
        await expect(innerTexts).toEqual(expected);
        await expect(page.locator(xdmod.selectors.exportDialog.imageSizeDropdown())).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.imageSizeDropdown());
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(8))).toBeHidden();
    });
    await test.step('Check show chart title exists', async () => {
        await expect(page.locator(xdmod.selectors.exportDialog.showTitleCheckbox())).toBeVisible();
    });
    await test.step('Switch to CSV output', async () => {
        await expect(page.locator(xdmod.selectors.exportDialog.formatDropdown())).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.formatDropdown());
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(1))).toBeVisible();
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByName('CSV'))).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.comboListItemByName('CSV'));
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(1))).toBeHidden();
    });
    await test.step('Make sure title and image options are not visible', async () => {
        await expect(page.locator(xdmod.selectors.exportDialog.showTitleCheckbox())).toBeHidden();
        await expect(page.locator(xdmod.selectors.exportDialog.imageSizeDropdown())).toBeHidden();
    });
    await test.step('Switch to PDF output', async () => {
        await expect(page.locator(xdmod.selectors.exportDialog.formatDropdown())).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.formatDropdown());
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(1))).toBeVisible();
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByName('PDF'))).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.comboListItemByName('PDF'));
        await expect(page.locator(xdmod.selectors.exportDialog.comboListItemByNum(1))).toBeHidden();
    });
    await test.step('Make sure title and size options are visible', async () => {
       await expect(page.locator(xdmod.selectors.exportDialog.showTitleCheckbox())).toBeVisible();
       await expect(page.locator(xdmod.selectors.exportDialog.widthInput())).toBeVisible();
       await expect(page.locator(xdmod.selectors.exportDialog.heightInput())).toBeVisible();
       await expect(page.locator(xdmod.selectors.exportDialog.fontInput())).toBeVisible();
    });
    await test.step('Close', async () => {
        await expect(page.locator(xdmod.selectors.exportDialog.cancelButton())).toBeVisible();
        await page.click(xdmod.selectors.exportDialog.cancelButton());
        await expect(page.locator(xdmod.selectors.exportDialog.window)).toBeHidden();
    });
});
