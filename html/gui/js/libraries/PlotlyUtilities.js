/* generateChartOptions - Generates data array and layout dict for Plotly Chart
 *                        ** Currently assumes that data is in format of a record returned in the JobViewer **
 *
 * @param{dict} Record containing chart data
 *
 */
function generateChartOptions(record) { // eslint-disable-line no-unused-vars
    var colors = ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970',
        '#f28f43', '#77a1e5', '#c42525', '#a6c96a'];
    var data = [];
    var isEnvelope = false;
    var tz = moment.tz.zone(record.data.schema.timezone).abbr(record.data.series[0].data[0].x);
    var ymin = record.data.series[0].data[0].y;
    var ymax = ymin;
    var sid = 0;
    if (record.data.series[sid].name === 'Range') {
        sid++;
        isEnvelope = true;
        ymin = record.data.series[1].data[0].y;
        ymax = ymin;
    }
    for (sid; sid < record.data.series.length; sid++) {
        var x = [];
        var y = [];
        var qtip = [];
        var color = colors[sid % 10];

        for (var i = 0; i < record.data.series[sid].data.length; i++) {
            x.push(moment.tz(record.data.series[sid].data[i].x, record.data.schema.timezone).format('Y-MM-DD HH:mm:ss.SSS'));
            y.push(record.data.series[sid].data[i].y);
            qtip.push(record.data.series[sid].data[i].qtip);
        }

        var trace = {
            x: x,
            y: y,
            marker: {
                size: 0.1,
                color: color
            },
            line: {
                width: 2,
                color: color
            },
            text: qtip,
            hovertemplate: '%{x|%A, %b %e, %H:%M:%S.%L} ' + tz + '<br> <span style=color:'
            + color + ';>●</span> ' + record.data.series[sid].name + ': <b>%{y:}</b> <extra></extra>',
            name: record.data.series[sid].name,
            chartSeries: record.data.series[sid],
            type: 'scatter',
            mode: 'markers+lines'
        };

        if (record.data.series[sid].name === 'Median' || record.data.series[sid].name === 'Minimum') {
            trace.fill = 'tonexty';
            trace.fillcolor = '#5EA0E2';
        }

        if (isEnvelope) {
            trace.hovertemplate = '<span style=color:' + color + ';>[%{text}]</span> %{x|%A, %b %e, %H:%M:%S.%L} ' + tz + '<br> <span style=color:' + color + ';>●</span> ' + record.data.series[sid].name + ': <b>%{y:}</b> <extra></extra>';
        }

        if (record.data.series[sid].data.length === 1) {
            trace.marker.size = 20;
            trace.mode = 'markers';
            delete trace.line;
        }

        data.push(trace);
        var tempMin = Math.min.apply(null, y);
        var tempMax = Math.max.apply(null, y);
        if (tempMin < ymin) {
            ymin = tempMin;
        }
        if (tempMax > ymax) {
            ymax = tempMax;
        }
    }

    var layout = {
        hoverlabel: {
            bgcolor: '#ffffff'
        },
        xaxis: {
            title: '<b> Time (' + record.data.schema.timezone + ') </b>',
            titlefont: {
                family: 'Open-Sans, verdana, arial, sans-serif',
                size: 12,
                color: '#5078a0'
            },
            color: '#606060',
            ticks: 'outside',
            tickcolor: '#c0cfe0',
            linecolor: '#c0cfe0',
            automargin: true,
            showgrid: false
        },
        yaxis: {
            title: '<b>' + record.data.schema.units + '</b>',
            titlefont: {
                family: 'Open-Sans, verdana, arial, sans-serif',
                size: 12,
                color: '#5078a0'
            },
            color: '#606060',
            range: [0, ymax + (ymax * 0.2)],
            showline: false,
            zeroline: false,
            gridcolor: '#d8d8d8',
            automargin: true,
            ticks: 'outside',
            tickcolor: '#ffffff',
            seperatethousands: true
        },
        title: {
            text: record.data.schema.description,
            font: {
                family: 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                color: '#444b6e',
                size: 16
            }
        },
        annotations: [{
            text: record.data.schema.source + '. Powered by XDMoD/Plotly',
            font: {
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
        hovermode: 'closest',
        showlegend: false,
        margin: {
            t: 50
        }
    };

    var ret = {
        chartData: data,
        chartLayout: layout
    };

    return ret;
}
