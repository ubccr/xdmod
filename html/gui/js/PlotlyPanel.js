
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
                        let pointClick = false;
                        // Point Menu
                        chartDiv.on('plotly_click', (evt) => {
                            const point = getClickedPoint(evt);
                            if (point) {
                                pointClick = true;
                                XDMoD.Module.MetricExplorer.pointContextMenu(point, point.data.datasetId, undefined);
                            } else {
                                pointClick = false;
                            }
                        });
                        // Context Menu
                        const plotAreaDiv = document.getElementsByClassName('xy')[0];
                        if (plotAreaDiv.firstChild) {
                            plotAreaDiv.firstChild.addEventListener('click', (event) => {
                                let hoverDiv = document.getElementsByClassName('hoverlayer')[0];
                                if (!pointClick || (hoverDiv && hoverDiv.children.length === 0)) {
                                    XDMoD.Module.MetricExplorer.chartContextMenu.call(event, false, undefined);
                                }
                            });
                        }
                        // Legend Menu
                        chartDiv.on('plotly_legendclick', (evt) => {
                            const series = evt.data[evt.curveNumber];
                            XDMoD.Module.MetricExplorer.seriesContextMenu(series, true, series.datasetId);
                            return false;
                        });
                        // Title Menu
                        const infoDiv = document.getElementsByClassName('infolayer')[0];
                        if (infoDiv) {
                            if (infoDiv.children.length != 0) {
                                infoDiv.setAttribute("pointer-events", "all");
                                const mainTitleDiv = document.getElementsByClassName('g-gtitle')[0];
                                mainTitleDiv.addEventListener('click', (event) => {
                                    XDMoD.Module.MetricExplorer.titleContextMenu(event);
                                });
                                // Axis Menu
                                const yAxisDiv = document.getElementsByClassName('g-ytitle')[0];
                                yAxisDiv.addEventListener('click', (event) => {
                                    XDMoD.Module.MetricExplorer.yAxisTitleContextMenu(this.chartOptions.layout.yaxis1);
                                    });
                                const xAxisDiv = document.getElementsByClassName('g-xtitle')[0];
                                xAxisDiv.addEventListener('click', (event) => {
                                    XDMoD.Module.MetricExplorer.xAxisTitleContextMenu(this.chartOptions.layout.xaxis);
                                });
                            }
                        }
                        // yAxis Ticks
                        const yAxisTickDiv = document.getElementsByClassName('yaxislayer-below')[0];
                        if (yAxisTickDiv) {
                            yAxisTickDiv.setAttribute("pointer-events", "all");
                            if (yAxisTickDiv.children && yAxisTickDiv.children.length != 0) {
                                for (let i = 0; i < yAxisTickDiv.children.length; i++) {
                                    yAxisTickDiv.children[i].setAttribute("pointer-events", "all");
                                    yAxisTickDiv.children[i].addEventListener('click', (event) => {
                                        XDMoD.Module.MetricExplorer.yAxisContextMenu(this.chartOptions.layout.yaxis1, this.chartOptions.data);
                                    });
                                }
                            }
                        }
                        // xAxis Ticks
                        const xAxisTickDiv = document.getElementsByClassName('xaxislayer-below')[0];
                        if (xAxisTickDiv) {
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
