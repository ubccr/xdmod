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
    readonly loggedInDisplayLocator = this.page.locator(InternalDashboard.selectors.loggedInDisplayName);
    readonly overviewTabLocator = this.page.locator(selectors.summary.tabs.overview());

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
        await this.overviewTabLocator.isVisible();
        await expect(this.loggedInDisplayLocator).toContainText(display);
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
