/* global Plotly */
Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

XDMoD.Module.JobViewer.VMStateChartPanel = Ext.extend(Ext.Panel, {

    store: null,
    renderChartTo: '',
    chartTitle: '',
    chartData: [],
    initComponent: function () {
        var self = this;

        this.layout = 'fit';
        this.items = [{
            xtype: 'container',
            id: this.id + '_hc',
            listeners: {
                render: function () {
                    self.renderChartTo = this.id;
                    self.fireEvent('renderChart', self);
                }
            }
        }];

        XDMoD.Module.JobViewer.VMStateChartPanel.superclass.initComponent.call(this, arguments);

        this.store.on('load', function (store, records, params) {
            var record = store.getAt(0);
            if (CCR.exists(record)) {
                self.fireEvent('load_record', self, record);
            }
            self.doLayout();
        });
    },
    getChartText: function (record) {
        var chartText = '';
        Object.entries(record).forEach(function (item, key, value) {
            if (item[0].length > 0) {
                chartText += item[0].replace('_', ' ') + ': ' + item[1] + ' <br />';
            }
        });

        return chartText;
    },
    listeners: {
        activate: function () {
            Ext.History.add(this.historyToken);
        },
        load_record: function (panel, record) {
            var self = this;
            // The active_states are event types that correlate to the following events,
            //START = 2, RESUME = 8, STATE_REPORT = 16, const UNSHELVE = 20, const UNSUSPEND = 61,
            //POWER_ON = 59, UNPAUSE_END = 57
            var active_states = [2, 8, 16, 20, 57, 59, 61];

            // The inactive_states are event types that correlate to the following events,
            // STOP = 4, TERMINATE = 6, SUSPEND = 17, SHELVE = 19, POWER_OFF = 45, POWER_OFF_START = 44
            var inactive_states = [4, 6, 17, 19, 45, 55];

            this.chartData = [];

            for (var i = 0; i < record.data.series.vmstates.length; i++) {
                var current = record.data.series.vmstates[i];
                var start_time = new Date(current.start_time).getTime();
                var end_time = new Date(current.end_time).getTime();
                var statusColor = 'white';
                var status = 'Unknown';
                var legend_group = 'unknown';
                var dataseries_name = 'unknown';

                this.chartTitle = 'Timeline for VM ' + current.provider_identifier;

                if (active_states.indexOf(parseInt(current.start_event_type_id, 10)) !== -1) {
                    statusColor = 'green';
                    status = 'Active';
                    legend_group = 'active';
                    dataseries_name = 'VM Active';
                } else if (inactive_states.indexOf(parseInt(current.start_event_type_id, 10)) !== -1) {
                    statusColor = 'red';
                    status = 'Stopped';
                    legend_group = 'stopped';
                    dataseries_name = 'VM Stopped';
                }

                this.chartData.push({
                    x: [start_time, end_time],
                    y: [1, 1],
                    mode: 'lines',
                    type: 'scatter',
                    legendgroup: legend_group,
                    showlegend: i <= 1,
                    opacity: 0.7,
                    line: {
                        color: statusColor,
                        width: 20
                    },
                    text: '<b>Status:</b> ' + status + '<br /> <b>Start Time:</b> ' + current.start_time + '<br /><b>End Time:</b> ' + current.end_time + '<br />',
                    hoverinfo: 'text',
                    name: dataseries_name
                });

                this.chartData.push({
                    y: [1, 1.5],
                    x: [start_time, start_time],
                    mode: 'lines+markers',
                    showlegend: false,
                    hoverinfo: 'skip',
                    line: {
                        color: statusColor,
                        width: 1
                    },
                    markers: {
                        color: statusColor
                    }
                });
            }

            for (var c = 0; c < record.data.series.events.length; c++) {
                var current_record = record.data.series.events[c];
                var event_time = new Date(current_record.Event_Time).getTime();

                this.chartData.push({
                    y: [0.5, 1],
                    x: [event_time, event_time],
                    mode: 'lines+markers',
                    showlegend: false,
                    line: {
                        color: 'black',
                        width: 2
                    },
                    markers: {
                        color: 'black'
                    },
                    text: self.getChartText(current_record)
                });
            }

            var sliderStartDate = new Date(this.sliderRangeEnd);
            sliderStartDate.setDate(sliderStartDate.getDate() - 7);
            if (sliderStartDate.getTime() > this.sliderRangeStart) {
                this.sliderRangeStart = sliderStartDate.getTime();
            }

            this.fireEvent('renderChart', this);
        },
        renderChart: function (panel) {
            if (Object.keys(this.chartData).length !== 0 && this.renderChartTo !== '') {
                var chart_settings = {
                    title: this.chartTitle,
                    xaxis: {
                        rangeselector: {
                            buttons: [{
                                step: 'day',
                                stepmode: 'backward',
                                count: 7,
                                label: '1w'
                            }, {
                                step: 'month',
                                stepmode: 'backward',
                                count: 1,
                                label: '1m'
                            }, {
                                step: 'month',
                                stepmode: 'backward',
                                count: 6,
                                label: '6m'
                            }, {
                                step: 'year',
                                stepmode: 'backward',
                                count: 1,
                                label: '1y'
                            }, {
                                step: 'all'
                            }]
                        },
                        rangeslider: {},
                        type: 'date'
                    },
                    yaxis: {
                        fixedrange: true,
                        range: [0, 2],
                        ticks: '',
                        showticklabels: false
                    },
                    font: {
                        family: 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                        size: 16,
                        color: '#444b6e'
                    },
                    hovermode: 'x unified',
                    hoverdistance: 1
                };
                var plotlyOpts = { displayModeBar: false };
                Plotly.newPlot(this.renderChartTo, this.chartData, chart_settings, plotlyOpts);
            }
        }
    }
});
