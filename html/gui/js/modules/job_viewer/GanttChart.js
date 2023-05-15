Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

XDMoD.Module.JobViewer.GanttChart = Ext.extend(XDMoD.Module.JobViewer.ChartTab, {

    initComponent: function () {
        this.chartSettings = {
            chart: {
                type: 'columnrange',
                grouping: false,
                inverted: true
            },
            yAxis: {
                type: 'datetime',
                minTickInterval: 1000,
                title: {
                    text: 'Time (' + self.displayTimezone + ')'
                }
            }
        };

        this.panelSettings = {
            pageSize: 11,
            url: this.url,
            baseParams: this.baseParams,
            store: {
                fields: ['series', 'schema', 'categories']
            }
        };

        XDMoD.Module.JobViewer.GanttChart.superclass.initComponent.call(this, arguments);

        this.addListener('updateChart', function (store) {
            if (store.getCount() < 1) {
                return;
            }
            var record = store.getAt(0);
	    console.log("record");
  	    console.log(record);
            this.updateTimezone(record.data.schema.timezone);
            var i;
	    var data = [];
	    var categories = [];
	    var count = 0;
	    var rect = [];
	    var yvals = [0];
	    let colors = ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970', '#f28f43', '#77a1e5', '#c42525', '#a6c96a'];
	    for (i = 0; i < record.data.series.length; i++) {
			var j = 0;
			for (j = 0; j < record.data.series[i].data.length; j++){
				low_data = moment.tz(record.data.series[i].data[j].low, record.data.schema.timezone).format('Y-MM-DD HH:mm:ss.SSS ');
				high_data = moment.tz(record.data.series[i].data[j].high, record.data.schema.timezone).format('Y-MM-DD HH:mm:ss.SSS ');	
				categories.push(record.data.categories[count]);
				var runtime = [];
				let template = record.data.series[i].name + ": " + "<b>" +  moment.tz(record.data.series[i].data[j].low, record.data.schema.timezone).format('ddd, MMM DD, HH:mm:ss z') + "</b> - <b>" + moment.tz(record.data.series[i].data[j].high, record.data.schema.timezone).format('ddd, MMM DD, HH:mm:ss z') + " </b> ";
				var tooltip = [template];
				var ticks = [count];
				var start_time = record.data.series[i].data[j].low;
				
				// Need to create a underlying scatter plot for each peer due to drawing gantt chart with rect shapes.
				// Points on the scatter plot are created every min to create better coverage for tooltip information
				while (start_time < record.data.series[i].data[j].high){
					ticks.push(count);
					tooltip.push(template);
					runtime.push(moment(start_time).format('Y-MM-DD HH:mm:ss z'));
					start_time += (60 * 1000);
				}
				runtime.push(high_data);
				
				rect.push({
					x0: low_data,
					x1: high_data,
					y0: count-0.25,
					y1: count+0.25,
					type: 'rect',
					xref: 'x',
					yref: 'y',
					fillcolor: colors[i % 10],
					color: colors[i % 10]	
				});

				
				var info = {}

				if (i > 0){
					info = {
	                                        realm: record.data.series[i].data[j].ref.realm,
						recordid: store.baseParams.recordid,
                                        	jobref: record.data.series[i].data[j].ref.jobid,
						infoid: 3
                                	};
				}
				 			
				peer = {
					x: runtime,
					y: ticks,
				        type: 'scatter',
			
					marker:{
			  		color: 'rgb(155,255,255)'	
					},
					orientation: 'h',
					hovertemplate: record.data.categories[count] + '<br>'+
					"<span style='color:"+colors[i %10]+";'>●</span>" + '%{text} <extra></extra>',
					text: tooltip,
					chartSeries: info
			        };

				data.push(peer);	
				count++;	
				yvals.push(count);
			}
			
		}
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
                        tickcolor: '#c0cfe0',
                        linecolor: '#c0cfe0',
			type: 'date',
			range: [record.data.series[0].data[0].low - (60 * 1000), record.data.series[0].data[0].high + (60 * 1000)]
                    },
		    yaxis: {
			autorange: 'reversed',
			ticktext: categories, 
			zeroline: false,
			tickvals: yvals
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
                    margin: {
                    	l: 175,
			b: 150    

                    }
                };

		layout['shapes'] = rect;
                Plotly.newPlot(this.id, data, layout, {displayModeBar: false, responsive: true});

		var panel = document.getElementById(this.id);
		panel.on('plotly_click', function(data){
                        var userOptions = data.points[0].data.chartSeries;
			userOptions['action'] = 'show';
			console.log('useroptions');
			console.log(userOptions);
			console.log('url');
			console.log(Ext.urlEncode(userOptions));
                        Ext.History.add('job_viewer?' + Ext.urlEncode(userOptions));

               }); 
            
        });
    }
});
