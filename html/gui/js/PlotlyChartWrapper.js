/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-Mar-07 (version 1)
 *
 * @author Ryan Gentner
 * @date 2013-Jun-23 (version 2)
 *
 * @author Andrew Stoltman
 * @date 2024-Feb-09 (version 3)
 *
 * Class to generate Plotly JS Chart. Also performs final configurations.
 */

Ext.namespace('XDMoD.utils');

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    const baseChartOptions = {};
    const configs = {
        displayModeBar: false,
        doubleClick: 'reset',
        doubleClickDelay: 500,
        showAxisRangeEntryBoxes: false,
        staticPlot: chartOptions.isExport
    };
    XDMoD.utils.deepExtend(baseChartOptions, chartOptions);
    const isEmpty = (!baseChartOptions.data) || (baseChartOptions.data && baseChartOptions.data.length === 0);

    // Configure plot for 'No Data' image. We want to wipe the layout object except for a couple things
    if (isEmpty) {
        const errorConfig = getNoDataErrorConfig();
        const { width } = baseChartOptions.layout;
        const { height } = baseChartOptions.layout;
        // Save the title and subtitle except for thumbnail plots
        let margin;
        let titleAndSubtitle;
        if (baseChartOptions.layout.annotations) {
            margin = baseChartOptions.layout.margin;
            titleAndSubtitle = baseChartOptions.layout.annotations.slice(0, 2);
        }
        baseChartOptions.layout = errorConfig;
        baseChartOptions.layout.width = width;
        baseChartOptions.layout.height = height;
        baseChartOptions.layout.annotations = [];
        // Category chart summary view needs adjustments
        if (baseChartOptions.realmOverview) {
            baseChartOptions.layout.images[0].sizex = 4;
            baseChartOptions.layout.images[0].sizey = 4;
            baseChartOptions.layout.images[0].y = 2.5;
        } else if (margin && titleAndSubtitle) {
            baseChartOptions.layout.margin = margin;
            baseChartOptions.layout.annotations = titleAndSubtitle;
        }
    } else {
        if (baseChartOptions.metricExplorer) {
            configs.showTips = false;
            // Check for empty custom titles. If so make sure title is empty string
            if (!baseChartOptions.layout.annotations[0].text) {
                baseChartOptions.layout.annotations[0].text = '';
            }
        }

        if (baseChartOptions.data[0].type === 'pie') {
            baseChartOptions.layout.pieChart = true;
            baseChartOptions.layout.margin.t += 30;
        }

        if (!baseChartOptions.credits && baseChartOptions.credits === false) {
            baseChartOptions.layout.annotations[2].text = '';
        }

        // Remove titles and credits from thumbnail plots
        if (baseChartOptions.realmOverview) {
            const endIndex = baseChartOptions.layout.annotations.findIndex((elem) => elem.name === 'data_label');
            if (endIndex === -1) {
                baseChartOptions.layout.annotations = [];
            }
            baseChartOptions.layout.annotations.splice(0, endIndex);
        }
        // Adjust trace ordering
        // Referenced https://stackoverflow.com/questions/45741397/javascript-sort-array-of-objects-by-2-properties
        // for comparison idea
        baseChartOptions.data.sort((trace1, trace2) => {
            const containsBarSeries = baseChartOptions.data.some((elem) => elem.type === 'bar');
            if (containsBarSeries && baseChartOptions.layout.barmode === 'stack') {
                return Math.sign(trace2.traceorder - trace1.traceorder);
            }
            return Math.sign(trace1.traceorder - trace2.traceorder);
        });

        // Format timezone -- Plotly does not support timezones
        // Therefore, we need to utilize moment JS.
        const axis = baseChartOptions.layout.swapXY ? 'yaxis' : 'xaxis';
        if (baseChartOptions.layout[axis].timeseries) {
            const axisName = axis.slice(0, 1);
            baseChartOptions.data.forEach((series) => {
                series[axisName].forEach((elem, index, seriesArr) => {
                    seriesArr[index] = moment.tz(elem, CCR.xdmod.timezone).format();
                });
            });

            // Format timezone for the tickvals also
            baseChartOptions.layout[axis].tickvals.forEach((tick, index, tickvals) => {
                tickvals[index] = moment.tz(tick, CCR.xdmod.timezone).format();
            });
        }
    }

    const chart = Plotly.newPlot(baseChartOptions.renderTo, baseChartOptions.data, baseChartOptions.layout, configs);
    const chartDiv = document.getElementById(baseChartOptions.renderTo);

    // Need this because of bug with Plotly JS pie chart automargin
    // which causes the chart to never fire the 'plotly_afterplot'
    // event. Therefore, we need to explictly call an event.
    if (chartDiv) {
        Plotly.relayout(baseChartOptions.renderTo, {});
    }

    chartDiv.once('plotly_relayout', (evt) => {
        if (baseChartOptions.layout.appkernels) {
            const appKernelsDiv = document.getElementById('app_kernels');
            const legendEntries = appKernelsDiv.getElementsByClassName('groups');
            for (let i = 0; i < legendEntries.length; i++) {
                for (let j = 0; j < legendEntries[i].children.length; j++) {
                    if (legendEntries[i].children[j].textContent === 'Change Indicator') {
                        const changeIndicatorIcon = legendEntries[i].children[j].children[1];
                        changeIndicatorIcon.innerHTML = '<image href="/gui/images/exclamation_ak.png" x="15" y="-12" width="20" height="20">';
                    }
                }
            }
        }

        if (baseChartOptions.layout.annotations.length === 0
           || (baseChartOptions.summary || baseChartOptions.dashboard || baseChartOptions.realmOverview)) {
            return;
        }

        const update = relayoutChart(chartDiv, baseChartOptions.layout.height, true, baseChartOptions.isExport);
        Plotly.relayout(baseChartOptions.renderTo, update);
    });

    return chart;
};
