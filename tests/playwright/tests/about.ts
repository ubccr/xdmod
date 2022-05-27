import {test, expect} from '@playwright/test';
import logInPage from "../lib/loginPage.page";
import About from "../lib/about.page";

const contextFile = './data/about-center-director.json';

test.use({storageState: contextFile});

test.describe('About', function about() => {
	await expect(logInPage.login.('centerdirector'));
	test('Logged In Test', function loggedInTests() => {
		await test.step('Verify About is the Last Tab'), function aboutIsTheLastTab() => {
			await expect(allInvisible('.ext-el-mask'));
			await expect(About.tab).toBeVisible({timeout: 30000});
			await expect(page.getText(About.last_tab)).to.equal('About');
		});
		
		await test.step('Select About Tab', function selectTab() => {
			await expect(page.locator(About.tab)).toBeVisible({timeout: 50000});
			await page.locator(About.tab).click();
			await expect(page.locator(About.container)).toBeVisible({timeout: 20000});
		});
		test('Check Nav Entries', function checkNavEntries() => {
			await test.step('XDMoD', function checkNavEntryXDMoD() => {
				About.checkTab('XDMoD');
			});
			await test.step('Open XDMoD', function checkNavEntryXDMoD() => {
				About.checkTab('Open XDMoD')
			});
			await test.step('SUPReMM', function checkNavEntrySUPReMM() => {
				About.checkTab('SUPReMM');
			});
			await test.step('Roadmap', function checkNavEntryXDMoD() => {
				About.checkRoadMap();
			});
			await test.step('Team', function checkNavEntryXDMoD() => {
				About.checkTab('Team');
			});
			await test.step('Publications', function checkNavEntryXDMoD() => {
				About.checkTab('Publications');
			});
			await test.step('Presentations', function checkNavEntryXDMoD() => {
				About.checkTab('Presentations');
			});
			await test.step('Links', function checkNavEntryXDMoD() => {
				About.checkTab('Links');
			});
			await test.step('Release Notes', function checkNavEntryXDMoD() => {
				About.checkTab('Release Notes');
			});
		});
	});
	
	test('Logged Out Tests', function loggedInTests() => {
		await test.step('Click the logout link', function clickLogout() => {
			await expect(page.locator('#logout_link', {timeout: 500000});
			await page.locator('#logout_link').click();
		});
		await test.step('Display Logged out State', function clickLogout() => {
			await expect('.ext-el-mask-msg');
			await expect(page.locator('a[href*=actionLogin]')).waitForExist();
		});
		await test.step('Verify About is the Last Tab', function aboutIsTheLastTab() => {
			 await expect(About.tab).toBeVisible({timeout: 30000});
			 await expect(page.getText(About.last_tab)).to.equal('About');
		});
		
		await test.step('Select About Tab', function selectTab() => {
                        await expect(page.locator(About.tab)).toBeVisible({timeout: 50000});
                        await page.locator(About.tab).click();
                        await expect(page.locator(About.container)).toBeVisible({timeout: 20000});
                });
                test('Check Nav Entries', function checkNavEntries() => {
                        await test.step('XDMoD', function checkNavEntryXDMoD() => {
                                About.checkTab('XDMoD');
                        });
                        await test.step('Open XDMoD', function checkNavEntryXDMoD() => {
                                About.checkTab('Open XDMoD')
                        });
                        await test.step('SUPReMM', function checkNavEntrySUPReMM() => {
                                About.checkTab('SUPReMM');
                        });
                        await test.step('Roadmap', function checkNavEntryXDMoD() => {
                                About.checkRoadMap();
                        });
                        await test.step('Team', function checkNavEntryXDMoD() => {
                                About.checkTab('Team');
                        });
                        await test.step('Publications', function checkNavEntryXDMoD() => {
                                About.checkTab('Publications');
                        });
                        await test.step('Presentations', function checkNavEntryXDMoD() => {
                                About.checkTab('Presentations');
                        });
                        await test.step('Links', function checkNavEntryXDMoD() => {
                                About.checkTab('Links');
                        });
                        await test.step('Release Notes', function checkNavEntryXDMoD() => {
                                About.checkTab('Release Notes');
                        });
                });
        });
	await expect(logInPage.login.('cd'));
	await expect(logInPage.logout());
});
