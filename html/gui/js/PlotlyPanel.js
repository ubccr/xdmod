
/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-Mar-07 (version 1)
 *
 * @author Ryan Gentner
 * @date 2013-Jun-23 (version 2)
 *
 *
 * Component for integrating with plotly api
 */
//TODO: Look into loading screens (involves plotly_afterplot)
//TODO: Add lisenter for 'timeseries_zoom' (involves plotly_click)

CCR.xdmod.ui.PlotlyPanel = function (config) {
    CCR.xdmod.ui.PlotlyPanel.superclass.constructor.call(this, config);
};

Ext.extend(CCR.xdmod.ui.PlotlyPanel, Ext.Panel, {
    credits: true,
    isEmpty: true,

    chartOptions: {},

    initComponent: function () {
        Ext.apply(this, {
            layout: 'fit'
        });
        CCR.xdmod.ui.PlotlyPanel.superclass.initComponent.apply(this, arguments);

        var self = this;
        var defaultOptions = {
            renderTo: this.id,
            layout: {},
        }; 

        defaultOptions.renderTo = this.id;
        defaultOptions.layout.width = this.width;
        defaultOptions.layout.height = this.height;
        //defaultOptions.exporting.enabled = false;
        //defaultOptions.credits.enabled = true;

        this.baseChartOptions = jQuery.extend(true, {}, defaultOptions, this.baseChartOptions);


        this.on('render', function () {
            this.initNewChart.call(this);

            this.on('resize', function (t, adjWidth, adjHeight, rawWidth, rawHeight) {
                if (this.chart) {
                    Plotly.relayout(this.chart.renderTo, { width: adjWidth, height: adjHeight });
                }
                this.baseChartOptions.layout.width = adjWidth;
                this.baseChartOptions.layout.height = adjHeight;
            });

            if (this.store) {
                this.store.on('load', function (t, records, opitons) {
                    if (t.getCount() <= 0) {
                        return;
                    }
                    this.chartOptions = jQuery.extend(true, {}, this.baseChartOptions, t.getAt(0).data);
                    
                    if (this.chartOptions.layout.title) {
                        this.chartOptions.layout.title.text = this.plotlyTextEncode(this.chartOptions.layout.title.text);
                    }
                    this.isEmpty = this.chartOptions.data && this.chartOptions.data.length === 0;

                    if (this.isEmpty) {
                        var ch_width = this.chartOptions.layout.width * 0.8;
                        var ch_height = this.chartOptions.layout.height * 0.8;
                        const errorConfig = getNoDataErrorConfig();
                        this.baseChartOptions = jQuery.extend(true, {}, this.baseChartOptions, errorConfig);
                    }
                    this.initNewChart.call(this);

                    if (this.chartOptions.metricExplorer) {
                        const chartDiv = document.getElementById(this.chartOptions.renderTo);
                        const metricDiv = document.getElementById(this.id);
                        let pointClick = false;
                        // Point Menu
                        chartDiv.on('plotly_click', (evt) => {
                            const point = getClickedPoint(evt);
                            if (point) {
                                pointClick = true;
                                XDMoD.Module.MetricExplorer.pointContextMenu(point, point.data.datasetId, undefined);
                            } else {
                                // Edge case where some chart hover labels are active anywhere on plot due to x|y unified hovermode
                                pointClick = false;
                            }
                        });
                        // Context Menu
                        const plotAreaLayer = metricDiv.getElementsByClassName('xy');
                        if (plotAreaLayer) {
                            const plotAreaDiv = metricDiv.getElementsByClassName('xy')[0];
                            if (plotAreaDiv.firstChild) {
                                plotAreaDiv.firstChild.addEventListener('click', (event) => {
                                    const hoverDivLayer = metricDiv.getElementsByClassName('hoverlayer');
                                    const hoverDiv = metricDiv.getElementsByClassName('hoverlayer')[0];
                                    if (!pointClick || (hoverDiv && hoverDiv.children.length === 0)) {
                                        XDMoD.Module.MetricExplorer.chartContextMenu.call(event, false, undefined);
                                    }
                                });
                            }
                        }
                        // Legend Menu
                        chartDiv.on('plotly_legendclick', (evt) => {
                            const series = evt.data[evt.curveNumber];
                            XDMoD.Module.MetricExplorer.seriesContextMenu(series, true, series.datasetId);
                            return false;
                        });
                        // Setup for yAxis Title and Tick events
                        let yAxes = [];
                        const layoutKeys = Object.keys(this.chartOptions.layout);
                        for (let i = 0; i < layoutKeys.length; i++) {
                            if (layoutKeys[i].startsWith('yaxis')) {
                                yAxes.push(layoutKeys[i]);
                            }
                        }
                        // Title Menu
                        const infoLayer = metricDiv.getElementsByClassName('infolayer');
                        const titleEvents = (el) => { 
                            if (el) {
                                const infoDiv = metricDiv.getElementsByClassName('infolayer')[0];
                                if (infoDiv.children.length != 0) {
                                    infoDiv.setAttribute("pointer-events", "all");
                                    const mainTitleDiv = infoDiv.getElementsByClassName('g-gtitle')[0];
                                    mainTitleDiv.addEventListener('click', (event) => {
                                        XDMoD.Module.MetricExplorer.titleContextMenu(event);
                                    });
                                    // Axis Menu
                                    let yAxisDiv = infoDiv.getElementsByClassName('g-ytitle')[0];
                                    if (yAxisDiv) {
                                        for (let i = 0; i < yAxes.length; i++) {
                                            const yAxis = i != 0 ? 'g-y' + (i+1) + 'title' : 'g-ytitle';
                                            yAxisDiv = infoDiv.getElementsByClassName(yAxis)[0];
                                            yAxisDiv.addEventListener('click', (event) => {
                                                XDMoD.Module.MetricExplorer.yAxisTitleContextMenu(this.chartOptions.layout[yAxes[i]], undefined);
                                            });
                                        }
                                    }
                                    const xAxisDiv = infoDiv.getElementsByClassName('g-xtitle')[0];
                                    if (xAxisDiv) {
                                        xAxisDiv.addEventListener('click', (event) => {
                                            XDMoD.Module.MetricExplorer.xAxisTitleContextMenu(this.chartOptions.layout.xaxis);
                                        });
                                    }
                                }
                            }
                        }
                        titleEvents(infoLayer);
                        // yAxis Ticks
                        const yAxisTickLayer = metricDiv.getElementsByClassName('yaxislayer-below');
                        const yAxisEvents = (el) => { 
                            if (el) {
                                const yAxisTickDiv = metricDiv.getElementsByClassName('yaxislayer-below')[0];
                                yAxisTickDiv.setAttribute("pointer-events", "all");
                                if (yAxisTickDiv.children && yAxisTickDiv.children.length != 0) {
                                    for (let i = 0; i < yAxisTickDiv.children.length; i++) {
                                        yAxisTickDiv.children[i].setAttribute("pointer-events", "all");
                                        yAxisTickDiv.children[i].addEventListener('click', (event) => {
                                            XDMoD.Module.MetricExplorer.yAxisContextMenu(this.chartOptions.layout.yaxis, this.chartOptions.data);
                                        });
                                    }
                                }
                                let multipleAxisLayer = metricDiv.getElementsByClassName('overaxes-below');
                                if (multipleAxisLayer) {
                                    multipleAxisLayer = multipleAxisLayer[0];
                                    multipleAxisLayer.setAttribute("pointer-events", "all");
                                    for (let i = 1; i < yAxes.length; i++) {
                                        const axisTicks = 'xy' + (i+1) + '-y';
                                        const multipleAxisTickDiv = multipleAxisLayer.getElementsByClassName(axisTicks)[0];
                                        if (multipleAxisTickDiv.children && multipleAxisTickDiv.children.length != 0) {
                                            for (let j = 0; j < multipleAxisTickDiv.children.length; j++) {
                                                multipleAxisTickDiv.children[j].setAttribute("pointer-events", "all");
                                                multipleAxisTickDiv.children[j].addEventListener('click', (event) => {
                                                    XDMoD.Module.MetricExplorer.yAxisContextMenu(this.chartOptions.layout[yAxes[i]], this.chartOptions.data);
                                                });
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        yAxisEvents(yAxisTickLayer);
                        // xAxis Ticks
                        const xAxisTickLayer = metricDiv.getElementsByClassName('xaxislayer-below');
                        const xAxisEvents = (el) => {
                            if (el) {
                                const xAxisTickDiv = metricDiv.getElementsByClassName('xaxislayer-below')[0];
                                xAxisTickDiv.setAttribute("pointer-events", "all");
                                if (xAxisTickDiv.children && xAxisTickDiv.children.length != 0) {
                                    for (let i = 0; i < xAxisTickDiv.children.length; i++) {
                                        xAxisTickDiv.children[i].setAttribute("pointer-events", "all");
                                        xAxisTickDiv.children[i].addEventListener('click', (event) => {
                                            XDMoD.Module.MetricExplorer.xAxisContextMenu(this.chartOptions.layout.xaxis);
                                        });
                                    }
                                }
                            }
                        }
                        xAxisEvents(xAxisTickLayer);

                        chartDiv.on('plotly_relayout', (evt) => { 
                            // Axis Titles and Ticks are re-rendered on relayout event so we need to reattach click events
                            const yAxisTickLayer = metricDiv.getElementsByClassName('yaxislayer-below');
                            const xAxisTickLayer = metricDiv.getElementsByClassName('xaxislayer-below');
                            const infoLayer = metricDiv.getElementsByClassName('infolayer');
                            yAxisEvents(yAxisTickLayer);
                            xAxisEvents(xAxisTickLayer);
                            titleEvents(infoLayer);
                        });

                        // Subtitle context menu
                        chartDiv.on('plotly_clickannotation', (evt) => {
                            if (evt.annotation.name === 'subtitle') {
                                XDMoD.Module.MetricExplorer.subtitleContextMenu(evt.event);
                            }
                        });
                    }

                }, this);
            }

        }, this, {
            single: true
        });

        this.addEvents("timeseries_zoom");
    },

    /**
     * Instantiates a new chart, optionally with given parameters instead of
     * the settings stored in this panel's configuration.
     *
     * @param  {Object} chartOptions (Optional) A set of Plotly options.
     */
    initNewChart: function (chartOptions) {
        if (this.chart) {
            Plotly.purge(this.chart.renderTo);
        }
        var finalChartOptions = {};
        if (chartOptions) {
            jQuery.extend(true, finalChartOptions, this.baseChartOptions, chartOptions);
        } else {
            jQuery.extend(true, finalChartOptions, this.baseChartOptions, this.chartOptions);
        }
        this.chart = XDMoD.utils.createChart(finalChartOptions);
    },

    /**
     * Instantiates a new chart that displays the given error message.
     *
     * @param  {String} mainMessage The main error message to display.
     * @param  {String} detailMessage (Optional) A secondary error message
     *                                to display.
	 * TODO:error display for plotly charts
     */
    displayError: function (mainMessage, detailMessage) {
        var errorChartOptions = {
            title: {
                text: mainMessage,
                yref: 'paper',
                yanchor: 'center',
                y: 0.5
            },
            xaxis: {
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

/**
 * Properly encodes a string for use as the title value in a Plotly instance.
 *
 * @param {String} text the text to be encoded for use in a Plotly title.
 * @return {String} the encoded text.
 **/
CCR.xdmod.ui.PlotlyPanel.prototype.plotlyTextEncode = function(text) {
    if (!text) {
        return text;
    }
    return String(text).replace(/>/g, '&gt;').replace(/</g, '&lt;');
};
