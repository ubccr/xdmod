import {expect, Locator, Page} from '@playwright/test';
import {BasePage} from "./base.page";

export interface LoginInterface {
    page: Page;

    login(username: string, password: string, display: string);

    logout();
}

export class LoginPage extends BasePage implements LoginInterface {
    readonly logo: Locator;
    readonly username: Locator;
    readonly password: Locator;
    readonly signInButton: Locator;
    readonly loginLink: Locator;
    readonly welcomeMessage: Locator;
    readonly mainTab: Locator;
    readonly logoutLink: Locator;

    readonly loginTitle: string;
    readonly adminTitle: string;

    constructor(page: Page, baseUrl: string) {
        super(page, baseUrl);
        this.logo = page.locator('#logo');
        this.loginLink = page.locator('a[href*=actionLogin]');
        this.username = page.locator('#txt_login_username');
        this.password = page.locator('#txt_login_password');
        this.signInButton = page.locator('#btn_sign_in');
        this.welcomeMessage = page.locator('#welcome_message');
        this.mainTab = page.locator('#main_tab_panel__about_xdmod');
        this.logoutLink = page.locator('#logout_link');


        this.loginTitle = 'Open XDMoD';
        this.adminTitle = 'XDMoD Internal Dashboard';
    }

    async login(username: string, password: string, display: string) {
        await this.verifyLocation('/', this.loginTitle);
        await expect(this.loginLink).toBeVisible();
        await this.loginLink.click();
        await expect(this.signInButton).toBeVisible();

        await this.username.fill(username);
        await this.password.fill(password);
        await this.signInButton.click();
        await expect(this.signInButton).toBeHidden();
        await this.welcomeMessage.isVisible();

        await expect(this.welcomeMessage).toContainText(display);
        await expect(this.mainTab).toBeVisible();
    }

    async logout() {
        await this.logoutLink.isVisible();
        await this.logoutLink.click();
        await this.loginLink.isVisible();
        await this.mainTab.isVisible();
    }
}
