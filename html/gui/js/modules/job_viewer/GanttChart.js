Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

XDMoD.Module.JobViewer.GanttChart = Ext.extend(XDMoD.Module.JobViewer.ChartTab, {

    initComponent: function () {

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
            let data = [];
            let categories = [];
            let count = 0;
            let rect = [];
            let yvals = [];
            let colors = ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970', '#f28f43', '#77a1e5', '#c42525', '#a6c96a'];
            for (let i = 0; i < record.data.series.length; i++) {
                for (let j = 0; j < record.data.series[i].data.length; j++){
                    let low_data = moment.tz(record.data.series[i].data[j].low, record.data.schema.timezone).format('Y-MM-DD HH:mm:ss.SSS ');
                    let high_data = moment.tz(record.data.series[i].data[j].high, record.data.schema.timezone).format('Y-MM-DD HH:mm:ss.SSS ');
                    categories.push(record.data.categories[count]);
                    let runtime = [];
                    let template = record.data.series[i].name + ": " + "<b>" +  moment.tz(record.data.series[i].data[j].low, record.data.schema.timezone).format('ddd, MMM DD, HH:mm:ss z') + "</b> - <b>" + moment.tz(record.data.series[i].data[j].high, record.data.schema.timezone).format('ddd, MMM DD, HH:mm:ss z') + " </b> ";
                    let tooltip = [template];
                    let ticks = [count];
                    let start_time = record.data.series[i].data[j].low;
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
                        y0: count-0.175,
                        y1: count+0.175,
                        type: 'rect',
                        xref: 'x',
                        yref: 'y',
                        fillcolor: colors[i % 10],
                    });

                    let info = {};
                    if (i > 0){
                        info = {
                            realm: record.data.series[i].data[j].ref.realm,
                            recordid: store.baseParams.recordid,
                            jobref: record.data.series[i].data[j].ref.jobid,
                            infoid: 3
                        };
                    }
                    let peer = {
                        x: runtime,
                        y: ticks,
                        type: 'scatter',
                        marker:{
                            color: 'rgb(255,255,255)',
                            size: 20
                        },
                        orientation: 'h',
                        hovertemplate: record.data.categories[count] + '<br>'+
                            "<span style='color:"+colors[i %10]+";'>● </span>" + '%{text} <extra></extra>',
                        text: tooltip,
                        chartSeries: info
                    };

                    data.push(peer);
                    yvals.push(count);
                    count++;
                }

            }
            let layout = {
                hoverlabel: {
                    bgcolor: '#ffffff'
                },
                xaxis: {
                    title: '<b>Time (' + record.data.schema.timezone + ')</b>',
                    titlefont: {
                        family: 'Arial, sans-serif',
                        size: 12,
                        color: '#5078a0'
                    },
                    color: '#606060',
                    zeroline: false,
                    gridcolor: '#d8d8d8',
                    type: 'date',
                    range: [record.data.series[0].data[0].low - (60 * 1000), record.data.series[0].data[0].high + (60 * 1000)]
                },
                yaxis: {
                    autorange: 'reversed',
                    ticktext: categories,
                    zeroline: false, 
                    showgrid: false,
                    showline: true,
                    linecolor: '#c0cfe0',
                    ticks: 'outside',
                    tickcolor: '#c0cfe0',
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
                annotations: [{
                    text: 'Powered by XDMoD/Plotly',
                    font:{
                        color: '#909090',
                        size: 10,
                        family: 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif'
                    },
                    xref: 'paper',
                    yref: 'paper',
                    xanchor: 'right',
                    yanchor: 'bottom',
                    x: 1,
                    y: 0,
                    yshift: -80,
                    showarrow: false
                }],
                showlegend: false,
                margin: {
                    t: 50,
                    l: 180,
                }
            };

            layout['shapes'] = rect;
            Plotly.react(this.id + '_hc', data, layout, {displayModeBar: false, doubleClick: 'reset'});

            const panel = document.getElementById(this.id + '_hc');
            panel.on('plotly_click', function(data){
                const userOptions = data.points[0].data.chartSeries;
                userOptions['action'] = 'show';
                Ext.History.add('job_viewer?' + Ext.urlEncode(userOptions));
            }); 

        });
    }
});
