/**
 * If you want to add other platforms use the following:
 * https://wiki.saucelabs.com/display/DOCS/Platform+Configurator
 * put them into variable and then add them to the proper `capabilities`
 * variable
 */
const crypto = require('crypto');
const fs = require('fs');

const screenshotDir = '/tmp/screenshots';

var HeadlessChrome = {
    acceptInsecureCerts: true,
    browserName: 'chrome',
    chromeOptions: {
        args: [
            '--no-sandbox',
            '--headless',
            '--no-gpu',
            'incognito',
            'disable-extensions',
            'start-maximized',
            'window-size=2560,1600'
        ]
    }
};
var Chrome = {
    acceptInsecureCerts: true,
    browserName: 'chrome',
    version: '87.0',
    screenResolution: '2560x1600',
    chromeOptions: {
        args: [
            'incognito',
            'disable-extensions',
            'start-maximized',
            'window-size=2560,1600'
        ]
    }
};

var secrets = require('../ci/testing.json');
secrets.url = process.env.TEST_URL ? process.env.TEST_URL : secrets.url;
var excludes = [
    './test/**/*.page.js'
];
if (!process.env.SSO) {
    excludes.push('./test/specs/xdmod/SSOLogin.js');
}
var capabilities = [Chrome];
var services = ['selenium-standalone'];
var port = 4444;
var path = '/wd/hub';
var reporters = ['spec'];
var reporterOptions = {};

var user = '';
var key = '';

if (process.env.WDIO_MODE === 'headless') {
    capabilities = [HeadlessChrome];
    services = ['chromedriver'];
    port = 9515;
    path = '/';
} else if (process.env.SAUCE_USER && process.env.SAUCE_KEY) {
    user = process.env.SAUCE_USER;
    key = process.env.SAUCE_KEY;
    services = ['sauce'];
    capabilities = [Chrome];
}
if (process.env.JUNIT_OUTDIR) {
    reporters.push('junit');
    reporterOptions.junit = {
        outputDir: process.env.JUNIT_OUTDIR,
        outputFileFormat: {
            single: function (config) {
                if (process.env.SSO) {
                    return 'xdmod-ui-sso.xml';
                }
                return 'xdmod-ui.xml';
            }
        }
    };
}

exports.config = {
    deprecationWarnings: false,
    user: user,
    key: key,
    // ==================
    // Specify Test Files
    // ==================
    // Define which test specs should run. The pattern is relative to the directory
    // from which `wdio` was called. Notice that, if you are calling `wdio` from an
    // NPM script (see https://docs.npmjs.com/cli/run-script) then the current working
    // directory is where your package.json resides, so `wdio` will be called from there.
    //
    specs: [
        './test/specs/**/*.js',
        '../../open_xdmod/modules/*/tests/automated_tests/test/specs/*.js'
    ],

    // Patterns to exclude.
    exclude: excludes,
    //
    // ============
    // Capabilities
    // ============
    // Define your capabilities here. WebdriverIO can run multiple capabilties at the same
    // time. Depending on the number of capabilities, WebdriverIO launches several test
    // sessions. Within your capabilities you can overwrite the spec and exclude option in
    // order to group specific specs to a specific capability.
    //
    // If you have trouble getting all important capabilities together, check out the
    // Sauce Labs platform configurator - a great tool to configure your capabilities:
    // https://docs.saucelabs.com/reference/platforms-configurator
    //
    // **NOTE** if no browserName is specified webdriverio defaults to firefox
    // Webdriver.io has the ability to run multiple browsers at once
    // uncomment the different objects to enable each browser
    // uncomment multiple objects to enable multiple at the same time
    // Please bear in mind that if the tests are manipulating saved state on the server
    // then running multiple logins concurrently could cause problems.

    // Define how many instances should be started at the same time. Let's
    // say you have 3 different capabilities (Chrome, Firefox, and Safari)
    // and you have set maxInstances to 1, wdio will spawn 3 processes.
    // Therefore, if you have 10 spec files and you set maxInstances to 10;
    // all spec files will get tested at the same time and 30 processes will
    // get spawned. The property handles how many capabilities from the same
    // test should run tests.
    maxInstances: 1,

    capabilities: capabilities,
    //
    // ===================
    // Test Configurations
    // ===================
    // Define all options that are relevant for the WebdriverIO instance here
    //

    // By default WebdriverIO commands are executed in a synchronous way using
    // the wdio-sync package. If you still want to run your tests in an async way
    // e.g. using promises you can set the sync option to false.
    sync: true,
    // Level of logging verbosity.
    logLevel: 'silent',
    //
    // Enables colors for log output.
    coloredLogs: true,
    //
    // Saves a screenshot to a given path if a command fails.
    // Comment out to disable feature.
    // screenshotPath: './errorShots/',
    //
    // Set a base URL in order to shorten url command calls. If your url parameter starts
    // with '/', the base url gets prepended.
    baseUrl: secrets.url,
    //
    // Default timeout for all waitForXXX commands.
    waitforTimeout: 10000,

    port: port,

    path: path,

    //
    // Initialize the browser instance with a WebdriverIO plugin. The object should have the
    // plugin name as key and the desired plugin options as property. Make sure you have
    // the plugin installed before running any tests. The following plugins are currently
    // available:
    // WebdriverCSS: https://github.com/webdriverio/webdrivercss
    // WebdriverRTC: https://github.com/webdriverio/webdriverrtc
    // Browserevent: https://github.com/webdriverio/browserevent
    // plugins: {
    //     webdrivercss: {
    //         screenshotRoot: 'my-shots',
    //         failedComparisonsRoot: 'diffs',
    //         misMatchTolerance: 0.05,
    //         screenWidth: [320,480,640,1024]
    //     },
    //     webdriverrtc: {},
    //     browserevent: {}
    // },
    //
    services: services,
    // Framework you want to run your specs with.
    // The following are supported: mocha, jasmine and cucumber
    // see also: http://webdriver.io/guide/testrunner/frameworks.html
    //
    // Make sure you have the node package for the specific framework installed before running
    // any tests. If not please install the following package:
    // Mocha: `$ npm install mocha`
    // Jasmine: `$ npm install jasmine`
    // Cucumber: `$ npm install cucumber`
    framework: 'mocha',
    //
    // Test reporter for stdout.
    // The following are supported: dot (default), spec and xunit
    // see also: http://webdriver.io/guide/testrunner/reporters.html
    reporters: reporters,

    reporterOptions: reporterOptions,

    //
    // Options to be passed to Mocha.
    // See the full list at http://mochajs.org/
    mochaOpts: {
        ui: 'bdd',
        timeout: 100000,
        bail: true
    },
    //
    // =====
    // Hooks
    // =====
    // Run functions before or after the test. If one of them returns with a promise, WebdriverIO
    // will wait until that promise got resolved to continue.
    //
    // Gets executed before all workers get launched.
    onPrepare: function onPrepare() {
        // do something
    },
    //
    // Gets executed before test execution begins. At this point you will have access to all global
    // variables like `browser`. It is the perfect place to define custom commands.
    before: function before() {
        // eslint-disable-next-line global-require
        global.testHelpers = require('./test/helpers');
        // eslint-disable-next-line global-require
        var webdriverHelpers = require('./webdriverHelpers');
        for (var extension in webdriverHelpers) {
            if (webdriverHelpers.hasOwnProperty(extension) && typeof webdriverHelpers[extension] === 'function') {
                browser.addCommand(extension, webdriverHelpers[extension].bind(browser));
            } else {
                process.stderr.write('Not Adding: ' + extension + typeof webdriverHelpers[extension]);
            }
        }
        // eslint-disable-next-line global-require
        var chai = require('chai');
        // eslint-disable-next-line no-global-assign
        expect = chai.expect;
    },
    //
    // Gets executed after all tests are done. You still have access to all global variables from
    // the test.
    after: function after(/* failures, pid */) {
        // do something
    },

    /**
     * Get's executed after each test.
     */
    afterTest: function afterTest(test, context) {
        if (!test.passed) {
            let hash = crypto.createHash('md5').update(test.fullTitle).digest('hex');
            let filename = `${screenshotDir}/${hash}`;
            browser.saveScreenshot(`${filename}.png`);
            fs.writeFileSync(`${filename}.json`, JSON.stringify(test));
        }
    },
    //
    // Gets executed after all workers got shut down and the process is about to exit. It is not
    // possible to defer the end of the process using a promise.
    onComplete: function onComplete() {
        // do something
    }
};
