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
            color: '#ff0000',
            bg_color: 'rgb(255,102,102)'
        },
        {
            value: .50,
            color: '#ffb336',
            bg_color: 'rgb(255,255,156)'

        },
        {
            value: .75,
            color: '#dddf00',
            bg_color: 'rgb(255,255,102)'
        },
        {
            value: 1,
            color: '#50b432',
            bg_color: 'rgb(182,255,152)'
        }
        ],

        layout: {
            height: 65,
            xaxis: {
                showticklabels: false,
                range: [0,1],
                color: '#606060',
                ticks: 'inside',
                tick0: 0.0,
                dtick: 0.2,
                ticklen: 2,
                tickcolor: '#ffffff',
                gridcolor: '#c0c0c0',
                linecolor: '#ffffff',
                zeroline : false,
                showgrid: true,
                zerolinecolor: '#000000',
                showline: false,
                zerolinewidth: 0,
                fixedrange: true
            },
            yaxis: {
                showticklabels: false,
                color: '#606060',
                showgrid : false,
                gridcolor: '#c0c0c0',
                linecolor: '#ffffff',
                zeroline: false,
                zerolinecolor: '#ffffff',
                showline: false,
                rangemode: 'tozero',
                zerolinewidth: 0,
                fixedrange: true
            },
            hovermode: false,
            shapes: [],
            images: [],
            annotations: [],
            showlegend: false,
            margin: {
                t: 10,
                l: 9,
                r: 13,
                b: 10,
                pad: 0
            }
        },
        config: {
            displayModeBar: false,
            staticPlot: true
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
        this.colorSteps = Ext.apply(this.colorSteps || [], this._DEFAULT_CONFIG.colorSteps);
        XDMoD.Module.JobViewer.AnalyticChartPanel.superclass.initComponent.call(this);
    }, // initComponent

    listeners: {

        /**
         * Event that is fired when this component is rendered to the page.
         * Processes that require the HighCharts component to be created /
         * a visible element to hook into are handled here.
         */
        render: function() {
            this.chart = Plotly.newPlot(this.id, [], this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
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
        }, // update_data

        /**
         * Attempt to reset this components' data ensuring that the HighCharts
         * instance updates correctly to reflect the change, if any.
         */
        reset: function() {
            if (this.chart) {
                Plotly.react(this.id, [], this._DEFAULT_CONFIG.layout, this._DEFAULT_CONFIG.config);
            }
        }, // reset

        beforedestroy: function () {
            if (this.chart) {
                Plotly.purge(this.id);
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
                const container = document.querySelector('#'+this.id);
                const bgcolor = container._fullLayout.plot_bgcolor;
                const annotation = structuredClone(container._fullLayout.annotations);
                const image = structuredClone(container._fullLayout.images);
                Plotly.relayout(this.id, {width: adjWidth});
                if (annotation.length > 0){
                    Plotly.relayout(this.id, {images: image});
                    Plotly.relayout(this.id, {annotations: annotation});
                    let update = {
                        xaxis: {
                            showticklabels: false,
                            tickcolor: '#ffffff',
                            gidcolor: '#ffffff',
                            linecolor: '#ffffff',
                            zeroline : false,
                            showgrid: false,
                            zerolinecolor: '#000000',
                            showline: false,
                            zerolinewidth: 0
                        }
                    };
                    Plotly.relayout(this.id, update);
                    Plotly.relayout(this.id, {plot_bgcolor: bgcolor});
                }
                else {
                    Plotly.relayout(this.id, {images: []});
                    Plotly.relayout(this.id, {annotations: []});
                    let update = {
                        xaxis: {
                            showticklabels: false,
                            range: [0,1],
                            color: '#606060',
                            ticks: 'inside',
                            tick0: 0.0,
                            dtick: 0.2,
                            ticklen: 2,
                            tickcolor: '#ffffff',
                            gridcolor: '#c0c0c0',
                            linecolor: '#ffffff',
                            zeroline : false,
                            showgrid: true,
                            zerolinecolor: '#000000',
                            showline: false,
                            zerolinewidth: 0,
                            fixedrange: true
                        }
                    };
                    Plotly.relayout(this.id, update);
                    Plotly.relayout(this.id, {plot_bgcolor: bgcolor});
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
            this.errorMsg = null;
        }
        if (errorStr) {
            this.errorMsg = errorStr;
            let errorImage = [
            {
                source: '/gui/images/about_16.png',
                align: 'left',
                xref: 'paper',
                yref: 'paper',
                sizex: 0.4,
                sizey: 0.4,
                x: 0,
                y: 1.2
            }
            ];
            this._DEFAULT_CONFIG.layout.images = errorImage;
            let errorText = [
            {
                text: '<b>' + errorStr + '</b>',
                align: 'left',
                xref: 'paper',
                yref: 'paper',
                font:{
                    size: 11,
                },
                x : 0.05,
                y : 1.2,
                showarrow: false
            }
            ];
            this._DEFAULT_CONFIG.layout.annotations = errorText;
            this._DEFAULT_CONFIG.layout.xaxis.showgrid = false;
        }
        else {
            this._DEFAULT_CONFIG.layout.images = [];
            this._DEFAULT_CONFIG.layout.annotations = [];
            this._DEFAULT_CONFIG.layout.xaxis.showgrid = true;
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
        let trace = {};
        if (data.error == '') {
            let chartColor = this._getDataColor(data.value);
            this._DEFAULT_CONFIG.layout['plot_bgcolor'] = chartColor.bg_color;

            trace = {
                x: [data.value],
                name: data.name ? data.name : '',
                width: [0.5],
                marker:{
                    color: chartColor.color,
                    line:{
                        color: '#ffffff',
                        width: 1
                    }
                },
                type: 'bar',
                orientation: 'h',
            };
        }
        else {
            this._DEFAULT_CONFIG.layout['plot_bgcolor'] = '#ffffff';
        }
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
        var color = null;
        if (typeof data === 'number') {
            var steps = this.colorSteps;
            var count = steps.length;
            for ( var i = 0; i < count; i++) {
                var step = steps[i];
                if (data <= step.value) {
                    let ret = {
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
