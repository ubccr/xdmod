/* eslint no-param-reassign: ["error", { "props": false }]*/
// TODO: Convert label and axis formatters for Plotly
Ext.namespace('XDMoD.utils');

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    var baseChartOptions = {};

    jQuery.extend(true, baseChartOptions, chartOptions);
    if (baseChartOptions.data && baseChartOptions.data.length === 0) {
        let errorConfig = getNoDataErrorConfig();
        if (baseChartOptions.layout.thumbnail) {
            errorConfig.images[0].x = 0;
            errorConfig.images[0].y = 0.6;
        }
        else {
            errorConfig.images[0].x = 0.35;
            errorConfig.images[0].y = -0.2;
        }
        baseChartOptions.layout = errorConfig;
    } else {
        // Check for thumbnail because plot for summary charts fit more towards bottom of portlet
        if (!baseChartOptions.layout.title || baseChartOptions.layout.title.text === '') {
            baseChartOptions.layout.annotations[0].yshift = (baseChartOptions.layout.height * -1) * 0.675;
        } else {
            baseChartOptions.layout.annotations[0].yshift = (baseChartOptions.layout.height * -1) * 0.825;
        }
    }
    // Wait for Plotly promise to fullfil due to race condition from 'resize' listener in PlotlyPanel.js resize listener
    const chartPromise = Promise.resolve(Plotly.newPlot(baseChartOptions.renderTo, baseChartOptions.data, baseChartOptions.layout, {displayModeBar: false}));
    let el = Ext.get(baseChartOptions.renderTo);
    el.mask('Loading...');
    chartPromise.then((chart) => {
        el.unmask();
        return chart;
    });
}
