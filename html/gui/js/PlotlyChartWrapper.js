/* eslint no-param-reassign: ["error", { "props": false }]*/
// TODO: Convert label and axis formatters for Plotly
Ext.namespace('XDMoD.utils');

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    var baseChartOptions = {};

    jQuery.extend(true, baseChartOptions, chartOptions);
    if (baseChartOptions.data && baseChartOptions.data.length === 0) {
        baseChartOptions.layout = getNoDataErrorConfig();
    }
    if ('annotations' in baseChartOptions.layout && baseChartOptions.layout.annotations.length > 0) {
       if (baseChartOptions.data.length >= 30) {
           baseChartOptions.layout.annotations[0].yshift = (baseChartOptions.layout.height * -1) + 45;
       }
       else if (baseChartOptions.data.length >= 20 && baseChartOptions.data.length < 30) {
           baseChartOptions.layout.annotations[0].yshift = (baseChartOptions.layout.height * -1) + 35;
       }
       else {
           baseChartOptions.layout.annotations[0].yshift = (baseChartOptions.layout.height * -1) + 35;
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
