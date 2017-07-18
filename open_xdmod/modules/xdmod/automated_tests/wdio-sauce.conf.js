var ie = {browserName: 'internet explorer'};
ie['platform'] = 'Windows 10';
ie['version'] = '11.103';
ie['screenResolution'] = '1280x1024';

var ff = {browserName: 'firefox'};
ff['platform'] = 'Windows 10';
ff['version'] = '45.0';
ff['screenResolution'] = '2560x1600';

var chr = {browserName: 'chrome'};
chr['platform'] = 'Windows 10';
chr['version'] = '48.0';
chr['screenResolution'] = '2560x1600';

sf = {browserName: 'safari'};
sf['platform'] = 'macOS 10.12';
sf['version'] = '10.0';
sf['screenResolution'] = '1024x768';

exports.config = {
    // Sauce Labs Login, comment out user and key to run tests locally
    user: "xdmod-sauce",
    key: "XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX", // enter key before running
	//
	// ==================
	// Specify Test Files
	// ==================
	// Define which test specs should run. The pattern is relative to the directory
	// from which `wdio` was called. Notice that, if you are calling `wdio` from an
	// NPM script (see https://docs.npmjs.com/cli/run-script) then the current working
	// directory is where your package.json resides, so `wdio` will be called from there.
	//
	specs: ["./test/specs/**/*.js"],

	// Patterns to exclude.
	exclude: [
        /*
        "./test/specs/xdmod/about.js",
		"./test/specs/xdmod/jobViewer.js",
		"./test/specs/xdmod/mainToolbar.js",
		"./test/specs/xdmod/metricExplorer.js",
		"./test/specs/xdmod/reportGenerator.js",
		"./test/specs/xdmod/usageTab.js",
        */

        // pageObject files should always be excluded
        "./test/**/*.page.js"
	],
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
    // The XDMoD SauceLabs account has a limit of 5 concurrent sessions.
    maxInstances: 5,
    //maxInstances: 1,


    capabilities: [
		/*
        {
			browserName: "firefox"
		},
        */
		/*
		{
			browserName: "phantomjs",
			"phantomjs.cli.args": [
				"--ignore-ssl-errors=true",
				"--web-security=false",
				"--debug=false"
			]
		},
		*/
		/*
		{
			browserName: "chrome",
			"chromeOptions": {
				"args": [
					"incognito",
					"disable-extensions",
					"start-maximized",

					// For some reason start-maximized doesnt always work on mac
					// so lets default its size
					// for retina display the default is: window-size=2560,1600 but that is double what it should be...

					"window-size=1280,800"
				]
			}
		},
        */
        //ie,
        //sf,
        ff,
        chr
		
	],
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
    //sync: false,
	// Level of logging verbosity.
	logLevel: "silent",
	//
	// Enables colors for log output.
	coloredLogs: true,
	//
	// Saves a screenshot to a given path if a command fails.
    // Comment out to disable feature.
	//screenshotPath: "./errorShots/",
	//
	// Set a base URL in order to shorten url command calls. If your url parameter starts
	// with "/", the base url gets prepended.
	//baseUrl: "https://xdmod-dev.ccr.buffalo.edu",
	baseUrl: "https://tas-reference-dbs.ccr.xdmod.org",
	//
	// Default timeout for all waitForXXX commands.
	waitforTimeout: 10000,
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
	//         screenshotRoot: "my-shots",
	//         failedComparisonsRoot: "diffs",
	//         misMatchTolerance: 0.05,
	//         screenWidth: [320,480,640,1024]
	//     },
	//     webdriverrtc: {},
	//     browserevent: {}
	// },
	//
    services: ['sauce'],
	// Framework you want to run your specs with.
	// The following are supported: mocha, jasmine and cucumber
	// see also: http://webdriver.io/guide/testrunner/frameworks.html
	//
	// Make sure you have the node package for the specific framework installed before running
	// any tests. If not please install the following package:
	// Mocha: `$ npm install mocha`
	// Jasmine: `$ npm install jasmine`
	// Cucumber: `$ npm install cucumber`
	framework: "mocha",
	//
	// Test reporter for stdout.
	// The following are supported: dot (default), spec and xunit
	// see also: http://webdriver.io/guide/testrunner/reporters.html
	reporter: "spec",
	//
	// Options to be passed to Mocha.
	// See the full list at http://mochajs.org/
	mochaOpts: {
		ui: "bdd",
        timeout: 100000
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
		global.testHelpers = require("./test/helpers");
		var webdriverHelpers = require("./webdriverHelpers");
		for(var extension in webdriverHelpers){
			if(webdriverHelpers.hasOwnProperty(extension) && typeof webdriverHelpers[extension] === "function"){
				browser.addCommand(extension, webdriverHelpers[extension].bind(browser));
			}
			else {
				console.warning("Not Adding: ", extension, typeof webdriverHelpers[extension]);
			}
		}
        /*
		require("webdrivercss")
			.init(browser, {
				screenshotRoot: "xdmod",
				// this is used to upload screenshots to a shared repository
				api: "https://screenshots.ben.dev/api/repositories/",
				misMatchTolerance: 0.05
			});*/
		/* jshint ignore:start */
		var chai = require("chai");
		expect = chai.expect;
		/* jshint ignore:end */
		//browser.sync();
		/*
		 *	Comment this out to make the window not maximize.
		 */
		//browser.windowHandleMaximize();
	},
	//
	// Gets executed after all tests are done. You still have access to all global variables from
	// the test.
	after: function after( /*failures, pid*/ ) {
		// do something
	},
	//
	// Gets executed after all workers got shut down and the process is about to exit. It is not
	// possible to defer the end of the process using a promise.
	onComplete: function onComplete() {
		// do something
	}
};
