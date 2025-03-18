import {expect, Page} from '@playwright/test';
import {BasePage} from "./base.page";
import selectors from "./mainToolbar.selectors";

class MainToolbar extends BasePage{
  readonly selectors = selectors;
  readonly toolbarCloseLocator = this.page.locator(selectors.toolbarClose);
  readonly toolbarAboutLocator = this.page.locator(selectors.toolbarAbout);
  readonly contactusLocator = this.page.locator(selectors.contactus);
  readonly helpLocator = this.page.locator(selectors.help);
  readonly aboutLocator = this.page.locator(selectors.about);
  readonly containerLocator = this.page.locator(selectors.container);
  readonly headerLocator = this.page.locator(selectors.header);
  readonly floatlayerLocator = this.page.locator(selectors.floatlayer);
  readonly noteLocator = this.page.locator(selectors.note);

  async helpFunc(type, mainTab){
    const helpTypesLoc = this.page.locator(selectors.helpTypes[type]);
    await this.helpLocator.click();
    await this.page.locator(selectors.floatlayer).waitFor({state:'visible'});
    await expect(this.floatlayerLocator).toBeVisible();
    await expect(helpTypesLoc).toBeVisible();
    let context = this.page.context();
    const [newPage]:Page = await Promise.all([
      context.waitForEvent('page'),
      await helpTypesLoc.click(),
    ]);
    await newPage.waitForLoadState();
    let ids = context.pages();
    let id = ids.length;
    await expect(id).toEqual(2);
    await newPage.close();
    await expect(context.pages().length).toEqual(1);
  }

  async contactFunc(type){
    const contactTypesLocator = this.page.locator(selectors.contactTypes[type]);
    await this.noteLocator.waitFor({state:'hidden'});
    await this.contactusLocator.click();
    await this.floatlayerLocator.waitFor({state:'visible'});
    await contactTypesLocator.waitFor({state:'visible'});
    await contactTypesLocator.click();
    await this.floatlayerLocator.waitFor({state:'hidden'});
    await this.containerLocator.waitFor({state:'visible'});
    const content = await this.headerLocator.textContent();
    await expect(content).toEqual(type);
    await this.toolbarCloseLocator.waitFor({state:'visible'});
    await this.toolbarCloseLocator.click();
    await expect(this.aboutLocator).toBeVisible();
  }

  async checkAbout(){
    await this.toolbarAboutLocator.isVisible();
    await this.toolbarAboutLocator.click();
    await this.aboutLocator.isVisible();
  }
}
export default MainToolbar;
