try {
    var system = require('system');
    var args = system.args;
} catch(err) {
    // Phantomjs version 1.4.0 does not support the 'system' module
    // cmdline args are accessed using the phantom global variable
    var args = [""].concat(phantom.args);
}

if (args.length != 6) {
   console.log('Usage: generate_highchart.js [png|svg] template filename width height');
   phantom.exit(1);
}

var output_format = args[1];
var address = args[2];
var output = args[3];
var width = args[4];
var height = args[5];

var page = new WebPage();

page.viewportSize = { width: width, height: height };
page.clipRect = { top: 0, left: 0, width: width, height: height };

page.open(address, function (status) {
   if (status !== 'success') {
      console.log('Unable to load the address, status: ' + status);
      phantom.exit(2);
      return;
   }

   if (output_format === 'png') {
      page.render(output);
      phantom.exit(0);
      return;
   }

   if (output_format === 'svg') {
      console.log(page.evaluate(function () {
         return chart.getSVG();
      }));

      phantom.exit(0);
      return;
   }

   console.log('Unknown format specified: ' + output_format);
   phantom.exit(3);
});//page.open(...

