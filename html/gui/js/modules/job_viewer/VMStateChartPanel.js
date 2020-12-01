Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

XDMoD.Module.JobViewer.VMStateChartPanel = Ext.extend(Ext.Panel, {

  chart: null,
  store: null,
  renderChartTo: '',
  displayTimezone: 'UTC',
  selectorOptions: {
    buttons: [{
        step: 'month',
        stepmode: 'backward',
        count: 1,
        label: '1m'
    }, {
        step: 'month',
        stepmode: 'backward',
        count: 1,
        label: '1m'
    }, {
        step: 'year',
        stepmode: 'todate',
        count: 1,
        label: 'YTD'
    }, {
        step: 'year',
        stepmode: 'backward',
        count: 1,
        label: '1y'
    }, {
        step: 'all',
    }],
  },
  defaultChartSettings: {
      title: '',
      xaxis: {
          rangeselector: this.selectorOptions,
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
  },
  chartData: [],
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

            this.defaultChartSettings.title = "Timeline for VM " + current.provider_identifier;

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
              showlegend: true,
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

        this.fireEvent('renderChart', this);
     },
     renderChart: function(panel) {
        if(Object.keys(this.chartData).length !== 0 && this.renderChartTo != ''){
            Plotly.newPlot(this.renderChartTo, this.chartData, this.defaultChartSettings, {displayModeBar: false});
        }
     }
  }
})
