Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

/**
 * A Component that handles the display of Job Viewer Analytics to the user. An
 * Analytic is meant to provide the user with an 'at-a-glance' view into a
 * particular dimension of their jobs performance.
 *
 * These are characterized as small left to right bar charts with x-axis ranging
 * from 0 -> 1. 0 is considered 'bad' and 1 is considered 'good'. These charts
 * also provide color hinting in the form of color steps @
 * <= 0.25, <= 0.5, <= 0.75 and <= 1.0. These color steps have been pre-selected
 * but can be updated if the need arises by the coder via the 'colorSteps'
 * config option.
 *
 */
XDMoD.Module.JobViewer.AnalyticChartPanel = Ext.extend(Ext.Panel, {
    _DEFAULT_CONFIG: {
        delimiter: ':',
        chartOptions: {
            exporting: {
                enabled: false
            },
            chart: {
                type: 'bar',
                height: 65,
                options: {
                },
                reflow: false
            },
            yAxis: {
                max: 1, // display all analytics barplots on a plot ranging from 0 to 1
                min: 0,
                gridLineColor: '#c0c0c0',
                labels: {
                    enabled: false
                },
                title: {
                    text: '',
                    margin: 1, // tighten vertical distance between axis title and labels
                    style: {
                        fontWeight: 'bold',
                        color: '#5078a0'
                    }
                }
            },
            xAxis: {
                tickLength: 0,
                labels: {
                    enabled: false
                }
            },
            tooltip: {
                enabled: false
            },
            credits: {
                enabled: false
            },
            legend: {
                enabled: false
            },
            plotOptions: {
                bar: {

                },
                series: {
                  animation: false
                }
            }
        },
        colorSteps: [
            {
                value: .25,
                color: '#FF0000'
            },
            {
                value: .50,
                color: '#FFB336'
            },
            {
                value: .75,
                color: '#DDDF00'
            },
            {
                value: 1,
                color: '#50B432'
            }
        ]
    },

    // The instance of Highcharts used as this components primary display.
    chart: null,

    /**
     * This components 'constructor'.
     */
    initComponent: function() {

        this.colorSteps = Ext.apply(
                this.colorSteps || [],
                this._DEFAULT_CONFIG.colorSteps
        );

        XDMoD.Module.JobViewer.AnalyticChartPanel.superclass.initComponent.call(
            this
        );

    }, // initComponent

    listeners: {

        /**
         * Event that is fired when this component is rendered to the page.
         * Processes that require the HighCharts component to be created /
         * a visible element to hook into are handled here.
         */
        render: function() {

            Ext.apply(this.chartOptions, this._DEFAULT_CONFIG.chartOptions);

            this.chartOptions.chart.renderTo = this.id;

            if (this.chart) {
                this.chart.destroy();
            }
            this.chart = new Highcharts.Chart(this.chartOptions);

        }, // render

        /**
         * Attempt to execute an update of this components' data ensuring that
         * the HighCharts instance updates.
         *
         * @param {Array|Number|Object} data that should be used to update this
         *                              component.
         */
        update_data: function(data) {
            this._updateData(data);
            this.chart.redraw(true);
        }, // update_data

        /**
         * Attempt to reset this components' data ensuring that the HighCharts
         * instance updates correctly to reflect the change, if any.
         */
        reset: function() {
            if (this.chart) {
                while (this.chart.series.length > 0) {
                    this.chart.series[0].remove(false);
                }
                this.chart.redraw();
            }
        }, // reset

        destroy: function () {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },

        /**
         * Attempt to resize this components HighCharts instance such that it
         * falls with in the new adjWidth and adjHeight.
         *
         * @param {Ext.Panel} panel
         * @param adjWidth
         * @param adjHeight
         * @param rawWidth
         * @param rawHeight
         */
        resize: function(panel, adjWidth, adjHeight, rawWidth, rawHeight) {
            if (this.chart && this.chart.series && this.chart.series.length > 0) this.chart.reflow();
        } // resize

    }, // listeners

    /**
     * Helper function that handles the work of updating this HighCharts
     * instance with new data.
     *
     * @param {Array|Object} data
     * @private
     */
    _updateData: function(data) {

        var brightFactor = 0.4;
        var color, nColor;

        while (this.chart.series.length > 0) {
            this.chart.series[0].remove(true);
        }

        this.chart.plotBackground = null;
        this.chart.chartBackground = null;

        if (data.error == '') { 

            color = this._getDataColor(data.value);
            nColor = new Highcharts.Color(color).brighten(brightFactor);
            this.chart.options.chart.plotBackgroundColor = 'rgba(' + nColor.rgba + ')';

            this.chart.addSeries({
                name: data.name ? data.name : '',
                data: [data.value],
                color: color
            }, true, true);

        } else {
            var text = this.chart.renderer.text(data.error, this.chart.plotLeft + 23, this.chart.plotTop + 10).add();
            var box = text.getBBox();
            this.chart.renderer.image('/gui/images/about_16.png', box.x - 23, box.y - 1, 16, 16).add();
        }

        this._updateTitle(data);
        this.ownerCt.doLayout(false, true);

    }, // _updateData

    /**
     * Update the title of the panel that contains this chart panel.
     *
     * @param {Object} data
     * @private
     */
    _updateTitle: function(data) {
        var title = data.name + this._DEFAULT_CONFIG.delimiter + ' ' + data.value;
        this.ownerCt.setTitle(title);
    }, // _updateTitle

    /**
     * Attempt to retrieve the color step for the provided data point.
     *
     * @param {Number} data
     * @returns {Null|String}
     * @private
     */
    _getDataColor: function(data) {
        var color = null;
        if (typeof data === 'number') {
            var steps = this.colorSteps;
            var count = steps.length;
            for ( var i = 0; i < count; i++) {
                var step = steps[i];
                if (data <= step.value) {
                    return step.color;
                }
            }

            // if we made it here than we didn't return one of the other values.
            return steps[steps.length - 1];
        }
        return color;
    }
}); // XDMoD.Module.JobViewer.AnalyticChartPanel
