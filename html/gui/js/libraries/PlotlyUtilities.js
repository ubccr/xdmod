function getDefaultLayout() {
	var layout = {
        hoverlabel: {
            bgcolor: '#ffffff'
        },
        xaxis: {
            titlefont: {
                family: 'Open-Sans, verdana, arial, sans-serif',
                size: 12,
                color: '#5078a0'
            },
            color: '#606060',
            ticks: 'outside',
            tickcolor: '#c0cfe0',
            tickfont: {
                size: 11
            },
            linecolor: '#c0cfe0',
            automargin: true,
            showgrid: false
        },
        yaxis: {
            titlefont: {
                family: 'Open-Sans, verdana, arial, sans-serif',
                size: 12,
                color: '#5078a0'
            },
            color: '#606060',
            showline: false,
            zeroline: false,
            gridcolor: '#d8d8d8',
            automargin: true,
            ticks: 'outside',
            tickcolor: '#ffffff',
            tickfont: {
                size: 11
            },
            seperatethousands: true
        },
        title: {
            font: {
                family: 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                color: '#444b6e',
                size: 16
            }
        },
        annotations: [{
            text: '. Powered by XDMoD/Plotly',
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

    return layout;
}

function getNoDataErrorConfig() {

    let errorLayout = {
        images: [
        {
            source: 'gui/images/report_thumbnail_no_data.png',
            align: 'center',
            xref: 'paper',
            xanchor: 'center',
            yanchor: 'center',
            yref: 'paper',
            sizex: 1.0,
            sizey: 1.0,
            x: 0.5,
            y: 1.0,
        }
        ],
        xaxis: {
            showticklabels: false,
            zeroline: false,
            showgrid: false,
            showline: false,

        },
        yaxis: {
            showticklabels: false,
            zeroline: false,
            showgrid: false,
            showline: false,
        }
    };

    return errorLayout;
}
/* generateChartOptions - Generates data array and layout dict for Plotly Chart
 *                        ** Currently assumes that data is in format of a record returned in the JobViewer **
 *
 * @param{dict} Record containing chart data
 *
 */
function generateChartOptions(record, params) { // eslint-disable-line no-unused-vars
    var args = params || {};
    var colors = ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970',
        '#f28f43', '#77a1e5', '#c42525', '#a6c96a'];
    var mainTitleFontSize = 16;
    var axisLabelFontSize = 11;
    var axisTitleFontSize = 12;
    var lineWidth = 2;
    if (args) {
        mainTitleFontSize = args.mainTitleFontSize;
        axisLabelFontSize = args.axisLabelFontSize;
        axisTitleFontSize = args.axisTitleFontSize;
        lineWidth = args.lineWidth;
    }
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
        tz = moment.tz.zone(record.data.schema.timezone).abbr(record.data.series[1].data[0].x);
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
                width: lineWidth,
                color: color
            },
            text: qtip,
            hovertemplate: '%{x|%A, %b %e, %H:%M:%S.%L} ' + tz + '<br> <span style="color:'
            + color + ';">●</span> ' + record.data.series[sid].name + ': <b>%{y:}</b> <extra></extra>',
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
            trace.hovertemplate = '<span style="color:' + color + ';">[%{text}]</span> %{x|%A, %b %e, %H:%M:%S.%L} ' + tz + '<br> <span style="color:' + color + ';">●</span> ' + record.data.series[sid].name + ': <b>%{y:}</b> <extra></extra>';
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
        xaxis: {
            title: `<b> Time (${record.data.schema.timezone}) </b>`,
            titlefont: {
                size: axisTitleFontSize,
            },
            tickfont: {
                size: axisLabelFontSize
            },
        },
        yaxis: {
            title: `<b> ${record.data.schema.units} </b>`,
            titlefont: {
                size: axisTitleFontSize,
            },
            range: [0, ymax + (ymax * 0.2)],
            tickfont: {
                size: axisLabelFontSize
            },
        },
        title: {
            text: record.data.schema.description,
            font: {
                size: mainTitleFontSize
            }
        },
        annotations: [{
            text: `${record.data.schema.source}. Powered by XDMoD/Plotly`,
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
    };

    /*let defaultLayout = getDefaultLayout();
    XDMoD.utils.deepExtend(layout, defaultLayout);*/

    let ret = {
        data: data,
        layout: layout
    };

    return ret;
}
function getClickedPoint(evt, traces, data = null) {
    let point;
    if ((traces && traces.length === 0) || (evt.points && evt.points.length === 0)) {
        return point;
    }
    for (let i = 0; i < traces.length; i++) {
        let points = traces[i].getElementsByClassName('points');
        if (points.length != 0 && points[0].children) {
            points = points[0].children;
            for (let j = 0; j < points.length; j++) {
                const dimensions = points[j].getBoundingClientRect();
                if (evt.event.pageX >= dimensions.left && evt.event.pageX <= dimensions.right &&
                    evt.event.pageY >= dimensions.top && evt.event.pageY <= dimensions.bottom) {
                    let pointIndex;
                    // Grab color of point so we can find it in the dataset
                    // This is assuming two primary traces cannot be the same color
                    // which seems to be the case
                    // Bar charts and scatter plots have their fill in different locations
                    let traceColor = points[j].firstChild ? points[j].firstChild.style.fill : points[j].style.fill;
                    // Strip trace color string to get values
                    if (traceColor.length != 0) {
                        if (traceColor[3] === 'a') {
                            // Strip the opacity
                            traceColor = traceColor.slice(5,-1).split(',').slice(0,-1);
                        }
                        else {
                            traceColor = traceColor.slice(4,-1).split(',');
                        }
                        // Convert trace color to hex so we can compare them to the dataset
                        // Referenced https://stackoverflow.com/questions/5623838/rgb-to-hex-and-hex-to-rgb @Tim Down
                        // for rgb to hex conversion
                        const rgbToHex = (num) => { 
                            var hex = Math.abs(num).toString(16);
                            return hex.length == 1 ? "0" + hex : hex;
                        }
                        const hexColor = '#' + rgbToHex(traceColor[0]) + rgbToHex(traceColor[1]) + rgbToHex(traceColor[2]);
                        for (let k = 0; k < data.length; k++) {
                            if (data[k].line.color === hexColor && !data[k].name.startsWith('Trend Line:')) {
                                pointIndex = data[k].name;
                            }
                        }
                    }
                    pointIndex = evt.points.findIndex((trace) => trace.data.name === pointIndex);
                    point = evt.points[pointIndex];
                    break;
                }
            }
        }
   }
    return point;
}

function getMultiAxisObjects(layout) {
    let multiAxes = [];
    const layoutKeys = Object.keys(layout);
    for (let i = 0; i < layoutKeys.length; i++) {
        if (layout.swapXY) {
            if (layoutKeys[i].startsWith('xaxis')) {
                multiAxes.push(layoutKeys[i]);
            }
        }
        else {
            if (layoutKeys[i].startsWith('yaxis')) {
                multiAxes.push(layoutKeys[i]);
            }
        }
    }
    return multiAxes;
}
