import {PlaywrightTestConfig, devices} from '@playwright/test';
const config: PlaywrightTestConfig = {
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    timeout: 50000,
    workers: 5,
    use: {
        trace: 'on-first-retry',
        video: 'on-first-retry',
        screenshot: 'only-on-failure',
        ignoreHTTPSErrors: true,
        viewport: {width: 2560, height: 1600},
        baseURL: process.env.BASE_URL,
        sso: process.env.SSO ? true : false,
        timeout: 50000
    },
    expect: {
        timeout: 30000,
    },
    projects: [
        {
            name: 'chromium',
            use: {...devices['Desktop Chrome']}
        },
        /*{
            name: 'firefox',
            use: {...devices['Desktop Firefox']}
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] }
        }*/
    ]
};

export default config;
