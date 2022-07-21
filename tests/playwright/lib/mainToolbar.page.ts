import {expect, Locator, Page} from '@playwright/test';
import {BasePage} from "./base.page";
import mTbSelectors from "./mainToolbar.selectors";

class MainToolbar extends BasePage{
    readonly mTbSelectors = mTbSelectors;

    readonly toolbarCloseLocator = this.page.locator(mTbSelectors.toolbarClose);
    readonly toolbarAboutLocator = this.page.locator(mTbSelectors.toolbarAbout);
    readonly contactusLocator = this.page.locator(mTbSelectors.contactus);
    readonly helpLocator = this.page.locator(mTbSelectors.help);
    readonly aboutLocator = this.page.locator(mTbSelectors.about);
    readonly containerLocator = this.page.locator(mTbSelectors.container);
    readonly headerLocator = this.page.locator(mTbSelectors.header);
    readonly floatlayerLocator = this.page.locator(mTbSelectors.floatlayer);
    readonly noteLocator = this.page.locator(mTbSelectors.note);

    async helpFunc(type, mainTab){
        const helpTypesLoc = this.page.locator(mTbSelectors.helpTypes[type]);
        await this.helpLocator.click();
        await this.page.locator(mTbSelectors.floatlayer).waitFor({state:'visible'});
        await expect(this.floatlayerLocator).toBeVisible();
        await expect(helpTypesLoc).toBeVisible();
        let context = this.page.context();
        const [newPage]:Page = await Promise.all([
            context.waitForEvent('page'),
            await helpTypesLoc.click()
        ]);
        await newPage.waitForLoadState();
        var ids = context.pages();
        var id = ids.length;
        await expect(id).toEqual(2);
        await newPage.close();
        await expect(context.pages().length).toEqual(1);
	}

    async contactFunc(type){
        const contactTypesLocator = this.page.locator(mTbSelectors.contactTypes[type]);
        const maskLocator = this.page.locator('//div[contains(@class, "ext-el-mask-msg") and contains(., "Processing Query")]');
        const maskHolder = await maskLocator.isVisible();
        if (maskHolder){
            await maskLocator.waitFor({state:"detached"});
        }
        const floatHolder = await this.floatlayerLocator.isHidden();
        if (floatHolder){
          await this.contactusLocator.click({position:{x:5,y:5}});
        }
        await expect(this.floatlayerLocator).toBeVisible();
        await expect(contactTypesLocator).toBeVisible();
        await contactTypesLocator.click();
        await expect(this.floatlayerLocator).toBeHidden();
        await expect(this.containerLocator).toBeVisible();
        await expect(this.headerLocator).toContainText(type);
        await expect(this.toolbarCloseLocator).toBeVisible();
        await this.toolbarCloseLocator.click();
    }

    async checkAbout(){
        await this.toolbarAboutLocator.isVisible();
        await this.toolbarAboutLocator.click();
        await this.aboutLocator.isVisible();
    }
}
export default MainToolbar;
