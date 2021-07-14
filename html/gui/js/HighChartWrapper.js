/* eslint no-param-reassign: ["error", { "props": false }]*/

Ext.namespace('XDMoD.utils');

XDMoD.utils.extend = function () {
    var src;
    var copy;
    var name;
    var options;
    var clone;
    var target = arguments[0] || {};
    var i = 1;
    var length = arguments.length;
    var deep = false;

    // Handle a deep copy situation
    if (typeof target === 'boolean') {
        deep = target;

        // skip the boolean and the target
        target = arguments[i] || {};
        i++;
    }

    // Handle case when target is a string or something (possible in deep copy)
    if (typeof target !== 'object' && typeof target !== 'function') {
        target = {};
    }

    for (; i < length; i++) {
        options = arguments[i];

        // Only deal with non-null/undefined values
        if (options != null) {
            // Extend the base object
            for (name in options) { // eslint-disable-line guard-for-in
                src = target[name];
                copy = options[name];

                // Prevent never-ending loop
                if (target === copy) {
                    continue;
                }

                // Recurse if we're merging plain objects or arrays
                if (deep && copy && (typeof copy === 'object')) {
                    if (Array.isArray(copy)) {
                        clone = src && Array.isArray(src) ? src : [];
                    } else {
                        clone = src && typeof src === 'object' ? src : {};
                    }

                    // Never move original objects, clone them
                    target[name] = XDMoD.utils.extend(deep, clone, copy);

                    // Don't bring in undefined values
                } else if (copy !== undefined) {
                    target[name] = copy;
                }
            }
        }
    }

    // Return the modified object
    return target;
};

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    var baseChartOptions = {
        chart: {
            events: {
                load: function () {
                    var eventHandlers = this.options.chart.events.loadHandlers;
                    if (!eventHandlers) {
                        return;
                    }
                    for (var i = 0; i < eventHandlers.length; i++) {
                        eventHandlers[i].apply(this, arguments);
                    }
                },
                redraw: function () {
                    var eventHandlers = this.options.chart.events.redrawHandlers;
                    if (!eventHandlers) {
                        return;
                    }
                    for (var i = 0; i < eventHandlers.length; i++) {
                        eventHandlers[i].apply(this, arguments);
                    }
                },
                helperFunctions: {
                    // Add function to add a background color to a chart element.
                    // (This is for chart elements that don't support this natively.)
                    // Idea for using rectangles as background color from: https://stackoverflow.com/a/21625239
                    addBackgroundColor: function (element, color) {
                        var xPadding = 3;
                        var yPadding = 2;
                        var rectCornerRadius = 1;

                        var elementBBox = element.getBBox();
                        return element.renderer.rect(
                                elementBBox.x - xPadding,
                                elementBBox.y - yPadding,
                                elementBBox.width + (xPadding * 2),
                                elementBBox.height + (yPadding * 2),
                                rectCornerRadius
                                ).attr({
                                    fill: color,
                                    zIndex: (element.zIndex ? element.zIndex : 0) - 1
                                }).add(element.parentGroup);
                    },
                    // Add functions to handle aligned labels.
                    // Based on: https://stackoverflow.com/a/19326076
                    alignAlignedLabels: function (chart) {
                        var alignedLabels = chart.alignedLabels;
                        if (!alignedLabels) {
                            return;
                        }
                        for (var i = 0; i < alignedLabels.length; i++) {
                            var alignedLabel = alignedLabels[i];
                            var labelObject = alignedLabel.label;
                            var labelBackground = alignedLabel.background;

                            labelObject.align(Highcharts.extend(
                                        labelObject.getBBox(),
                                        chart.options.alignedLabels.items[i]
                                        ), null, chart.renderer.spacingBox);
                            if (labelBackground) {
                                labelBackground.align(Highcharts.extend(
                                            labelObject.getBBox(),
                                            chart.options.alignedLabels.items[i]
                                            ), null, chart.renderer.spacingBox);
                            }
                        }
                    }
                },
                loadHandlers: [
                    function () {
                        var alignedLabelsOptions = this.options.alignedLabels;
                        if (!alignedLabelsOptions) {
                            return;
                        }

                        this.alignedLabels = [];
                        for (var i = 0; i < alignedLabelsOptions.items.length; i++) {
                            var alignedLabel = {};
                            var alignedLabelOptions = alignedLabelsOptions.items[i];
                            alignedLabel.group = this.renderer.g().add();
                            alignedLabel.label = this.renderer.label(alignedLabelOptions.html).add(alignedLabel.group);

                            if (alignedLabelOptions.backgroundColor) {
                                alignedLabel.background = this.options.chart.events.helperFunctions.addBackgroundColor(alignedLabel.label, alignedLabelOptions.backgroundColor);
                                alignedLabel.label.toFront();
                            }
                            this.alignedLabels.push(alignedLabel);
                        }
                        this.options.chart.events.helperFunctions.alignAlignedLabels(this);
                    },
                    function () {
                        if (!this.series || this.options.chart.showWarnings === false) {
                            return;
                        }

                        for (var i = 0; i < this.series.length; i++) {
                            var series = this.series[i];
                            if (series.options.isRestrictedByRoles && series.legendItem) {
                                this.options.chart.events.helperFunctions.addBackgroundColor(series.legendItem, '#DFDFDF');
                            }
                        }
                    }
                ],
                redrawHandlers: [function () {
                    this.options.chart.events.helperFunctions.alignAlignedLabels(this);
                }]
            }
        },
        plotOptions: {
            series: {
                dataLabels: {
                    formatter: function () {
                        var settings = this.series.userOptions.dataLabels.settings;
                        if (!settings) {
                            return this.y.toString();
                        }
                        if (this.series.type === 'pie') {
                            var lines = this.point.name.wordWrap(settings.wrap, '\t').split('\t');
                            if (lines.length > 2) {
                                lines[1] += '...';
                            }

                            return '<b>' + lines.slice(0, 2).join('</b><br/><b>') + '</b><br/>' + Highcharts.numberFormat(this.y, settings.decimals);
                        }

                        if (settings.value_labels && settings.error_labels) {
                            return Highcharts.numberFormat(this.y, settings.decimals) + ' [+/-' + Highcharts.numberFormat(this.percentage, settings.decimals) + ']';
                        } else if (settings.error_labels) {
                            return '+/-' + Highcharts.numberFormat(this.percentage, settings.decimals);
                        }
                        return Highcharts.numberFormat(this.y, settings.decimals);
                    }
                }
            },
            errorbar: {
                tooltip: {
                    pointFormatter: function () {
                        var fErr = Highcharts.numberFormat(this.stderr, this.series.userOptions.tooltip.valueDecimals);
                        return '<span style="color: ' + this.series.userOptions.color + '">\u25CF</span> ' + this.series.userOptions.name + ': <b>+/-' + fErr + '</b><br/>';
                    }
                }
            }

        }
    };

    if (chartOptions.xAxis) {
        if (chartOptions.xAxis.type !== 'datetime') {
            baseChartOptions.xAxis = {
                labels: {
                    formatter: function () {
                        var settings = this.chart.userOptions.xAxis.labels.settings;
                        var x = this.value;
                        if (this.value.length > settings.maxL - 3) {
                            x = this.value.substring(0, settings.maxL - 3) + '...';
                        }
                        if (settings.wrap) {
                            return x.wordWrap(settings.wrap, '<br />');
                        }
                        return x;
                    }
                }
            };
        }
    }

    XDMoD.utils.extend(true, baseChartOptions, chartOptions);

    if (extraHandlers) {
        if (extraHandlers.loadHandlers) {
            baseChartOptions.chart.events.loadHandlers = baseChartOptions.chart.events.loadHandlers.concat(extraHandlers.loadHandlers);
        }
        if (extraHandlers.redrawHandlers) {
            baseChartOptions.chart.events.redrawHandlers = baseChartOptions.chart.events.redrawHandlers.concat(extraHandlers.redrawHandlers);
        }
    }

    var addAxisFormatter = function (axis) {
        var decimals;
        var minval;
        if (axis.labels && axis.labels.decimals) {
            decimals = axis.labels.decimals;
            minval = Math.pow(10, -decimals);

            axis.labels.formatter = function () {
                if (this.value < minval) {
                    return this.value;
                }
                return Highcharts.numberFormat(this.value, decimals);
            };
        }
    };

    if (baseChartOptions.yAxis) {
        if (Array.isArray(baseChartOptions.yAxis)) {
            baseChartOptions.yAxis.forEach(addAxisFormatter);
        } else {
            addAxisFormatter(baseChartOptions.yAxis);
        }
    }

    if (baseChartOptions.legend && baseChartOptions.legend.wordWrap) {
        baseChartOptions.legend.labelFormatter = function () {
            var ret = '';
            var x = this.name;
            var indexOfSQ = x.indexOf(']');
            var brAlready = false;
            if (indexOfSQ > 0) {
                ret += x.substring(0, indexOfSQ + 1) + '<br/>';
                x = x.substring(indexOfSQ + 1, x.length);
                brAlready = true;
            }
            var indexOfBr = x.indexOf('{');
            if (indexOfBr > 0 && !brAlready) {
                ret += x.substring(0, indexOfBr) + '<br/>';
                x = x.substring(indexOfBr, x.length);
            }
            ret += x.wordWrap(50, '<br/>');
            return ret;
        };
    }

    return new Highcharts.Chart(baseChartOptions);
};
