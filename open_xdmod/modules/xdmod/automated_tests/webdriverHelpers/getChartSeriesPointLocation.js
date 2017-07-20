/**
 * Attempt to retrieve the location ( top, left ) relative to the provided
 * containerId of the point in the series ( pointIndex, seriesIndex ). A top
 * and left offset can also be supplied.
 *
 * @param {String} containerId     - the 'id' property of the container that
 *                                   contains the Highcharts instance.
 *                                   NOTE: this is an Ext id so no '#'.
 * @param {Number} [seriesIndex=0] - the index of the series that contains
 *                                   the point to be located.
 * @param {Number} [pointIndex=0]  - the index of the point to be located.
 *
 * @return {Object} in the form:
 *                    {
 *                      top:  <number>,
 *                      left: <number>
 *                    }
 **/
module.exports = function getChartSeriesPointLocation(containerId, seriesIndex, pointIndex) {
    var thisSeriesIndex = (seriesIndex !== undefined) ? seriesIndex : 0;
    var thisPointIndex = (pointIndex !== undefined) ? pointIndex : 0;
    return this.execute(function (innerContainerId, innerSeriesIndex, innerPointIndex) {
        // TODO: Fix this withOut having to use EXT if Possible
        // eslint-disable-next-line no-undef
        var cmp = Ext.getCmp(innerContainerId);
        var axes = cmp.chart.axes;
        var xAxis = axes[0];
        var yAxis = axes[1];
        var series = cmp.chart.series[innerSeriesIndex];
        var point = series.data[innerPointIndex];
        var top = yAxis.toPixels(point.options.y);
        var left = xAxis.toPixels(point.options.x);

        return {
            top: Number(top.toFixed(0)),
            left: Number(left.toFixed(0))
        };
    }, containerId, thisSeriesIndex, thisPointIndex);
};
