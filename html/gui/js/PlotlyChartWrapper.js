/* eslint no-param-reassign: ["error", { "props": false }]*/
// TODO: Convert label and axis formatters for Plotly
Ext.namespace('XDMoD.utils');

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    var baseChartOptions = {};

    jQuery.extend(true, baseChartOptions, chartOptions);
    //console.log(baseChartOptions);
    return Plotly.newPlot(baseChartOptions.renderTo, baseChartOptions.data, baseChartOptions.layout, {displayModeBar: false});

}
