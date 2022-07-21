import {expect, Locator, Page} from '@playwright/test';
import {BasePage} from "./base.page";
import aboutSelectors from "./about.selectors";

class About extends BasePage{
    readonly aboutSelectors = aboutSelectors;

    readonly tabLocator = this.page.locator(aboutSelectors.tab);
    readonly containerLocator = this.page.locator(aboutSelectors.container);
    readonly centerLocator =  this.page.locator(aboutSelectors.center);
    readonly lastTabLocator = this.page.locator(aboutSelectors.last_tab);

    async navEntry(name){
        return '//div[@class="x-tree-root-node"]//div[contains(@class,"x-tree-node-el")]//span[contains(text(),"' + String(name) + '")]';
    }

    async checkTab(name){
        var check = await this.navEntry(name);
        if (name == 'XDMoD'){
            check = '(' + check + ')[1]';
        }
        await expect(this.page.locator(check)).toBeVisible();
        await this.page.click(check);
        await this.page.waitForLoadState();
        await expect(this.page.locator(aboutSelectors.container)).toBeVisible();
        //Copied from js version:
        //TODO: Determine Pass case ffor this without using screenshot
        //browser.takeScreenshot(name.replace(' '. ''), this.center, "xdmod");
    }

    async checkRoadMap(){
        await expect(this.page.locator(await this.navEntry('Roadmap'))).toBeVisible();
        await this.page.locator(await this.navEntry('Roadmap')).click();
        await expect(this.page.locator('//iframe[@id="about_roadmap"]')).toBeVisible();
        await this.page.locator('//iframe[@id="about_roadmap"]', async function (err, result){
            await expect(err).toEqual(undefined);
            await expect(result).not.toEqual(null);
        });
        await expect(this.page.frameLocator('//iframe[@id="about_roadmap"]').locator('//div[contains(@class,"full-bleed-trello-board")]')).toBeVisible();
        await expect(this.page.frameLocator('//iframe[@id="about_roadmap"]').locator('//div[contains(@class,"full-bleed-trello-board")]').innerText()).not.toEqual(null);
    }
}

export default About;
