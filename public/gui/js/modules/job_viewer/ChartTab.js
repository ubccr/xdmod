Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

/**
 * @class XDMoD.Module.JobViewer.ChartTab
 * @extends Ext.Panel
 * <p>A specialized panel intended for the display of timeseries data from an Ext.store into
 * a Plotly chart in the job viewer.</p>
 * <p> The derived class should implement the updateChart event listener that is fired when
 * ever data is loaded from the store.
 * @constructor
 * @param {Object} config The config object
 */
XDMoD.Module.JobViewer.ChartTab = Ext.extend(Ext.Panel, {
    /**
     * @cfg {string} panelSettings.url
     * The url to connect to
     *
     * @cfg {string} panelSettings.baseParams
     * The baseParams for the store
     *
     * @cfg {Object} panelSettings.store
     * Overrides for any store settings.
     *
     * @cfg {number} panelSettings.pageSize
     * If defined then the paging toolbar is enabled and the number of records to request from the store
     * is set the pageSize. If undefined then no paging is done (default undefined).
     */

    chart: null,
    store: null,
    displayTimezone: 'UTC',

    initComponent: function () {
        var self = this;

        var createChart = function () {
            this.chart = Plotly.newPlot(this.id, [], [], { displayModeBar: false, doubleClick: 'reset' });
            var storeParams;
            if (self.panelSettings.pageSize) {
                storeParams = {
                    params: {
                        start: 0,
                        limit: self.panelSettings.pageSize
                    }
                };
            }
            self.store.load(storeParams);
        };

        var defaultStoreSettings = {
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: this.panelSettings.url,
                listeners: {
                    exception: function (proxy, type, action, options, response) {
                        var update = {
                            annotations: [
                                {
                                    text: 'ERROR ' + response.status + ' ' + response.statusText,
                                    align: 'center',
                                    xref: 'paper',
                                    yref: 'paper',
                                    font: {
                                        size: 12,
                                        family: 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif'
                                    },
                                    x: 0.5,
                                    y: 0.5,
                                    showarrow: false
                                }
                            ],
                            images: [
                                {
                                    source: '/gui/images/about_16.png',
                                    align: 'center',
                                    xref: 'paper',
                                    yref: 'paper',
                                    x: 0.45,
                                    y: 0.55,
                                    sizex: 0.1,
                                    sizey: 0.1
                                }
                            ],
                            xaxis: {
                                showgrid: false,
                                showline: false,
                                showticklabels: false,
                                zeroline: false
                            },
                            yaxis: {
                                showgrid: false,
                                showline: false,
                                showticklabels: false,
                                zeroline: false
                            }
                        };
                        this.chart = Plotly.react(this.id, [], update, { staticPlot: true });
                    }
                }
            }),
            baseParams: this.panelSettings.baseParams,
            autoLoad: false,
            root: 'data',
            fields: ['series', 'schema'],
            listeners: {
                load: function (inst, record, options) {
                    self.fireEvent('updateChart', inst, record, options);
                }
            }
        };

        var storeSettings = XDMoD.utils.deepExtend({}, defaultStoreSettings, this.panelSettings.store);

        this.store = new Ext.data.JsonStore(storeSettings);

        this.layout = 'fit';
        this.items = [{
            xtype: 'container',
            id: this.id + '_hc',
            listeners: {
                resize: function (panel, adjWidth, adjHeight, rawWidth, rawHeight) {
                    if (this.chart) {
                        Plotly.relayout(this.id, { width: adjWidth, height: adjHeight });
                    }
                },
                render: createChart
            }
        }];

        if (this.panelSettings.pageSize) {
            this.bbar = new Ext.PagingToolbar({
                pageSize: this.panelSettings.pageSize,
                buttonAlign: 'right',
                store: this.store,
                listeners: {
                    load: function (store, records, options) {
                        this.onLoad(store, records, options);
                    }
                }
            });
        }

        XDMoD.Module.JobViewer.ChartTab.superclass.initComponent.call(this, arguments);
    },

    listeners: {
        activate: function () {
            Ext.History.add(this.historyToken);
        },
        beforedestroy: function () {
            if (this.chart) {
                Plotly.purge(this.id);
                this.chart = false;
            }
        }
    }
});
