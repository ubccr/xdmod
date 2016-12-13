/**
 * Log summary store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Log');

XDMoD.Log.SummaryStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/log.php',

    listeners: {
        exception: function () {
            console.log(arguments);
        }
    },

    constructor: function (config) {
        config = config || {};

        Ext.apply(config, {
            baseParams: {
                operation: 'get_summary',
                ident: config.ident
            },

            root: 'response',
            idProperty: 'id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                {
                    name: 'process_start_time',
                    type: 'date',
                    dateFormat: 'c'
                },
                {
                    name: 'process_end_time',
                    type: 'date',
                    dateFormat: 'c'
                },
                {
                    name: 'data_start_time',
                    type: 'string'
                },
                {
                    name: 'data_end_time',
                    type: 'string'
                },
                {
                    name: 'warning_count',
                    type: 'int'
                },
                {
                    name: 'error_count',
                    type: 'int'
                },
                {
                    name: 'critical_count',
                    type: 'int'
                },
                {
                    name: 'alert_count',
                    type: 'int'
                },
                {
                    name: 'emergency_count',
                    type: 'int'
                },
                {
                    name: 'records_examined_count',
                    type: 'int'
                },
                {
                    name: 'records_loaded_count',
                    type: 'int'
                },
                {
                    name: 'records_incomplete_count',
                    type: 'int'
                },
                {
                    name: 'records_parse_error_count',
                    type: 'int'
                },
                {
                    name: 'records_queued_count',
                    type: 'int'
                },
                {
                    name: 'records_error_count',
                    type: 'int'
                },
                {
                    name: 'records_sql_error_count',
                    type: 'int'
                },
                {
                    name: 'records_unknown_count',
                    type: 'int'
                },
                {
                    name: 'records_duplicate_count',
                    type: 'int'
                },
                {
                    name: 'records_exception_count',
                    type: 'int'
                }
            ]
        });

        XDMoD.Log.SummaryStore.superclass.constructor.call(this, config);
    }
});

