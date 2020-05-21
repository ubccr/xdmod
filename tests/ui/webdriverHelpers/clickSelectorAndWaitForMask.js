    





module.exports = function clickSelectorAndWaitForMask(selector) {
 
       browser.waitForVisible(selector);
        browser.waitForAllInvisible('.ext-el-mask');
        for (let i = 0; i < 100; i++) {
            try {
                browser.click(selector);
                break;
            } catch (e) {
                browser.waitForAllInvisible('.ext-el-mask');
                  
            }
        }
    };

    /*clickLogoAndWaitForMask() {
        this.clickSelectorAndWaitForMask('.xtb-text.logo93');
    }*/
