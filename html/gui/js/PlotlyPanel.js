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
        var defaultOptions = getDefaultLayout();

        defaultOptions.renderTo = this.id;
        defaultOptions.width = this.width;
        defaultOptions.height = this.height;
        defaultOptions.exporting.enabled = false;
        defaultOptions.credits.enabled = true;

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

                    // Format Data


