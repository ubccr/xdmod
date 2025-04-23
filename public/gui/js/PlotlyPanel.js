/**
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
 * Plotly JS Chart extended as an ExtJS component
 */

CCR.xdmod.ui.PlotlyPanel = function (config) {
    CCR.xdmod.ui.PlotlyPanel.superclass.constructor.call(this, config);
};

Ext.extend(CCR.xdmod.ui.PlotlyPanel, Ext.Panel, {
    credits: true,
    isEmpty: true,

    chartOptions: {},

    initComponent: function () { /* eslint object-shorthand: "off" */
        Ext.apply(this, {
            layout: 'fit'
        });
        CCR.xdmod.ui.PlotlyPanel.superclass.initComponent.apply(this, arguments); /* eslint prefer-rest-params: "off" */

        const defaultOptions = {
            renderTo: this.id,
            layout: {
                width: this.width,
                height: this.height
            }
        };

        this.baseChartOptions = XDMoD.utils.deepExtend({}, defaultOptions, this.baseChartOptions);

        this.on('render', function () {
            this.initNewChart.call(this);

            this.on('resize', function (t, adjWidth, adjHeight, rawWidth, rawHeight) {
                this.baseChartOptions.layout.width = adjWidth;
                this.baseChartOptions.layout.height = adjHeight;
            });

            if (this.store) {
                this.store.on('load', function (t, records, options) {
                    if (t.getCount() <= 0) {
                        return;
                    }
                    this.chartOptions = XDMoD.utils.deepExtend({}, t.getAt(0).data, this.baseChartOptions);
                    this.chartOptions.credits = this.credits;
                    this.initNewChart.call(this);

                    // Since the chart is re-created on store loads we need
                    // to re-attach histogram drilldown.
                    if (this.chartOptions.efficiency) {
                        const extDiv = Ext.getCmp(this.chartOptions.renderTo);
                        extDiv.fireEvent('afterrender', extDiv);
                    }

                    if (this.chartOptions.summary || this.chartOptions.dashboard) {
                        const chartDiv = document.getElementById(this.chartOptions.renderTo);
                        overrideLegendEvent(chartDiv);
                    }

                    if (this.chartOptions.metricExplorer) {
                        const chartDiv = document.getElementById(this.chartOptions.renderTo);
                        const metricDiv = document.getElementById(this.id);
                        let isPie = false;
                        if (this.chartOptions.data && this.chartOptions.data.length > 0) {
                            isPie = this.chartOptions.data[0].type === 'pie';
                        }
                        let pointClick = false;
                        // Point Menu
                        chartDiv.on('plotly_click', (evt) => {
                            if (isPie) {
                                XDMoD.Module.MetricExplorer.pointContextMenu(evt.points[0], evt.points[0].data.datasetId);
                            } else {
                                const traces = [];
                                const mainAxisTraces = metricDiv.getElementsByClassName('plot')[0];
                                if (mainAxisTraces && mainAxisTraces.children.length !== 0) {
                                    for (let i = 0; i < mainAxisTraces.children.length; i++) {
                                        traces.push(...mainAxisTraces.children[i].children);
                                    }
                                }
                                const multiAxisTraces = metricDiv.getElementsByClassName('overplot')[0];
                                if (multiAxisTraces.children && multiAxisTraces.children.length !== 0) {
                                    for (let i = 0; i < multiAxisTraces.children.length; i++) {
                                        if (multiAxisTraces.children[i].firstChild && multiAxisTraces.children[i].firstChild.children) {
                                            traces.push(...multiAxisTraces.children[i].firstChild.children);
                                        }
                                    }
                                }
                                const point = getClickedPoint(evt, traces);
                                if (point) {
                                    pointClick = true;
                                    XDMoD.Module.MetricExplorer.pointContextMenu(point, point.data.datasetId);
                                } else {
                                    // Edge case where some chart hover labels are active anywhere on plot due to x|y unified hovermode
                                    pointClick = false;
                                }
                            }
                        });
                        // Context Menu
                        const plotAreaLayer = metricDiv.getElementsByClassName('xy');
                        const plotEvents = (el) => {
                            if (el && !isPie) {
                                const plotAreaDiv = metricDiv.getElementsByClassName('xy')[0];
                                if (plotAreaDiv.firstChild) {
                                    plotAreaDiv.firstChild.addEventListener('click', (event) => {
                                        const hoverDiv = metricDiv.getElementsByClassName('hoverlayer')[0];
                                        if (!pointClick || (hoverDiv && hoverDiv.children.length === 0)) {
                                            XDMoD.Module.MetricExplorer.chartContextMenu.call(event, false);
                                        }
                                    });
                                }
                            }
                        };
                        plotEvents(plotAreaLayer);
                        // Legend Menu
                        chartDiv.on('plotly_legendclick', (evt) => {
                            const series = evt.data[evt.curveNumber];
                            XDMoD.Module.MetricExplorer.seriesContextMenu(series, true, series.datasetId);
                            return false;
                        });
                        // Setup for yAxis Title and Tick events
                        let xAxes = ['xaxis'];
                        let yAxes = getMultiAxisObjects(this.chartOptions.layout);
                        const multiAxes = getMultiAxisObjects(this.chartOptions.layout);
                        if (this.chartOptions.layout.swapXY) {
                            xAxes = getMultiAxisObjects(this.chartOptions.layout);
                            yAxes = ['yaxis'];
                        }
                        // Title Menu
                        const infoLayer = metricDiv.getElementsByClassName('infolayer');
                        const titleEvents = (el) => {
                            if (el) {
                                const infoDiv = metricDiv.getElementsByClassName('infolayer')[0];
                                if (infoDiv.children.length !== 0) {
                                    infoDiv.setAttribute('pointer-events', 'all');
                                    // Axis Menu
                                    let yAxisDiv = infoDiv.getElementsByClassName('g-ytitle')[0];
                                    if (yAxisDiv) {
                                        for (let i = 0; i < yAxes.length; i++) {
                                            const yAxis = i === 0 ? 'g-ytitle' : `g-y${i + 1}title`;
                                            [yAxisDiv] = infoDiv.getElementsByClassName(yAxis);
                                            yAxisDiv.addEventListener('click', (event) => {
                                                XDMoD.Module.MetricExplorer.yAxisTitleContextMenu(this.chartOptions.layout[yAxes[i]]);
                                            });
                                        }
                                    }
                                    let xAxisDiv = infoDiv.getElementsByClassName('g-xtitle')[0];
                                    if (xAxisDiv) {
                                        for (let i = 0; i < xAxes.length; i++) {
                                            const xAxis = i === 0 ? 'g-xtitle' : `g-x${i + 1}title`;
                                            [xAxisDiv] = infoDiv.getElementsByClassName(xAxis);
                                            xAxisDiv.addEventListener('click', (event) => {
                                                XDMoD.Module.MetricExplorer.xAxisTitleContextMenu(this.chartOptions.layout[xAxes[i]]);
                                            });
                                        }
                                    }
                                }
                            }
                        };
                        titleEvents(infoLayer);
                        // yAxis Ticks
                        const yAxisTickLayer = metricDiv.getElementsByClassName('yaxislayer-below');
                        const yAxisEvents = (el) => {
                            if (el && !isPie) {
                                const yAxisTickDiv = metricDiv.getElementsByClassName('yaxislayer-below')[0];
                                yAxisTickDiv.setAttribute('pointer-events', 'all');
                                if (yAxisTickDiv.children && yAxisTickDiv.children.length !== 0) {
                                    for (let i = 0; i < yAxisTickDiv.children.length; i++) {
                                        yAxisTickDiv.children[i].setAttribute('pointer-events', 'all');
                                        yAxisTickDiv.children[i].addEventListener('click', (event) => {
                                            if (this.chartOptions.layout.swapXY) {
                                                XDMoD.Module.MetricExplorer.xAxisContextMenu(this.chartOptions.layout.yaxis);
                                            } else {
                                                XDMoD.Module.MetricExplorer.yAxisContextMenu(this.chartOptions.layout.yaxis, this.chartOptions.data);
                                            }
                                        });
                                    }
                                }
                                let multipleAxisLayer = metricDiv.getElementsByClassName('overaxes-below');
                                if (multipleAxisLayer) {
                                    [multipleAxisLayer] = multipleAxisLayer;
                                    multipleAxisLayer.setAttribute('pointer-events', 'all');
                                    for (let i = 1; i < multiAxes.length; i++) {
                                        const axisTicks = this.chartOptions.layout.swapXY ? `x${i + 1}y-x` : `xy${i + 1}-y`;
                                        const multipleAxisTickDiv = multipleAxisLayer.getElementsByClassName(axisTicks)[0];
                                        if (multipleAxisTickDiv.children && multipleAxisTickDiv.children.length !== 0) {
                                            for (let j = 0; j < multipleAxisTickDiv.children.length; j++) {
                                                multipleAxisTickDiv.children[j].setAttribute('pointer-events', 'all');
                                                multipleAxisTickDiv.children[j].addEventListener('click', (event) => {
                                                    XDMoD.Module.MetricExplorer.yAxisContextMenu(this.chartOptions.layout[multiAxes[i]], this.chartOptions.data);
                                                });
                                            }
                                        }
                                    }
                                }
                            }
                        };
                        yAxisEvents(yAxisTickLayer);
                        // xAxis Ticks
                        const xAxisTickLayer = metricDiv.getElementsByClassName('xaxislayer-below');
                        const xAxisEvents = (el) => {
                            if (el && !isPie) {
                                const xAxisTickDiv = metricDiv.getElementsByClassName('xaxislayer-below')[0];
                                xAxisTickDiv.setAttribute('pointer-events', 'all');
                                if (xAxisTickDiv.children && xAxisTickDiv.children.length !== 0) {
                                    for (let i = 0; i < xAxisTickDiv.children.length; i++) {
                                        xAxisTickDiv.children[i].setAttribute('pointer-events', 'all');
                                        xAxisTickDiv.children[i].addEventListener('click', (event) => {
                                            if (this.chartOptions.layout.swapXY) {
                                                XDMoD.Module.MetricExplorer.yAxisContextMenu(this.chartOptions.layout.xaxis, this.chartOptions.data);
                                            } else {
                                                XDMoD.Module.MetricExplorer.xAxisContextMenu(this.chartOptions.layout.xaxis);
                                            }
                                        });
                                    }
                                }
                            }
                        };
                        xAxisEvents(xAxisTickLayer);

                        chartDiv.on('plotly_relayout', (evt) => {
                            // Axis Titles and Ticks are re-rendered on relayout event so we need to reattach click events
                            yAxisEvents(yAxisTickLayer);
                            xAxisEvents(xAxisTickLayer);
                            titleEvents(infoLayer);
                            // There is a relayout event when empty charts are initialized which
                            // requires us to reattach the click event
                            if (this.chartOptions.data && this.chartOptions.data.length === 0) {
                                plotEvents(plotAreaLayer);
                            }
                            // Certain properties are in zoom events
                            const evtKeys = Object.keys(evt);
                            this.chartOptions.layout.zoom = evtKeys.some((key) => {
                                switch (key) {
                                    case 'xaxis.range[0]':
                                        return true;
                                    case 'xaxis.range[1]':
                                        return true;
                                    case 'yaxis.range[0]':
                                        return true;
                                    case 'yaxis.range[1]':
                                        return true;
                                    default:
                                        return false;
                                }
                            });
                        });
                        // Subtitle context menu
                        chartDiv.on('plotly_clickannotation', (evt) => {
                            if (evt.annotation.name === 'subtitle') {
                                XDMoD.Module.MetricExplorer.subtitleContextMenu(evt.event);
                            } else if (evt.annotation.name === 'title') {
                                XDMoD.Module.MetricExplorer.titleContextMenu(evt.event);
                            }
                        });
                    } // end if metric explorer
                }, this);
            }
        }, this, {
            single: true
        });
        this.addEvents('timeseries_zoom');
    },
    /**
     * Instantiates a new chart, optionally with given parameters instead of
     * the settings stored in this panel's configuration.
     *
     * @param  {Object} chartOptions (Optional) A set of Plotly options.
     */
    initNewChart: function (chartOptions) { /* eslint object-shorthand: "off" */
        const finalChartOptions = {};
        if (chartOptions) {
            XDMoD.utils.deepExtend(finalChartOptions, this.baseChartOptions, chartOptions);
        } else {
            XDMoD.utils.deepExtend(finalChartOptions, this.baseChartOptions, this.chartOptions);
        }
        this.chart = XDMoD.utils.createChart(finalChartOptions);
    },
    /**
     * Instantiates a new chart that displays the given error message.
     *
     * @param  {String} mainMessage The main error message to display.
     * @param  {String} detailMessage (Optional) A secondary error message
     *                                to display.
     */
    displayError: function (mainMessage, detailMessage) { /* eslint object-shorthand: "off" */
        const errorChartOptions = {
            title: {
                text: mainMessage,
                yref: 'paper',
                xanchor: 'center',
                yanchor: 'center',
                x: 0.5,
                y: 1.0
            },
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

        if (detailMessage) {
            errorChartOptions.annotations = [{
                text: detailMessage,
                xref: 'paper',
                yref: 'paper',
                xanchor: 'center',
                yanchor: 'top',
                x: 0.5,
                y: 0.8,
                showarrow: false
            }];
        }

        this.initNewChart.call(this, errorChartOptions);
    }
});
