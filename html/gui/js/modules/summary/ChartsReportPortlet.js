Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    autoScroll: true,
    title: 'Charts and Reports',

    initComponent: function () {
        var aspectRatio = 0.8;
        var searchField = new Ext.form.TwinTriggerField({
            xtype: 'twintriggerfield',
            validationEvent: false,
            validateOnBlur: false,
            trigger1Class: 'x-form-clear-trigger',
            trigger2Class: 'x-form-search-trigger',
            hideTrigger1: true,
            enableKeyEvents: true,
            emptyText: 'Search',
            store: this.chartReportStore,
            onTrigger1Click: function () {
                this.store.clearFilter();
                this.el.dom.value = '';
                this.triggers[0].hide();
            }, // onTrigger1Click
            onTrigger2Click: function () {
                var v = this.getRawValue();
                if (v.length < 1) {
                    this.onTrigger1Click();
                    return;
                }
                this.store.filter('name', v, true, true);
                this.triggers[0].show();
            }, // onTrigger2Click
            listeners: {
                scope: this,
                specialkey: function (field, e) {
                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.getKey() === e.ENTER) {
                        searchField.onTrigger2Click();
                    }
                }
            } // listeners
        }); // searchField
        this.chartReportStore = new Ext.data.JsonStore({
            // store configs
            autoDestroy: true,
            url: XDMoD.REST.url + '/summary/chartsreports',
            storeId: 'chartReportStore',
            // reader configs
            root: 'data',
            idProperty: 'name',
            fields: [
                'name',
                'report_id',
                'url',
                'config',
                'type',
                {name: 'recordid', type: 'int'},
                {name: 'ts',
                    convert: function (v, rec) {
                        return Ext.util.Format.date(new Date(rec['ts'] * 1000).toString(), 'Y-m-d h:i:s');
                    }
                }
            ]
        });
        this.chartReportGrid = new Ext.grid.GridPanel({
            store: this.chartReportStore,
            border: false,
            monitorResize: true,
            autoScroll: true,
            viewConfig: {
                forceFit: true
            },
            colModel: new Ext.grid.ColumnModel({
                columns: [
                    {header: 'Name', dataIndex: 'name'},
                    {header: 'Last Modified', dataIndex: 'ts'},
                    {header: 'Type', dataIndex: 'type'}
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
                        if (r.data.type === 'Chart') {
                            selModel.clearSelections();
                            var config = Ext.util.JSON.decode(r.data.config);
                            XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));
                        } else if (r.data.type === 'Report') {
                            selModel.clearSelections();
                            CCR.xdmod.ui.reportGenerator.fireEvent('load_report', r.data.report_id);
                        }

                    }
                }
            })
        }); // chartReportGrid
        this.height = this.width * aspectRatio;
        this.items = [this.chartReportGrid];
        this.chartReportStore.reload();
        XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        duration_change: function (timeframe) {
            this.chartReportStore.reload();
            this.chartReportGrid.getView().refresh();
        }
    }
});

/**
 * The Ext.reg call is used to register an xtype for this class so it
 * can be dynamically instantiated
 */
Ext.reg('ChartsReportsPortlet', XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet);
