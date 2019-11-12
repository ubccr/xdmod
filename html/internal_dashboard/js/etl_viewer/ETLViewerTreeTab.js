Ext.namespace('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

XDMoD.Admin.ETL.ETLViewerTreeTab = Ext.extend(Ext.Panel, {
    title: 'ETL Tree View',
    layout: 'border',

    initComponent: function () {
        this.tree = new XDMoD.Admin.ETL.ETLViewerTreePanel({
            region: 'center',
            parentTab: this
        });


        Ext.apply(this, {
            items: [
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
