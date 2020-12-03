Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

XDMoD.Module.JobViewer.VMStateChartPanel = Ext.extend(Ext.Panel, {

  store: null,
  renderChartTo: '',
  chartTitle: '',
  chartData: [],
  sliderRangeStart: null,
  sliderRangeEnd: null,
  initComponent: function () {
      var self = this;

      this.layout = 'fit';
      this.items = [{
          xtype: 'container',
          id: this.id + '_hc',
          listeners: {
              render: function() {
                self.renderChartTo = this.id;
                self.fireEvent('renderChart', self);
              }
          }
      }];

      XDMoD.Module.JobViewer.VMStateChartPanel.superclass.initComponent.call(this, arguments);

      this.store.on('load', function(store, records, params) {
          var record = store.getAt(0);
          if (CCR.exists(record)) {
              self.fireEvent('load_record', self, record);
          }
          self.doLayout();
      });
  },
  listeners: {
      activate: function () {
          Ext.History.add(this.historyToken);
      },
      load_record: function(panel, record) {

        var active_states = [2,8,16,20,57,59,61];
        var inactive_states = [4,6,17,19,45,55];

        this.chartData = [];

        for (i = 0; i < record.data.series.vmstates.length; i++) {
            var current = record.data.series.vmstates[i];
            var start_time = new Date(current.start_time).getTime();
            var end_time = new Date(current.end_time).getTime();
            var statusColor = 'white';
            var status = "Unknown";

            this.chartTitle = "Timeline for VM " + current.provider_identifier;

            if (this.sliderRangeEnd < end_time && this.sliderRangeEnd === null) {
                this.sliderRangeEnd = end_time;
            }

            if (this.sliderRangeStart < start_time && this.sliderRangeStart === null) {
                this.sliderRangeStart = start_time;
            }

            if( active_states.indexOf(parseInt(current.start_event_type_id)) != -1) {
                statusColor = 'green';
                status = 'Active';
                legend_group = 'active';
                dataseries_name = 'VM Active';
            } else if ( inactive_states.indexOf(parseInt(current.start_event_type_id)) != -1) {
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
              showlegend: (i <= 1) ? true : false,
              opacity: 0.7,
              line: {
                color: statusColor,
                width: 20
              },
              text: "<b>Status:</b> "+ status +"<br /> <b>Start Time:</b> "+current.start_time+"<br /><b>End Time:</b> "+current.end_time+"<br />",
              hoverinfo: 'text',
              name: dataseries_name
            });
        }

        for (i = 0; i < record.data.series.events.length; i++) {
            var current = record.data.series.events[i];
            var event_time = new Date(current.Event_Time).getTime();
            var chartMsg = '';

            Object.entries(current).forEach(function(item, key, value){
                if( item[0].length > 0 ) {
                    chartMsg += item[0].replace('_', ' ') + ": " +item[1] + ' <br />';
                }
            });

            this.chartData.push({
              y: [.5, 1],
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
              text: chartMsg
            });
        }

        var sliderStartDate = new Date(this.sliderRangeEnd);
        sliderStartDate.setDate(sliderStartDate.getDate() - 7);
        if (sliderStartDate.getTime() > this.sliderRangeStart) {
            this.sliderRangeStart = sliderStartDate.getTime();
        }

        this.fireEvent('renderChart', this);
     },
     renderChart: function(panel) {
        if(Object.keys(this.chartData).length !== 0 && this.renderChartTo != ''){
            var chart_settings = {
                title: this.chartTitle,
                xaxis: {
                    rangeselector: {},
                    rangeslider: {
                        range: [this.sliderRangeStart, this.sliderRangeEnd]
                    },
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
            Plotly.newPlot(this.renderChartTo, this.chartData, chart_settings, {displayModeBar: false});
        }
     }
  }
})
