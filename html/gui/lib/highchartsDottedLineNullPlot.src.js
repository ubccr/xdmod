// Override for HighCharts Series prototype drawGraph method
// Draw dotted line past null points for series with option.connectNull = true.
//
// http://www.highcharts.com/docs/extending-highcharts/extending-highcharts

(function (H) {
    /**
     * Build an array of graph segments that frame null series points.
     * Each array element in missingSegments[] should consist of two points.
     * (see Series prototype @getSegment in HighCharts 4.2.1 and earlier)
     */
    H.wrap(H.Series.prototype, 'getMissingSegment', function (proceed) {
        var series = this,
            lastNull = -1,
            lastNonNull = -1,
            missingSegments = [],
            points = series.points,
            pointsLength = points.length;

        if (pointsLength) { // no action required for []

            var missingSegment = [];

            H.each(points, function (point, i) {

                // current point is null or not?
                if (point.y === null) {
                    lastNull = i;
                } else {
                    lastNonNull = i;
                }

                // Now look for and assign the segment's ends:

                // beginning of the null segment
                if(lastNull === i && lastNonNull === i - 1 && lastNonNull > -1) {
                    missingSegment.push(points[i-1]);
                }
                // end of null segment
                if(((i === lastNonNull && lastNull === i - 1)  ||
                    (i === pointsLength - 1 && lastNull !== i)) && missingSegment.length > 0) {

                        missingSegment.push(points[i]);
                        missingSegments.push(missingSegment);

                        // reinitialize segment
                        missingSegment = [];
                }
            });
        }
        return(missingSegments);
    });

    /**
     * Compute graph path for null series points  (see Series prototype @getGraphPath)
     */
    H.wrap(H.Series.prototype, 'getMissingGraphPath', function (proceed, points) {
        var series = this,
            options = series.options,
            step = options.step,
            missingGraphPath = [],
            missingSegments = this.getMissingSegment();

        // Reverse the steps (#5004)
        step = { right: 1, center: 2 }[step] || (step && 3);

        // Extract each missing segment, which is a pair of points:
        H.each(missingSegments, function (points, iseg) {

            // Build the line defined by the two points:
            H.each(points, function (point, i) {

                var plotX = point.plotX,
                    plotY = point.plotY,
                    lastPoint = points[i - 1],
                    pathToPoint; // the path to this point from the previous

                if (i === 0 ) {
                    pathToPoint = ['M', point.plotX, point.plotY];

                } else if (series.getPointSpline) { // generate the spline as defined in the SplineSeries object

                    pathToPoint = series.getPointSpline(points, point, i);

                } else if (step) {

                    if (step === 1) { // right
                        pathToPoint = [
                            'L',
                            lastPoint.plotX,
                            plotY
                        ];

                    } else if (step === 2) { // center
                        pathToPoint = [
                            'L',
                            (lastPoint.plotX + plotX) / 2,
                            lastPoint.plotY,
                            'L',
                            (lastPoint.plotX + plotX) / 2,
                            plotY
                        ];

                    } else {
                        pathToPoint = [
                            'L',
                            plotX,
                            lastPoint.plotY
                        ];
                    }
                    pathToPoint.push('L', plotX, plotY);

                } else {
                    // normal line to next point
                    pathToPoint = [
                        'L',
                        plotX,
                        plotY
                    ];
                }
                // Add the line to missingGraphPath
                missingGraphPath.push.apply(missingGraphPath, pathToPoint);
            });
        });
        //series.missingGraphPath = missingGraphPath;
        return missingGraphPath;
    });

    /**
     * Partial override to Series.drawGraph to connect across null series points with dotted line
     * (see Series prototype @drawGraph )
     */
    H.wrap(H.Series.prototype, 'drawGraph', function (proceed) {

        var series = this,
            options = this.options,
            props = [['graph', options.lineColor || this.color, options.dashStyle]],
            lineWidth = options.lineWidth;

        // missingGraph case: Draw the dotted line for null segments of the graph only if
        // options.connectNulls is set for the Highcharts Series
        // and we are drawing a line or spline type graph.

        if (options.connectNulls && (options.type == 'line' || options.type == 'spline') ) {
            var missingGraphPath = this.getMissingGraphPath();

            props = [['missingGraph', options.lineColor || this.color, options.dashStyle]],
            H.each(props, function (prop, i) {
                var graphKey = prop[0],
                    graph = series[graphKey],
                    attribs;

                if (graph) {
                    H.stop(graph); // cancel running animations
                    graph.animate({ d: missingGraphPath });

                } else if (lineWidth) {
                    attribs = {
                        stroke: prop[1],
                        'stroke-width': lineWidth,
                        dashstyle: 'Dot', // this is the punchline for missingGraph points!
                        zIndex: 1
                    };

                    series[graphKey] = series.chart.renderer.path(missingGraphPath)
                        .attr(attribs)
                        .add(series.group)
                        .shadow((i < 2) && options.shadow); // add shadow to normal series (0) or to first zone (1)
                }
            });
        }
        // Call the original drawGraph function for the non-null portion of the graph.
        // For line or spline, do not render solid line across null points (connectNulls must be false)

        var origConnectNulls = options.connectNulls;

        if (options.type == 'line' || options.type == 'spline') {
            options.connectNulls = false;
        }
        proceed.call(this, Array.prototype.slice.call(arguments, 1));

        options.connectNulls = origConnectNulls;
    });
}(Highcharts));
