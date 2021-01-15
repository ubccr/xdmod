Ext.namespace('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

XDMoD.Admin.ETL.ETLViewerTreeTab = Ext.extend(Ext.Panel, {
    title: 'ETL Tree View',
    layout: 'border',

    initComponent: function () {

        this.fileSelect = new Ext.form.ComboBox({
            fieldLabel: 'ETL File',
            valueField: 'name',
            displayField: 'name',
            typeAhead: true,
            lazyRender: true,
            triggerAction: 'all',
            store: new Ext.data.JsonStore({
                autoLoad: true,
                url: XDMoD.REST.url  + '/etl/files',
                root: 'results',
                idProperty: 'name',
                fields: ['name']
            })
        });

        this.searchPanel = new Ext.Panel({
            title: 'Search',
            region: 'west',
            split: true,
            collapsible: true,
            collapsed: false,
            collapseFirst: false,
            width: 375,
            layout: 'border',
            margins: '2 0 2 2',
            border: true,
            items: [
                {
                    xtype: 'form',
                    layout: 'fit',
                    region: 'center',
                    height: 90,
                    border: false,
                    items: [
                        {
                            xtype: 'fieldset',
                            header: false,
                            layout: 'form',
                            hideLabels: false,
                            border: false,
                            defaults: {
                                anchor: '0'
                            },
                            items: [
                                this.fileSelect
                            ]
                        }
                    ]
                }
            ]
        });

        this.tree = new Ext.ux.tree.TreeGrid({
            region: 'center',
            closable: false,
            updateHistory: true,
            title: 'ETL Pipeline Viewer',
            autoScroll: true,
            enableDD: true,
            dataUrl: XDMoD.REST.url  + '/etl/pipelines/actions',
            columns: [
                {
                    header: 'Name',
                    dataIndex: 'name',
                    width: 500
                },
                {
                    header: 'Value',
                    dataIndex: 'value',
                    width: 500
                }
            ]
        });

        Ext.apply(this, {
            items: [
                this.searchPanel,
                this.tree
            ]
        });
        XDMoD.Admin.ETL.ETLViewerTreeTab.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        expand_node: function (path) {
            this.tree.fireEvent('expand_node', path);
        }
    }
});
