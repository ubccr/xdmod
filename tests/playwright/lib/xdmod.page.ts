import {expect} from '@playwright/test';
import {BasePage} from "./base.page";
import selectors from "./xdmod.selectors";

class XDMoD extends BasePage{
    readonly selectors = selectors;
    readonly maskLocator = this.page.locator(selectors.mask);

    async selectTab(tabId:string){
        const tabLocator = this.page.locator(this.selectors.tab(tabId));
        const panel = this.page.locator(this.selectors.panel(tabId));

        await tabLocator.click();
        await panel.waitFor({state:'visible'});
        await expect(this.maskLocator).toBeHidden();
    }
}
export default XDMoD;
