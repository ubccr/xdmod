Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

/**
 * This component is used when displaying any general purpose chart to a user.
 * In the Single Job Viewer context it is being used to display the Timeseries
 * data charts.
 */
XDMoD.Module.JobViewer.ChartPanel = Ext.extend(Ext.Panel, {

    // The default chart config options.
    _DEFAULT_CONFIG: {
        chartPrefix: 'CreateChartPanel',
        chartOptions: {
            chart: {
                type: 'line',
                zoomType: 'x'
            },
            // Specify Highcharts v.3 default colors for plotting Job Viewer Timeseries data
            colors: ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970',
                        '#f28f43', '#77a1e5', '#c42525', '#a6c96a'],
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
            xAxis: {
                type: 'datetime',
                minTickInterval: 1000,
                title: {
                    style: {
                        fontWeight: 'bold',
                        color: '#5078a0'
                    },
                    text: 'Time'
                }
            },
            yAxis: {
                title: {
                    style: {
                        fontWeight: 'bold',
                        color: '#5078a0'
                    },
                    text: 'Units'
                },
                min: 0.0
            },
            legend: {
                enabled: false
            },
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuClassName: 'job-viewer-timeseries-chart-contextmenu',
                        symbol: 'menu',
                        title: 'Chart context menu',
                        menuItems: [
                        {
                            text: 'Print chart',
                            onclick: function () {
                                this.print();
                            }
                        },
                        {
                            separator: true
                        },
                        {
                            text: 'Download PNG image',
                            onclick: function () {
                                document.location = this.userOptions.dataurl + '&filetype=image/png';
                            }
                        },
                        {
                            text: 'Download SVG image',
                            onclick: function () {
                                document.location = this.userOptions.dataurl + '&filetype=image/svg';
                            }
                        },
                        {
                            separator: true
                        },
                        {
                            text: 'Download CSV data',
                            onclick: function () {
                                document.location = this.userOptions.dataurl + '&filetype=text/csv';
                            }
                        }
                        ]
                    }
                }
            },
            tooltip: {
                dateTimeLabelFormats: {
                    millisecond:"%A, %b %e, %H:%M:%S.%L %T",
                    second:"%A, %b %e, %H:%M:%S %T",
                    minute: "%A, %b %e, %H:%M:%S %T",
                    hour: "%A, %b %e, %H:%M:%S %T"
                }
            },
            plotOptions: {
                line: {
                    marker: {
                        enabled: false
                    }
                },
                series: {
                    allowPointSelect: false,
                    animation: false
                }
            }
        }
    },

    // The HighCharts chart instance.
    chart: null,

    /**
     * The component 'constructor'.
     */
    initComponent: function() {

        this.options = this.options || {};

        this.loaded = false;

        jQuery.extend(true, this.options, this._DEFAULT_CONFIG.chartOptions);
        XDMoD.Module.JobViewer.ChartPanel.superclass.initComponent.call(this, arguments);

        // ADD: store listeners ( if we have a store )
        this._addStoreListeners(this.store);

        // ADD: The custom events that we're listening for.
        this.addEvents(
            'load_record',
            'record_loaded'
        );

        var self = this;

        // We need this for some of it's helper functions.
        var jv = this.jobViewer;
        this.store.proxy.on('beforeload', function(proxy){
            var path = self.path;
            var token = jv._createHistoryTokenFromArray(path);
            self.loaded = true;
            var url = self.baseUrl + '?' + token + '&token=' + XDMoD.REST.token;
            proxy.setUrl(url, true);
        });
        this.store.on('load', function(store, records, params) {
            self.doLayout();
        });

        this.displayTimezone = 'UTC';
    }, // initComponent

    setHighchartTimezone: function() {
        Highcharts.setOptions({
            global: {
                timezone: this.displayTimezone
            }
        });
    },

    listeners: {

        /**
         *
         */
        activate: function(tab, reload) {

            this.setHighchartTimezone();

            reload = reload || false;
            // This is here so that when the chart is / panel is loaded
            // via one of it's child nodes that it triggers a re-load.
            if (reload === true) {
                this.chart.showLoading();
                this.store.load();
            }
        }, // activate

        /**
         *
         */
        render: function() {
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
        resize: function(panel, adjWidth, adjHeight, rawWidth, rawHeight) {
            if (this.chart !== null && this.chart !== undefined) {
                this.chart.setSize(adjWidth, adjHeight);
            }
            this.options.chart.width = adjWidth;
            this.options.chart.height = adjHeight;
        }, // resize

        /**
         *
         * @param panel
         * @param record
         */
        load_record: function(panel, record) {

            var self = this;

            var chartClickHandler = function(event) {
                var userOptions = this.series.userOptions;
                if (!userOptions || !userOptions.dtype) {
                    return;
                }
                var drilldown;
                /*
                 * The drilldown data are stored on each point for envelope
                 * plots and for the series for simple plots.
                 */
                if (userOptions.dtype == 'index') {
                    drilldown = {
                        dtype: userOptions.index,
                        value: event.point.options[userOptions.index]
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
            };

            var chartOptions = this.options;
            chartOptions.chart.renderTo = this.id;
            if (record !== null && record !== undefined) {
                chartOptions.series = record.data.series;
                chartOptions.yAxis.title.text = record.data.schema.units;
                chartOptions.xAxis.title.text = 'Time (' + record.data.schema.timezone + ')';
                chartOptions.title.text = record.data.schema.description;
                chartOptions.dataurl = record.store.proxy.url;
                this.displayTimezone = record.data.schema.timezone;

                this.setHighchartTimezone();

                var hasMultipleXValues = false;
                var firstXValue = null;

                for ( var i = 0; i < chartOptions.series.length; i++) {
                    var series = chartOptions.series[i];

                    var header = '<span style="font-size: 10px">{point.key}</span><br/>';
                    if (series.dtype === 'index') {
                        header = '<span style="font-size: 10px; color:{point.series.color};">[ {point.point.options.qtip} ]: <span style="font-size: 10px; color: #000">{point.key}</span></span><br/>';
                    }

                    series.tooltip = {
                        headerFormat: header
                    };

                    series.point = {
                        events: {
                            click: chartClickHandler
                        }
                    };

                    // If the data series only contains one point, enable
                    // point markers so that it is visible.
                    if (series.data.length === 1) {
                        series.marker = {
                            enabled: true
                        };
                    }

                    // Check for the presence of multiple x-axis values
                    // (if this fact has not already been established).
                    if (!hasMultipleXValues) {
                        for (var j = 0; j < series.data.length; j++) {
                            var pointXValue = series.data[j].x;
                            if (firstXValue === null) {
                                firstXValue = pointXValue;
                                continue;
                            }

                            if (firstXValue !== pointXValue) {
                                hasMultipleXValues = true;
                                break;
                            }
                        }
                    }
                }

                // If there are not multiple x-axis values, manually specify
                // a minimum range for the x-axis so that it can be rendered.
                if (!hasMultipleXValues) {
                    chartOptions.xAxis.minRange = 2000;
                }

                if (record.data.schema.help) {
                    panel.helptext.documentation = record.data.schema.help;
                    this.jobTab.fireEvent("display_help", panel.helptext);
                }
            }
            panel.chart = new Highcharts.Chart(chartOptions);
            if (!record) {
                panel.chart.showLoading();
            }
            panel.fireEvent('record_loaded');
            if (CCR.isType(panel.layout, CCR.Types.Object) && panel.rendered) panel.doLayout();
        } // load_record

    }, // listeners

    /**
     *
     * @param series
     * @returns {*}
     * @private
     */
    _findDtype: function(series) {
        if (!CCR.isType(series, CCR.Types.Array)) return null;

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
     * data store. This listener will ensure that each time the store receives
     * a load event, if there is at least one record, then this components
     * 'load_record' event will be fired with a reference to the first record
     * returned.
     *
     * @param store to be listened to.
     * @private
     */
    _addStoreListeners: function(store) {
        if ( typeof store === 'object') {
            var self = this;
            store.on('load', function(tor, records, options) {
                if (tor.getCount() == 0) {
                    return;
                }
                var record = tor.getAt(0);
                if (CCR.exists(record) &&
                   CCR.exists(self.chart)) {
                    self.chart = null;
                    self.fireEvent('load_record', self, record);
                } else if (CCR.exists(record)) {
                    self.fireEvent('load_record', self, record);
                }
            });
        }
    } // _addStoreListeners
});
