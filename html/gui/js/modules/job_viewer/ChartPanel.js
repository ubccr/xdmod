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
    },

    // The chart instance.
    chart: null,

    /**
     * The component 'constructor'.
     */
    initComponent: function() {

        this.options = this.options || {};

        this.loaded = false;

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

    listeners: {

        /**
         *
         */
        activate: function(tab, reload) {


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
            if (panel.chart) {
                Plotly.relayout(panel.id, {width: adjWidth, height: adjHeight});
            }
        }, // resize

        destroy: function () {
/*
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
*/
        },

        export_option_selected: function (exportParams) {
            document.location = this.dataurl + '&' + Ext.urlEncode(exportParams);
        },

        print_clicked: function () {
            if (this.chart) {
                this.chart.print();
            }
        },

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

            if (record !== null && record !== undefined) {
                this.dataurl = record.store.proxy.url;
                this.displayTimezone = record.data.schema.timezone;

                if (record.data.schema.help) {
                    panel.helptext.documentation = record.data.schema.help;
                    this.jobTab.fireEvent("display_help", panel.helptext);
                }
            }

            if (record) {
		let colorChoices = ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970',
                        '#f28f43', '#77a1e5', '#c42525', '#a6c96a'];
                let data = [];
		var singleDataPoint = false;
		var tz = moment.tz.zone(record.data.schema.timezone).abbr(record.data.series[0].data[0].x);
		var ymin, ymax;
		if (record.data.series[0].name === "Range"){
			ymin = record.data.series[1].data[0].y;
			ymax = ymin;
		}
		else{
                        ymin = record.data.series[0].data[0].y;
                        ymax = ymin;
		}
                for (let sid = 0; sid < record.data.series.length; sid++) {
		    if (record.data.series[sid].name === "Range") {
			tz = moment.tz.zone(record.data.schema.timezone).abbr(record.data.series[1].data[0].x);
			continue;
		    }	
		    let x = [];
		    let y = [];
		    let colors = colorChoices[sid % 10];
                    for(let i=0; i < record.data.series[sid].data.length; i++) {
			if (record.data.series[sid].data.length == 1){
				singleDataPoint = true;	
			}
			x.push(moment.tz(record.data.series[sid].data[i].x, record.data.schema.timezone).format('Y-MM-DD HH:mm:ss.SSS '));
                        y.push(record.data.series[sid].data[i].y);
                    }

		    if (record.data.series[sid].name === "Median" || record.data.series[sid].name === "Minimum"){
                    	data.push({
                        	x: x,
                        	y: y,
				fill: 'tonexty',
				fillcolor: '#2f7ed8',
				marker: {
                                        size: 0.1,
					color: colors
				},        
				line: {
					width: 2,
                                        color: colors
                                },
                	        hovertemplate:
				"%{x|%A, %b %e, %H:%M:%S.%L} " + tz + "<br>" +
	                        "<span style='color:"+colors+";'>●</span>" +  record.data.series[sid].name + ": <b>%{y}</b>" +
        	                "<extra></extra>",
                	        name: record.data.series[sid].name, chartSeries: record.data.series[sid],  type: 'scatter', mode: 'markers+lines'});
	 	    }
		    else{
			 var trace = {
                                x: x,
                                y: y,
				marker: {
                                        size: 0.1,
                                        color: colors
                                },
                                line: {
                                        width: 2,
                                        color: colors
                                },
                                hovertemplate:
                                "%{x|%A, %b %e, %H:%M:%S.%L} " + tz + "<br>" +
                                "<span style='color:"+colors+";'>●</span>" + record.data.series[sid].name + ":<b>%{y}</b>" +
                                "<extra></extra>",
                                name: record.data.series[sid].name, chartSeries: record.data.series[sid],  type: 'scatter', mode: 'markers+lines'};

			if (singleDataPoint){
				trace.marker.size = 20;
				trace.mode = 'markers';
				delete trace.line;
			} 
			data.push(trace);
		   }
		   var tempyMin = Math.min(...y);
   		   var tempyMax = Math.max(...y);
		   if (tempyMin < ymin) ymin = tempyMin;
		   if (tempyMax > ymax) ymax = tempyMax;
		}
		
                panel.getEl().unmask();
                let layout = {
                    hoverlabel: {
                        bgcolor: 'white'
                    },
                    xaxis: {
                        title: '<b>Time (' + record.data.schema.timezone + ')</b>',
                        titlefont: {
                            family: 'Arial, sans-serif',
                            size: 12,
                            color: '#5078a0'
                        },
                        color: '#606060',
                        ticks: 'outside',
			ticklen: 10,
                        tickcolor: '#c0cfe0',
                        linecolor: '#c0cfe0',
			automargin: true,
                        showgrid: false 
                    },
                    yaxis: {
                        title: '<b>' + record.data.schema.units + '</b>',
                        titlefont: {
                            family: 'Arial, sans-serif',
                            size: 12,
                            color: '#5078a0'
                        },
                        color: '#606060',
			range: [0, ymax + (ymax * 0.2)],
                        rangemode: 'nonnegative',
			gridcolor: 'lightgray',
			automargin: true,
                        linecolor: '#c0cfe0'
                    },
                    title: {
                        text:  record.data.schema.description,
                        font: {
                            color: '#444b6e',
                            size: 16
                        }
                    },
                    hovermode: 'closest',
                    showlegend: false,
		    autosize: true,
                    margin: {
                        t: 50
                    }
                };
                if (panel.chart) {
                    Plotly.react(this.id, data, layout, {displayModeBar: false} );
                } else {
                    Plotly.newPlot(this.id, data, layout, {displayModeBar: false} );
                }

            if (!panel.chart) {
                panel.chart = document.getElementById(this.id);
                panel.chart.on('plotly_click', function(data, event){
                        var userOptions = data.points[0].data.chartSeries
                        if (!userOptions || !userOptions.dtype) {
                            return;
                        }
                        var drilldown;
                        /*
                         * The drilldown data are stored on each point for envelope
                         * plots and for the series for simple plots.
                         */
                        if (userOptions.dtype == 'index') {
                            var nodeidIndex = data.points[0].data.chartSeries.data.findIndex(({y}) => y === data.points[0].y);
                            if (nodeidIndex === -1) return;
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
		/*if (!singleDataPoint){
		panel.chart.on('plotly_hover', function(data){
			if (!data.points) return;
			setTimeout(() => {}, 50);
			console.log(data);
			let idx = data.points[0].pointNumber;
			var sizes = Array(data.points[0].data.x.length).fill(0.1);
			sizes[idx] = 12;
			var update = {
				'marker.size': [sizes],
				'line.width': 3
                        };
                        Plotly.restyle(panel.chart, update, data.points[0].curveNumber);
		});
		panel.chart.on('plotly_unhover', function(data){
			if (!data.points) return;
			var update = {
				line:{
					width: 2,
					color: data.points[0].data.line.color
				     },
				marker:{
					size: 0.1,
					color: data.points[0].data.marker.color
				}
			};
                        Plotly.restyle(panel.chart, update, data.points[0].curveNumber);
                });
	      }*/

            }
	}
            if (!record) {
                panel.getEl().mask('Loading...');
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
     * this.series.userOptions;data store. This listener will ensure that each time the store receives
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
                if (CCR.exists(record)) {
                    self.fireEvent('load_record', self, record);
                }
            });
        }
    } // _addStoreListeners
});
