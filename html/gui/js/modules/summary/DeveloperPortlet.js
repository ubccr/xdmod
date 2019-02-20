/**
 * XDMoD.Modules.SummaryPortlets.DeveloperPortlet
 *
 * This is an example panel that can be used as a template.
 */

Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.DeveloperPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    autoScroll: true,
    title: 'XDMoD software developer info',
    bbar: {
        items: [{
            text: 'Reset Layout',
            handler: function () {
                Ext.Ajax.request({
                    url: XDMoD.REST.url + '/summary/layout',
                    method: 'DELETE'
                });
            }
        }]
    },

    /**
     *
     */
    initComponent: function () {
        var aspectRatio = 0.8;
        this.height = this.width * aspectRatio;

        this.items = [{
            itemId: 'console',
            html: '<pre>' + JSON.stringify(this.config, null, 4) + '</pre>'
        }];

        XDMoD.Modules.SummaryPortlets.DeveloperPortlet.superclass.initComponent.apply(this, arguments);
    },

    listeners: {
        /**
         * duration_change event gets fired when the duration settings
         * in the top toolbar are changed by the user or when the refresh
         * button is clicked.
         *
         * A typical portlet will reload its content with the updated
         * duration parameters.
         */
        duration_change: function (timeframe) {
            var console = this.getComponent('console');
            console.body.insertHtml('beforeBegin', '<pre>' + JSON.stringify(timeframe, null, 4) + '</pre>');
        }
    }
});

/**
 * The Ext.reg call is used to register an xtype for this class so it
 * can be dynamically instantiated
 */
Ext.reg('DeveloperPortlet', XDMoD.Modules.SummaryPortlets.DeveloperPortlet);


/**
 * 
 * Add Charts and Reports Portlet
 * 
 */


var chartReportRecord = Ext.data.Record.create([
    'name',
    'config',
    'type',
    // { name: 'ts', type: 'date', dateFormat: 'timestamp' },
    {
        name: 'ts', convert: function (v, rec) {
            return Ext.util.Format.date(new Date(rec["ts"] * 1000).toString(), 'Y-m-d h:i:s')
        }
    },
    { name: 'recordid', type: 'int' }
]); // chartReportRecord

var chartReportStore = new Ext.data.Store({
    restful: true,
    method: 'GET',
    url: XDMoD.REST.url + '/summary/chartsreports',
    // groupField: 'type',
    reader: new Ext.data.JsonReader({
        root: 'data',
        idProperty: 'name'
    }, chartReportRecord),
    autoLoad: false
}); // chartReportStore

var searchField = new Ext.form.TwinTriggerField({
    xtype: 'twintriggerfield',
    validationEvent: false,
    validateOnBlur: false,
    trigger1Class: 'x-form-clear-trigger',
    trigger2Class: 'x-form-search-trigger',
    hideTrigger1: true,
    enableKeyEvents: true,
    emptyText: 'Search',
    store: chartReportStore,
    onTrigger1Click: function () {
        XDMoD.TrackEvent('Metric Explorer', 'Cleared chart search field');

        this.store.clearFilter();
        this.el.dom.value = '';
        this.triggers[0].hide();
    }, //onTrigger1Click
    onTrigger2Click: function () {
        var v = this.getRawValue();
        if (v.length < 1) {
            this.onTrigger1Click();
            return;
        }

        XDMoD.TrackEvent('Metric Explorer', 'Using chart search field', Ext.encode({
            search_string: v
        }));

        this.store.filter('name', v, true, true);
        this.triggers[0].show();
    }, //onTrigger2Click
    listeners: {
        scope: this,
        'specialkey': function (field, e) {
            // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
            // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
            if (e.getKey() == e.ENTER) {
                searchField.onTrigger2Click();
            }
        }
    } //listeners

}); //searchField


var chartReportGrid = new Ext.grid.GridPanel({
    store: chartReportStore,
    border: false,
    monitorResize: true,
    autoScroll: true,
    viewConfig: {
        forceFit: true
    },
    colModel: new Ext.grid.ColumnModel({
        columns: [
            { header: "Name", dataIndex: 'name' },
            { header: "Last Modified", dataIndex: 'ts' },
            { header: "Type", dataIndex: 'type' },
        ]

    }),
    tbar: {
        items: [
            searchField
        ]
    },
    selModel: new Ext.grid.RowSelectionModel({
        singleSelect: true,
        listeners: {
            rowselect: function (selModel, index, r) {
                if (r.data.type == 'Chart') {
                    var config = Ext.util.JSON.decode(r.data.config);
                    XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));
                } else if (r.data.type == 'Report') {
                    selModel.clearSelections();
                    var tabPanel = Ext.getCmp('main_tab_panel');
                    tabPanel.setActiveTab('report_generator');
                    var queueGrid = Ext.getCmp('reportPool_queueGrid');
                    if (queueGrid.rowToSelect !== undefined) {
                        var selModel = queueGrid.getSelectionModel();
                        selModel.selectRow(r.data.config);
                        queueGrid.fireEvent('rowdblclick', queueGrid, r.data.config);
                    } else {
                        queueGrid.rowToSelect = r.data.config;
                    }
                }

            }
        }
    })
}); // chartReportGrid


XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    autoScroll: true,
    title: 'Charts and Reports',

    initComponent: function () {
        var aspectRatio = 0.8;
        this.height = this.width * aspectRatio;
        this.items = [chartReportGrid];    
        chartReportStore.reload();    
        XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        duration_change: function (timeframe) {
            chartReportStore.reload();
            chartReportGrid.getView().refresh();
        }
    }
});

Ext.reg('ChartsReportsPortlet', XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet);