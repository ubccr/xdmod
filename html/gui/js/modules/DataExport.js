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
    _DEFAULT_PAGE_SIZE: 24,


    initComponent: function () {
        var requestStore = new Ext.data.ArrayStore({
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
                    'Pending',
                    '2018-05-16',
                    null
                ],
                [
                    'Jobs',
                    '2017-01-01',
                    '2017-12-31',
                    'CSV',
                    'Available',
                    '2018-05-16',
                    '2018-07-01'
                ]
            ]
        });

        Ext.apply(this, {
            items: [
                {
                    xtype: 'form',
                    title: 'Request Data Export',
                    region: 'west',
                    width: 375,
                    bodyStyle:'padding:5px',
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
                },
                {
                    xtype: 'grid',
                    title: 'Status of Export Requests',
                    region: 'center',
                    // TODO: Replace with remote store.
                    store: requestStore,
                    columns: [
                        {
                            id: 'realm',
                            header: 'Realm',
                            dataIndex: 'realm'
                        },
                        {
                            id: 'start_date',
                            header: 'Export Start Date',
                            dataIndex: 'start_date'
                        },
                        {
                            id: 'end_date',
                            header: 'Export End Date',
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
                    ]
                }
            ]
        });

        XDMoD.Module.DataExport.superclass.initComponent.call(this);
    }
});
