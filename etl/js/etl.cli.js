#!/usr/bin/env node
/*node.js javascript document
 *
 * @authors: Amin Ghadersohi
 * @date: 3/4/2014
 * @requirements:
 *	node.js
 * 	node.js mongodb driver: npm install mongodb;
 *  node.js mysql: npm install mysql
 *
 */

var ETL = require('./etl.js');
var optionparser = require('./lib/optionparser.js')({
    shortflags: ['v', 'd', 'q', 'a', 'c', 'o', 'e', 't', 'i', 'h'],
    shortopts: [],
    longflags: ['verbose', 'version'],
    longopts: ['dataset', 'log-level']
});
var argv = optionparser(process.argv.slice(2));

process.env.logLevel = "warn";
if('verbose' in argv || 'v' in argv){
	process.env.logLevel = "info";
}
if('d' in argv){
	process.env.logLevel = "debug";
}
if('q' in argv){
	process.env.logLevel = "error";
}
if('log-level' in argv){
	process.env.logLevel = argv['log-level'];
}

var etl = new ETL();
function printUsage() {
	console.log('\nUsage:\n' + require('util').inspect({
		'-a': 'Aggregate data',
		'-c': 'Create dynamic output tables as per etl schema',
		'-o': 'Create sql script to generate dynamic output tables',
		'-e': 'ETL (Extract Transform Load) data (unaggregated)',
		'--dataset=<dataset.name>': 'Process one dataset. dataset.name refers to datasets in etl.profile.js [use in conjunction with -e]',
		'-t': 'Regression tests',
		'-i': 'Integrate with XDMoD',
		'--version': 'Output version information',
		'-v, --verbose': 'verbose output, same as log-level=info',
		'-d': 'debug output, same as log-level=debug',
		'-q': 'quiet output, same as log-level=error',
		'--log-level=<level>': 'set specific log level, default:warn'
	}));
}
if ("a" in argv) {
	etl.runAggregation();
} else if ("t" in argv) {
	etl.runRegressionTests();
} else if ("c" in argv) {
	etl.createOutputTables("sql");
} else if ("o" in argv) {
	etl.createOutputTables("stdout");
} else if ("i" in argv) {
	etl.integrateWithXDMoD();
} else if ("e" in argv) {
	if("dataset" in argv) {
		etl.processAll(undefined, undefined, [argv.dataset]);
	} else {
		etl.processAll();
	}
} else if ("version" in argv) {
    etl.reportVersion();
} else {
	printUsage();
}
