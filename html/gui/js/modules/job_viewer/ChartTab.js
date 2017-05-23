Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

/**
 * @class XDMoD.Module.JobViewer.ChartTab
 * @extends Ext.Panel
 * <p>A specialized panel intended for the display of timeseries data from an Ext.store into
 * a Highcharts chart in the job viewer.</p>
 * <p> The derived class should implement the updateChart event listener that is fired when
 * ever data is loaded from the store.
 * @constructor
 * @param {Object} config The config object
 */
XDMoD.Module.JobViewer.ChartTab = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Object} chartSettings
     * The Highcharts chart settings. This settings object overrides the default values.
     *
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
     * is set the the pageSize. If undefined then no paging is done (default undefined).
     */

    chart: null,
    store: null,
    displayTimezone: 'UTC',

    initComponent: function () {
        var self = this;

        var createChart = function () {
            var defaultChartSettings = {
                chart: {
                    renderTo: self.id + '_hc'
                },
                colors: ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970', '#f28f43', '#77a1e5', '#c42525', '#a6c96a'],
                title: {
                    style: {
                        color: '#444b6e',
                        fontSize: '16px'
                    },
                    text: ''
                },
                loading: {
                    style: {
                        opacity: 0.7
                    }
                },
                yAxis: {
                    title: {
                        style: {
                            fontWeight: 'bold',
                            color: '#5078a0'
                        }
                    }
                },
                legend: {
                    enabled: false
                },
                exporting: {
                    enabled: false
                },
                tooltip: {
                    pointFormat: '<span style="color:{series.color}">●</span> {series.name}: <b>{point.low:%A, %b %e, %H:%M:%S}</b> - <b>{point.high:%A, %b %e, %H:%M:%S}</b><br/>',
                    dateTimeLabelFormats: {
                        millisecond: '%A, %b %e, %H:%M:%S.%L %T',
                        second: '%A, %b %e, %H:%M:%S %T',
                        minute: '%A, %b %e, %H:%M:%S %T',
                        hour: '%A, %b %e, %H:%M:%S %T'
                    }
                },
                plotOptions: {
                    line: {
                        marker: {
                            enabled: false
                        }
                    },
                    columnrange: {
                        minPointLength: 3,
                        animation: false,
                        dataLabels: {
                            enabled: false
                        }
                    },
                    series: {
                        allowPointSelect: false,
                        animation: false
                    }
                }
            };

            var chartOptions = jQuery.extend(true, {}, defaultChartSettings, self.chartSettings);

            self.chart = new Highcharts.Chart(chartOptions);
            self.chart.showLoading();

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
                        while (self.chart.series.length > 0) {
                            self.chart.series[0].remove(true);
                        }
                        var text = self.chart.renderer.text('ERROR ' + response.status + ' ' + response.statusText, self.chart.plotLeft + 23, self.chart.plotTop + 10).add();
                        var box = text.getBBox();
                        self.chart.renderer.image('/gui/images/about_16.png', box.x - 23, box.y - 1, 16, 16).add();
                        self.chart.hideLoading();
                        self.chart.redraw();
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

        var storeSettings = jQuery.extend(true, {}, defaultStoreSettings, this.panelSettings.store);

        this.store = new Ext.data.JsonStore(storeSettings);

        this.layout = 'fit';
        this.items = [{
            xtype: 'container',
            id: this.id + '_hc',
            listeners: {
                resize: function () {
                    if (self.chart) {
                        self.chart.reflow();
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

    updateTimezone: function (timezone) {
        this.displayTimezone = timezone;
        this.setHighchartTimezone();
    },

    setHighchartTimezone: function () {
        Highcharts.setOptions({
            global: {
                timezone: this.displayTimezone
            }
        });
    },

    listeners: {
        activate: function () {
            this.setHighchartTimezone();
            Ext.History.add(this.historyToken);
        },
        destroy: function () {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        }
    }
});
