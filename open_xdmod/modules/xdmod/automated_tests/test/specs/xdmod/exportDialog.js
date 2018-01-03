var usage = require('./usageTab.page.js');
var xdmod = require('./xdmod.page.js');

describe('Export Dialog', function () {
    it('Select "Usage" tab', function () {
        browser.url('/');
        usage.selectTab();
    });
    it('Bring up export dialog', function () {
        browser.waitAndClick(usage.toolbar.exportButton);
        browser.waitForVisible(xdmod.selectors.exportDialog.window);
    });
    it('Check format list', function () {
        browser.waitAndClick(xdmod.selectors.exportDialog.formatDropdown());
        browser.waitForVisible(xdmod.selectors.exportDialog.comboList);
        var expected = [
            'PNG - Portable Network Graphics',
            'SVG - Scalable Vector Graphics',
            'CSV - Comma Separated Values',
            'XML - Extensible Markup Language',
            'PDF - Portable Document Format'
        ];
        expect(browser.getText(xdmod.selectors.exportDialog.comboListItems)).to.deep.equal(expected);
        browser.waitAndClick(xdmod.selectors.exportDialog.formatDropdown());
    });
    it('Check Image Sizes', function () {
        browser.waitAndClick(xdmod.selectors.exportDialog.imageSizeDropdown());
        browser.waitForVisible(xdmod.selectors.exportDialog.comboList);
        var expected = [
            'Small',
            'Medium',
            'Large',
            'Poster',
            'Custom'
        ];
        expect(browser.getText(xdmod.selectors.exportDialog.comboListItems)).to.deep.equal(expected);
        browser.waitAndClick(xdmod.selectors.exportDialog.imageSizeDropdown());
    });
    it('Check show chart title exists', function () {
        browser.waitForVisible(xdmod.selectors.exportDialog.showTitleCheckbox());
    });
    it('Switch to CSV output', function () {
        browser.waitAndClick(xdmod.selectors.exportDialog.formatDropdown());
        browser.waitForVisible(xdmod.selectors.exportDialog.comboList);
        browser.waitAndClick(xdmod.selectors.exportDialog.comboListItemByName('CSV'));
    });
    it('Make sure title and image options are not visible', function () {
        browser.waitForInvisible(xdmod.selectors.exportDialog.showTitleCheckbox());
        browser.waitForInvisible(xdmod.selectors.exportDialog.imageSizeDropdown());
    });
    it('Switch to PDF output', function () {
        browser.waitAndClick(xdmod.selectors.exportDialog.formatDropdown());
        browser.waitForVisible(xdmod.selectors.exportDialog.comboList);
        browser.waitAndClick(xdmod.selectors.exportDialog.comboListItemByName('PDF'));
    });
    it('Make sure title and size options are visible', function () {
        browser.waitForVisible(xdmod.selectors.exportDialog.showTitleCheckbox());
        browser.waitForVisible(xdmod.selectors.exportDialog.widthInput());
        browser.waitForVisible(xdmod.selectors.exportDialog.heightInput());
        browser.waitForVisible(xdmod.selectors.exportDialog.fontInput());
    });
    it('Close', function () {
        browser.waitAndClick(xdmod.selectors.exportDialog.cancelButton());
        browser.waitForInvisible(xdmod.selectors.exportDialog.window);
    });
});
