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

var cluster = require('cluster');
var util = require('util');
var version = '0.1a';
var config = require('./config.js');
DatasetProcessor = require('./lib/dataset_processor.js');

if (cluster.isMaster) {
	var allProcessingDetails = DatasetProcessor.initBaseStats();
	var datasetName = undefined;
	//Count requestes
	function messageHandler(msg) {
		if (msg.processingDetails) {
			DatasetProcessor.addStats(allProcessingDetails, msg.processingDetails);
		}
	}

	// to run on multiple machines, need to pass in offset
	var localCores = require('os').cpus().length;
	var totalCores = localCores;
	var offset = 0;
	var logLevel = "warn";
	
	function printUsage() {
		console.log('XDMoD ETL Cluster ' + version + '\nUsage:\n' + util.inspect({
			'--offset=value': 'The offset core index to use',
			'--localCores=value': 'The number of cores on this machine to use',
			'--totalCores=value': 'The total cores among all machines',
			'--dataset=<dataset.name>': 'Process one dataset. dataset.name refers to datasets in etl.profile.js',
			'-v, --verbose': 'verbose output, same as log-level=info',
			'-d': 'debug output, same as log-level=debug',
			'-q': 'quiet output, same as log-level=error',
			'--log-level=<level>': 'set specific log level, default:warn'
		}));
		process.exit(1);
	}
	var optionparser = require('./lib/optionparser')({
	    shortflags: ['v', 'd', 'q', 'h', '\\?'],
	    shortopts: [],
	    longopts: ['offset', 'localCores', 'totalCores', 'dataset', 'log-level'],
	    longflags: ['help']
	});
	var argv = optionparser(process.argv.slice(2), printUsage);
	if ("offset" in argv) {
		if(argv.offset < 0) {
			console.log('offset must be non-negative');
			process.exit(1);
		}
		offset = argv['offset'];
	}  
	if ("localCores" in argv) {
		if(argv.localCores < 1) {
			console.log('localCores must be greater than 0');
			process.exit(1);
		} else if(argv.localCores > localCores) {
			console.log('localCores cannot be greater than cores on machine: ' + localCores);
			process.exit(1);
		} 
		//assume totalCores same as localCores (until we know better)
		localCores = totalCores = argv['localCores']; 
	} 
	if ("totalCores" in argv) {
		if(argv.totalCores < 1) {
			console.log('totalCores must be greater than 0');
			process.exit(1);
		} else if(argv.totalCores < localCores) {
			localCores = argv.totalCores;
			//console.log('totalCores cannot be less than localCores on machine: ' + localCores);
			//process.exit(1);
		} 
		totalCores = argv.totalCores;
		if ((localCores + offset) > totalCores) { 
			console.log('(localCores + offset) must be less than totalCores=' + totalCores + ', ' + (localCores + offset) + ' given.');
			process.exit(1);
		}
	}  
	if("dataset" in argv) {
		datasetName = argv.dataset;
	} 
	if ('verbose' in argv || 'v' in argv) {
		logLevel = "info";
	}
	if ('d' in argv) {
		logLevel = "debug";
	}
	if ('q' in argv) {
		logLevel = "error";
	}
	if ('log-level' in argv) {
		logLevel = argv['log-level'];
	}
	if ("?" in argv || "h" in argv || "help" in argv) { 
		printUsage();
	}
	
	var datasets = {};
	if(datasetName) {
		datasets[datasetName] = {cores: []};
	} else {
		config.profiles.forEach(function (etl_profile) {
			etl_profile.init();
			etl_profile.datasets.forEach(function (dataset) {
				if ( dataset.enabled ) {
					datasets[dataset.name] = {cores: []};
				}
			});
		});
	}
	var datasetNames = Object.keys(datasets);
	var datasetCount = datasetNames.length;
	if(totalCores > datasetCount) {
		for (var i = 0; i < totalCores; i++) {
			var datasetIndex = i % datasetCount;
			datasets[datasetNames[datasetIndex]].cores.push(i);
		}
	} else {
		for (var i = 0; i < datasetCount; i++) {
			var coreIndex = i % totalCores;
			datasets[datasetNames[i]].cores.push(coreIndex);
		}
	}

	var coreJobs = [];
	for (var i = 0; i < datasetCount; i++) {
		var datasetCoreCount = datasets[datasetNames[i]].cores.length;
		for(var c = 0; c < datasetCoreCount; c++) {
			var datasetName = datasetNames[i];
			var dataset = datasets[datasetName];
			var cores = dataset.cores[c];
			if(cores >= offset && cores < (offset + localCores)) {
				if(coreJobs[cores] === undefined) coreJobs[cores] = [];
				coreJobs[cores].push( {
					totalCores: datasetCoreCount, 
					coreIndex: c,
					datasetName: datasetName
				});
			}
		}
	}	

	for(var c = 0; c < coreJobs.length; c++) {
		if(coreJobs[c] === undefined) continue;
		cluster.fork({jobs: JSON.stringify(coreJobs[c]), logLevel: logLevel});
	}
	Object.keys(cluster.workers).forEach(function(id) {
    	cluster.workers[id].on('message', messageHandler);
 	});
    cluster.on('exit', function (worker, code, signal) {
		var allExited = true;
		Object.keys(cluster.workers).forEach(function(id) {
			allExited = allExited && cluster.workers[id].exitCode !== undefined;
		});
		if(allExited) {
			allProcessingDetails.rate = allProcessingDetails.rate.toFixed(2);
			if(logLevel == 'info' || logLevel == 'debug') {
				console.log('All Processing Details:\n' + util.inspect(allProcessingDetails));
			}
		}
    });
	
} else {
	var jobs = JSON.parse(process.env.jobs);
	var datasetNames = [];
	var totalCores = undefined;
	var coreIndex = undefined;
	for(var j = 0 ; j < jobs.length; j++) {
		var job = jobs[j];
		var datasetName = job.datasetName;
		job.totalCores = new Number(job.totalCores) + 0;
		job.coreIndex = new Number(job.coreIndex) + 0;
		if(j > 0 && (totalCores !== job.totalCores || coreIndex !== job.coreIndex)) {
			console.log('Multiple datasets on the same core may not have different totalCores or coreIndex');
			console.log(datasets);
			process.exit(1);
		}
		totalCores = job.totalCores;
		coreIndex = job.coreIndex;
		
		datasetNames.push(datasetName);
	}

	var ETL = require('./etl.js');
	var start_ts = Date.now();
	var etl = new ETL();
	etl.on('afterprocessall', function (processingDetails) {
		var end_ts = Date.now();
		var duration_ts = (end_ts - start_ts)/ 1000;
		etl.emit('message', util.format("ETL Worker %d of %d: afterprocessall. Duration: %d s", coreIndex + 1, totalCores, duration_ts));
		process.send({processingDetails: processingDetails});
		process.exit(1);
	});
	etl.processAll(totalCores, coreIndex, datasetNames);	
}
