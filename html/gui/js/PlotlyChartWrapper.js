/* eslint no-param-reassign: ["error", { "props": false }]*/
// TODO: Convert label and axis formatters for Plotly
Ext.namespace('XDMoD.utils');

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    var baseChartOptions = {};

    jQuery.extend(true, baseChartOptions, chartOptions);

    return Plotly.newPlot(baseChartOptions.id, baseChartOptions.layout, baseChartOptions.data, {displayModeBar: false});
