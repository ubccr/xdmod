const selectors = {
    index: `file:///xdmod/html/unit_tests/index.html`,
    passes: '//div[@id="mocha"]//ul[@id="mocha-stats"]//li[@class="passes"]//em',
    fails: '//div[@id="mocha"]//ul[@id="mocha-stats"]//li[@class="failures"]//em',
    time: '//div[@id="mocha"]//ul[@id="mocha-stats"]//li[@class="duration"]//em',
    taskNav: function(task) {
        return `//div[@id="mocha"]//ul[@id="mocha-report"]//li[@class="suite"]//h2[text()="${task}"]//a`;
    },
    tasksDisplayed: '//div[@id="mocha"]//ul[@id="mocha-report"]//li[@class="suite"]//ul//ul//li//h2',
    messageBox:{
        window: '//div[contains(@class, "x-window x-window-plain")]',
        button:{
            ok: function() {
                return selectors.messageBox.window + '//button[text()="OK"]';
            },
            yes: function() {
                return selectors.messageBox.window + '//button[text()="Yes"]';
            },
            no: function() {
                return selectors.messageBox.window + '//button[text()="No"]';
            },
            cancel: function() {
                return selectors.messageBox.window + '//button[text()="Cancel"]';
            }
        }
    },
    headers: {
        navHeader: function(name) {
            return `//ul[@id="mocha-report"]//li//h1//a[text()="${name}"]`;
        },
    },
    codeBlocks: '//ul[@id="mocha-report"]//li//ul//ul//pre'
};

export default selectors;
