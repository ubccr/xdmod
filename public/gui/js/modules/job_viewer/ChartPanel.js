Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

/**
 * This component is used when displaying any general purpose chart to a user.
 * In the Single Job Viewer context it is being used to display the Timeseries
 * data charts.
 */
XDMoD.Module.JobViewer.ChartPanel = Ext.extend(Ext.Panel, {

    // The chart instance.
    chart: null,

    /**
     * The component 'constructor'.
     */
    initComponent: function () {
        this.options = this.options || {};

        this.loaded = false;

        XDMoD.Module.JobViewer.ChartPanel.superclass.initComponent.call(this, arguments);

        // ADD: store listeners ( if we have a store )
        this._addStoreListeners(this.store);

        // ADD: The custom events that we're listening for.
        this.addEvents('load_record', 'record_loaded');

        var self = this;

        // We need this for some of it's helper functions.
        var jv = this.jobViewer;
        this.store.proxy.on('beforeload', function (proxy) {
            var path = self.path;
            var token = jv._createHistoryTokenFromArray(path);
            self.loaded = true;
            var url = self.baseUrl + '?' + token + '&token=' + XDMoD.REST.token;
            proxy.setUrl(url, true);
        });
        this.store.on('load', function (store, records, params) {
            self.doLayout();
        });

        this.displayTimezone = 'UTC';
    }, // initComponent

    listeners: {
        /**
         *
         */
        activate: function (tab, reload) {
            reload = reload || false;
            // This is here so that when the chart is / panel is loaded
            // via one of it's child nodes that it triggers a re-load.
            if (reload === true) {
                tab.getEl().mask('Loading...');
                this.store.load();
            }
        }, // activate

        /**
         *
         */
        render: function () {
            if (this.store && this.store.getCount() > 0) {
                var record = this.store.getAt(0);
                this.fireEvent('load_record', this, record);
            } else {
                this.fireEvent('load_record', this, null);
            }
        }, // render

        /**
         *
         * @param panel
         * @param adjWidth
         * @param adjHeight
         * @param rawWidth
         * @param rawHeight
         */
        resize: function (panel, adjWidth, adjHeight, rawWidth, rawHeight) {
            if (panel.chart) {
                Plotly.relayout(this.id, { width: adjWidth, height: adjHeight });
            }
        }, // resize

        beforedestroy: function () {
            if (this.chart) {
                Plotly.purge(this.id);
                this.chart = false;
            }
        },

        export_option_selected: function (exportParams) {
            document.location = this.dataurl + '&' + Ext.urlEncode(exportParams);
        },

        print_clicked: function () {
            if (this.chart) {
                var chartDiv = document.querySelector('#' + this.id);
                chartDiv = chartDiv.firstChild.firstChild; // parent div of the plotly SVGs

                // Make deep copy
                var tmpWidth = Ext.apply(chartDiv.clientWidth, {});
                var tmpHeight = Ext.apply(chartDiv.clientHeight, {});

                // Resize to 'medium' export width and height -- Currently placeholder width and height
                Plotly.relayout(this.id, { width: 916, height: 484 });

                // Combine Plotly svg elements similar to export
                var plotlyChart = chartDiv.children[0].outerHTML;
                var plotlyLabels = chartDiv.children[2].innerHTML;

                plotlyChart = plotlyChart.substring(0, plotlyChart.length - 6);
                var svg = plotlyChart + plotlyLabels + '</svg>';

                var printWindow = window.open();
                printWindow.document.write('<html> <head> <title> Printing </title> </head> </html>');
                printWindow.document.write(svg);
                printWindow.print();
                printWindow.close();

                Plotly.relayout(this.id, { width: tmpWidth, height: tmpHeight });
            }
        },

        /**
         *
         * @param panel
         * @param record
         */
        load_record: function (panel, record) {
            var self = this;

            if (record !== null && record !== undefined) {
                this.dataurl = record.store.proxy.url;
                this.displayTimezone = record.data.schema.timezone;
                if (record.data.schema.help) {
                    panel.helptext.documentation = record.data.schema.help;
                    this.jobTab.fireEvent('display_help', panel.helptext);
                }
            }

            if (record) {
                var chartOptions = generateChartOptions(record);
                panel.getEl().unmask();

                if (panel.chart) {
                    Plotly.react(this.id, chartOptions.data, chartOptions.layout, { displayModeBar: false, doubleClick: 'reset' });
                } else {
                    Plotly.newPlot(this.id, chartOptions.data, chartOptions.layout, { displayModeBar: false, doubleClick: 'reset' });

                    panel.chart = document.getElementById(this.id);
                    panel.chart.on('plotly_click', function (data, event) {
                        var userOptions = data.points[0].data.chartSeries;
                        if (!userOptions || !userOptions.dtype) {
                            return;
                        }
                        var drilldown;
                        /*
                         * The drilldown data are stored on each point for envelope
                         * plots and for the series for simple plots.
                         */
                        if (userOptions.dtype === 'index') {
                            var nodeidIndex = data.points[0].pointIndex;
                            if (nodeidIndex === -1) {
                                return;
                            }
                            drilldown = {
                                dtype: userOptions.index,
                                value: userOptions.data[nodeidIndex].nodeid
                            };
                        } else {
                            drilldown = {
                                dtype: userOptions.dtype,
                                value: userOptions[userOptions.dtype]
                            };
                        }
                        var path = self.path.concat([drilldown]);
                        var token = self.jobViewer.module_id + '?' + self.jobViewer._createHistoryTokenFromArray(path);
                        Ext.History.add(token);
                    });
                }
            }

            if (!record) {
                panel.getEl().mask('Loading...');
            }
            panel.fireEvent('record_loaded');
            if (CCR.isType(panel.layout, CCR.Types.Object) && panel.rendered) {
                panel.doLayout();
            }
        } // load_record

    }, // listeners

    /**
     *
     * @param series
     * @returns {*}
     * @private
     */
    _findDtype: function (series) {
        if (!CCR.isType(series, CCR.Types.Array)) {
            return null;
        }

        var result = null;
        for (var i = 0; i < series.length; i++) {
            var entry = series[i];
            if (CCR.isType(entry.dtype, CCR.Types.String)) {
                result = [entry.dtype, entry[entry.dtype]];
                break;
            }
        }

        return result;
    },

    /**
     * Helper function that adds an explicit 'load' listener to the provided
     * this.series.userOptions;data store. This listener will ensure that each time the store receives
     * a load event, if there is at least one record, then this components
     * 'load_record' event will be fired with a reference to the first record
     * returned.
     *
     * @param store to be listened to.
     * @private
     */
    _addStoreListeners: function (store) {
        if (typeof store === 'object') {
            var self = this;
            store.on('load', function (tor, records, options) {
                if (tor.getCount() === 0) {
                    return;
                }
                var record = tor.getAt(0);
                if (CCR.exists(record)) {
                    self.fireEvent('load_record', self, record);
                }
            });
        }
    } // _addStoreListeners
});
