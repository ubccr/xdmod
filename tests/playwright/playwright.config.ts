import {PlaywrightTestConfig, devices} from '@playwright/test';
// Comment to trigger CI man do I hate trello boards...
const config: PlaywrightTestConfig = {
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    timeout: 60000,
    workers: 5,
    use: {
        trace: 'on-first-retry',
        video: 'on-first-retry',
        screenshot: 'only-on-failure',
        ignoreHTTPSErrors: true,
        viewport: {width: 2560, height: 1600},
        baseURL: process.env.BASE_URL,
        sso: process.env.SSO ? true : false,
        timeout: 15000
    },
    projects: [
        {
            name: 'chromium',
            use: {...devices['Desktop Chrome']}
        }
    ]
};

export default config;
