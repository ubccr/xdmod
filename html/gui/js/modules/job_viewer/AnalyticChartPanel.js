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
                height: 150,
                options: {
                },
                reflow: false
            },
            yAxis: {
                max: 1, // display all analytics barplots on a plot ranging from 0 to 1
                min: 0,
                gridLineColor: '#c0c0c0',
                labels: {
                    y: 13 // tighten vertical distance between axis and its labels
                },
                title: {
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
    isType: function(value, type) {
        if (typeof type === 'string') {
            return Object.prototype.toString.call(value) === type;
        } else {
            return Object.prototype.toString.call(value) ===
                Object.prototype.toString.call(type);
        }
    },

    // The instance of Highcharts used as this components primary display.
    chart: null,

    /**
     * This components 'constructor'.
     */
    initComponent: function() {

        this.addEvents(
            'update_data'
        );

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

            var seriesColor = this._getSeriesColor(this.chartOptions.series);
            if (seriesColor !== null) {
                this.chartOptions.chart.options.colors = [seriesColor];
            }

            if (this.chart) {
                this.chart.destroy();
            }
            this.chart = new Highcharts.Chart(this.chartOptions);
            if (CCR.exists(this.chart.series)
                && CCR.exists(this.chart.series[0])
                && CCR.exists(this.chart.series.data)
                && CCR.exists(this.chart.series[0].data[0])) {

                this._updateTitle(this.chart.series[0].data[0]);
            }

        }, // render

        /**
         * Event that is fired after this component has completely rendered to
         * the page. We take care of attaching our resize listeners which are
         * responsible for resizing the HighCharts instance whenever the window
         * is resized here.
         */
        afterrender: function() {
           this._attachResizeListener();
        }, // afterrender

        /**
         * Attempt to execute an update of this components' data ensuring that
         * the HighCharts instance updates.
         *
         * @param {Array|Number|Object} data that should be used to update this
         *                              component.
         */
        update_data: function(data) {
            var isType = this.isType;
            if (isType(data, CCR.Types.Array) && data.length > 0) {
                this._updateData(data[0]);
            } else if (typeof data === 'number') {
                this._updateData(data);
            } else if (isType(data, CCR.Types.Object)) {
                this._updateData(data);
            } else {
                console.log('Updating with I dont know...');
            }
            this.chart.redraw(true);
        }, // update_data

        /**
         * This even indicates that no data was retrieved for this chart and that
         * the appropriate steps should be taken. This includes setting a default
         * message to communicate to the users that no data was returned.
         *
         */
        no_data: function() {
            this.chart.setTitle({
                text: 'No Data Returned',
                align: "center",
                verticalAlign: "middle",
                style: {
                    color: "#60606a",
                    fontWeight: "bold",
                    fontSize: '12px'
                }
            });
        },

        /**
         * Attempt to reset this components' data ensuring that the HighCharts
         * instance updates correctly to reflect the change, if any.
         */
        reset: function() {
            var exists = CCR.exists;
            if (exists(this.chart)) {
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
     * Attach a new listener to this components parent 'resize' event that will
     * will fire a 'resize' event for this component.
     *
     * @private
     */
    _attachResizeListener: function() {
        var self = this;
        var target = this.ownerCt;
        target.on('resize', function(panel,
                                     adjWidth,
                                     adjHeight,
                                     rawWidth,
                                     rawHeight) {
            self.fireEvent('resize', self, adjWidth, adjHeight, rawWidth, rawHeight);
        });
    }, // _attachResizeListener

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
            this.chart.options.chart.plotBackgroundColor = this._rgbToHex(nColor.rgba);

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
     * Both set the color for the provided series and return it if the requisite
     * properties could be found.
     *
     * @param {Array} series
     * @returns {*}
     * @private
     */
    _getSeriesColor: function(series) {
        var isType = this.isType;
        if (isType(series,'[object Array]')) {
            for (var i = 0; i < series.length; i++) {
                var record = series[i];
                if ( record && record.data &&
                     isType(record.data,'[object Array]')) {
                    var data = record.data[0];
                    var color = this._getDataColor(data);
                    record.color = color;
                    return color;
                }
            }
        }
        return null;
    }, // _getSeriesColor

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
    }, // _getDataColor

    /**
     * Helper function that converts an array of 4 numbers (R,G,B,A) to a hex
     * color string representation.
     *
     * @param {Array} data
     * @returns {String}
     * @private
     */
    _rgbToHex: function(data) {
        var isType = CCR.isType;
        var toHex = this._numberToHex;
        return isType(data, CCR.Types.Array) && data.length === 4
                ? "#" + toHex(data[0]) + toHex(data[1]) + toHex(data[2])
                : '';

    }, // _rgbToHex

    /**
     * Helper function that converts a number value to its equivalent
     * hexadecimal value.
     *
     * @param {Number} value
     * @returns {String}
     * @private
     */
    _numberToHex: function(value) {
        var isType = CCR.isType;
        if (isType(value, CCR.Types.Number)) {
            return ('0' + new Number(value).toString(16)).slice(-2);
        }
        return '00';
    } // _numberToHex

}); // XDMoD.Module.JobViewer.AnalyticChartPanel
