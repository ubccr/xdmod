import {expect, Locator, Page} from '@playwright/test';
import {BasePage} from "./base.page";
import xdmodSelectors from "./xdmod.selectors";

class XDMoD extends BasePage{
    readonly xdmodSelectors = xdmodSelectors;
    readonly maskLocator = this.page.locator(xdmodSelectors.mask);

    async selectTab(tabId:string){
        const tab = this.page.locator('//div[contains(@class, "x-tab-strip-wrap")]//li[@id="main_tab_panel__' + tabId + '"]');
        const panel = this.page.locator('//div[@id="' + tabId + '"]');

        const tempMaskLocator = this.page.locator('//div[contains(@class, "ext-el-mask-msg") and contains(., "Processing Query")]');
        const maskHolder = await tempMaskLocator.isVisible();
        if (maskHolder){
            await tempMaskLocator.waitFor({state:"detached"});
        }
        for (let i = 0; i < 100; i++){
            try {
                await tab.click();
                break;
            } catch (e) {
                await expect(this.maskLocator).toBeHidden();
            }
        }
        await panel.waitFor({state:'visible'});
        await expect(this.maskLocator).toBeHidden();
    }
}
export default XDMoD;
