import {expect, Locator, Page} from '@playwright/test';
import {BasePage} from "./base.page";
import myProfileSelectors from './myProfile.selectors'

class myProfile extends BasePage{
    static readonly myProfileSelectors = myProfileSelectors;
    static readonly toolbarButton = '#global-toolbar-profile';
    static readonly container = '//div[@id="xdmod-profile-editor"]';
    static readonly general = this.container + '//div[@id="xdmod-profile-general-settings"]';
    
  //static readonly general = myProfileSelectors.general;

    /**
     * Retrieve an XPath for a tab that contains the parameter text within the
     * My Profile window. Values can be found within `this.names.tabs`.
     *
     * @param text {string} the text found within the tab to be returned.
     * @returns {string}
     */
    async tab(text) {
        return myProfile.container + '//span[contains(@class, "x-tab-strip-text") and contains(text(),"'+ text + '")]';
    }

    //generalUserInformation(name) transferred to myProfile.selectors.ts since selectors use it

    /**
     * Retrieve an XPath for a button, identified by the name parameter, within
     * the 'My Profile' window. Values for name provided by `this.names.buttons`
     *
     * @param name {string}
     * @returns {string}
     */
    async button(name) {
      return myProfile.general + '//button[contains(@class, "' + name + '")]';
    }
}
export default myProfile;
