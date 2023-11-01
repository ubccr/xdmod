
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
        var defaultOptions = {};

        defaultOptions.renderTo = this.id;
        defaultOptions.width = this.width;
        defaultOptions.height = this.height;
        //defaultOptions.exporting.enabled = false;
        //defaultOptions.credits.enabled = true;

        this.baseChartOptions = jQuery.extend(true, {}, defaultOptions, this.baseChartOptions);

        this.on('render', function () {
            this.initNewChart.call(this);

            this.on('resize', function (t, adjWidth, adjHeight, rawWidth, rawHeight) {
                if (this.chart) {
                    this.chart.setSize(adjWidth, adjHeight);
                }
                this.baseChartOptions.width = adjWidth;
                this.baseChartOptions.height = adjHeight;
            });

            if (this.store) {
                this.store.on('load', function (t, records, opitons) {
                    if (t.getCount() <= 0) {
                        return;
                    }
                    this.chartOptions = jQuery.extend(true, {}, this.baseChartOptions, t.getAt(0).data);

                    //this.chartOptions.exporting.enabled = (this.exporting === true);
                    //this.chartOptions.credits.enabled = (this.credits === true);

                    this.chartOptions.layout.title.text = this.plotlyTextEncode(this.chartOptions.title.text);
                    this.isEmpty = this.chartOptions.data && this.chartOptions.data.length === 0;

                    this.initNewChart.call(this);
                    if (this.isEmpty) {
                        var ch_width = this.chartOptions.layout.width * 0.8;
                        var ch_height = this.chartOptions.layout.height * 0.8;

                        var errorObj = getErrorConfig();
                        this.chartOptions.layout.images.push(errorObj.image);
                        this.chartOptions.layout.annotations.push(errorObj.text);
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
            }
        };

        if (detailMessage) {
            errorChartOptions.annotations = {
                text: detailMessage,
                xref: 'paper',
                yref: 'paper',
                xanchor: 'center',
                yanchor: 'top',
                x: 0.5,
                y: 0.8,
                showarrow: false
            };
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
