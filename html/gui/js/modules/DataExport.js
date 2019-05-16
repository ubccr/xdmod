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
        var requestData = [
            ['Jobs', '2018-01-01', '2018-12-31', 'CSV', 'Pending']
        ];

        var requestStore = new Ext.data.ArrayStore({
			fields: [
			   {name: 'realm'},
			   {name: 'start_date'},
			   {name: 'end_date'},
			   {name: 'format'},
			   {name: 'state'}
			]
		});

        requestStore.loadData(requestData);

        Ext.apply(this, {
            items: [
                {
                    xtype: 'form',
                    title: 'Request Data Export',
                    region: 'west',
                    width: 375,
                    layout: 'vbox',
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
                            name: 'start_date'
                        },
                        {
                            xtype: 'datefield',
                            fieldLabel: 'End Date',
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
                        },
                        {
                            xtype: 'button',
                            text: 'Submit Request'
                        }
                    ]
                },
                {
                    xtype: 'grid',
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
                            header: 'Start Date',
                            dataIndex: 'start_date'
                        },
                        {
                            id: 'end_date',
                            header: 'End Date',
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
                        }
                    ]
                }
            ]
        });

        XDMoD.Module.DataExport.superclass.initComponent.call(this);
    }
});
