import {expect, Locator, Page} from '@playwright/test';

export class About{
	constructor() {
		this.tab = '//ul[contains@class, "x-tab-strip")]//span[text()="About"]';
	       	this.container = '//div[@id="about_xdmod"]';
		this.center = '//div[@id="about_xdmod"]//div[contains(@class, "x-panel-body") and contains(@class, "x-border-layout-ct")]/div[contains(@class,"x-panel") and contains(@class,"x-panel-reset") and contains(@class,"x-border-panel")]';
		this.last_tab = '//ul[contains(@class, "x-tab-strip")]//li[contains(@class, "tab-strip")[last()]';
	}
	
	async navEntry(name:string){
		return '//div[@class="x-tree-root-node"]//div[contains(@class,"x-tree-node-el")]//span[contains(text(),"'+ name + '")]';
	}

	async checkTab(name:string){
		await expect(this.navEntry(name)).toBeVisible({timeout: 50000});
		await page.locator(this.navEntry(name)).click();
		await expect(this.container).waitForProperty(50000);
		//Copied from js version:
		//TODO: Determine Pass case ffor this without using screenshot
		//browser.takeScreenshot(name.replace(' '. ''), this.center, "xdmod");
	}

	async checkRoadMap(){
		await expect(this.navEntry('Roadmap')).toBeVisibile();
		await page.locator(this.navEntry('Roadmap')).click();
		await expect(page.locator('iframe#about_roadmap')).toBeVisible({timeout: 30000});
//		await page.frame('about_roadmap', function (err, result){
//			await expect(err).to.equal('undefined');
//			await expect(result).to.not.equal('null');
//		});
		await expect(page.frame('about_roadmap').content()).to.equal('undefined');
		await expect(page.locator('.full-bleed-trello-board')).toBeVisible({timeout: 30000});
		await expect(page.locator('.full-bleed-trello-board')).waitForProperty(30000);
		await page.frame.parentFrame();
	}
}
export default About;
//module.exports = new About();
