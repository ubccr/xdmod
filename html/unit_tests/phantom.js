/* eslint no-console: "off" */
var page = require('webpage').create();

page.onError = function (msg, trace) {
    var msgStack = ['PHANTOM ERROR: ' + msg];
    if (trace && trace.length) {
        msgStack.push('TRACE:');
        trace.forEach(function (t) {
            msgStack.push(' -> ' + (t.file || t.sourceURL) + ': ' + t.line + (t.function ? ' (in function ' + t.function + ')' : ''));
        });
    }
    console.log(msgStack.join('\n'));
    phantom.exit(1);
};

page.onConsoleMessage = function (msg) {
    console.log(msg);
};

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

