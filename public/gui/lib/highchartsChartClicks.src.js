// Events for axis, tick, chart clicks: Metric Explorer
// http://www.highcharts.com/docs/extending-highcharts/extending-highcharts
//

(function (H) {

    /*
     * Chart.prototype setTitle override
     * Provide click events for XDMoD Chart title, subtitle. User can edit.
     * Full override of function
    */
    H.wrap(H.Chart.prototype, 'setTitle', function (proceed, titleOptions, subtitleOptions, redraw) {

        var chart = this,
            options = chart.options,
            chartTitleOptions,
            chartSubtitleOptions;

        chartTitleOptions = options.title = H.merge(options.title, titleOptions);
        chartSubtitleOptions = options.subtitle = H.merge(options.subtitle, subtitleOptions);

        // add title and subtitle
        H.each([
            ['title', titleOptions, chartTitleOptions],
            ['subtitle', subtitleOptions, chartSubtitleOptions]
        ], function (arr) {
            var name = arr[0],
                title = chart[name],
                titleOptions = arr[1],
                chartTitleOptions = arr[2];

            if (title && titleOptions) {
                chart[name] = title = title.destroy(); // remove old
            }

            if (chartTitleOptions && chartTitleOptions.text && !title) {
                chart[name] = chart.renderer.text(
                    chartTitleOptions.text,
                    0,
                    0,
                    chartTitleOptions.useHTML
                )

                // XDMoD: provide titleClick, subtitleClick events
                .on('click',function() {
                    H.fireEvent(chart, arr[0]+'Click');
                })
                .on('mouseover', function () {
                    H.css(this, { cursor: 'pointer' });
                })
                .on('mouseout', function () {
                    H.css(this, { cursor: 'default' });
                })
                // End XDMoD

                .attr({
                    align: chartTitleOptions.align,
                    'class': H.PREFIX + name,
                    zIndex: chartTitleOptions.zIndex || 4
                })
                .css(chartTitleOptions.style)
                .add();
            }
        });
        chart.layOutTitles(redraw);
    });

    /*
     * Tick.prototype render override
     * Provide click events for XDMoD chart Ticks. User can edit axis range or title
    */
    H.wrap(H.Tick.prototype, 'render', function (proceed, index, old, opacity) { // Before the original function

        var tick = this,
            axis = tick.axis,
            options = axis.options,
            chart = axis.chart,
            renderer = chart.renderer,
            horiz = axis.horiz,
            type = tick.type,
            label = tick.label,
            pos = tick.pos,
            labelOptions = options.labels,
            gridLine = tick.gridLine,
            gridPrefix = type ? type + 'Grid' : 'grid',
            tickPrefix = type ? type + 'Tick' : 'tick',
            gridLineWidth = options[gridPrefix + 'LineWidth'],
            gridLineColor = options[gridPrefix + 'LineColor'],
            dashStyle = options[gridPrefix + 'LineDashStyle'],
            tickSize = axis.tickSize(tickPrefix),
            tickColor = options[tickPrefix + 'Color'],
            gridLinePath,
            mark = tick.mark,
            markPath,
            step = /*axis.labelStep || */labelOptions.step,
            attribs,
            show = true,
            tickmarkOffset = axis.tickmarkOffset,
            xy = tick.getPosition(horiz, pos, tickmarkOffset, old),
            x = xy.x,
            y = xy.y,
            reverseCrisp = ((horiz && x === axis.pos + axis.len) || (!horiz && y === axis.pos)) ? -1 : 1; // #1480, #1687

        opacity = H.pick(opacity, 1);
        this.isActive = true;

        // create the grid line
        if (gridLineWidth) {
            gridLinePath = axis.getPlotLinePath(pos + tickmarkOffset, gridLineWidth * reverseCrisp, old, true);

            if (gridLine === H.UNDEFINED) {
                attribs = {
                    stroke: gridLineColor,
                    'stroke-width': gridLineWidth
                };
                if (dashStyle) {
                    attribs.dashstyle = dashStyle;
                }
                if (!type) {
                    attribs.zIndex = 1;
                }
                if (old) {
                    attribs.opacity = 0;
                }
                tick.gridLine = gridLine =
                    gridLineWidth ?
                        renderer.path(gridLinePath)
                            .attr(attribs).add(axis.gridGroup) :
                        null;
            }

            // If the parameter 'old' is set, the current call will be followed
            // by another call, therefore do not do any animations this time
            if (!old && gridLine && gridLinePath) {
                gridLine[tick.isNew ? 'attr' : 'animate']({
                    d: gridLinePath,
                    opacity: opacity
                });
            }
        }

        // create the tick mark
        if (tickSize) {
            if (axis.opposite) {
                tickSize[0] = -tickSize[0];
            }
            markPath = tick.getMarkPath(x, y, tickSize[0], tickSize[1] * reverseCrisp, horiz, renderer);
            if (mark) { // updating
                mark.animate({
                    d: markPath,
                    opacity: opacity
                });
            } else { // first time
                tick.mark = renderer.path(
                    markPath
                ).attr({
                    stroke: tickColor,
                    'stroke-width': tickSize[1],
                    opacity: opacity
                }).add(axis.axisGroup);
            }
        }

        // the label is created on init - now move it into place
        if (label && H.isNumber(x)) {
            label.xy = xy = tick.getLabelPosition(x, y, label, horiz, labelOptions, tickmarkOffset, index, step);

            // Apply show first and show last. If the tick is both first and last, it is
            // a single centered tick, in which case we show the label anyway (#2100).
            if ((tick.isFirst && !tick.isLast && !H.pick(options.showFirstLabel, 1)) ||
                    (tick.isLast && !tick.isFirst && !H.pick(options.showLastLabel, 1))) {
                show = false;

            // Handle label overflow and show or hide accordingly
            } else if (horiz && !axis.isRadial && !labelOptions.step && !labelOptions.rotation && !old && opacity !== 0) {
                tick.handleOverflow(xy);
            }

            // apply step
            if (step && index % step) {
                // show those indices dividable by step
                show = false;
            }

            // Set the new position, and show or hide
            if (show && H.isNumber(xy.y)) {
                xy.opacity = opacity;

                if(tick.isNew) {

                    // XDMoD: xAxisLabelClick, yAxisLabelClick events
                    label.attr(xy)
                    .on('click',function() {
                        H.fireEvent(chart, axis.coll+'LabelClick', axis);
                    })
                    .on('mouseover', function () {
                        H.css(this, { cursor: 'pointer' });
                    })
                    .on('mouseout', function () {
                        H.css(this, { cursor: 'default' });
                    });
                    // End XDMoD

                } else {
                    label.animate(xy);
                }

                tick.isNew = false;
            } else {
                label.attr('y', -9999); // #1338
            }
        }
    });

    /*
     * Axis.prototype render override
     * Provide click events for XDMoD chart axes. User can edit axis title
    */
    H.wrap(H.Axis.prototype, 'render', function (proceed) {

        /**
         * Render the axis
         */
        var axis = this,
            chart = axis.chart,
            renderer = chart.renderer,
            options = axis.options,
            isLog = axis.isLog,
            lin2log = axis.lin2log,
            isLinked = axis.isLinked,
            tickPositions = axis.tickPositions,
            axisTitle = axis.axisTitle,
            ticks = axis.ticks,
            minorTicks = axis.minorTicks,
            alternateBands = axis.alternateBands,
            stackLabelOptions = options.stackLabels,
            alternateGridColor = options.alternateGridColor,
            tickmarkOffset = axis.tickmarkOffset,
            lineWidth = options.lineWidth,
            linePath,
            hasRendered = chart.hasRendered,
            slideInTicks = hasRendered && H.isNumber(axis.oldMin),
            showAxis = axis.showAxis,
            from,
            to;

        // Reset
        axis.labelEdge.length = 0;
        axis.overlap = false;

        // Mark all elements inActive before we go over and mark the active ones
        H.each([ticks, minorTicks, alternateBands], function (coll) {
            var pos;
            for (pos in coll) {
            	if (coll.hasOwnProperty(pos)) {
                	coll[pos].isActive = false;
                }
            }
        });

        // If the series has data draw the ticks. Else only the line and title
        if (axis.hasData() || isLinked) {

            // minor ticks
            if (axis.minorTickInterval && !axis.categories) {
                H.each(axis.getMinorTickPositions(), function (pos) {
                    if (!minorTicks[pos]) {
                        minorTicks[pos] = new H.Tick(axis, pos, 'minor');
                    }

                    // render new ticks in old position
                    if (slideInTicks && minorTicks[pos].isNew) {
                        minorTicks[pos].render(null, true);
                    }

                    minorTicks[pos].render(null, false, 1);
                });
            }

            // Major ticks. Pull out the first item and render it last so that
            // we can get the position of the neighbour label. #808.
            if (tickPositions.length) { // #1300
                H.each(tickPositions, function (pos, i) {

                    // linked axes need an extra check to find out if
                    if (!isLinked || (pos >= axis.min && pos <= axis.max)) {

                        if (!ticks[pos]) {
                            ticks[pos] = new H.Tick(axis, pos);
                        }

                        // render new ticks in old position
                        if (slideInTicks && ticks[pos].isNew) {
                            ticks[pos].render(i, true, 0.1);
                        }

                        ticks[pos].render(i);
                    }

                });
                // In a categorized axis, the tick marks are displayed between labels. So
                // we need to add a tick mark and grid line at the left edge of the X axis.
                if (tickmarkOffset && (axis.min === 0 || axis.single)) {
                    if (!ticks[-1]) {
                        ticks[-1] = new H.Tick(axis, -1, null, true);
                    }
                    ticks[-1].render(-1);
                }
            }

            // alternate grid color
            if (alternateGridColor) {
                H.each(tickPositions, function (pos, i) {
                    to = tickPositions[i + 1] !== H.UNDEFINED ? tickPositions[i + 1] + tickmarkOffset : axis.max - tickmarkOffset;
                    if (i % 2 === 0 && pos < axis.max && to <= axis.max + (chart.polar ? -tickmarkOffset : tickmarkOffset)) { // #2248, #4660
                        if (!alternateBands[pos]) {
                            alternateBands[pos] = new Highcharts.PlotLineOrBand(axis);
                        }
                        from = pos + tickmarkOffset; // #949
                        alternateBands[pos].options = {
                            from: isLog ? lin2log(from) : from,
                            to: isLog ? lin2log(to) : to,
                            color: alternateGridColor
                        };
                        alternateBands[pos].render();
                        alternateBands[pos].isActive = true;
                    }
                });
            }

            // custom plot lines and bands
            if (!axis._addedPlotLB) { // only first time
                H.each((options.plotLines || []).concat(options.plotBands || []), function (plotLineOptions) {
                    axis.addPlotBandOrLine(plotLineOptions);
                });
                axis._addedPlotLB = true;
            }

        } // end if hasData

        // Remove inactive ticks
        H.each([ticks, minorTicks, alternateBands], function (coll) {
            var pos,
                i,
                forDestruction = [],
                destroyInactiveItems = function () {
                    i = forDestruction.length;
                    while (i--) {
                        // When resizing rapidly, the same items may be destroyed in different timeouts,
                        // or the may be reactivated
                        if (coll[forDestruction[i]] && !coll[forDestruction[i]].isActive) {
                            coll[forDestruction[i]].destroy();
                            delete coll[forDestruction[i]];
                        }
                    }
                };

            for (pos in coll) {
				if (coll.hasOwnProperty(pos) && !coll[pos].isActive) {
                    // Render to zero opacity
                    coll[pos].render(pos, false, 0);
                    coll[pos].isActive = false;
                    forDestruction.push(pos);
                }
            }

            // When the objects are finished fading out, destroy them
            destroyInactiveItems.call(0); 
        });

        // Static items. As the axis group is cleared on subsequent calls
        // to render, these items are added outside the group.
        // axis line
        if (lineWidth) {

            linePath = axis.getLinePath(lineWidth);

            if (!axis.axisLine) {
                axis.axisLine = renderer.path(linePath)
                    .attr({
                        stroke: options.lineColor,
                        'stroke-width': lineWidth,
                        zIndex: 7
                    })

                    // XDMoD: provide xAxisClick, yAxisClick events
                    .on('click', function() {
                        H.fireEvent(chart, axis.coll+'Click', axis);
                    })
                    .on('mouseover', function () {
                        H.css(this, { cursor: 'pointer' });
                    })
                    .on('mouseout', function () {
                        H.css(this, { cursor: 'default' });
                    })
                    // End XDMoD

                    .add(axis.axisGroup);
            } else {
                axis.axisLine.animate({ d: linePath });
            }

            // show or hide the line depending on options.showEmpty
            axis.axisLine[showAxis ? 'show' : 'hide'](true);
        }

        if (axisTitle && showAxis) {

            axisTitle[axisTitle.isNew ? 'attr' : 'animate'](

                axis.getTitlePosition()

                // XDMoD: provide xAxisTitleClick, yAxisTitleClick events
                ).on('click', function(){
                    H.fireEvent(chart, axis.coll+'TitleClick', axis);
                })
                .on('mouseover', function () {
                    H.css(this, { cursor: 'pointer' });
                })
                .on('mouseout', function () {
                    H.css(this, { cursor: 'default' });
                });
                // End XDMoD changes

            axisTitle.isNew = false;
        }

        // Stacked totals:
        if (stackLabelOptions && stackLabelOptions.enabled) {
            axis.renderStackTotals();
        }

        axis.isDirty = false;
    });
}(Highcharts));
