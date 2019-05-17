// TODO: Replace this with a HTTP proxy JSON store.
var requestsStore = new Ext.data.ArrayStore({
    fields: [
        'realm',
        'start_date',
        'end_date',
        'format',
        'state',
        'requested_date',
        'expires_date'
    ],
    data: [
        [
            'Jobs',
            '2018-01-01',
            '2018-12-31',
            'CSV',
            'Submitted',
            '2018-05-16',
            null
        ],
        [
            'SUPReMM',
            '2017-01-01',
            '2017-12-31',
            'CSV',
            'Available',
            '2018-05-16',
            '2018-07-01'
        ],
        [
            'Jobs',
            '2016-01-01',
            '2016-12-31',
            'CSV',
            'Expired',
            '2018-01-01',
            '2018-05-01',
        ],
        [
            'Jobs',
            '2018-01-01',
            '2018-12-31',
            'JSON',
            'Failed',
            '2018-05-16',
            null
        ]
    ]
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
        // TODO: Replace with JsonStore.
        this.requestsStore = requestsStore;

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
                            fieldLabel: 'Realm',
                            name: 'realm',
                            forceSelection: true,
                            emptyText: 'Select a realm',
                            // TODO: Switch to remote.
                            mode: 'local',
                            valueField: 'id',
                            displayField: 'name',
                            store: new Ext.data.ArrayStore({
                                fields: ['id', 'name'],
                                data: [
                                    ['jobs', 'Jobs'],
                                    ['supremm', 'SUPReMM'],
                                    ['accounts', 'Accounts'],
                                    ['allocations', 'Allocations'],
                                    ['requests', 'Requests'],
                                    ['resource_allocations', 'Resource Allocations']
                                ]
                            })
                        },
                        {
                            xtype: 'datefield',
                            fieldLabel: 'Start Date',
                            emptyText: 'Start Date',
                            name: 'start_date'
                        },
                        {
                            xtype: 'datefield',
                            fieldLabel: 'End Date',
                            emptyText: 'End Date',
                            name: 'end_date'
                        },
                        {
                            xtype: 'combo',
                            fieldLabel: 'Format',
                            name: 'format',
                            mode: 'local',
                            emptyText: 'Select an export format',
                            valueField: 'id',
                            displayField: 'name',
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
                            text: 'Submit Request'
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
                    id: 'realm',
                    header: 'Realm',
                    dataIndex: 'realm'
                },
                {
                    id: 'start_date',
                    header: 'Data Start Date',
                    dataIndex: 'start_date'
                },
                {
                    id: 'end_date',
                    header: 'Data End Date',
                    dataIndex: 'end_date'
                },
                {
                    id: 'format',
                    header: 'Format',
                    dataIndex: 'format'
                },
                {
                    id: 'state',
                    header: 'State',
                    dataIndex: 'state'
                },
                {
                    id: 'requested_date',
                    header: 'Request Date',
                    dataIndex: 'requested_date'
                },
                {
                    id: 'expiration_date',
                    header: 'Expiration Date',
                    dataIndex: 'expires_date'
                }
            ],
            bbar: {
                xtype: 'paging',
                store: this.store,
                pageSize: this.pageSize,
                displayInfo: true,
                displayMsg: 'Displaying export requests {0} - {1} of {2}',
                emptyMsg: 'No export requests to display'
            }
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
