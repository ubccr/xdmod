# Automated Regression tests for the XDMoD Frontend

Based on [webdriver.io][wd]

## Setup

```bash
npm install
```

This will not work with a "clean" reference database yet.

You will need to perform the following steps

1. Login as the user you want the tests to run as
2. Goto the Metric Explorer
3. Add Data

  1. Jobs
  2. CPU Hours: Total
  3. Group By None (if using Metric Catalog)

4. Save

5. Save Changes

6. Check Available For Report

7. Switch to the Report Generator Tab

8. Click new

9. Drag untitled query 1 from the Available Charts to Included Charts
10. Click Save

### wdio.conf.js/wdio-sauce.conf.js

#### host

Change the baseUrl to point to the environment you want it pointed to.

By default this uses the "reference" database set up on <https://tas-reference-dbs.ccr.xdmod.org>.

```javascript
  // Set a base URL in order to shorten url command calls. If your url parameter starts
  // with "/", the base url gets prepended.
  baseUrl: "https://tas-reference-dbs.ccr.xdmod.org",
```

#### browser Options

Change the browser to run the tests in by changing the list of browsers in the capabilities array.

```
InternetExplorer
FireFox
Chrome
Safari
PhantomJS
```

By default the tests run only in Chrome.

```javascript
capabilities: [
    Chrome
],
```

##### SauceLabs

Tests will automatically use [SauceLabs][sl] if you have SAUCE_USER and SAUCE_KEY environment variables set.

To choose/add another browser to `wdio.conf.js`, use the [Platform Configurator][sl-conf] and add specifications using the same syntax of the `Chrome` and `FireFox` browsers. and then update the capabilities array in the check for the SAUCE environment variables.

#### Other Options

In `mochaOpts`, set the `timeout` to desired time length.

Set `maxInstances` to the desired number of concurrent tests to run at a time.

_Note:_ The XDMoD SauceLabs account allows for a max of 5 concurrent tests at a time.

If running tests through SauceLabs, enter the correct `key` for the `xdmod-sauce` account.

#### testing.json

The user names and passwords are read from a file called testing.json in the ci directory under testing.

## Run

### run tests locally

```bash
npm test
```

### run tests through sauce labs

```bash
SAUCE_USER=sauce-user SAUCE_KEY=X-X-X npm run test
```

and see the fun that is automated ui testing.

[sl]: https://saucelabs.com/
[sl-conf]: https://wiki.saucelabs.com/display/DOCS/Platform+Configurator#/
[wd]: http://webdriver.io/
