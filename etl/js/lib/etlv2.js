#!/usr/bin/env nodejs

var fs = require('fs');
var sqlType = require('./mysql.js').sqlType;

/*
 * etlv2 uses a different substitution syntax for the various time period variables.
 * This function changes the substitution variables appropriately.
 */
var remapSql = function (sql) {
    var m;
    var map = {
        ':period_start_ts': '${:PERIOD_START_TS}',
        ':period_end_ts': '${:PERIOD_END_TS}',
        ':period_id': '${:PERIOD_ID}',
        ':year': '${:YEAR_VALUE}',
        ':period': '${:PERIOD_VALUE}',
        ':seconds': '${:PERIOD_SECONDS}'
    };

    var output = sql;
    for (m in map) {
        if (map.hasOwnProperty(m)) {
            output = output.replace(new RegExp(m, 'g'), map[m]);
        }
    }

    return output;
};

var mkdirAndWrite = function (dirname, filename, data, type = 'json') {
    try {
        fs.mkdirSync(dirname);
    } catch (exception) {
        // Ignore error if directory exists already
        if (exception.code !== 'EEXIST') {
            throw exception;
        }
    }
    if (type === 'json') {
        fs.writeFileSync(dirname + '/' + filename + '.json', JSON.stringify(data, null, 4));
    } else {
        fs.writeFileSync(dirname + '/' + filename, data);
    }
};

var generateAggTableIdentifier = function (table, hasJobList) {
    if (hasJobList) {
        return table.name + '_by_day';
    }
    return table.name + '_by';
};

var generateJobListTableIdentifier = function (table) {
    return generateAggTableIdentifier(table, true) + '_joblist';
};


module.exports = {

    /**
     * Create an ETLv2 table definition for the provided dynamic table.
     *
     * @param table the DynamicTable to process.
     * @param isErrorTable whether to create the definition of the associated error table
     *        or the fact table.
     *
     * @return [Object] the ETLv2 table definition.
     */
    createDynamicTableDefinition: function (table, isErrorTable) {
        var tableDefinition = {
            name: table.name,
            engine: 'MyISAM',
            comment: '',
            columns: []
        };
        var tableColumns = table.getDynamicTableFields(isErrorTable);

        if (isErrorTable) {
            tableDefinition.name += '_errors';
        }

        for (let i = 0; i < tableColumns.length; i++) {
            let definition = {
                name: tableColumns[i].name,
                type: sqlType(tableColumns[i].type, tableColumns[i].length),
                nullable: tableColumns[i].nullable,
                comment: tableColumns[i].comments
            };
            if (tableColumns[i].hasOwnProperty('def')) {
                definition.default = tableColumns[i].def;
            }
            if (tableColumns[i].hasOwnProperty('extra')) {
                definition.extra = tableColumns[i].extra;
            }
            tableDefinition.columns.push(definition);
        }

        tableDefinition.indexes = [{
            name: 'PRIMARY',
            columns: [
                tableDefinition.columns[0].name
            ],
            type: 'BTREE',
            is_unique: true
        }];

        if (!isErrorTable) {
            if (table.meta.unique) {
                tableDefinition.indexes.push({
                    name: 'pk_index',
                    columns: table.meta.unique,
                    type: 'BTREE',
                    is_unique: true
                });
            }
        }

        for (let i = 0; i < table.meta.extras.length; i++) {
            if (typeof table.meta.extras[i] === 'string') {
                // text string definitions are deprecated. Support only one
                // type of definition for backwards compatibility
                let match = /^KEY ([^\s]+) \(([^)]+)\)$/.exec(table.meta.extras[i]);
                if (!match) {
                    throw new Error('Unsupported table metadata definition ' + table.meta.extras[i]);
                }
                tableDefinition.indexes.push({
                    name: match[1],
                    columns: match[2].split(','),
                    type: 'BTREE',
                    is_unique: false
                });
            } else {
                tableDefinition.indexes.push(table.meta.extras[i]);
            }
        }

        return tableDefinition;
    },

    /**
     * Generate the configuration files for the aggregation tables. This will
     * generate two table definitions and two action files. One for the days
     * table and one for the month,quarter,year tables.
     *
     * Also generate the joblist table that is used to map the rows in the
     * days aggregate table back to the original fact table.
     */
    createAggregateTableDefinition: function (table, hasJobList) {
        var i;
        var tableDefinition = {};
        var tableColumns = table.getAggregationTableFields();

        tableDefinition.name = generateAggTableIdentifier(table, hasJobList);
        tableDefinition.table_prefix = table.name + '_by_';
        tableDefinition.engine = 'MyISAM';
        tableDefinition.comment = table.name + ' aggregated by ${AGGREGATION_UNIT}.';

        tableDefinition.columns = [];

        if (hasJobList) {
            tableDefinition.columns.push({
                name: 'id',
                type: 'int(11)',
                nullable: false,
                extra: 'auto_increment'
            });
        }

        tableDefinition.columns.push(
            {
                name: '${AGGREGATION_UNIT}_id',
                type: 'int(10) unsigned',
                nullable: false,
                comment: 'DIMENSION: The id related to modw.${AGGREGATION_UNIT}s.'
            },
            {
                name: 'year',
                type: 'smallint(5) unsigned',
                nullable: false,
                comment: 'DIMENSION: The year of the ${AGGREGATION_UNIT}'
            },
            {
                name: '${AGGREGATION_UNIT}',
                type: 'smallint(5) unsigned',
                nullable: false,
                comment: 'DIMENSION: The ${AGGREGATION_UNIT} of the year.'
            }
        );

        tableDefinition.indexes = [];

        if (hasJobList) {
            tableDefinition.indexes.push({
                name: 'PRIMARY',
                columns: [
                    'id'
                ],
                type: 'BTREE',
                is_unique: true
            });
        }

        tableDefinition.indexes.push(
            {
                name: 'index_' + table.name + '_by_${AGGREGATION_UNIT}_${AGGREGATION_UNIT}_id',
                columns: ['${AGGREGATION_UNIT}_id']
            },
            {
                name: 'index_' + table.name + '_by_${AGGREGATION_UNIT}_${AGGREGATION_UNIT}',
                columns: ['${AGGREGATION_UNIT}']
            }
        );

        if (hasJobList) {
            tableDefinition.indexes.push(
                {
                    name: 'last_modified',
                    columns: ['last_modified']
                }
            );
        }

        for (i = 0; i < tableColumns.length; ++i) {
            tableDefinition.columns.push({
                name: tableColumns[i].name,
                type: sqlType(tableColumns[i].type, tableColumns[i].length),
                nullable: !tableColumns[i].dimension,
                comment: (tableColumns[i].dimension ? 'DIMENSION: ' : 'FACT: ') + tableColumns[i].comments
            });
            if (tableColumns[i].dimension) {
                tableDefinition.indexes.push({
                    name: 'index_' + table.name + '_' + tableColumns[i].name,
                    columns: [tableColumns[i].name]
                });
            }
        }

        if (hasJobList) {
            tableDefinition.columns.push({
                name: 'job_id_list',
                type: 'mediumtext',
                nullable: false,
                comment: 'METADATA: the ids in the fact table for the rows that went into this row'
            }, {
                name: 'last_modified',
                type: 'timestamp',
                default: 'CURRENT_TIMESTAMP',
                nullable: false,
                extra: 'ON UPDATE CURRENT_TIMESTAMP'
            });
        }

        return {
            table_definition: tableDefinition
        };
    },

    createJobListTableDefinition: function (table) {
        var tableDefinition = {
            name: generateJobListTableIdentifier(table),
            engine: 'InnoDB',
            columns: [
                {
                    name: 'agg_id',
                    type: 'int(11)',
                    nullable: false
                },
                {
                    name: 'jobid',
                    type: 'int(11)',
                    nullable: false
                }
            ],
            indexes: [
                {
                    name: 'PRIMARY',
                    columns: [
                        'agg_id',
                        'jobid'
                    ],
                    type: 'BTREE',
                    is_unique: true
                }
            ],
            triggers: []
        };
        return {
            table_definition: tableDefinition
        };
    },

    createAggregateTableAction: function (table, hasJobList) {
        var tableColumns = table.getAggregationTableFields();

        var records = {
            '${AGGREGATION_UNIT}_id': '${:PERIOD_ID}',
            year: '${:YEAR_VALUE}',
            '${AGGREGATION_UNIT}': '${:PERIOD_VALUE}'
        };
        var groupby = [];

        for (let i = 0; i < tableColumns.length; ++i) {
            if (tableColumns[i].dimension) {
                if (tableColumns[i].useSqlInGroupBy) {
                    groupby.push(remapSql(tableColumns[i].sql));
                } else {
                    groupby.push(tableColumns[i].name);
                }
            }
            records[tableColumns[i].name] = remapSql(tableColumns[i].sql);
        }
        if (hasJobList) {
            records.job_id_list = 'GROUP_CONCAT(jf._id)';
        }

        return {
            table_definition: {
                $ref: '${table_definition_dir}/' + table.meta.realmName.toLowerCase() + '/' + generateAggTableIdentifier(table, hasJobList) + '.json#/table_definition'
            },
            aggregation_period_query: {
                overseer_restrictions: {
                    last_modified_start_date: 'last_modified >= ${VALUE}',
                    last_modified_end_date: 'last_modified <= ${VALUE}',
                    include_only_resource_codes: 'resource_id IN ${VALUE}',
                    exclude_resource_codes: 'resource_id NOT IN ${VALUE}'
                },
                conversions: {
                    start_day_id: 'YEAR(FROM_UNIXTIME(start_time_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(start_time_ts))',
                    end_day_id: 'YEAR(FROM_UNIXTIME(end_time_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(end_time_ts))'
                }
            },
            destination_query: {
                overseer_restrictions: {
                    include_only_resource_codes: 'record_resource_id IN ${VALUE}',
                    exclude_resource_codes: 'record_resource_id NOT IN ${VALUE}'
                }
            },
            source_query: {
                overseer_restrictions: {
                    include_only_resource_codes: 'record.resource_id IN ${VALUE}',
                    exclude_resource_codes: 'record.resource_id NOT IN ${VALUE}'
                },
                query_hint: 'SQL_NO_CACHE',
                records: records,
                groupby: groupby,
                joins: [{
                    name: 'job',
                    schema: '${SOURCE_SCHEMA}',
                    alias: 'jf'
                }],
                where: [
                    'YEAR(FROM_UNIXTIME(jf.start_time_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(jf.start_time_ts)) <= ${:PERIOD_END_DAY_ID} AND YEAR(FROM_UNIXTIME(jf.end_time_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(jf.end_time_ts)) >= ${:PERIOD_START_DAY_ID}'
                ]
            }
        };
    },

    createJobListTableAction(table) {
        var action = {
            table_definition: {
                $ref: '${table_definition_dir}/' + table.meta.realmName.toLowerCase() + '/' + generateJobListTableIdentifier(table) + '.json#/table_definition'
            },
            source_query: {
                overseer_restrictions: {
                    last_modified_start_date: 'last_modified >= ${VALUE}',
                    last_modified_end_date: 'last_modified <= ${VALUE}'
                },
                records: {
                    agg_id: 'id',
                    job_id_list: 'job_id_list',
                    job_id: -1
                },
                joins: [{
                    name: generateAggTableIdentifier(table, true),
                    schema: '${SOURCE_SCHEMA}',
                    alias: 'jf'
                }]
            },
            destination_record_map: {
            }
        };
        action.destination_record_map[generateJobListTableIdentifier(table)] = {
            agg_id: 'agg_id',
            jobid: 'job_id'
        };
        return action;
    },

    /**
     * generate the etlv2 table definitions and action files for all supported tables
     * in a given etl profile and write them to the specified path.
     *
     * @param profile The etl profile object
     * @param xdmodConfigDirectory the path to the XDMoD configuration directory under
     *        which the config files will be written.
     */
    generateDefinitionFiles: function (profile, xdmodConfigDirectory) {
        var etlv2ConfigDir = xdmodConfigDirectory + '/etl';
        var tables = profile.getAggregationTables();

        if (profile.schema.postprocess) {
            let sqlDefnDir = etlv2ConfigDir + '/etl_sql.d/' + profile.name.toLowerCase().split(' ')[0];
            mkdirAndWrite(sqlDefnDir, 'postprocess.sql', profile.schema.postprocess.join('//\n'), 'sql');
        }

        for (let t in tables) {
            if (tables.hasOwnProperty(t)) {
                let table = tables[t];

                let actionDefnDir = etlv2ConfigDir + '/etl_action_defs.d/' + table.meta.realmName.toLowerCase();
                let tableDefnDir = etlv2ConfigDir + '/etl_tables.d/' + table.meta.realmName.toLowerCase();

                [true, false].forEach(function (hasJobList) {
                    var actionDefn = module.exports.createAggregateTableAction(table, hasJobList);
                    mkdirAndWrite(actionDefnDir, generateAggTableIdentifier(table, hasJobList), actionDefn);

                    var tableDefn = module.exports.createAggregateTableDefinition(table, hasJobList);
                    mkdirAndWrite(tableDefnDir, generateAggTableIdentifier(table, hasJobList), tableDefn);
                });

                let joblistActionDefn = module.exports.createJobListTableAction(table);
                mkdirAndWrite(actionDefnDir, generateJobListTableIdentifier(table), joblistActionDefn);

                let joblistTableDefn = module.exports.createJobListTableDefinition(table);
                mkdirAndWrite(tableDefnDir, generateJobListTableIdentifier(table), joblistTableDefn);
            }
        }

        var facttables = profile.getTables();

        for (let t in facttables) {
            if (facttables.hasOwnProperty(t)) {
                let table = facttables[t];
                let tableDefnDir = etlv2ConfigDir + '/etl_tables.d/' + table.meta.schema.substring(5);

                [true, false].forEach(function (isErrorTable) {
                    let tableDefn = module.exports.createDynamicTableDefinition(table, isErrorTable);
                    mkdirAndWrite(tableDefnDir, tableDefn.name, tableDefn);
                });
            }
        }
    }
};
