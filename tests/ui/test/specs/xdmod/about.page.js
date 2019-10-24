class About {
    constructor() {
        this.tab = '//ul[contains(@class, "x-tab-strip")]//span[text()="About"]';
        this.container = '//div[@id="about_xdmod"]';
        this.center = '//div[@id="about_xdmod"]//div[contains(@class, "x-panel-body") and contains(@class, "x-border-layout-ct")]/div[contains(@class,"x-panel") and contains(@class,"x-panel-reset") and contains(@class,"x-border-panel")]';
        this.last_tab = '//ul[contains(@class, "x-tab-strip")]//li[contains(@class, "tab-strip")][last()]';
    }

    navEntry(name) {
        return '//div[@class="x-tree-root-node"]//div[contains(@class,"x-tree-node-el")]//span[contains(text(),"' + name + '")]';
    }

    checkTab(name) {
        browser.waitForLoadedThenClick(this.navEntry(name), 50000);
        $(this.container).waitForText(50000);
        // TODO: Determine Pass case for this without using screenshot
        // browser.takeScreenshot(name.replace(' ',''), this.center, "xdmod");
    }

    checkRoadmap() {
        browser.waitForLoadedThenClick(this.navEntry('Roadmap'));
        browser.waitForExist('iframe#about_roadmap', 30000);
        browser.frame('about_roadmap', function (err, result) {
            expect(err).to.be.a('undefined');
            expect(result).to.not.be.a('null');
        });
        browser.waitForExist('.trello-lists', 30000);
        browser.waitForText('.trello-lists', 30000);
        browser.frameParent();
    }

}
module.exports = new About();
