/**
 * Returns text wrapped by given width. Mainly used for subtitle
 * text wrapping.
 *
 * @return {String} s - Subtitle text
 * @return {Integer} wrapWidth - word wrap boundary
 */
/* exported lineSplit */
function lineSplit(s, wrapWidth) {
    return s.match(new RegExp(`([^\\n]{1,${wrapWidth}})(?=\\s|$)`, 'g'));
}
/**
 * Returns Plotly JS layout configuration for charts with no data found
 *
 * @return {Object} errorLayout - Plotly JS layout configuration
 */
/* exported getNoDataErrorConfig */
function getNoDataErrorConfig() {
    const errorLayout = {
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
                y: 1.0
            }
        ],
        xaxis: {
            showticklabels: false,
            zeroline: false,
            showgrid: false,
            showline: false
        },
        yaxis: {
            showticklabels: false,
            zeroline: false,
            showgrid: false,
            showline: false
        }
    };

    return errorLayout;
}
/**
 * generateChartOptions - Generates data array and layout dict for Plotly Chart
 *                        Currently assumes that data is in format of a record returned in the JobViewer
 *
 * @param  {Object} Record containing chart data
 *
 */
/* exported generateChartOptions */
function generateChartOptions(record, params = null) {
    var colors = ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970',
        '#f28f43', '#77a1e5', '#c42525', '#a6c96a'];
    var mainTitleFontSize = 16;
    var axisLabelFontSize = 11;
    var axisTitleFontSize = 12;
    var lineWidth = 2;
    if (params) {
        mainTitleFontSize = params.mainTitleFontSize;
        axisLabelFontSize = params.axisLabelFontSize;
        axisTitleFontSize = params.axisTitleFontSize;
        lineWidth = params.lineWidth;
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

        const trace = {
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

        if (y.includes(null)) {
            data.push({
                name: 'null connector',
                line: {
                    color: color,
                    dash: 'dash',
                    width: lineWidth
                },
                mode: 'lines',
                type: 'scatter',
                connectgaps: true,
                hoverinfo: 'skip',
                showlegend: false,
                x: x,
                y: y
            });
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

    const layout = {
        hoverlabel: {
            bgcolor: '#ffffff'
        },
        xaxis: {
            title: {
                text: `<b> Time (${record.data.schema.timezone}) </b>`,
                font: {
                    family: 'Open-Sans, verdana, arial, sans-serif',
                    size: axisTitleFontSize,
                    color: '#5078a0'
                }
            },
            color: '#606060',
            ticks: 'outside',
            tickcolor: '#c0cfe0',
            tickfont: {
                size: axisLabelFontSize
            },
            linecolor: '#c0cfe0',
            automargin: true,
            showgrid: false
        },
        yaxis: {
            title: {
                text: `<b> ${record.data.schema.units} </b>`,
                font: {
                    family: 'Open-Sans, verdana, arial, sans-serif',
                    size: axisTitleFontSize,
                    color: '#5078a0'
                }
            },
            color: '#606060',
            range: [0, ymax + (ymax * 0.2)],
            showline: false,
            zeroline: false,
            gridcolor: '#d8d8d8',
            automargin: true,
            ticks: 'outside',
            tickcolor: '#ffffff',
            tickfont: {
                size: axisLabelFontSize
            },
            seperatethousands: true
        },
        title: {
            text: record.data.schema.description,
            font: {
                family: 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                color: '#444b6e',
                size: mainTitleFontSize
            }
        },
        annotations: [{
            text: `${record.data.schema.source}. Powered by XDMoD/Plotly JS`,
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

    return { data, layout };
}
/**
 * Returns the point clicked on in a chart click event.
 * Needed to determine which point was clicked for unified hovermode.
 *
 * @param  {Object} clickEvent - Plotly JS click event object.
 * @param  {Array} traceDivs - Array of chart series svg elements
 * @return {Object} point from Plotly JS click event
 */
/* exported getClickedPoint */
function getClickedPoint(evt, traces) {
    if ((traces && traces.length === 0) || (evt.points && evt.points.length === 0)) {
        return null;
    }
    for (let i = 0; i < traces.length; i++) {
        let points = traces[i].getElementsByClassName('points');
        if (points.length > 0 && points[0].children) {
            points = points[0].children;
            for (let j = 0; j < points.length; j++) {
                const dimensions = points[j].getBoundingClientRect();
                if (evt.event.pageX >= dimensions.left && evt.event.pageX <= dimensions.right
                    && evt.event.pageY >= dimensions.top && evt.event.pageY <= dimensions.bottom) {
                    const swapXY = !evt.points[0].data.yaxis;
                    const dataValue = points[j].__data__.s || (swapXY ? points[j].__data__.x : points[j].__data__.y);
                    const pointIndex = evt.points.findIndex((trace) => {
                        if (trace.value) {
                            return trace.value === dataValue;
                        }
                        return swapXY ? trace.x === dataValue : trace.y === dataValue;
                    });
                    return evt.points[pointIndex];
                }
            }
        }
    }
    return null;
}
/**
 * Returns array of axis layout keys for the axis that represents
 * the range of the data
 *
 * @param  {Object} layout - Plotly JS layout configuration object.
 * @return {Array} multiAxes - Array of Plotly JS layout keys
 */
/* exported getMultiAxisObjects */
function getMultiAxisObjects(layout) {
    const multiAxes = [];
    const layoutKeys = Object.keys(layout);
    const axisKeyStart = (layout.swapXY ? 'x' : 'y') + 'axis';
    for (let i = 0; i < layoutKeys.length; i++) {
        if (layoutKeys[i].startsWith(axisKeyStart)) {
            multiAxes.push(layoutKeys[i]);
        }
    }
    return multiAxes;
}
/**
 * Determines if legend is located at the top of the chart.
 *
 * @param  {Object} layout - Plotly JS layout configuration object.
 * @return {Boolean} If the legend is top center or not
 */
function isTopLegend(layout) {
    if (layout.legend) {
        return (layout.legend.xanchor === 'center'
                && layout.legend.yanchor === 'top'
                && layout.legend.yref !== 'paper');
    }
    return false;
}
/**
 * Word wrap subtitle text and adjust margin depending
 * on legend location and subtitle length.
 *
 * @param  {Object} layout - Plotly JS layout configuration object
 * @param  {Integer} subtitleIndex - Index of subtitle annotation
 * @param  {Boolean} legendTopCenter - Indicates if legend location is 'top_center'
 * @param  {Boolean} firstRender - Indicates if is initial render or resize event
 * @return {Object} update - Contains Plotly JS relayout updates and subtitle line count
 */
function adjustSubtitle(layout, subtitleIndex, legendTopCenter, firstRender) {
    let prevLineCount = layout.annotations[1].text.match(/<br \/>/g);
    if (prevLineCount) {
        prevLineCount = prevLineCount.length;
    } else {
        prevLineCount = 1;
    }
    const subtitle = layout.annotations[1].text.replace(/<br \/>/g, '');
    const update = { chartUpdates: {}, subtitleLineCount: 0 };
    if (subtitle.length > 0) {
        if (!layout.width) {
            layout.width = 1000; // default width -- need for custom queries because the width is not set for some reason
        }
        let axWidth = layout.width - layout.margin.l - layout.margin.r;
        if (layout.pieChart) {
            axWidth = layout.width / 1.1;
        }
        const subtitle_lines = lineSplit(subtitle, Math.trunc(axWidth / 7.5));
        update.chartUpdates[`annotations[${subtitleIndex}].text`] = subtitle_lines.join('<br />');
        if (legendTopCenter) {
            update.chartUpdates['legend.y'] = 0.95 - (0.025 * subtitle_lines.length);
        }
        update.subtitleLineCount = subtitle_lines.length;
        if (firstRender || prevLineCount === update.subtitleLineCount) {
            update.chartUpdates['margin.t'] = 45 + subtitle_lines.length * 15;
        }
    }

    return update;
}
/**
 * Configures the layout object passed to the Plotly.relayout function.
 *
 * @param  {Object} chartDiv - Plotly JS chart div.
 * @param  {Integer} adjHeight - Height of chart at initial render or during resize event
 * @param  {Boolean} firstRender - Indicates if is initial render or resize event
 * @return {Object} update - Layout object passed to Plotly.relayout
 */
/* exported relayoutChart */
function relayoutChart(chartDiv, adjWidth, adjHeight, firstRender = false, isExport = false) {
    const update = {};
    if (chartDiv._fullLayout.annotations.length > 0) {
        // Wrap long titles based on width
        const traceNameUpdates = { name: [] };
        const traceIndices = [];
        const chartRatioChange = adjWidth / chartDiv.clientWidth;
        let characterLimit = 150;
        if (adjWidth < 400) {
            characterLimit = 20;
        } else if (adjWidth < 650) {
            characterLimit = 40;
        } else if (adjWidth < 850) {
            characterLimit = 70;
        } else if (adjWidth < 1250) {
            characterLimit = 100;
        }
        const wordWrapLimit = Number.parseInt(chartRatioChange * characterLimit, 10);
        const regex = new RegExp(`(?![^\\n]{1,${wordWrapLimit}}$)(?:([^\\n]{1,${wordWrapLimit}})\\s|([^\\n]{${wordWrapLimit}}))`, 'g');

        chartDiv.data.forEach((trace, index) => {
            if (trace.oname) {
                traceNameUpdates.name.push(
                    // Hardwrap is when we have to break middle of a word (add hyphen)
                    // Otherwise we are soft word wrapping and will break on whitespace.
                    trace.oname.replaceAll(regex, (match, softWrap, hardWrap) => {
                        if (hardWrap) {
                            return `${hardWrap}-<br>`;
                        }
                        return `${softWrap}<br>`;
                    })
                );
                traceIndices.push(index);
            }
        });
        update.data = traceNameUpdates;
        update.traces = traceIndices;

        const topCenter = isTopLegend(chartDiv._fullLayout);
        const marginRight = chartDiv._fullLayout._size.r;
        const marginLeft = chartDiv._fullLayout._size.l;
        const legendHeight = (topCenter && adjHeight > 550) ? chartDiv._fullLayout.legend._height : 0;
        const func = (legendHeight === 0) ? Math.max : Math.min;
        let marginTop = func(chartDiv._fullLayout.margin.t, chartDiv._fullLayout._size.t);
        let extraShift = 0;

        const titleHeight = 31;
        const subtitleHeight = 15;

        let titleIndex = -1;
        let subtitleIndex = -1;
        let creditsIndex = -1;
        let restrictedIndex = -1;
        for (let idx = 0; idx < chartDiv._fullLayout.annotations.length; idx++) {
            const { name } = chartDiv._fullLayout.annotations[idx];
            switch (name) {
                case 'title':
                    titleIndex = idx;
                    break;
                case 'subtitle':
                    subtitleIndex = idx;
                    break;
                case 'credits':
                    creditsIndex = idx;
                    break;
                case 'Restricted Data Warning':
                    restrictedIndex = idx;
                    break;
                default:
            }
        }

        const isPie = chartDiv._fullData.length > 0 && chartDiv._fullData[0].type === 'pie';

        const subtitleUpdates = adjustSubtitle(chartDiv._fullLayout, subtitleIndex, topCenter, firstRender);
        update.layout = subtitleUpdates.chartUpdates;

        if (isPie && topCenter && subtitleUpdates.subtitleLineCount > 0) {
            extraShift -= 10;
        }

        if (subtitleUpdates.subtitleLineCount > 0 && firstRender) {
            marginTop = subtitleUpdates.chartUpdates['margin.t'];
        }

        // Handle <br> in title
        // Grab the contents inbetween leading and trailing <br> tags
        // Eslint throws invalid syntax on regex even though it is valid.
        const titleContents = chartDiv._fullLayout.annotations[titleIndex].text.match(/(?![<br>])(.*\S)(?<![<br>])/g);
        let lineBreakCount = 0;
        if (titleContents) {
            const count = titleContents[0].match(/<br>/g);
            if (count) {
                lineBreakCount = count.length;
            }
        }

        // Observed inconsistency with margin when subtitle is one line long. Unsure of the cause.
        if (lineBreakCount > 0) {
            if (firstRender) {
                update.layout['margin.t'] = marginTop + (titleHeight * lineBreakCount);
            } else if (subtitleUpdates.subtitleLineCount === 1) {
                marginTop = subtitleUpdates.chartUpdates['margin.t'] - (titleHeight * lineBreakCount);
            } else {
                marginTop -= (titleHeight * lineBreakCount);
            }
            if (topCenter) {
                if (subtitleUpdates.subtitleLineCount > 0) {
                    marginTop += subtitleHeight + 5;
                    update.layout['legend.y'] -= 0.025;
                    update.layout['margin.t'] += chartDiv._fullLayout.legend._height + subtitleHeight;
                } else {
                    marginTop -= chartDiv._fullLayout.legend._height / 2;
                }
            }
        }

        if (topCenter && subtitleUpdates.subtitleLineCount === 0 && !isPie) {
            extraShift += 15;
        }

        marginTop += extraShift;

        if ((titleIndex === -1 || chartDiv._fullLayout.annotations[titleIndex].text.length === 0) && subtitleUpdates.subtitleLineCount === 0) {
            if (topCenter) {
                update.layout['legend.y'] = 1;
            } else {
                update.layout['margin.t'] = 10;
            }
        }

        const titleYShift = (marginTop + legendHeight) - titleHeight;

        if (titleIndex !== -1) {
            update.layout[`annotations[${titleIndex}].yshift`] = subtitleUpdates.subtitleLineCount >= 3 ? titleYShift + 5 : titleYShift;
        }

        if (subtitleIndex !== -1) {
            update.layout[`annotations[${subtitleIndex}].yshift`] = titleYShift - (subtitleHeight * subtitleUpdates.subtitleLineCount);
        }

        const marginBottom = chartDiv._fullLayout._size.b;
        let pieChartXShift = 0;
        if (isPie) {
            pieChartXShift = subtitleUpdates.subtitleLineCount > 0 ? 2 : 1;
        }

        const shiftYDown = marginBottom * -1;
        const exportShift = isExport ? 30 : 0;
        if (creditsIndex !== -1) {
            update.layout[`annotations[${creditsIndex}].yshift`] = shiftYDown;
            update.layout[`annotations[${creditsIndex}].xshift`] = marginRight - pieChartXShift - exportShift;
        }
        if (restrictedIndex !== -1) {
            update.layout[`annotations[${restrictedIndex}].yshift`] = shiftYDown;
            update.layout[`annotations[${restrictedIndex}].xshift`] = (marginLeft - pieChartXShift - exportShift) * -1;
        }
    }
    return update;
}
/**
 * Changes Plotly JS legend_click and legend_doubleclick functionality.
 * Disables legend_doubleclick event. Overrides legend_click
 * to correctly hide Std Err traces when hiding traces and adjust axis
 * tickmode due to Plotly JS bug with custom ticktext.
 *
 * @param  {Object} chartDiv - Plotly JS chart div.
 */
/* exported overrideLegendEvent */
function overrideLegendEvent(chartDiv) {
    chartDiv.on('plotly_legendclick', (evt) => {
        // Update std err bar based on legend event details.
        const { node } = evt;
        const nodeVisibility = evt.node.style.opacity;
        const errorBar = evt.layout.swapXY ? 'error_x.visible' : 'error_y.visible';
        // Check for std err to update where the error bars go
        if (node.textContent.startsWith('Std Err:')) {
            if (nodeVisibility === '1') {
                Plotly.update(chartDiv, { [errorBar]: false }, {}, [evt.curveNumber + 1]);
            } else {
                Plotly.update(chartDiv, { [errorBar]: true, visible: true }, {}, [evt.curveNumber + 1]);
            }
        // Clicked on primary trace
        } else if (node.nextSibling) {
            const sibling = node.nextSibling;
            if (sibling.textContent.startsWith('Std Err:')) {
                if (sibling.style.opacity === '1') {
                    // Turning off primary trace so need to transfer error bars
                    if (nodeVisibility === '1') {
                        Plotly.update(chartDiv, { visible: 'legendonly' }, {}, [evt.curveNumber - 1]);
                    }
                } else if (nodeVisibility === '0.5') {
                    Plotly.update(chartDiv, { [errorBar]: true }, {}, [evt.curveNumber]);
                    Plotly.update(chartDiv, { visible: true }, {}, [evt.curveNumber - 1]);
                }
            }
        }
    });

    chartDiv.on('plotly_legenddoubleclick', (evt) => false);
}
