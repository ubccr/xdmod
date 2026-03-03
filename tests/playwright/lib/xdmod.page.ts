import {expect} from '@playwright/test';
import {BasePage} from "./base.page";
import selectors from "./xdmod.selectors";

class XDMoD extends BasePage {
    readonly selectors = selectors;

    async selectTab(tabId:string){
        const tabLocator = this.page.locator(this.selectors.tab(tabId));
        const panel = this.page.locator(this.selectors.panel(tabId));

        await tabLocator.click();
        await panel.waitFor({state:'visible'});
        await this.expectAllMasksToBeHidden();
    }

    /**
     * When ExtJS creates a `mask` element it adds the `x-masked` class to the `body` dom element, which is removed
     * when the mask is hidden / removed. This function waits for / expects there to not be a body element with the
     * `x-masked` class, which means there are no currently active masks.
     */
    async expectAllMasksToBeHidden() {
        const maskLocator = this.page.locator('body.x-masked');
        await expect(maskLocator).toBeHidden();
    }
}
export default XDMoD;
