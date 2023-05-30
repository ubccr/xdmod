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
        colorSteps: [
            {
                value: .25,
                color: '#FF0000',
		bg_color: 'rgb(255,102,102)'
            },
            {
                value: .50,
                color: '#FFB336',
		bg_color: 'rgb(255,255,156)'
		
            },
            {
                value: .75,
                color: '#DDDF00',
		bg_color: 'rgb(255,255,102)'
            },
            {
                value: 1,
                color: '#50B432',
		bg_color: 'rgb(182,255,152)'
            }
        ],
    
           
        layout: {
            hoverlabel: {
                bgcolor: 'white'
            },
            paper_bgcolor: 'white',
            plot_bgcolor: 'white',
            height: 65,
            xaxis: {
                showticklabels: false,
                range: [0,1],
                color: '#606060',
                ticks: 'inside',
                tick0: 0.0,
                dtick: 0.2,
                ticklen: 2,
                tickcolor: 'white',
                gridcolor: '#c0c0c0',
                linecolor: 'white',
                zeroline : false,
                showgrid: true,
                zerolinecolor: 'black',
                showline: false,
                zerolinewidth: 0,
		        fixedrange: true
            },
            yaxis: {
                showticklabels: false,
                color: '#606060',
                showgrid : false,
                gridcolor: '#c0c0c0',
                linecolor: 'white',
                zeroline: false,
                zerolinecolor: 'white',
                showline: false,
                rangemode: 'tozero',
                zerolinewidth: 0,
		        fixedrange: true
            },
            hovermode: false,
	        shapes: [],
            showlegend: false,
            margin: {
                t: 12.5,
                l: 7.5,
                r: 7.5,
                b: 12.5,
                pad: 0
            }
        },
        traces: [],
	    config: {
		    displayModeBar: false,
	    },
	    bgColors: []
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
	        this.chart = true;
            Plotly.newPlot(this.id, [], this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
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
            //Plotly.react(this.id, this._DEFAULT_CONFIG.traces, this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
        }, // update_data

        /**
         * Attempt to reset this components' data ensuring that the HighCharts
         * instance updates correctly to reflect the change, if any.
         */
        reset: function() {
            if (this.chart) {
                this._DEFAULT_CONFIG.traces = [];
                Plotly.react(this.id, this._DEFAULT_CONFIG.traces, this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
            }
        }, // reset

        beforedestroy: function () {
            if (this.chart) {
		        Plotly.purge(this.id);
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
		        Plotly.relayout(this.id, {width: adjWidth}); 
        		var elements = document.querySelectorAll('.bglayer');
                if (elements){
		            for (var i=0; i < elements.length; i++){
			            if (elements[i].firstChild){
        				    elements[i].firstChild.style.fill = this._DEFAULT_CONFIG.bgColors[i];
    		        	}
	    	        }
                }
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
        this._DEFAULT_CONFIG.traces = [];

        this._DEFAULT_CONFIG.layout['plot_bgcolor'] = 'white';
	var trace = {};
        if (data.error == '') { 

            var chartColor = this._getDataColor(data.value);
	    this._DEFAULT_CONFIG.layout['plot_bgcolor'] = chartColor.bg_color;
            this._DEFAULT_CONFIG.bgColors.push(chartColor.bg_color);
            
            trace = {
                    x: [data.value],
                    name: data.name ? data.name : '',
                    width: [0.5],
                    marker:{ 
                        color: chartColor.color,
                        line:{
                            color: 'white',
                            width: 1
                        }
                    },
                    type: 'bar',
                    orientation: 'h',
                };
        }
	this._DEFAULT_CONFIG.traces.push(trace);
        this.updateErrorMessage(data.error);
        this._updateTitle(data);
        this.ownerCt.doLayout(false, true);
        Plotly.react(this.id, [trace], this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);

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
	var ret = {};
        var color = null;
        if (typeof data === 'number') {
            var steps = this.colorSteps;
            var count = steps.length;
            for ( var i = 0; i < count; i++) {
                var step = steps[i];
                if (data <= step.value) {
		    ret = {
			color: step.color,
			bg_color: step.bg_color
		    };
                    return ret;
                }
            }

            // if we made it here than we didn't return one of the other values.
            return steps[steps.length - 1];
        }
        return color;
    }
}); // XDMoD.Module.JobViewer.AnalyticChartPanel
