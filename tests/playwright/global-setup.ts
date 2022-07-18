import {chromium, FullConfig} from '@playwright/test';
import userRoles from './config/users.json';
import {LoginInterface, LoginPage} from "./lib/login.page";
import InternalDashboard from "./lib/internal_dashboard.page";
import globalConfig from './playwright.config';

async function globalSetup(config: FullConfig) {
    const browser = await chromium.launch();
    let loginPage: LoginInterface;
    let contextPath: string;
    let baseUrl = globalConfig.use.baseURL;
    let sso = globalConfig.use.sso;

    for (const index in userRoles) {
        const userRole = userRoles[index];
        const page = await browser.newPage({ignoreHTTPSErrors: true});

	contextPath = `./data/${userRole.role}-state.json`;
        switch (userRole.role) {
            case 'admin':
                loginPage = new InternalDashboard(page, baseUrl);
                break;
	    default:
                loginPage = new LoginPage(page, baseUrl, sso);
                break;
        }
	await loginPage.login(userRole.username, userRole.password, userRole.display);
	await loginPage.page.context().storageState({ path: contextPath});
    }
}

export default globalSetup;
