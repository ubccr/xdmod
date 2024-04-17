/**
 *  Wait for the Highchart that contains the provided series to load.
 *
 *  @param {String}  series          - name of the series to be used to further
 *                                   identify the Highcharts instance.
 *  @param {Number}  [ms=9000]       - Milliseconds to wait for the element to
 **/
module.exports = function waitUntilChartLoadsBySeries(series, ms) {
    var timeOut = ms || 9000;
    return this.waitForVisible(
    "//*[local-name() = 'svg']//*[name() = 'g' and contains(@class, 'g-ytitle')]//*[local-name() = 'tspan' and text()[contains(., '" + series + "')]]",
    timeOut
  );
};
