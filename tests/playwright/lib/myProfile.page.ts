import {BasePage} from "./base.page";
import selectors from './myProfile.selectors'

class MyProfile extends BasePage{
    static readonly selectors = selectors;

    static readonly toolbarButton = this.selectors.buttons.toolbar;
    static readonly container = this.selectors.container;
    static readonly general = this.selectors.general;
    static readonly tabs = this.selectors.tabs;

    /**
     * Retrieve an XPath for a tab that contains the parameter text within the
     * My Profile window. Values can be found within `this.names.tabs`.
     *
     * @param text {string} the text found within the tab to be returned.
     * @returns {string}
     */
    async tab(text) {
        return MyProfile.tabs.byText(text);
    }

    /**
     * Retrieve an XPath for a button, identified by the name parameter, within
     * the 'My Profile' window. Values for name provided by `this.names.buttons`
     *
     * @param name {string}
     * @returns {string}
     */
    async button(name) {
      return MyProfile.general.generalName(name);
    }
}
export default MyProfile;
