Ext.ns('XDMoD.Module.DataExport');

/**
 * Data warehouse export module.
 */
XDMoD.Module.DataExport = Ext.extend(XDMoD.PortalModule, {
    module_id: 'data_export',
    title: 'Data Export',
    usesToolbar: false,

    /**
     * The default number of results to retrieve during paging operations.
     *
     * @var {Number}
     */
    defaultPageSize: 24,

    initComponent: function () {
        this.requestsStore = new XDMoD.Module.DataExport.RequestsStore();

        this.requestForm = new XDMoD.Module.DataExport.RequestForm({
            title: 'Create Bulk Data Export Request',
            bodyStyle: 'padding: 5px 5px 0 5px',
            border: false,
            region: 'north'
        });

        this.requestsGrid = new XDMoD.Module.DataExport.RequestsGrid({
            title: 'Status of Export Requests',
            region: 'center',
            margins: '2 2 2 0',
            pageSize: this.defaultPageSize,
            store: this.requestsStore
        });

        this.requestsGrid.on('afterrender', this.requestsStore.load, this.requestsStore, { single: true });
        this.requestForm.on('actioncomplete', this.requestsGrid.reload, this.requestsGrid);

        this.items = [
            {
                xtype: 'panel',
                border: true,
                width: 375,
                split: true,
                region: 'west',
                margins: '2 0 0 2',
                layout: 'vbox',
                layoutConfig: {
                    align: 'stretch'
                },
                items: [
                    this.requestForm,
                    {
                        // Spacer panel
                        xtype: 'panel',
                        border: false,
                        flex: 1
                    }
                ]
            },
            this.requestsGrid
        ];

        XDMoD.Module.DataExport.superclass.initComponent.call(this);
    }
});

/**
 * Data export request form.
 */
XDMoD.Module.DataExport.RequestForm = Ext.extend(Ext.form.FormPanel, {
    initComponent: function () {
        this.maxDateRangeText = '1 year';
        this.maxDateRangeInMilliseconds = 1000 * 60 * 60 * 24 * 365;

        Ext.apply(this.initialConfig, {
            method: 'POST',
            url: 'rest/v1/warehouse/export/request'
        });

        Ext.apply(this, {
            monitorValid: true,
            tools: [
                {
                    id: 'help',
                    qtip: XDMoD.Module.DataExport.createRequestHelpText
                }
            ],
            items: [
                {
                    xtype: 'fieldset',
                    style: {
                        margin: '0'
                    },
                    columnWidth: 1,
                    items: [
                        {
                            xtype: 'combo',
                            hiddenName: 'realm',
                            fieldLabel: 'Realm',
                            emptyText: 'Select a realm',
                            valueField: 'id',
                            displayField: 'name',
                            allowBlank: false,
                            editable: false,
                            triggerAction: 'all',
                            mode: 'local',
                            store: {
                                xtype: 'jsonstore',
                                autoLoad: true,
                                autoDestroy: true,
                                url: 'rest/v1/warehouse/export/realms',
                                root: 'data',
                                fields: [
                                    { name: 'id', type: 'string' },
                                    { name: 'name', type: 'string' }
                                ]
                            }
                        },
                        {
                            xtype: 'datefield',
                            name: 'start_date',
                            fieldLabel: 'Start Date',
                            emptyText: 'Start Date',
                            format: 'Y-m-d',
                            allowBlank: false,
                            validator: this.validateStartDate.bind(this)
                        },
                        {
                            xtype: 'datefield',
                            name: 'end_date',
                            fieldLabel: 'End Date',
                            emptyText: 'End Date',
                            format: 'Y-m-d',
                            allowBlank: false,
                            validator: this.validateEndDate.bind(this)
                        },
                        {
                            xtype: 'combo',
                            name: 'format',
                            fieldLabel: 'Format',
                            emptyText: 'Select an export format',
                            allowBlank: false,
                            editable: false,
                            triggerAction: 'all',
                            mode: 'local',
                            store: ['CSV', 'JSON']
                        }
                    ]
                }
            ],
            buttons: [
                {
                    xtype: 'button',
                    text: 'Submit Request',
                    formBind: true,
                    disabled: true,
                    scope: this,
                    handler: function () {
                        this.getForm().submit();
                    }
                }
            ]
        });

        XDMoD.Module.DataExport.RequestForm.superclass.initComponent.call(this);

        this.getForm().on('actionfailed', function (form, action) {
            switch (action.failureType) {
                case Ext.form.Action.CLIENT_INVALID:
                    // It shouldn't be possible to submit and invalid form, but it
                    // does happen display an error message.
                    Ext.Msg.alert('Error', 'Validation failed, please check input values and resubmit.');
                    break;
                case Ext.form.Action.CONNECT_FAILURE:
                case Ext.form.Action.SERVER_INVALID:
                    var response = action.response;
                    Ext.Msg.alert(
                        response.statusText || 'Error',
                        JSON.parse(response.responseText).message || 'Unknown Error'
                    );
                    break;
                case Ext.form.Action.LOAD_FAILURE:
                    // This error occurs when the server doesn't return anything.
                    Ext.Msg.alert('Submission Error', 'Failed to submit request, try again later.');
                    break;
                default:
                    Ext.Msg.alert('Unknown Error', 'An unknown error occured, try again later.');
            }
        });
    },

    validateStartDate: function (date) {
        var startDate;
        try {
            startDate = this.parseDate(date);
        } catch (e) {
            return e.message;
        }

        var endDate = this.getForm().getFieldValues().end_date;

        if (endDate === '') {
            return true;
        }

        if (startDate > endDate) {
            return 'Start date must be before the end date';
        }

        if (endDate - startDate > this.maxDateRangeInMilliseconds) {
            return 'Date range must be less than ' + this.maxDateRangeText;
        }

        return true;
    },

    validateEndDate: function (date) {
        var endDate;
        try {
            endDate = this.parseDate(date);
        } catch (e) {
            return e.message;
        }

        var startDate = this.getForm().getFieldValues().start_date;

        if (startDate === '') {
            return true;
        }

        if (startDate > endDate) {
            return 'End date must be after the start date';
        }

        if (endDate - startDate > this.maxDateRangeInMilliseconds) {
            return 'Date range must be less than ' + this.maxDateRangeText;
        }

        return true;
    },

    parseDate: function (date) {
        if (Ext.isDate(date)) {
            return date;
        }

        var format = 'Y-m-d';
        var parsedDate = Date.parseDate(date, format);

        if (parsedDate === undefined) {
            throw new Error(date + ' is not a valid date - it must be in the format ' + format);
        }

        return parsedDate;
    }
});

/**
 * Data export request grid.
 */
XDMoD.Module.DataExport.RequestsGrid = Ext.extend(Ext.grid.GridPanel, {
    initComponent: function () {
        Ext.apply(this, {
            loadMask: true,
            tools: [
                {
                    id: 'help',
                    qtip: XDMoD.Module.DataExport.exportStatusHelpText
                }
            ],
            columns: [
                {
                    header: 'Request Date',
                    dataIndex: 'requested_datetime',
                    xtype: 'datecolumn',
                    format: 'Y-m-d'
                },
                {
                    header: 'State',
                    dataIndex: 'state',
                    renderer: function (value, metaData) {
                        switch (value) {
                            case 'Available':
                                metaData.attr = 'style="background-color:#040"';
                                break;
                            case 'Expired':
                            case 'Failed':
                                metaData.attr = 'style="background-color:#f00"';
                                break;
                            case 'Submitted':
                                metaData.attr = 'style="background-color:yellow"';
                                break;
                            default:
                        }
                        return value;
                    }
                },
                {
                    header: 'Realm',
                    dataIndex: 'realm'
                },
                {
                    header: 'Data Start Date',
                    dataIndex: 'start_date',
                    xtype: 'datecolumn',
                    format: 'Y-m-d'
                },
                {
                    header: 'Data End Date',
                    dataIndex: 'end_date',
                    xtype: 'datecolumn',
                    format: 'Y-m-d'
                },
                {
                    header: 'Format',
                    dataIndex: 'export_file_format'
                },
                {
                    header: 'Expiration Date',
                    dataIndex: 'expires_date',
                    xtype: 'datecolumn',
                    format: 'Y-m-d'
                },
                {
                    header: 'Actions',
                    xtype: 'actioncolumn',
                    dataIndex: 'state',
                    scope: this,
                    items: [
                        {
                            icon: 'gui/images/report_generator/delete_report.png',
                            tooltip: 'Delete Request',
                            iconCls: 'data-export-action-icon',
                            handler: function (grid, rowIndex) {
                                this.deleteRequest(grid.store.getAt(rowIndex));
                            }
                        },
                        {
                            icon: 'gui/images/report_generator/download_report.png',
                            tooltip: 'Download Exported Data',
                            getClass: function (state) {
                                return 'data-export-action-icon' + (state !== 'Available' ? '-hidden': '');
                            },
                            handler: function (grid, rowIndex) {
                                this.downloadRequest(grid.store.getAt(rowIndex));
                            }
                        },
                        {
                            icon: 'gui/images/arrow_redo.png',
                            tooltip: 'Resumbit Request',
                            getClass: function (state) {
                                return 'data-export-action-icon' + (state !== 'Expired' && state !== 'Failed' ? '-hidden': '');
                            },
                            handler: function (grid, rowIndex) {
                                this.resubmitRequest(grid.store.getAt(rowIndex));
                            }
                        }
                    ]
                }
            ],
            bbar: [
                {
                    xtype: 'button',
                    text: 'Delete all expired requests',
                    scope: this,
                    handler: this.deleteExpiredRequests
                },
                '->',
                {
                    xtype: 'paging',
                    store: this.store,
                    pageSize: this.pageSize,
                    displayInfo: true,
                    displayMsg: 'Displaying export requests {0} - {1} of {2}',
                    emptyMsg: 'No export requests to display'
                }
            ]
        });

        XDMoD.Module.DataExport.RequestsGrid.superclass.initComponent.call(this);
    },

    reload: function () {
        this.store.reload();
    },

    deleteExpiredRequests: function () {
        Ext.Msg.confirm(
            'Delete All Expired Requests',
            'Are you sure that you want to delete all expired requests? You cannot undo this operation.',
            function (selection) {
                if (selection === 'yes') {
                    Ext.Msg.alert('TODO', 'TODO: Delete all the expired requests');
                }
            },
            this
        );
    },

    deleteRequest: function (record) {
        Ext.Msg.confirm(
            'Delete Request',
            'Are you sure that you want to delete this request? You cannot undo this operation.',
            function (selection) {
                if (selection === 'yes') {
                    console.log({ 'delete': record });
                    Ext.Msg.alert('TODO', 'TODO: Delete the request');
                }
            },
            this
        );
    },

    downloadRequest: function (record) {
        // TODO
        console.log({ download: record });
    },

    resubmitRequest: function (record) {
        // TODO
        console.log({ resubmit: record });
    }
});

XDMoD.Module.DataExport.createRequestHelpText =
'Create a new request to bulk export data from the data warehouse. Date ' +
'ranges are inclusive and are limited to one year. When the exported data is ' +
'ready you will receive an email notification.';

XDMoD.Module.DataExport.exportStatusHelpText =
'Bulk data export requests and their current statuses.<br><br>' +
'Status descriptions:<br>' +
'<b>Submitted</b>: Request has been submitted, but exported data is not yet ' +
'available.<br>' +
'<b>Available</b>: Requested data is available for download.<br>' +
'<b>Expired</b>: Requested data has expired and is no longer available.<br>' +
'<b>Failed</b>: Data export failed. Submit a support request for more ' +
'information.';

/**
 * Data warehouse export batch requests data store.
 */
XDMoD.Module.DataExport.RequestsStore = Ext.extend(Ext.data.JsonStore, {
    constructor: function (c) {
        var config = c || {};
        Ext.apply(config, {
            url: 'rest/v1/warehouse/export/requests',
            root: 'data',
            fields: [
                {
                    name: 'id',
                    type: 'int'
                },
                {
                    name: 'realm',
                    type: 'string'
                },
                {
                    name: 'start_date',
                    type: 'date',
                    dateFormat: 'Y-m-d'
                },
                {
                    name: 'end_date',
                    type: 'date',
                    dateFormat: 'Y-m-d'
                },
                {
                    name: 'export_file_format',
                    type: 'string'
                },
                {
                    name: 'requested_datetime',
                    type: 'date',
                    dateFormat: 'Y-m-d H:i:s'
                },
                {
                    name: 'export_created_datetime',
                    type: 'date',
                    dateFormat: 'Y-m-d H:i:s'
                },
                {
                    name: 'export_expires_datetime',
                    type: 'date',
                    dateFormat: 'Y-m-d H:i:s'
                },
                {
                    name: 'export_expired',
                    type: 'boolean'
                },
                {
                    name: 'state',
                    type: 'string'
                }
            ]
        });

        XDMoD.Module.DataExport.RequestsStore.superclass.constructor.call(this, config);
    }
});
