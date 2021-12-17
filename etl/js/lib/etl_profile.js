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
var glob = require('glob');
var sorting = require('./sorting.js');

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

var CamelCase = function (input, insertSpaces) {
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
                self.emit('error', JSON.stringify(err));
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
 * Get the raw statistics configuration data.
 *
 * @return {object}
 */
ETLProfile.prototype.getRawStatisticsConfiguration = function () {
    var rawStatsConfig = this.schema.rawStatistics;
    var tables = this.getTables();

    rawStatsConfig.tables = rawStatsConfig.tables || {};
    rawStatsConfig.fields = rawStatsConfig.fields || {};

    for (var t in tables) {
        if ({}.hasOwnProperty.call(tables, t)) {
            var tableName = tables[t].meta.schema + '.' + tables[t].name;

            if (tableName === rawStatsConfig.table) {
                util._extend(rawStatsConfig.fields, tables[t].columns);
            }
        }
    }

    return rawStatsConfig;
};

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
            // detect renamed columns is to look for old name in comment
			//(ie. comment 'old_name:comment_text'). This could be documented
			//convention. can get table structure before doing the query, but
			//after the list of fields get generated inside the table class

            // todo: create a table for metric errors
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
            // other db engines not yet supported. , this class can be specialized
            // or modularized at that time.
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
        if (agg.table !== undefined && agg.table !== null) {
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
                dimension: agg.dimension || false,
                show_all_dimension_values: agg.show_all_dimension_values || false
			};
            newf.name = newf.name.replace(/\:field_name/g, field);
			newf.sql = agg.sql || newf.name;
			newf.sql = newf.sql.replace(/\:field_name/g, field);
			newf.sqlType = sqlType(newf.type, newf.length);
            newf.useSqlInGroupBy = agg.sql && !agg.name;
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
            // other db engines not yet supported. , this class can be specialized
            // or modularized at that time.
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

    this.addStatistic = function (name) {
        realmConfig.statistics.push({
            name: name
        });
    };
    this.addGroupBy = function (name, roleAccessConfig) {
        roles.push({ realm: realmName, group_by: name, config: roleAccessConfig });
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

        // Sort role configuration data and output.

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

/**
 * Raw statistics integrator.
 *
 * @constructor
 * @param {string} realmName - The name/ID of the realm.
 * @param {string} realmDisplay - The display name of the realm.
 * @param {number} realmOrder - The order prefix to prepend to the raw statistics configuration file.
 */
var RawStatsIntegrator = function (realmName, realmDisplay, realmOrder) {
    /** @var {string} The realm/ID name. */
    this.realmName = realmName;

    /** @var {string} The realm display name. */
    this.realmDisplay = realmDisplay;

    /** @var {number} The raw statistics file order prefix. */
    this.realmOrder = realmOrder;

    /** @var {array} Raw statistics tables. */
    this.tables = [];

    /** @var {array} Raw statistics fields. */
    this.fields = [];

    /**
     * @var {object} Used to lookup table alias.
     * @private
     */
    this.tableAliases = {};

    /**
     * Get a unique identifier for a table definition.
     *
     * @private
     * @param {string} schemaName - The name of the database schema.
     * @param {string} tableName - The name of the database table.
     * @param {string} foreignTableAlias - Alias of table this table is joined to.
     * @param {string} foreignKey - Foreign key used to join the table.
     */
    this.getTableAliasKey = function (schemaName, tableName, foreignTableAlias, foreignKey) {
        // This assumes that none of these values contain any dot characters.
        return [schemaName, tableName, foreignTableAlias, foreignKey].join('.');
    };

    /**
     * Add a table to the raw statistics configuration.
     *
     * @param {object} tableDef - Table definition.
     * @param {string} tableDef.schema - Name of the database schema.
     * @param {string} tableDef.name - Name of the database table.
     * @param {string} tableDef.alias - Alias to use for this table.
     * @param {object} tableDef.join - Join definition.
     * @param {string} tableDef.join.primaryKey - Primary key of table this table is joined to.
     * @param {string} tableDef.join.foreignTableAlias - Alias
     * @param {string} tableDef.join.foreignKey - Foreign key used in join.
     */
    this.addTable = function (tableDef) {
        this.tables.push(tableDef);
        var key = this.getTableAliasKey(tableDef.schema, tableDef.name, tableDef.join.foreignTableAlias, tableDef.join.foreignKey);
        this.tableAliases[key] = tableDef.alias;
    };

    /**
     * Add a field to the raw statistics configuration.
     *
     * @param {object} field - Field definition.
     */
    this.addField = function (fieldDef) {
        this.fields.push(fieldDef);
    };

    /**
     * Find the alias of a table that's already been added.
     *
     * @param {string} schemaName - The name of the database schema.
     * @param {string} tableName - The name of the database table.
     * @param {string} foreignTableAlias - Alias of table this table is joined to.
     * @param {string} foreignKey - Foreign key used to join the table.
     * @return {string|null} The table's alias or null if the table has not been added.
     */
    this.getTableAlias = function (schemaName, tableName, foreignTableAlias, foreignKey) {
        var key = this.getTableAliasKey(schemaName, tableName, foreignTableAlias, foreignKey);
        return this.tableAliases[key] ? this.tableAliases[key] : null;
    };

    /**
     * Write raw statistics configuration to file.
     */
    this.write = function () {
        var rawStats = {
            '+realms': [
                {
                    name: this.realmName,
                    display: this.realmDisplay
                }
            ]
        };

        this.fields.sort(sorting.dynamicSortMultiple('dtype', 'group', 'units', 'name'));

        rawStats[realmName] = {
            tables: this.tables,
            fields: this.fields
        };

        fs.writeFileSync(
            config.xdmodBuildConfigDir + '/rawstatistics.d/' + this.realmOrder + '_' + this.realmName.toLowerCase() + '.json',
            JSON.stringify(rawStats, null, 4)
        );
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

var generateGroupBy = function (itemAlias, column)
{
    var label = column.label;
    var description = column.comments;
    for( var tagidx in column.dynamictags ) {
        label = label.replace(new RegExp(':label_' + tagidx, 'g'), column.dynamictags[tagidx]);
        label = label.replace(new RegExp(':Label_' + tagidx, 'g'), wordToUpper(column.dynamictags[tagidx]));
        description = description.replace(new RegExp(':label_' + tagidx, 'g'), column.dynamictags[tagidx]);
        description = description.replace(new RegExp(':Label_' + tagidx, 'g'), wordToUpper(column.dynamictags[tagidx]));
    }
    return {
        attribute_table_schema: 'modw_supremm',
        attribute_to_aggregate_table_key_map: [
            {
                id: column.name
            }
        ],
        attribute_values_query: {
            joins: [
                {
                    name: column.dimension_table
                }
            ],
            orderby: [
                'id'
            ],
            records: {
                id: 'id',
                name: 'description',
                order_id: 'id',
                short_name: 'description'
            }
        },
        category: column.category || 'unknown',
        chart_options: {
            combine_method: 'stack',
            dataset_display_type: {
                aggregate: 'bar'
            },
            dataset_type: 'aggregate'
        },
        data_sort_order: null,
        description_html: description,
        name: label || itemAlias,
        show_all_dimension_values: column.show_all_dimension_values
    };
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
    try {
        var roles = [];

        var tables = this.getAggregationTables();

		for (var t in tables) {

			var table = tables[t];
			var realmName = table.meta.realmName;

            writeRealmMetadata(realmName, this.version);
            var groupBys = {};
            var includefiles = glob.sync(this.root + '/output_db/groupbys*.json');
            includefiles.sort();
            for (let i = 0; i < includefiles.length; i++) {
                Object.assign(groupBys, JSON.parse(fs.readFileSync(includefiles[i], 'utf8')));
            }
            var xdmodInteg = new xdmodIntegrator(realmName, this.root);

            xdmodInteg.addGroupBy('none', this.schema.groupByNoneRoleConfig);

			self.emit('message', 'Processing table: ' + table.schema + '.' + table.name);

            var statistics = {};

			var tableColumns = table.getAggregationTableFields();

			for(var tc in tableColumns) {

                if(tableColumns[tc].label) {

                    var itemAlias = tableColumns[tc].alias || tableColumns[tc].name;
                    xdmodInteg.addGroupBy(itemAlias, tableColumns[tc].roles);

                    groupBys[itemAlias] = generateGroupBy(itemAlias, tableColumns[tc]);

                } else if (tableColumns[tc].dimension ) {

                    var items = [ tableColumns[tc].alias || tableColumns[tc].name.replace(/_id$/, '') ];
                    if ( Array.isArray(items[0]) ) {
                        items = items[0];
                    }
                    for(var i = 0; i < items.length; i++) {
                        var itemName = items[i];

                        if(tableColumns[tc].dimension_table) {
                            tableColumns[tc].label = CamelCase(itemName);
                            groupBys[itemName] = generateGroupBy(itemName, tableColumns[tc]);
                        }
                        xdmodInteg.addGroupBy(itemName, tableColumns[tc].roles);
                    }
                }

				for(var st in tableColumns[tc].stats) {
                    var statsname = tableColumns[tc].stats[st].name;
                    var fieldName = tableColumns[tc].name;
                    if (statsname === undefined) {
                        statsname = fieldName;
                    } else {
                        statsname = statsname.replace(':field_name', fieldName);
                    }

                    var label = tableColumns[tc].stats[st].label.replace(':field_name', fieldName);
                    var description = tableColumns[tc].stats[st].description;
                    if (typeof description === 'string') {
                       description = description.replace(':field_name', fieldName);
                    }

                    for (var tagidx in tableColumns[tc].dynamictags) {
                        if (tableColumns[tc].dynamictags.hasOwnProperty(tagidx)) {
                            var lSubStr = ':label_' + tagidx;
                            var uSubStr = ':Label_' + tagidx;
                            var replacement = tableColumns[tc].dynamictags[tagidx];
                            label = label.replace(lSubStr, replacement);
                            label = label.replace(uSubStr, wordToUpper(replacement));
                            if (typeof description === 'string') {
                                description = description.replace(lSubStr, replacement);
                                description = description.replace(uSubStr, wordToUpper(replacement));
                            }
                        }
                    }
                    var decimals = 1;
                    if ('decimals' in tableColumns[tc].stats[st]) {
                        decimals = tableColumns[tc].stats[st].decimals;
                    }

                    var aggregate_formula;
                    var timeseries_formula;

                    if (tableColumns[tc].stats[st].sql) {
                        aggregate_formula = tableColumns[tc].stats[st].sql.replace(/:field_name/g, tableColumns[tc].name);
                        timeseries_formula = aggregate_formula;
                    } else {
                        aggregate_formula = tableColumns[tc].stats[st].aggregate_sql.replace(/:field_name/g, tableColumns[tc].name);
                        timeseries_formula = tableColumns[tc].stats[st].timeseries_sql.replace(/:field_name/g, tableColumns[tc].name);
                    }

                    xdmodInteg.addStatistic(statsname);
                    statistics[statsname] = {
                        aggregate_formula: aggregate_formula,
                        description_html: description,
                        name: label,
                        precision: decimals,
                        timeseries_formula: timeseries_formula,
                        unit: tableColumns[tc].stats[st].unit
                    };
                    if ('requirenotnull' in tableColumns[tc].stats[st]) {
                        statistics[statsname].additional_where_condition = [tableColumns[tc].name, 'IS NOT', 'NULL'];
                    }
                }
            }
            xdmodInteg.mkdirandwrite(config.xdmodBuildConfigDir + '/datawarehouse.d/ref/', realmName.toLowerCase() + '-statistics', statistics);
            xdmodInteg.mkdirandwrite(config.xdmodBuildConfigDir + '/datawarehouse.d/ref/', realmName.toLowerCase() + '-group-bys', groupBys);
            xdmodInteg.write();
        }

        var rawStatsConfig = this.getRawStatisticsConfiguration();
        var rawStatsInteg = new RawStatsIntegrator(rawStatsConfig.realmName, rawStatsConfig.realmDisplay, rawStatsConfig.realmOrder);
        var tableIndex = 1;
        var key;

        for (key in rawStatsConfig.tables) {
            if ({}.hasOwnProperty.call(rawStatsConfig.tables, key)) {
                rawStatsInteg.addTable(rawStatsConfig.tables[key]);
            }
        }

        for (key in rawStatsConfig.fields) {
            if ({}.hasOwnProperty.call(rawStatsConfig.fields, key)) {
                var col = rawStatsConfig.fields[key];
                var columnName = key;
                var alias = key;
                var dtype = col.dtype;
                if (!dtype) {
                    dtype = col.queries ? 'foreignkey' : 'statistic';
                }
                var group = col.group ? col.group : 'misc';
                var visibility = col.visibility ? col.visibility : 'public';
                var batchExport = col.batchExport ? col.batchExport : false;

                var name = extractandsubst(col, 'name');
                if (!name) {
                    name = Namealize(key);
                }

                // Default to using the fact table, but override for foreign
                // key dtype.
                var tableAlias = 'jf';

                if (dtype === 'foreignkey') {
                    if (!col.join) {
                        continue;
                    }

                    var join = col.join;
                    var tableSchema = join.schema;
                    var tableName = join.table;
                    var foreignKey = join.foreignKey ? join.foreignKey : key;
                    alias = name;
                    columnName = join.column ? join.column : 'name';

                    tableAlias = rawStatsInteg.getTableAlias(tableSchema, tableName, 'jf', foreignKey);

                    if (tableAlias === null) {
                        tableAlias = 'ft' + tableIndex;
                        ++tableIndex;

                        rawStatsInteg.addTable({
                            schema: tableSchema,
                            name: tableName,
                            alias: tableAlias,
                            join: {
                                // All tables currently have primary key 'id'
                                // and are joined to the fact table 'jf'.
                                primaryKey: 'id',
                                foreignTableAlias: 'jf',
                                foreignKey: foreignKey
                            }
                        });
                    }
                }

                var fieldDef = {
                    key: key,
                    alias: alias,
                    name: name,
                    dtype: dtype,
                    units: col.unit,
                    per: col.per,
                    documentation: col.comments,
                    visibility: visibility,
                    batchExport: batchExport,
                    group: group
                };

                if (col.formula) {
                    fieldDef.formula = col.formula;
                } else {
                    fieldDef.column = columnName;
                    fieldDef.tableAlias = tableAlias;
                }

                if (col.withError) {
                    fieldDef.withError = col.withError;
                }

                rawStatsInteg.addField(fieldDef);
            }
        }

        rawStatsInteg.write();
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
            // Check that the getQuery and MarkAsDone functions can be called.
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
