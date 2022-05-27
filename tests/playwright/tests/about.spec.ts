import {test, expect} from '@playwright/test';
import LoginPage from "../lib/login.page";
import About from "../lib/about.page";

const contextFile = './data/about-center-director.json';

test.use({storageState: contextFile});

test.describe('About', async () => {
	await expect(LoginPage.login('centerdirector'));
	test('Logged In Test', async () => {
		await test.step('Verify About is the Last Tab', async () => {
			await expect(page.reload());
			await expect(About.tab).toBeVisible({timeout:30000});
			await expect(page.locator(About.last_tab)).toContainText('About');
		});
		
		await test.step('Select About Tab', async () => {
			await expect(page.locator(About.tab)).toBeVisible({timeout: 50000});
			await page.locator(About.tab).click();
			await expect(page.locator(About.container)).toBeVisible({timeout: 20000});
		});
		test('Check Nav Entries', async () => {
			await test.step('XDMoD', async () => {
				About.checkTab('XDMoD');
			});
			await test.step('Open XDMoD', async () => {
				About.checkTab('Open XDMoD')
			});
			await test.step('SUPReMM', async () => {
				About.checkTab('SUPReMM');
			});
			await test.step('Roadmap', async () => {
				About.checkRoadMap();
			});
			await test.step('Team', async () => {
				About.checkTab('Team');
			});
			await test.step('Publications', async () => {
				About.checkTab('Publications');
			});
			await test.step('Presentations', async () => {
				About.checkTab('Presentations');
			});
			await test.step('Links', async () => {
				About.checkTab('Links');
			});
			await test.step('Release Notes', async () => {
				About.checkTab('Release Notes');
			});
		});
	});
	
	test('Logged Out Tests', async () => {
		await test.step('Click the logout link', async () => {
			await expect(page.locator('#logout_link').toBevisible(), {timeout: 500000});
			await page.locator('#logout_link').click();
		});
		await test.step('Display Logged out State', async () => {
			await expect('.ext-el-mask-msg');
			await expect(page.locator('a[href*=actionLogin]')).waitForExist();
		});
		await test.step('Verify About is the Last Tab', async () => {
			 await expect(About.tab).toBeVisible({timeout: 30000});
			 await expect(page.getText(About.last_tab)).to.equal('About');
		});
		
		await test.step('Select About Tab', async () => {
                        await expect(page.locator(About.tab)).toBeVisible({timeout: 50000});
                        await page.locator(About.tab).click();
                        await expect(page.locator(About.container)).toBeVisible({timeout: 20000});
                });
                test('Check Nav Entries', async () => {
                        await test.step('XDMoD', async () => {
                                About.checkTab('XDMoD');
                        });
                        await test.step('Open XDMoD', async () => {
                                About.checkTab('Open XDMoD')
                        });
			await test.step('SUPReMM', async () => {
                                About.checkTab('SUPReMM');
                        });
                        await test.step('Roadmap', async () => {
                                About.checkRoadMap();
                        });
                        await test.step('Team', async () => {
                                About.checkTab('Team');
                        });
                        await test.step('Publications', async () => {
                                About.checkTab('Publications');
                        });
                        await test.step('Presentations', async () => {
                                About.checkTab('Presentations');
                        });
                        await test.step('Links', async () => {
                                About.checkTab('Links');
                        });
                        await test.step('Release Notes', async () => {
                                About.checkTab('Release Notes');
                        });
                });
        });
	await expect(LoginPage.login('cd'));
	await expect(LoginPage.logout());
});
