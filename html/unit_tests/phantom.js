var page = require('webpage').create();
page.open('file://' + phantom.libraryPath + '/index.html', function (status) {
    var failures = -1;
    if (status === 'success') {
        failures = page.evaluate(function () {
            return mocha.run().failures;
        });
    }
    console.log('Javascript Unit Test Failures: ' + failures);
    phantom.exit(failures);
});

