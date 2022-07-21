import {expect, Locator, Page} from "@playwright/test";

export class BasePage {
    readonly page: Page;
    readonly maskSelector: string;
    readonly mask: Locator;
    readonly baseUrl: string;

    constructor(page: Page, baseUrl: string) {
        this.page = page;
        this.maskSelector = '.ext-el-mask-msg';
        this.mask = page.locator(this.maskSelector);
        this.baseUrl = baseUrl;
    }

    public async verifyLocation(url: string, expectedTitle: string) {
        const newUrl = new URL(url, this.baseUrl);
        try{
         await this.page.goto(newUrl.toString());
        }catch(error){
          throw new Error(error);
        }
        const title = await this.page.title();
        expect(title).toEqual(expectedTitle);
    }
}
