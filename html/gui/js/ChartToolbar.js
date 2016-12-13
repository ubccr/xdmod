/**
 * This class contains functionality for the chart config.
 *
 * @author Amin Ghadersohi
 */
CCR.xdmod.ui.ChartToolbar = function (config) {
    CCR.xdmod.ui.ChartToolbar.superclass.constructor.call(this, config);
};

Ext.extend(CCR.xdmod.ui.ChartToolbar, Ext.Toolbar, {
    show_legend: 'y',
    dataset_type: 'aggregate',
    display_type: 'auto',
    data_combine_method: 'auto',
    log_scale: 'n',

    resetValues: function () {
        this.setValues(
            // dataset_type
            'aggregate',

            // display_type
            'auto',

            // data_combine_method
            'auto',

            // show_legend
            'y',

            // limit
            20,

            // offset
            0,

            // log_scale
            'n',

            // show_guide_lines
            'y',

            // show_trend_line
            'y',

            // show_error_bars
            'y',

            // show_aggregate_labels
            'y',

            // show_error_labels
            'y',

            // enable_errors
            'y',

            // enable_trend_line
            'y',

            // hide_tooltip
            false
        );
    },

    toJSON: function () {
        var settings = {
            dataset_type: this.getDatasetType(),
            display_type: this.getDisplayType(),
            combine_type: this.getDataCombineMethod(),
            show_legend: this.getShowLegend() == 'y',
            show_guide_lines: this.getShowGuideLines(),
            limit: this.getLimit(),
            offset: this.getOffset(),
            log_scale: this.getLogScale() == 'y',
            show_trend_line: this.getShowTrendLine(),
            show_error_bars: this.getShowErrorBars(),
            show_aggregate_labels: this.getShowAggregateLabels(),
            show_error_labels: this.getShowErrorLabels(),
            enable_errors: this.getEnableErrors(),
            enable_trend_line: this.getEnableTrendLine(),
            hide_tooltip: this.getHideTooltip()
        };

        return Ext.util.JSON.encode(settings);
    },

    disable: function () {
        if (this.chartConfigButton.menu.isVisible()) {
            this.chartConfigButton.menu.wasVisible = this.chartConfigButton.menu.isVisible();
            if (this.chartConfigButton.menu.wasVisible) {
                this.chartConfigButton.menu.pos = this.chartConfigButton.menu.getPosition(false);
                this.chartConfigButton.menu.temporaryInvisible = true;
                this.chartConfigButton.menu.setVisible(false);
            }
            this.chartConfigButton.menu.setDisabled(true);
        }
    },

    enable: function () {
        this.chartConfigButton.menu.setDisabled(false);
        if (this.chartConfigButton.menu.wasVisible) {
            this.chartConfigButton.menu.wasVisible = false;
            this.chartConfigButton.menu.showAt(this.chartConfigButton.menu.pos);
        }
    },

    fromJSON: function (chartSettingsString) {
        var chartSettingsObject = Ext.util.JSON.decode(chartSettingsString);
        this.setValues(chartSettingsObject.dataset_type,
            chartSettingsObject.display_type,
            chartSettingsObject.combine_type,
            chartSettingsObject.show_legend,
            chartSettingsObject.limit,
            chartSettingsObject.offset,
            chartSettingsObject.log_scale,
            chartSettingsObject.show_guide_lines,
            chartSettingsObject.show_trend_line,
            chartSettingsObject.show_error_bars,
            chartSettingsObject.show_aggregate_labels,
            chartSettingsObject.show_error_labels,
            chartSettingsObject.enable_errors,
            chartSettingsObject.enable_trend_line,
            chartSettingsObject.hide_tooltip);
    },

    setValues: function (
        dataset_type,
        display_type,
        data_combine_method,
        show_legend,
        limit,
        offset,
        log_scale,
        show_guide_lines,
        show_trend_line,
        show_error_bars,
        show_aggregate_labels,
        show_error_labels,
        enable_errors,
        enable_trend_line,
        hide_tooltip
    ) {
        this.chartConfigButton.menu.setLegendParam(show_legend);
        this.chartConfigButton.menu.setLogScaleParam(log_scale);

        this.chartConfigButton.menu.setGuideLinesParam(show_guide_lines);
        this.chartConfigButton.menu.setTrendLineParam(show_trend_line);
        this.chartConfigButton.menu.setErrorBarsParam(show_error_bars);
        this.chartConfigButton.menu.setAggregateLabelsParam(show_aggregate_labels);
        this.chartConfigButton.menu.setErrorLabelsParam(show_error_labels);
        this.chartConfigButton.menu.setEnableErrorsParam(enable_errors);
        this.chartConfigButton.menu.setEnableTrendLineParam(enable_trend_line);
        this.chartConfigButton.menu.setHideTooltipParam(hide_tooltip);

        this.limitField.setValue(limit);
        this.offsetField.setValue(offset);

        this.chartConfigButton.menu.setDisplayParam(display_type);
        this.chartConfigButton.menu.setDataCombineMethodParam(data_combine_method);
        this.chartConfigButton.menu.setDatasetParam(dataset_type);
    },

    onHandle: function () {
        if (this.handler) this.handler(this.toJSON());
    },

    getDatasetType: function () {
        return this.chartConfigButton.menu.datasetParam;
    },

    getDisplayType: function () {
        return this.chartConfigButton.menu.displayParam;
    },

    getDataCombineMethod: function () {
        return this.chartConfigButton.menu.dataCombineMethodParam;
    },

    getShowLegend: function () {
        return this.chartConfigButton.menu.legendParam;
    },

    getLogScale: function () {
        return this.chartConfigButton.menu.logScaleParam;
    },

    getShowTrendLine: function () {
        return this.chartConfigButton.menu.trendLineParam;
    },

    getShowErrorBars: function () {
        return this.chartConfigButton.menu.errorBarsParam;
    },

    getShowGuideLines: function () {
        return this.chartConfigButton.menu.guideLinesParam;
    },

    getShowAggregateLabels: function () {
        return this.chartConfigButton.menu.aggregateLabelsParam;
    },

    getShowErrorLabels: function () {
        return this.chartConfigButton.menu.errorLabelsParam;
    },

    getHideTooltip: function() {
        return this.chartConfigButton.menu.hideTooltipParam;
    },

    getLimit: function () {
        return this.limitField.getValue();
    },

    getOffset: function () {
        return 0;
    },

    getEnableErrors: function () {
        return this.chartConfigButton.menu.enableErrorsParam;
    },

    getEnableTrendLine: function () {
        return this.chartConfigButton.menu.enableTrendLineParam;
    },

    getStatus: function () {
        var text = 'Dataset: ' + CCR.ucfirst(this.dataset_type);
        if (this.display_type != 'auto') {
            text += ' | Display: ' + CCR.ucfirst(this.display_type.replace('-', ' '));
        }
        return text;
    },

    initComponent: function () {
        this.addEvents('chart_limit_field_updated');

        this.limitField = new Ext.form.NumberField({
            fieldLabel: 'End Index',
            name: 'limit',
            minValue: 1,
            maxValue: 40,
            allowDecimals: false,
            decimalPrecision: 0,
            incrementValue: 1,
            alternateIncrementValue: 2,
            accelerate: true,
            width: 24,
            emptyText: 'Auto',
            defaultValue: 20,
            listeners: {
                'change': function (t, newValue, oldValue) {
                    if (t.isValid(false) && newValue != oldValue) {
                        this.parent.fireEvent("chart_limit_field_updated", newValue);
                        this.parent.onHandle();
                    }
                },

                'specialkey': function (t, e) {
                    if (t.isValid(false) && e.getKey() == e.ENTER) {
                        this.parent.onHandle();
                    }
                }
            }
        });

        this.offsetField = new Ext.ux.form.SpinnerField({
            fieldLabel: 'Start Index',
            name: 'offset',
            minValue: 0,
            allowDecimals: false,
            decimalPrecision: 0,
            incrementValue: 1,
            alternateIncrementValue: 2,
            accelerate: true,
            emptyText: 'Auto',
            defaultValue: 0,
            width: 60,
            listeners: {
                'change': function (t, newValue, oldValue) {
                    if (t.isValid(false) && newValue != oldValue)
                        this.parent.onHandle();

                },
                'specialkey': function (field, e) {
                    if (t.isValid(false) && e.getKey() == e.ENTER) {
                        this.parent.onHandle();
                    }
                }
            }
        });

        this.offsetField.parent = this;
        this.limitField.parent = this;

        var items = this.items ? this.items : [];

        var chartConfigMenu = new CCR.xdmod.ui.ChartConfigMenu({
            id: 'chart_config_menu_' + this.id,
            resetFunction: this.resetFunction || function () {}
        });

        this.chartConfigButton = new Ext.Button({
            text: 'Display',
            iconCls: 'custom_chart',
            menu: chartConfigMenu,
            tooltip: 'Configure display parameters'
        });

        this.chartConfigButton.menu.on('paramchange', function (paramName, paramValue) {
            this.onHandle();
        }, this);

        CCR.xdmod.ui.ChartToolbar.superclass.initComponent.apply(this, arguments);
    }
});
