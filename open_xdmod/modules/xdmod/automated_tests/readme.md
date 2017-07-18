# Automated Regression tests for the XDMod Frontend

Based on [webdriver.io][wd] and [webdriverCSS][wdc]

## Requirements

### webdriver.io installed globally

```bash
npm -g install webdriverio
```

or if you want run it from node modules

```bash
npm install webdriverio
```

### webdriverCSS webdriverCSS will need [GraphicsMagick][gm]

For most up to date instructions follow these [instructions][wdc-reqs]
To install this GraphicsMagick

#### Mac OS X using [Homebrew](http://mxcl.github.io/homebrew/)

```sh
brew install graphicsmagick
```

#### Ubuntu using apt-get

```sh
sudo apt-get install graphicsmagick
```

#### Windows

Download and install executables for [GraphicsMagick][gmd].

### Selenium

To run this without the use of a selenium grid (not yet ready)
The recommended way to install selenium is using [selenium standalone][ss]

### Optional

[webdriverCSS Admin][wdc-admin] is used to more easily compare screenshots.
There are plans to make a shared instance of this for now it is run locally.

## Setup

```bash
npm install
```

### wdio.conf.js/wdio-sauce.conf.js

#### host

change the baseurl to point to the environment you want it pointed to.

```javascript
// Set a base URL in order to shorten url command calls. If your url parameter starts
// with "/", the base url gets prepended.
baseUrl: "https://tas-reference-dbs.ccr.xdmod.org",
```

#### browser Options

Change the browser to run the tests in by changing the browserName

```javascript
capabilities: [{
    //browserName: "firefox",
    browserName: "phantomjs",
    //This allows phantomjs to use self signed certs
    "phantomjs.cli.args": [
        "--ignore-ssl-errors=true",
        //"--ssl-protocol=tlsv1",
        "--web-security=false",
        "--debug=false"
    ]
}],
```
Choose/add another browser to `wdio-sauce.conf.js`, use the [Platform Configurator](https://wiki.saucelabs.com/display/DOCS/Platform+Configurator#/) and add specifications using the same syntax of the `chr` and `ff` browsers.  

In `mochaOpts`, set the `timeout` to desired time length.  

Set `maxInstances` to the desired number of concurrent tests to run at a time.  
*Note:* The XDMoD SauceLabs account allows for a max of 5 concurrent tests at a time.  

If running tests through SauceLabs, enter the correct `key` for the `xdmod-sauce` account.

#### template.secrets.json

```bash
cp template.secrets.json .secrets.json
```

modify .secrets.json to have correct username, password, and display name for user being tested.

## Run

### run tests locally

```bash
npm test
```

### run tests through sauce labs

```bash
npm run test-sauce
```

and see the fun that is automated ui testing.



[wd]: http://webdriver.io/
[wdc]: https://github.com/webdriverio/webdrivercss
[wdc-reqs]: https://github.com/webdriverio/webdrivercss#install
[ss]: https://github.com/vvo/selenium-standalone
[gm]: http://www.graphicsmagick.org/
[gmd]: http://www.graphicsmagick.org/download.html
[wdc-admin]: https://github.com/webdriverio/webdrivercss-adminpanel
