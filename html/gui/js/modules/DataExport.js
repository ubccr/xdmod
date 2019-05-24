var requestsStore = new Ext.data.JsonStore({
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
            convert: function (v, record) {
                // TODO
                if (record.export_expired == '1') {
                    return 'Expired';
                }

                return 'Submitted';
            }
        }
    ],
    proxy: new Ext.data.HttpProxy({
        method: 'GET',
        url: 'rest/v1/warehouse/export/requests'
    })
});

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
        this.requestsStore = requestsStore;

        this.on('afterrender', this.requestsStore.load, this.requestsStore);

        this.requestForm = new XDMoD.Module.DataExport.RequestForm({
            region: 'west',
            width: 375,
            split: true,
            margins: '2 0 2 2'
        });

        this.requestsGrid = new XDMoD.Module.DataExport.RequestsGrid({
            region: 'center',
            margins: '2 2 2 0',
            pageSize: this.defaultPageSize,
            store: this.requestsStore
        });

        this.items = [this.requestsGrid, this.requestForm];

        XDMoD.Module.DataExport.superclass.initComponent.call(this);
    }
});

/**
 * Data export request form.
 */
XDMoD.Module.DataExport.RequestForm = Ext.extend(Ext.form.FormPanel, {
    title: 'Create Bulk Data Export Request',
    bodyStyle: 'padding:5px',

    initComponent: function () {
        Ext.apply(this, {
            tools: [
                {
                    id: 'help',
                    qtip: XDMoD.Module.DataExport.createRequestHelpText
                }
            ],
            items: [
                {
                    xtype: 'fieldset',
                    columnWidth: 1,
                    items: [
                        {
                            xtype: 'combo',
                            name: 'realm',
                            fieldLabel: 'Realm',
                            emptyText: 'Select a realm',
                            valueField: 'id',
                            displayField: 'name',
                            allowBlank: false,
                            editable: false,
                            triggerAction: 'all',
                            mode: 'local',
                            store: new Ext.data.JsonStore({
                                autoLoad: true,
                                autoDestroy: true,
                                root: 'data',
                                fields: ['id', 'name'],
                                proxy: new Ext.data.HttpProxy({
                                    method: 'GET',
                                    url: 'rest/v1/warehouse/export/realms'
                                })
                            })
                        },
                        {
                            xtype: 'datefield',
                            name: 'start_date',
                            fieldLabel: 'Start Date',
                            emptyText: 'Start Date',
                            format: 'Y-m-d',
                            allowBlank: false
                        },
                        {
                            xtype: 'datefield',
                            name: 'end_date',
                            fieldLabel: 'End Date',
                            emptyText: 'End Date',
                            format: 'Y-m-d',
                            allowBlank: false
                        },
                        {
                            xtype: 'combo',
                            name: 'format',
                            fieldLabel: 'Format',
                            emptyText: 'Select an export format',
                            valueField: 'id',
                            displayField: 'name',
                            allowBlank: false,
                            editable: false,
                            triggerAction: 'all',
                            mode: 'local',
                            store: new Ext.data.ArrayStore({
                                fields: ['id', 'name'],
                                data: [
                                    ['csv', 'CSV'],
                                    ['json', 'JSON']
                                ]
                            })
                        }
                    ],
                    buttons: [
                        {
                            xtype: 'button',
                            text: 'Submit Request',
                            scope: this,
                            handler: function () {
                                Ext.Ajax.request({
                                    url: 'rest/v1/warehouse/export/request',
                                    method: 'POST',
                                    params: this.getForm().getValues(),
                                    scope: this,
                                    success: function (response) {
                                        // TODO
                                    },
                                    failure: function (response) {
                                        // TODO
                                    }
                                });
                            }
                        }
                    ]
                }
            ]
        });

        XDMoD.Module.DataExport.RequestForm.superclass.initComponent.call(this);
    }
});

/**
 * Data export request grid.
 */
XDMoD.Module.DataExport.RequestsGrid = Ext.extend(Ext.grid.GridPanel, {
    title: 'Status of Export Requests',

    initComponent: function () {
        Ext.apply(this, {
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
                    dataIndex: 'state'
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
                    xtype: 'templatecolumn',
                    tpl: new Ext.XTemplate(
                        '<img title="Delete" src="gui/images/delete.png"/>',
                        ' <tpl if="state == \'Available\'"><img title="Download" src="gui/images/disk.png"/></tpl>',
                        ' <tpl if="state == \'Expired\' || state == \'Failed\'"><img title="Resubmit" src="gui/images/arrow_redo.png"/></tpl>'
                    )
                }
            ],
            bbar: [
                {
                    xtype: 'button',
                    text: 'Delete all expired requests',
                    handler: function () {
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
                    }
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
