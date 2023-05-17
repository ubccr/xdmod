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
                color: 'rgb(255,0,0)'
            },
            {
                value: .50,
                color: 'rgb(255,179,54)'
            },
            {
                value: .75,
                color: 'rgb(221,223,0)'
            },
            {
                value: 1,
                color: 'rgb(80,180,50)'
            }
        ],
    
           
        layout: {
            'hoverlabel': {
                'bgcolor': 'white'
            },
            'paper_bgcolor': 'white',
            'plot_bgcolor': 'white',
            'height': 60,
            'xaxis': {
                'showticklabels': false,
                'range': [0,1],
                'color': '#606060',
                'ticks': 'inside',
                'tick0': 0.0,
                'dtick': 0.2,
                'ticklen': 2,
                'tickcolor': 'white',
                'gridcolor': '#c0c0c0',
                'linecolor': 'white',
                'zeroline' : false,
                'showgrid': true,
                'zerolinecolor': 'black',
                'showline': false,
                'zerolinewidth': 0,
		'fixedrange': true
            },
            'yaxis': {
                'showticklabels': false,
                'color': '#606060',
                'showgrid' : false,
                'gridcolor': '#c0c0c0',
                'linecolor': 'white',
                'zeroline': false,
                'zerolinecolor': 'white',
                'showline': false,
                'rangemode': 'tozero',
                'zerolinewidth': 0,
		'fixedrange': true
            },
            'hovermode': false,
            'showlegend': false,
	    'autosize': true,
            'margin': {
                't': 10,
                'l': 7.5,
                'r': 7.5,
                'b': 10,
                'pad': 0
            }
        },

        traces: [],
	config: {
		displayModeBar: false,
	},

        },

    // The instance of Highcharts used as this components primary display.
    chart: null,


    // private member that stores the error message object
    errorMsg: null,

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
            Plotly.newPlot(this.id, this._DEFAULT_CONFIG.traces, this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);

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
            Plotly.react(this.id, this._DEFAULT_CONFIG.traces, this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
        }, // update_data

        /**
         * Attempt to reset this components' data ensuring that the HighCharts
         * instance updates correctly to reflect the change, if any.
         */
        reset: function() {
            if (this.chart) {
                this.traces = [];
                Plotly.react(this.id, this._DEFAULT_CONFIG.traces, this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
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
        resize: function (panel, adjWidth, adjHeight, rawWidth, rawHeight) {
            if (this.chart) {
                this._DEFAULT_CONFIG.layout['width'] = adjWidth;
                this._DEFAULT_CONFIG.layout['height'] = adjHeight;
                Plotly.react(this.id, this._DEFAULT_CONFIG.traces, this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
                if (this.errorMsg) {
                    this.updateErrorMessage(this.errorMsg.text.textStr);
                }
            }
        } // resize

    }, // listeners

    /* Add an error message or update an existing error message on the chart.
     *
     * @param errorStr the string to display. The message is displayed next to
     * an alert icon. If the string is empty then the alert icon is not
     * displayed
     */
    updateErrorMessage: function (errorStr) {
        if (this.errorMsg) {
            this.errorMsg.text.destroy();
            this.errorMsg.image.destroy();
            this.errorMsg = null;
        }
        if (errorStr) {
            this._DEFAULT_CONFIG.layout['images'] = [
                {
                    "source": '/gui/images/about_16.png',
		    "align": "left",
                    "xref": "paper",
                    "yref": "paper",
                    "sizex": 0.4,
                    "sizey": 0.4,
                    "x": 0,
                    "y": 1.2
                }
            ]
	    console.log("new annontation");
            this._DEFAULT_CONFIG.layout['annotations'] = [
                {       
                    "text": '<b>' + errorStr + '</b>',
                    "align": "left",
                    "xref": "paper",
                    "yref": "paper",
		    "font":{ 
			"size": 11,
		    },
                    "x" : 0.05,
                    "y" : 1.2,
                    "showarrow": false
                }
            ]
            this._DEFAULT_CONFIG.layout['xaxis']['showgrid'] = false;
        }
        else {
            this._DEFAULT_CONFIG.layout['images'] = [];
            this._DEFAULT_CONFIG.layout['annotations'] = [];
            this._DEFAULT_CONFIG.layout['xaxis']['showgrid'] = true;
        }
    },

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

        this._DEFAULT_CONFIG.traces = [];

        this._DEFAULT_CONFIG.layout['plot_bgcolor'] = 'white';


        if (data.error == '') { 

            color = this._getDataColor(data.value);
            bg_color = color.substring(0,3) + 'a' + color.substring(3, color.length-1) + ',0.4)';
            this._DEFAULT_CONFIG.layout['plot_bgcolor'] = bg_color;
            
            this._DEFAULT_CONFIG.traces.push(
                {
                    x: [data.value],
                    name: data.name ? data.name : '',
                    width: [0.5],
                    marker:{ 
                        color: color,
                        line:{
                            color: 'white',
                            width: 1.5
                        }
                    },
                    type: 'bar',
                    orientation: 'h'
                }
            );
        }
        this.updateErrorMessage(data.error);
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
