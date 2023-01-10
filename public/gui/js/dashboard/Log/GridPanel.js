/**
 * Log grid panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Log');

XDMoD.Log.GridPanel = Ext.extend(Ext.grid.GridPanel, {
    loadMask: true,

    autoExpandColumn: 'message',
    autoExpandMax: 10000,

    listeners: {
        viewready: function () {
            this.store.load();
        }
    },

    constructor: function (config) {
        config = config || {};

        this.ident = config.ident;
        this.logLevelsStore = config.logLevelsStore;
        this.pageSize = config.pageSize || 100;

        this.store = new XDMoD.Log.Store({
            logLevelsStore: this.logLevelsStore
        });

        this.store.setBaseParam('ident', this.ident);
        this.store.setBaseParam('limit', this.pageSize);

        var expander = new Ext.ux.grid.RowExpander({
            tpl: new Ext.XTemplate(
                '<div class="status-info-details">' +
                '<pre>{message}</pre>' +
                '{[this.messageParts(values.messageData)]}' +
                '</div>',
                {
                    messageParts: function (messageData) {
                        var parts = [];
                        Ext.iterate(messageData, function (key, value) {
                            if (key !== 'message') {
                                parts.push(key + ': ' + value);
                            }
                        }, this);
                        if (parts.length === 0) {
                            return '';
                        }
                        return '<pre>' + parts.join('<br/>') + '</pre>';
                    }
                }
            )
        });

        // Override the RowExpander getRowClass and add error or warning
        // classes in addition to the class returned from the original
        // getRowClass function.

        var getRowClass = expander.getRowClass;

        expander.getRowClass = function (record) {
            var rowClass = getRowClass.apply(this, arguments),
                priority = record.get('priority'),
                data = record.get('messageData');

            if (priority === 4) {
                return rowClass + ' grid-row-warning';
            } else if (priority < 4) {
                return rowClass + ' grid-row-error';
            }

            if (data.process_start_time || data.process_end_time) {
                return rowClass + ' grid-start-stop';
            }

            return rowClass;
        };

        this.plugins = [expander];

        var startDate = new Date(),
            endDate = new Date(),
            tbarItems;

        // One week.
        startDate.setDate(endDate.getDate() - 6);

        this.store.setStartDate(startDate);
        this.store.setEndDate(endDate);

        tbarItems = [
            {
                xtype: 'checkbox',
                boxLabel: 'Only Most Recent Run',
                checked: false,
                listeners: {
                    check: {
                        fn: function (checkbox, checked) {
                            var tb = this.getTopToolbar(),
                                datefields = tb.findByType('datefield');

                            this.store.setOnlyMostRecent(checked);

                            Ext.each(datefields, function (item, index) {
                                item.setDisabled(checked);
                            }, this);
                        },
                        scope: this
                    }
                }
            },
            {
                xtype: 'tbspacer',
                width: 10
            },
            {
                xtype: 'datefield',
                value: startDate,
                listeners: {
                    change: {
                        fn: function (field, date) {
                            this.store.setStartDate(date);
                        },
                        scope: this
                    }
                }
            },
            {
                xtype: 'tbspacer',
                width: 10
            },
            {
                xtype: 'datefield',
                value: endDate,
                listeners: {
                    change: {
                        fn: function (field, date) {
                            this.store.setEndDate(date);
                        },
                        scope: this
                    }
                }
            },
            {
                xtype: 'tbspacer',
                width: 10
            }
        ];

        this.logLevelsStore.each(function (record) {
            var levelId = record.get('id');

            this.store.addLogLevel(levelId);

            tbarItems.push({
                xtype: 'checkbox',
                boxLabel: record.get('name'),
                name: 'level-' + levelId,
                checked: true,
                listeners: {
                    check: {
                        fn: function (checkbox, checked) {
                            if (checked) {
                                this.store.addLogLevel(levelId);
                            } else {
                                this.store.removeLogLevel(levelId);
                            }
                        },
                        scope: this
                    }
                }
            });

            tbarItems.push({
                xtype: 'tbspacer',
                width: 10
            });
        }, this);

        tbarItems.push({
            xtype: 'button',
            text: 'Refresh',
            iconCls: 'refresh',
            listeners: {
                click: {
                    fn: this.store.load,
                    scope: this.store
                }
            }
        });

        this.tbar = new Ext.Toolbar({
            items: tbarItems
        });

        this.bbar = new Ext.PagingToolbar({
            store: this.store,
            displayInfo: true,
            pageSize: this.pageSize
        });

        this.colModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },

            columns: [
                expander,
                {
                    header: 'Time',
                    dataIndex: 'logtime',
                    width: 120,
                    renderer: function (value) {
                        return value.format('Y-m-d H:i:s');
                    }
                },
                {
                    header: 'Priority',
                    dataIndex: 'priorityName'
                },
                {
                    id: 'message',
                    header: 'Message',
                    dataIndex: 'message',
                    scope: this,
                    renderer: this.messageRenderer
                }
            ]
        });

        XDMoD.Log.GridPanel.superclass.constructor.call(this, config);
    },

    messageRenderer: function (value, metaData, record) {
        var message = this.addLinksToMessage(value),
            extraParts = [];

        Ext.iterate(record.get('messageData'), function (key, value) {
            if (key !== 'message') {
                extraParts.push(key + ': ' + value);
            }
        }, this);

        if (extraParts.length > 0) {
            message += ' (' + extraParts.join(', ') + ')';
        }

        return message;
    },

    addLinksToMessage: function (message) {
        var matches = message.match(/(.*\()#(\d+)(.*)/),
            instanceId;

        if (matches) {
            instanceId = matches[2];
            return matches[1] + '<a href="#" onclick="javascript:' +
                'var iw=new XDMoD.AppKernel.InstanceWindow({instanceId:' +
                instanceId + '});iw.show()">#' + instanceId + '</a>' + matches[3];
        }

        return message;
    }
});

