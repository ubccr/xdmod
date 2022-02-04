import {chromium, FullConfig} from '@playwright/test';
import userRoles from './config/users.json';
import {LoginInterface, LoginPage} from "./lib/login.page";
import InternalDashboard from "./lib/internal_dashboard.page";

async function globalSetup(config: FullConfig) {
    const browser = await chromium.launch();
    let loginPage: LoginInterface;
    let contextPath: string;

    for (const index in userRoles) {
        const userRole = userRoles[index];
        const page = await browser.newPage({ignoreHTTPSErrors: true});

        switch (userRole.role) {
            case 'admin':
                loginPage = new InternalDashboard(page);
                contextPath = `./data/internal_dashboard-${userRole.role}-state.json`;
                await loginPage.login(userRole.username, userRole.password, userRole.display);
                await loginPage.page.context().storageState({ path: contextPath});
                break;
            default:
                loginPage = new LoginPage(page);
                contextPath = `./data/${userRole.role}-state.json`;
                break;

        }

    }
}

export default globalSetup;
