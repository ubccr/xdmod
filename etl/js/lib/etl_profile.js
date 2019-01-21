/*node.js javascript document
 *
 * @authors: Amin Ghadersohi
 * @date: 2/20/2014
 *
 * Class to encapsulate functionality related to an etl profile.
 * A etl profile maps multiple datasets to a single dataset
 *
 * @requirements:
 *	node.js
 *
 */ 
var events = require('events'),
    util = require('util'),
    Schema = require('./schema.js'),
    DynamicTable = require('./mysql.js').DynamicTable,
	queryFormat = require('./mysql.js').queryFormat,
	sqlType = require('./mysql.js').sqlType,
    DatasetMap = require('./dataset_map.js'),
    mysql = require('mysql'),
    DatasetProcessor = require('./dataset_processor.js'),
	ce = require('cloneextend'),
	config = require('../config.js');
var etlv2 = require('./etlv2.js');
var fs = require('fs');

var ETLProfile = module.exports = function (etlProfile) {
    etlProfile.init();

    // Remove the disabled datasets to avoid having to do any processing on them
    for (var i = 0; i < etlProfile.datasets.length; ++i) {
        if (etlProfile.datasets[i].enabled === false) {
            etlProfile.datasets.splice(i--, 1);
        }
    }

    util._extend(this, etlProfile);
    this.schema = new Schema(this.schema);
}
util.inherits(ETLProfile, events.EventEmitter);

var arrayUnique = function(array, comparisonfn) {
    var a = array.concat();
    for(var i=0; i<a.length; ++i) {
        for(var j=i+1; j<a.length; ++j) {
            if(0 === comparisonfn(a[i],a[j]) )
                a.splice(j--, 1);
        }
    }

    return a;
};

var CamelCase = function(input, insertSpaces) { 
    var tmp = input.charAt(0).toUpperCase() + input.slice(1);
    return tmp.replace(/_(.)/g, function(match, group) {
        if(insertSpaces) {
            return " " + group.toUpperCase();
        } else {
            return group.toUpperCase();
        }
    });
}

/*
 * Try to turn the db column name into a human friendly one
 */
var Namealize = function(input) {
    var endsWith = function(str, suffix) {
        return str.indexOf(suffix, str.length - suffix.length) !== -1;
    };

    if( endsWith(input, "_id") && !endsWith(input, "job_id") ) {
        // Strip id
        input = input.substring(0, input.length-3);
    }
    return CamelCase(input, true);
}

var camelCase = function(input, insertSpaces) { 
    return input.replace(/_(.)/g, function(match, group) {
        if(insertSpaces) {
            return " " + group.toUpperCase();
        } else {
            return group.toUpperCase();
        }
    });
}

/* Create a new string with the first letter capitalized
 */
var wordToUpper = function(str) {
    if (typeof str !== "string") {
        return str;
    }
    if (str.length < 1) {
        return str;
    }
    return str.charAt(0).toUpperCase() + str.slice(1);
};

ETLProfile.prototype.processAllDatasets = function (totalCores, coreIndex, markProcessedRecords) {
    var delay = 0;
    this.datasets.forEach(function (dataset) {
		setTimeout(function(){
			this.emit('message', 'ETLProfile: processAllDatasets: ' + dataset.name + '[totalCores: '+totalCores+', coreIndex: ' + coreIndex + ']' );
        	this.processDataset(dataset, totalCores, coreIndex, markProcessedRecords);
		}.bind(this), delay+= (Math.random() * 1000));
    }.bind(this));
}
ETLProfile.prototype.processDatasets = function (datasetNames, totalCores, coreIndex, markProcessedRecords) {
	this.emit('message', 'ETLProfile: processDatasets: ' + datasetNames.join(',') + '[totalCores: '+totalCores+', coreIndex: ' + coreIndex + ']' );
	this.datasets.forEach(function (dataset) {

		if(datasetNames.indexOf(dataset.name) > -1 ) {
			this.processDataset(dataset, totalCores, coreIndex, markProcessedRecords);
		} else {
			dataset._processed = true;
			dataset._processingDetails = {};
		}
    }.bind(this));
	var allProcessed = true;
	for(var ds = 0; ds < this.datasets.length && allProcessed; ds++) {
		allProcessed = allProcessed && (this.datasets[ds]._processed === true);
	}
	if(allProcessed) {
		this.emit('afterprocessall', {});
	}
}
ETLProfile.prototype.processDataset = function (dataset, totalCores, coreIndex, markProcessedRecords) {
    this.emit('message', 'ETLProfile: processDataset: ' + dataset.name + '[totalCores: ' + totalCores + ', coreIndex: ' + coreIndex + ']');
    var self = this;
    var datasetProcessor;
    try {
        datasetProcessor = new DatasetProcessor(this, dataset, markProcessedRecords);
    } catch (ex) {
        self.emit('error', ex);
        return;
    }
    datasetProcessor.on('message', function (msg) {
        self.emit('message', msg);
    });
    datasetProcessor.on('error', function (error) {
        self.emit('error', error);
    });
    datasetProcessor.on('afterprocess', function (processingDetails, etlLog) {
        self.emit('message', dataset.name + ': afterprocess: \n' + util.inspect(processingDetails));

        self.emit('message', 'Loggin ETL run');
        var etlLogHeader = {
            etlProfileName: 'etlProfile.name',
            etlProfileVersion: 'etlProfile.version',
            dataset: 'etlProfile.dataset[i].name',
            start_ts: 'etl start time',
            end_ts: 'etl end time',
            min_index: 'min record etld',
            max_index: 'max record etld',
            processed: 'number of docs processed',
            good: 'number of processed that were good',
            details: 'any extras'
        };

        for (var key in etlLog) {
            if (!(key in etlLogHeader)) {
                throw Error('The etlLog must conform to the etlLogHeader. key: ' + key + '\netLogHeader: ' + util.inspect(etlLogHeader) + '\nGiven etlLog: ' + util.inspect(etlLog));
            }
        }

        var etlLogKeys = Object.keys(etlLog);
        var mysqlConfig = util._extend({
            multipleStatements: true
        }, config.etlLogging.config);
        var mysqlConnection = mysql.createConnection(mysqlConfig);
        var insertLog = 'insert into ' + config.etlLogging.config.database + '.log (' + etlLogKeys.join(',') + ') values (:' + etlLogKeys.join(',:') + ')';
        insertLog = queryFormat(insertLog, etlLog);

        mysqlConnection.query(insertLog, function (err, result) {
            mysqlConnection.end();
            if (err) {
                self.emit('error', err);
                return;
            }

            dataset._processed = true;
            dataset._processingDetails = processingDetails;

            var allProcessed = true;
            for (let ds = 0; ds < self.datasets.length && allProcessed; ds++) {
                allProcessed = allProcessed && (self.datasets[ds]._processed === true);
            }
            if (allProcessed === true) {
                // combine all processing details and send as one
                var allProcessingDetails = DatasetProcessor.initBaseStats();
                for (let ds = 0; ds < self.datasets.length; ds++) {
                    DatasetProcessor.addStats(allProcessingDetails, self.datasets[ds]._processingDetails);
                }
                self.emit('afterprocessall', allProcessingDetails);
            }
        });
    });
    datasetProcessor.process(totalCores, coreIndex);
};

/*
* @returns the dynamic tables for the etl profile. 
*/
ETLProfile.prototype.getTables = function () {
    var unionFields = util._extend({}, this.schema.derivedFields),
        tables = {};
    for (var iit in this.datasets) {
        var dataset = this.datasets[iit];
        util._extend(unionFields, dataset.mapping.attributes);
    }
    for (var field in unionFields) {
        var f = unionFields[field] = this.schema.getField(field);
        if (f === undefined || f === null) {
            f = unionFields[field] = this.schema.getDerivedField(field);
        }
        if (f !== undefined && f !== null && f.table !== undefined && f.table !== null && this.schema.tables[f.table].definition === 'dynamic') {
            if (tables[f.table] === undefined) {
                tables[f.table] = new DynamicTable({
                    name: f.table,
                    meta: this.schema.tables[f.table],
                    columns: {}
                });
            }
            tables[f.table].columns[field] = f;
        }
    }

    for(var t in tables) {
        for(var c in tables[t].columns) {
            tables[t].columns[c].comments = extractandsubst(tables[t].columns[c], 'comments');
        }
    }

    return tables;
}

/**
 * Write the documentation for the autogenerated tables as an HTML page.
 *
 */
ETLProfile.prototype.getTableDocumentation = function () {
    var tableDefToHtml = function (caption, defn) {
        var output = '<table><caption>' + caption + '</caption><thead><tr><th>Column Name</th><th>Description</th><th>Units</th><th>Per</th><th>Type</th></tr></thead><tbody>\n';
        for (var i = 0; i < defn.length; i++) {
            output += '<tr><td>' + defn[i][1] + '</td><td>' + defn[i][2] + '</td><td>' + defn[i][3] + '</td><td>' + defn[i][4] + '</td><td>' + defn[i][0] + '</td></tr>\n';
        }
        output += '</tbody></table>';
        return output;
    };

    var htmltemplate = fs.readFileSync('./templates/table_documentation.html.template', 'utf8');
    var htmlout;

    var tables = this.getTables();
    for (var t in tables) {
        if (tables.hasOwnProperty(t)) {
            htmlout = htmltemplate.replace('__TABLE__', tableDefToHtml('Documentation for table `' + this.output.config.database + '`.`' + t + '`', tables[t].getTableDocumentation()));

            process.stdout.write(htmlout);
        }
    }
};

/*
* Creates the dynamic tables for the etl profile. 
*/
ETLProfile.prototype.createOutputTables = function (outputmode) {
    var self = this;
    try {
        if (this.output.dbEngine === 'mysqldb') {
			//todo: handle already existing table case. use alter table
			//detect removed/modified column(s) and their type. one way to
			//detect renamed columns is to look for old name in comment 
			//(ie. comment 'old_name:comment_text'). This could be documented
			//convention. can get table structure before doing the query, but
			//after the list of fields get generated inside the table class
			
			//todo: create a table for metric errors 
			//todo: create a table for logging etl runs
			//todo: create a table for etl profiles
			//todo: create a table to hold a list of all datasets across all etl profiles
            var poolConnectionMax = 2;

            var allStatements = [];
            // create/populate dimension tables
            for (var t in this.schema.dimension_tables) {
                var table_defn = this.schema.dimension_tables[t];
                if(table_defn.definition != "static") {
                    // Future work - add support for dynamic dimension tables.
                    self.emit('error', 'non-static dimension tables are not supported in this software version.');
                    return;
                }
                if(table_defn.import_stmt) {
                    allStatements.push(table_defn.import_stmt());
                }
            }
			var allStatements = allStatements.join(';\n');
			var mysqlConfig = util._extend({
				multipleStatements: true,
                connectionLimit: poolConnectionMax
			}, this.output.config); 
            switch( outputmode ) {
                case 'sql':
                    var mysqlConnection = mysql.createConnection(mysqlConfig);
                    mysqlConnection.query(allStatements, function (err, result) {
                        mysqlConnection.end();
                        if (err) {
                            self.emit('error', err + ': ' + allStatements);
                            return;
                        }
                    });
                    break;
                case 'stdout':
                    console.log(allStatements);
                    break;
                default:
                    self.emit('error', 'Unsupported output mode ' + outputmode);
            }
        } else {
            //other db engines not yet supported. , this class can be specialized 
			//or modularized at that time. 
			this.emit('error', this.output.dbEngine + ' is an unsupported dbEngine: ' 
								+ util.inspect(this.output) );
        }
    } catch (exception) {
        self.emit('error', exception.stack);
    }
}

/*
* @returns the dynamic aggregation tables for the etl profile. 
*/
ETLProfile.prototype.getAggregationTables = function () {
    var unionFields = util._extend({}, this.schema.derivedFields),
        tables = {};
    for (var iit in this.datasets) {
        var dataset = this.datasets[iit];
        util._extend(unionFields, dataset.mapping.attributes);
    }
	
	function addAggregationField(agg, f, field){
		if(agg.table !== undefined && agg.table !== null) { 
			if (tables[agg.table] === undefined) {
				tables[agg.table] = new DynamicTable({
					name: agg.table,
					schema: this.schema.aggregationTables[agg.table].schema,
					meta: this.schema.aggregationTables[agg.table],
					columns: {}
				});
			}
			var newf = {
				name: agg.name || field,
                alias: agg.alias,
				type: agg.type || f.type,
                roles: agg.roles || f.roles || null,
				length: agg.length || f.length || 50,
				comments: agg.comments || f.comments,
				dynamictags: agg.dynamictags || f.dynamictags || [],
				nullable: agg.nullable || f.nullable,
				stats: agg.stats || f.stats || [],
                label: agg.label,
                dimension_table: agg.dimension_table,
                category: agg.category,
				def: agg.def || f.def,
				dimension: agg.dimension || false
			};
            newf.name = newf.name.replace(/\:field_name/g, field);
			newf.sql = agg.sql || newf.name;
			newf.sql = newf.sql.replace(/\:field_name/g, field);
			newf.sqlType = sqlType(newf.type, newf.length);
			tables[agg.table].columns[newf.name] = newf;
		} else {
			throw Error('ETLProfile.prototype.getAggregationTables: addAggregationField(agg, f, field): agg.table is required: agg: ' + util.inspect(agg));
		}
	}
	
    for (var field in unionFields) {
        var f = unionFields[field] = this.schema.getField(field);
        if (f === undefined || f === null) {
            f = unionFields[field] = this.schema.getDerivedField(field);
        }
		
		if(f.agg !== undefined && f.agg !== null) {
			if(f.agg instanceof Array){
				for(var i in f.agg) {
					addAggregationField.call(this, f.agg[i], f, field);
				}
			} else {
				addAggregationField.call(this, f.agg, f, field);
			}
		}
    }
	for (var t in tables) {
		var table = tables[t];
		if(this.schema.aggregationTables[t] !== undefined) {
			for( var f in this.schema.aggregationTables[t].fields) {
				var field = this.schema.aggregationTables[t].fields[f];
				field.name = f;
				field.nullable = true;
				field.def = null;
				field.sqlType = sqlType(field.type, field.length);
				table.columns[f] = field;
				
			}
		}
	}
	
    return tables;
}

/*
* Creates and updates an aggregated table per dynamic tables in the etl profile. 
*/
ETLProfile.prototype.aggregate = function () {
    var self = this;
	try { 
        if (this.output.dbEngine === 'mysqldb') {
            etlv2.generateDefinitionFiles(this, config.xdmodBuildConfigDir);
        } else {
            //other db engines not yet supported. , this class can be specialized 
			//or modularized at that time. 
			this.emit('error', this.output.dbEngine + ' is an unsupported dbEngine: ' 
								+ util.inspect(this.output) );
        }
    } catch (exception) {
        self.emit('error', util.inspect(exception));
    }
}

var xdmodIntegrator = function(realmName, realmConfigRoot) {
    var self = this;
    var roles = [];
    var realms = { "+realms": {} };
    realms["+realms"][realmName] = { "schema": "N/A", "table": "N/A", "datasource": realmName, group_bys: [], statistics: [] };
    var realmConfig = realms["+realms"][realmName];

    var aggFolder = config.xdmodBuildRoot + '/classes/DataWarehouse/Query/' + realmName + '/GroupBys';
    var statFolder = config.xdmodBuildRoot + '/classes/DataWarehouse/Query/' + realmName + '/Statistics';
    this.addStatistic = function(name, className, classSrc) {
        realmConfig.statistics.push({
            name: name,
            class: className
        });
        fs.writeFileSync(statFolder + '/' + className + '.php', classSrc);	
    };

    this.addGroupBy = function(name, className, classSrc, roleAccessConfig) {
        if( ! classSrc ) {
            var sourceFile = realmConfigRoot + '/output_db/Query/' + realmName + '/GroupBys/' + className + '.php';
            classSrc = fs.readFileSync(sourceFile, 'utf-8');
        }

        realmConfig.group_bys.push({
            name: name,
            class: className
        });

        if( ! name.match(/^(day|month|quarter|year)$/) ) {
            roles.push( { realm: realmName, group_by: name, config: roleAccessConfig } );
        }

        fs.writeFileSync(aggFolder + '/' + className + '.php', classSrc);	
    };

    this.namecomparison = function(keyname) {
        return function(a,b){
            return a[keyname].localeCompare(b[keyname]);
        };
    };

    this.groupbysorter = function(keyname) {
        var namecomp = self.namecomparison(keyname);
        return function(a,b){
            if( a[keyname] == "none" && b[keyname] == "none") {
                return 0;
            } else if ( a[keyname] == "none" ) {
                return -1;
            } else if ( b[keyname] == "none" ) {
                return 1;
            } else {
                return namecomp(a,b);
            }
        }
    };

    this.mkdirandwrite = function(dirname, filename, data) {
 
        try {
            fs.mkdirSync(dirname);
        } catch (exception) {
            // Ignore error if directory exists already
            if(exception.code != "EEXIST") {
                throw exception;
            }
        }
        fs.writeFileSync( dirname + "/" + filename + ".json", JSON.stringify(data, null, 4));
    };

    this.getQueryDescriptors = function(role) {
        var qdescs = [];
        for(var i = 0; i < roles.length; i++) {

            var qdesc = {
                realm: roles[i].realm,
                group_by: roles[i].group_by
            };

            if( roles[i].config  && roles[i].config.disable &&
                    (roles[i].config.disable.indexOf(role) >= 0) )
            {
                qdesc['disable'] = true;
            }

            qdescs.push(qdesc);
        }
        return qdescs;
    };

    this.write = function() {

        // Sort realm configuration data and output

        realmConfig.group_bys.sort( self.groupbysorter("name") );
        realmConfig.group_bys = arrayUnique(realmConfig.group_bys, self.namecomparison("name"));

        realmConfig.statistics.sort(self.namecomparison("name"));
        realmConfig.statistics = arrayUnique(realmConfig.statistics, self.namecomparison("name"));

        self.mkdirandwrite(config.xdmodBuildConfigDir + '/datawarehouse.d', realmName.toLowerCase(), realms);

        // Sort role configuration data and output

        roles.sort(self.groupbysorter("group_by"));

        var topLevelRoleFile = config.xdmodConfigDir + '/roles.json';
        var roleConfig = JSON.parse(fs.readFileSync(topLevelRoleFile, 'ascii'));

        var roleout = { "+roles": {}};

        for(var r in roleConfig.roles) {
            if(roleConfig.roles[r].query_descripters /* sic */) {
                roleout["+roles"]["+" + r] = {"+query_descripters": self.getQueryDescriptors(r) };
            }
        }

        self.mkdirandwrite(config.xdmodBuildConfigDir + '/roles.d', realmName.toLowerCase(), roleout);
    };

};

var extractandsubst = function(column, item) {
    if( !column.hasOwnProperty(item) ) {
        return null;
    }
    var result = "" + config.parseuri(column[item]);

    if(result == column[item]) {
        // i.e. no configuration file substitution was performed
        for(var tagidx in column.dynamictags) {
            result = result.replace(new RegExp(":label_"+tagidx, "g"), column.dynamictags[tagidx]);
            result = result.replace(new RegExp(":Label_" + tagidx, "g"), wordToUpper(column.dynamictags[tagidx]));
        }
    }
    return result;
};

var generateGroupBy = function(aggTemplate, itemAlias, className, column)
{
    var aggCode = aggTemplate.replace(/_NAME_/g , itemAlias);
    aggCode = aggCode.replace(/_AGGREGATE_COLUMN_/g , column.name);
    aggCode = aggCode.replace(/_GROUPBY_CLASS_/g, className);
    aggCode = aggCode.replace(/_DIMENSION_TABLE_/g, column.dimension_table);
    aggCode = aggCode.replace(/_INFO_/g, column.comments);
    aggCode = aggCode.replace(/_LABEL_/g, column.label || itemAlias);
    aggCode = aggCode.replace(/_CATEGORY_/g, column.category || 'unknown');
    aggCode = aggCode.replace(/\:field_name/g, column.name);
    for( var tagidx in column.dynamictags ) {
        aggCode = aggCode.replace( new RegExp(":label_"+tagidx, "g"), column.dynamictags[tagidx] );
        aggCode = aggCode.replace(new RegExp(":Label_" + tagidx, "g"), wordToUpper(column.dynamictags[tagidx]));
    }
    return aggCode;
}

var writeRealmMetadata = function (realm, profileVersion) {
    fs.writeFileSync(
        config.xdmodBuildConfigDir + '/' + realm.toLocaleLowerCase() + 'config.json',
        JSON.stringify({
            etlversion: profileVersion
        }, null, 4)
    );
};

ETLProfile.prototype.integrateWithXDMoD = function () {
    var self = this;
	var escape = require('mysql').escape;
	try { 
        var roles = [];
		
        var tables = this.getAggregationTables();

		for (var t in tables) {

			var table = tables[t];
			var realmName = table.meta.realmName;

    writeRealmMetadata(realmName, this.version);

			var statTemplate = fs.readFileSync(this.root + '/output_db/Query/' + realmName + '/Statistics/template.stat.php', 'utf8');
			var aggTemplate = fs.readFileSync(this.root + '/output_db/Query/' + realmName + '/GroupBys/template.groupby.php', 'utf8');

            var xdmodInteg = new xdmodIntegrator(realmName, this.root);

            xdmodInteg.addGroupBy( "none", "GroupByNone", null, this.schema.groupByNoneRoleConfig );
            xdmodInteg.addGroupBy( "day", "GroupByDay", null );
            xdmodInteg.addGroupBy( "month", "GroupByMonth", null );
            xdmodInteg.addGroupBy( "quarter", "GroupByQuarter", null );
            xdmodInteg.addGroupBy( "year", "GroupByYear", null );
            
			self.emit('message', 'Processing table: ' + table.schema + '.' + table.name);

			var tableColumns = table.getAggregationTableFields();

			for(var tc in tableColumns) {

                if(tableColumns[tc].label) {

                    var itemAlias = tableColumns[tc].alias || tableColumns[tc].name;
                    var className = camelCase("GroupBy_" + tableColumns[tc].name);

                    var aggCode = generateGroupBy(aggTemplate, itemAlias, className, tableColumns[tc]);

                    xdmodInteg.addGroupBy(itemAlias, className, aggCode, tableColumns[tc].roles);

                } else if (tableColumns[tc].dimension ) {

                    var items = [ tableColumns[tc].alias || tableColumns[tc].name.replace(/_id$/, '') ];
                    if ( Array.isArray(items[0]) ) {
                        items = items[0];
                    }
                    for(var i = 0; i < items.length; i++) {
                        var itemName = items[i];
                        var className = camelCase("GroupBy_" + itemName);

                        var aggCode = null;
                        if(tableColumns[tc].dimension_table) {
                            tableColumns[tc].label = CamelCase(itemName);
                            aggCode = generateGroupBy(aggTemplate, itemName, className, tableColumns[tc]);
                        }

                        xdmodInteg.addGroupBy(itemName, className, aggCode, tableColumns[tc].roles);
                    }
                }

				for(var st in tableColumns[tc].stats) {
					var statCode = statTemplate + '';
					var statsname = tableColumns[tc].stats[st].name;
					if(statsname === undefined) {
						statsname = tableColumns[tc].name;
					} else {
						statsname = statsname.replace(":field_name", tableColumns[tc].name);
					}
					var className = statsname + '_Statistic';

					var label = tableColumns[tc].stats[st].label.replace(":field_name", tableColumns[tc].name);
                    var description = tableColumns[tc].stats[st].description.replace(":field_name", tableColumns[tc].name);
					for( var tagidx in tableColumns[tc].dynamictags ) {
						label = label.replace( ":label_"+tagidx, tableColumns[tc].dynamictags[tagidx] );
						label = label.replace(":Label_" + tagidx, wordToUpper(tableColumns[tc].dynamictags[tagidx]));
                        description = description.replace( ":label_"+tagidx, tableColumns[tc].dynamictags[tagidx] );
                        description = description.replace(":Label_" + tagidx, wordToUpper(tableColumns[tc].dynamictags[tagidx]));
					}

					statCode = statCode.replace('_STAT_CLASS_', className);
					statCode = statCode.replace('_FORMULA_', escape( tableColumns[tc].stats[st].sql.replace(/\:field_name/g, tableColumns[tc].name)));
					statCode = statCode.replace('_NAME_', escape(statsname.replace(":field_name", tableColumns[tc].name)))
					statCode = statCode.replace('_LABEL_', escape(label) );
					statCode = statCode.replace('_UNIT_', escape(tableColumns[tc].stats[st].unit))
					statCode = statCode.replace('_INFO_', escape(description) );
                    var decimals = 1;
                    if( 'decimals' in tableColumns[tc].stats[st]) {
                        decimals = tableColumns[tc].stats[st].decimals;
                    }
                    statCode = statCode.replace('_DECIMALS_', escape(decimals) );

                    var whereclause = 'NULL';
                    if('requirenotnull' in tableColumns[tc].stats[st]) {
                        var whereclause = "new \\DataWarehouse\\Query\\Model\\WhereCondition(" +
                            escape(tableColumns[tc].stats[st].requirenotnull.replace(":field_name", tableColumns[tc].name)) +
                            ", 'IS NOT', 'NULL' )";
                    }
                    statCode = statCode.replace('_WHERECLAUSE_', whereclause);

                    xdmodInteg.addStatistic(statsname, className, statCode);
				}

			}

            xdmodInteg.write();
		}

        var rawstats = {};
        var tables = this.getTables();
        for( var t in tables) {
            var tableName = tables[t].meta.schema + "." + tables[t].name;
            if( !(tableName in rawstats) ) {
                rawstats[tableName] = [];
            }
            var columns = tables[t].columns;
            for( var c in columns) {
                var dtype = columns[c].dtype ? columns[c].dtype : (columns[c].queries ? "foreignkey" : "statistic" );
                var group = columns[c].group ? columns[c].group : "misc";
                var visibility = columns[c].visibility ? columns[c].visibility : 'public';

                var name = extractandsubst(columns[c], "name");
                if(!name) {
                    name = Namealize(c, true);
                }

                rawstats[tableName].push({
                    key: c,
                    name: name,
                    units: columns[c].unit,
                    per: columns[c].per,
                    documentation: columns[c].comments,
                    dtype: dtype,
                    visibility: visibility,
                    group: group
                });
            }
            var sorting = require("./sorting.js");
            rawstats[tableName].sort(sorting.dynamicSortMultiple("dtype", "group", "units", "name"));
        }
    var rawStatisticsConfigFile = config.xdmodBuildConfigDir + '/rawstatisticsconfig.json';
        fs.writeFileSync(rawStatisticsConfigFile, JSON.stringify(rawstats, null, 4));

    } catch (exception) {
        self.emit('error', util.inspect(exception));
    }
};

var getRegressionTests = function(dataset) {

    var files;
    var testConfig = [];
    var i;

    try {
        files = fs.readdirSync(dataset.regressionTestDir + '/input');
        for(i = 0; i < files.length; i++) {
            testConfig.push({
                input: dataset.regressionTestDir + '/input/' + files[i],
                expectedOutput: dataset.regressionTestDir + '/expected/' + files[i]
            });
        }
    }
    catch(err) {
        if(err.code === 'ENOENT') {
            // allow test directory to no exist
        } else {
            throw err;
        }
    }
    return testConfig;
};

/*
* Creates the dynamic tables for the etl profile. 
*/
ETLProfile.prototype.regressionTests = function () {
    var self = this;

    var path = require('path');
    var arrayCompare = require('./array_compare.js');

    // Careful with this option. It will cause the test harness to regenerate
    // expected results for any failed tests. The developer should confirm
    // that the new expected results are correct.
    var regenerateOnFail = false;

    this.datasets.forEach(function (dataset) {
        try {
            // Check that the getQuery and MarkAsDone functions can be called
            if ( dataset.input.getQuery() == null ) {
                throw "GetQuery returned null";
            }
            var coll = { updateOne: function () { } };
            cof = { errors: null, warnings: null };
            dataset.input.markAsProcessed(coll, 1, cof, console.log);

            var datasetMap = new DatasetMap(self, dataset);
        } catch (exception) {
            self.emit('error', util.inspect(exception));
            return;
        }

        getRegressionTests(dataset).forEach(function (regressionTest) {
            var input = JSON.parse(fs.readFileSync(regressionTest.input, 'utf8'));
            var expected = JSON.parse(fs.readFileSync(regressionTest.expectedOutput, 'utf8'));

            var transformed = datasetMap.transform(input);
            var output = transformed.data;

            var compareResult = arrayCompare(output, expected, 'testOutput', 'expectedOutput');

            var inputFilename = path.basename(regressionTest.input);
            if (compareResult.objectsAreEqual && ! transformed.errors && ! transformed.warnings) {
                self.emit('message', util.format('Regression test of "%s" passed.', inputFilename));
            } else {
                if(regenerateOnFail) {
                    fs.writeFileSync(regressionTest.expectedOutput, JSON.stringify(output, null, 4) + "\n");
                }
                self.emit('error', util.format('Regression test of "%s" failed.', inputFilename));
                compareResult.unequalReasons.forEach(function (reason) {
                    self.emit('error', reason);
                });
                if( transformed.errors ) {
                    self.emit('error', JSON.stringify(transformed.errors,null, 4) ) ;
                } 
                if( transformed.warnings ) {
                    self.emit('error', JSON.stringify(transformed.warnings, null, 4) ) ;
                }
            }
        });
    });
};
