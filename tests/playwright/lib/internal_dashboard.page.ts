import {expect} from "@playwright/test";
import {BasePage} from "./base.page";
import {LoginInterface} from "./login.page";
import selectors from "./internal_dashboard.selectors";

class InternalDashboard extends BasePage implements LoginInterface {
    static readonly selectors = selectors;

    readonly usernameLocator = this.page.locator(InternalDashboard.selectors.login.username);
    readonly passwordLocator = this.page.locator(InternalDashboard.selectors.login.password);
    readonly submitLocator = this.page.locator(InternalDashboard.selectors.login.submit);
    readonly logoutLinkLocator = this.page.locator(InternalDashboard.selectors.logoutLink);

    async login(username: string, password: string, display: string) {
        await this.verifyLocation('/internal_dashboard', 'XDMoD Internal Dashboard');
        await this.usernameLocator.isVisible();
        await this.passwordLocator.isVisible();
        await this.submitLocator.isVisible();

        await this.usernameLocator.fill(username);
        await this.passwordLocator.fill(password);
        await this.submitLocator.click();
        await this.submitLocator.isHidden();

        await this.logoutLinkLocator.isVisible();

        const login = this.page.locator(selectors.loggedIn(display));
        await login.isVisible();

        const overviewTab = this.page.locator(selectors.summary.tabs.overview());
        await overviewTab.isVisible();

        const usersPanel = this.page.locator(selectors.summary.tabs.usersPanel);
        await usersPanel.isVisible();

        const userManagementTab = this.page.locator(selectors.header.tabs.user_management());
        await userManagementTab.isVisible();
    }

    async logout() {
        console.log('Logging Out!');
        await this.logoutLinkLocator.isVisible();
        await this.logoutLinkLocator.click();

        await this.usernameLocator.isVisible();
        await this.passwordLocator.isVisible();
        await this.submitLocator.isVisible();
    }
}

export default InternalDashboard;
