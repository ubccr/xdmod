
Ext.namespace('XDMoD.utils');

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
                        var chart = this;
                        var alignedLabelsOptions = chart.options.alignedLabels;
                        if (!alignedLabelsOptions) {
                            return;
                        }

                        chart.alignedLabels = [];
                        for (var i = 0; i < alignedLabelsOptions.items.length; i++) {
                            var alignedLabel = {};
                            var alignedLabelOptions = alignedLabelsOptions.items[i];
                            alignedLabel.group = chart.renderer.g().add();
                            alignedLabel.label = chart.renderer.label(alignedLabelOptions.html).add(alignedLabel.group);

                            if (alignedLabelOptions.backgroundColor) {
                                alignedLabel.background = chart.options.chart.events.helperFunctions.addBackgroundColor(alignedLabel.label, alignedLabelOptions.backgroundColor);
                                alignedLabel.label.toFront();
                            }
                            chart.alignedLabels.push(alignedLabel);
                        }
                        chart.options.chart.events.helperFunctions.alignAlignedLabels(chart);
                    },
                    function () {
                        var chart = this;
                        if (!chart.series || chart.options.chart.showWarnings === false) {
                            return;
                        }

                        for (var i = 0; i < chart.series.length; i++) {
                            var series = chart.series[i];
                            if (!series.options.isRestrictedByRoles) {
                                continue;
                            }

                            chart.options.chart.events.helperFunctions.addBackgroundColor(series.legendItem, "#DFDFDF");
                        }
                    }
                ],
                redrawHandlers: [function () {
                    var chart = this;
                    chart.options.chart.events.helperFunctions.alignAlignedLabels(chart);
                }]
            }
        },
        legend: {
            labelFormatter: function() {
                var ret = '';
                var x = this.name;
                var indexOfSQ = x.indexOf(']');
                var brAlready = false;
                if( indexOfSQ > 0)
                {
                    ret += x.substring(0,indexOfSQ+1)+'<br/>';
                    x = x.substring(indexOfSQ+1,x.length);
                    brAlready = true;
                }
                var indexOfBr = x.indexOf('{');
                if( indexOfBr > 0 && !brAlready)
                {
                    ret += x.substring(0,indexOfBr)+'<br/>';
                    x = x.substring(indexOfBr,x.length);
                }
                ret+=x.wordWrap(50,'<br/>');
                return ret;

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
                             var x = this.point.name;
                             if (this.point.name.length > settings.maxL + 3) {
                                 x = this.point.name.substring(0, settings.maxL - 3);
                             }
                             return '<b>' + x.wordWrap(settings.wrap, '</b><br/><b>') + '</b><br/>' + Highcharts.numberFormat(this.y, settings.decimals);
                        }

                        if (settings.value_labels && settings.error_labels) {
                            return Highcharts.numberFormat(this.y, settings.decimals) + ' [+/-' + Highcharts.numberFormat(this.percentage, settings.decimals) + ']';
                        } else if (settings.error_labels) {
                            return '+/-' + Highcharts.numberFormat(this.percentage, settings.decimals);
                        } else {
                            return Highcharts.numberFormat(this.y, settings.decimals);
                        }
                    }
                }
            },
            errorbar: {
                tooltip: {
                    pointFormatter: function () {
                        var fErr = Highcharts.numberFormat(this.stderr, this.series.userOptions.tooltip.valueDecimals);
                        return '<span style="color: ' + this.series.userOptions.color  + '">\u25CF</span> ' + this.series.userOptions.name +  ': <b>+/-' + fErr + '</b><br/>';
                    }
                }
            }
                 
        }
    };

    if (chartOptions.xAxis) {
        if (chartOptions.xAxis.type !== 'datetime') {
            baseChartOptions.xAxis =  {
                labels: {
                    formatter: function () {
                        var settings = this.chart.userOptions.xAxis.labels.settings;
                        var x = this.value;
                        if (this.value.length > settings.maxL - 3) {
                            x = this.value.substring(0, settings.maxL - 3) + '...';
                        }
                        if (settings.wrap) {
                            return x.wordWrap(settings.wrap, '<br />');
                        } else {
                            return x;
                        }
                    }
                }
            };
        }
    }

    jQuery.extend(true, baseChartOptions, chartOptions);

    if (extraHandlers) {
        if (extraHandlers.loadHandlers) {
            baseChartOptions.chart.events.loadHandlers = baseChartOptions.chart.events.loadHandlers.concat(extraHandlers.loadHandlers);
        }
        if (extraHandlers.redrawHandlers) {
            baseChartOptions.chart.events.redrawHandlers = baseChartOptions.chart.events.redrawHandlers.concat(extraHandlers.redrawHandlers);
        }
    }

    return new Highcharts.Chart(baseChartOptions);

};
