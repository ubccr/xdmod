/**
 * Log store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Log');

XDMoD.Log.Store = Ext.extend(Ext.data.JsonStore, {
    url: '/controllers/log.php',

    listeners: {
        exception: function () {
            console.log(arguments);
        }
    },

    constructor: function (config) {
        config = config || {};

        this.logLevels = [];

        Ext.apply(config, {
            baseParams: {
                operation: 'get_messages',
                start: 0,
                limit: 1000
            },

            root: 'response',
            idProperty: 'id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                {
                    name: 'id',
                    type: 'int'
                },
                {
                    name: 'logtime',
                    type: 'date',
                    dateFormat: 'Y-m-d H:i:s'
                },
                {
                    name: 'ident',
                    type: 'string'
                },
                {
                    name: 'priority',
                    type: 'int'
                },
                {
                    name: 'priorityName',
                    type: 'string',
                    convert: function (value, record) {
                        var priority = record.priority,
                            logLevel = config.logLevelsStore.getById(priority);
                        return logLevel.get('name');
                    }
                },
                {
                    name: 'messageData',
                    convert: function (value, record) {
                        try {
                            return Ext.decode(record.message);
                        } catch (e) {
                            console.log('Invalid JSON: ' + record.message);
                            return {};
                        }
                    }
                },
                {
                    name: 'message',
                    type: 'string',
                    convert: function (value, record) {
                        var message;
                        try {
                            message = Ext.decode(record.message).message;
                            if (message === null || message === undefined) {
                                message = '';
                            }
                            return message;
                        } catch (e) {
                            console.log('Invalid JSON: ' + record.message);
                            return '';
                        }
                    }
                }
            ]
        });

        XDMoD.Log.Store.superclass.constructor.call(this, config);
    },

    addLogLevel: function (levelId) {
        this.logLevels.push(levelId);
        this.setBaseParam('logLevels[]', this.logLevels);
    },

    removeLogLevel: function (levelId) {
        this.logLevels.remove(levelId);
        this.setBaseParam('logLevels[]', this.logLevels);
    },

    setStartDate: function (date) {
        this.setBaseParam('start_date', date.format('Y-m-d'));
    },

    setEndDate: function (date) {
        this.setBaseParam('end_date', date.format('Y-m-d'));
    },

    setOnlyMostRecent: function (only) {
        this.setBaseParam('only_most_recent', only ? '1' : '0');
    }
});

