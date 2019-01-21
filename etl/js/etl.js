/*node.js javascript document
 *
 * @authors: Amin Ghadersohi
 * @date: 2/4/2014
 * @requirements:
 *	node.js
 * 	node.js mongodb driver: npm install mongodb;
 *  node.js mysql: npm install mysql
 *  node.js cloneextend: npm install cloneextend
 *  node.js winston: npm install winston
 *  node.js winston-mysql-transport: npm install winston-mysql-transport
 *
 */
var version = "0.9beta",
	events = require('events'),
	config = require('./config.js'),
	ETLProfile = require('./lib/etl_profile.js'),
	util = require('util'),
	markProcessedRecords = true,
	DatasetProcessor = require('./lib/dataset_processor.js');

var winston = require('winston');

//ETL details

//1. (Extract) loop through data from mongo/folder (have option for both, or arbitrary), make sure the data wasnt processed by adding a processed tag to processed jobs and include the timestamp
//2. (Transform) for each document, if it passes a filter function ( which might be part of the query to mongo, but in case of files it could be something else), etl a trasform function
//3. extract function will use the mapping, in conjuction with the unified schema to get each field from the document and trasform it into the format specified by the schema.
//4. (Load) the results of the transform function, in the form of an object, will be passed to the load function for insertion into the destination database.
//   The load function will may need to use caching to optimize the insertions
//5. Aggregate the data into various aggregation units
//6*. Put necessary hooks in XDMoD
//*. This might call for making generic dim/metric classes.
//
var ETL = module.exports = function() {
	events.EventEmitter.call(this);

    const logFormat = winston.format.combine(
        winston.format.timestamp(),
        winston.format.printf(info => {
            return `${info.timestamp} - ${info.level}: ${info.message}`;
        })
    );

    const colourLogFormat = winston.format.combine(
        winston.format.colorize(),
        logFormat
    );

    var etlLogger = winston.createLogger({
        transports: [
            new winston.transports.Console({
                format: process.stdout.isTTY ? colourLogFormat : logFormat,
                level: process.env.logLevel || 'warn'
            })
        ]
    });

	this.on('message', function(msg) {
		etlLogger.info(msg);
	});
	this.on('error', function(err) {
		etlLogger.error(err);
	});
};

util.inherits(ETL, events.EventEmitter);

ETL.prototype.reportVersion = function() {
	console.log('XDMoD ETL ' + version);
};

ETL.prototype.getTableDocumentation = function () {
    config.profiles.forEach(function (etl_profile) {
        var etlProfile = new ETLProfile(etl_profile);
        etlProfile.getTableDocumentation();
    });
};

ETL.prototype.createOutputTables = function(outputmode) {
	var self = this;
	if (outputmode == 'sql') {
		self.emit('message', 'Creating output database(s)...');
	}
	config.profiles.forEach(function(etl_profile) {
		var etlProfile = new ETLProfile(etl_profile);
		if (outputmode == 'sql') {
			etlProfile.on('message', function(msg) {
				self.emit('message', msg);
			});
		}
		etlProfile.on('error', function(error) {
			self.emit('error', error);
		});
		etlProfile.createOutputTables(outputmode);
	});
}

ETL.prototype.runAggregation = function() {
	var self = this;
	self.emit('message', 'Aggregation...');
	config.profiles.forEach(function(etl_profile) {
		var etlProfile = new ETLProfile(etl_profile);
		etlProfile.on('message', function(msg) {
			self.emit('message', msg);
		});
		etlProfile.on('error', function(error) {
			self.emit('error', error);
		});
		etlProfile.aggregate();
	});
}

ETL.prototype.integrateWithXDMoD = function() {
	var self = this;
	self.emit('message', 'Integrating with XDMoD...');
	config.profiles.forEach(function(etl_profile) {
		var etlProfile = new ETLProfile(etl_profile);
		etlProfile.on('message', function(msg) {
			self.emit('message', msg);
		});
		etlProfile.on('error', function(error) {
			self.emit('error', error);
		});
		etlProfile.integrateWithXDMoD();
	});
}

ETL.prototype.runRegressionTests = function () {
    var self = this;
    self.emit('message', 'Regression Testing...');
    config.profiles.forEach(function (etl_profile) {
        var etlProfile = new ETLProfile(etl_profile);
        etlProfile.on('message', function (msg) {
            self.emit('message', msg);
        });
        etlProfile.on('error', function (error) {
            self.emit('error', error);
        });
        etlProfile.regressionTests();
    });
};

ETL.prototype.processAll = function(totalCores, coreIndex, datasetNames) {
	var self = this;
	config.profiles.forEach(function(etl_profile) {
		var etlProfile = new ETLProfile(etl_profile);
		etlProfile.on('message', function(msg) {
			self.emit('message', msg);
		});
		etlProfile.on('error', function(error) {
			self.emit('error', error);
		});
		etlProfile.on('afterprocessall', function(processingDetails) {
			self.emit('message', etlProfile.name + ' afterprocessall:\n' + util.inspect(processingDetails));
			etl_profile._processed = true;
			etl_profile._processingDetails = processingDetails;
			var allProcessed = true;
			for (var ds = 0; ds < config.profiles.length && allProcessed; ds++) {
				allProcessed = allProcessed && (config.profiles[ds]._processed === true);
			}
			if (allProcessed === true) {
				//combine all processing details and send as one
				var allProcessingDetails = DatasetProcessor.initBaseStats();
				for (var ds = 0; ds < config.profiles.length; ds++) {
					DatasetProcessor.addStats(allProcessingDetails, config.profiles[ds]._processingDetails);
				}
				self.emit('afterprocessall', allProcessingDetails);
			}

		});
		etlProfile.setup(function(err) {
			if(err) {
				self.emit('error', 'Error in etl profile setup ' + util.inspect(err));
				self.emit('afterprocessall', {error: util.inspect(err)});
				return;
			}
			if (datasetNames) {
				etlProfile.processDatasets(datasetNames, totalCores, coreIndex, markProcessedRecords);
			} else {
				etlProfile.processAllDatasets(totalCores, coreIndex, markProcessedRecords);
			}
		});
	});
}
