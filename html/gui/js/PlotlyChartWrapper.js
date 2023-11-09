/* eslint no-param-reassign: ["error", { "props": false }]*/
// TODO: Convert label and axis formatters for Plotly
Ext.namespace('XDMoD.utils');

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    var baseChartOptions = {};

    jQuery.extend(true, baseChartOptions, chartOptions);

    // Wait for Plotly promise to fullfil due to race condition from 'resize' listener in PlotlyPanel.js resize listener
    const chartPromise = Promise.resolve(Plotly.newPlot(baseChartOptions.renderTo, baseChartOptions.data, baseChartOptions.layout, {displayModeBar: false}));
    let el = Ext.get(baseChartOptions.renderTo);
    el.mask('Loading...');
    chartPromise.then((chart) => {
        el.unmask();
        return chart;
    });
}
