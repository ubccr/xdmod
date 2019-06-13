/**
 * This class contains functionality for the Display menu in the usage tab.
 *
 * @author Amin Ghadersohi
 */
CCR.xdmod.ui.ChartConfigMenu = function (config) {
    CCR.xdmod.ui.ChartConfigMenu.superclass.constructor.call(this, config);
};

Ext.extend(CCR.xdmod.ui.ChartConfigMenu, Ext.menu.Menu, {
    legendParam: 'y',
    datasetParam: 'aggregate',
    displayParam: 'auto',
    dataCombineMethodParam: 'auto',
    logScaleParam: 'n',
    errorBarsParam: 'y',
    guideLinesParam: 'y',
    trendLineParam: 'y',
    aggregateLabelsParam: 'y',
    errorLabelsParams: 'y',
    enableErrorsParam: false,
    enableTrendLineParam: false,
    persist: false,
    hideTooltipParam: false,

    hideTooltipFunction: function(item, checked) {
        this.hideTooltipParam = checked;
        if (item) {
            this.fireEvent("paramchange", 'hide_tooltip', this.hideTooltipParam);
        } else {
            this.hideTooltipItem.setChecked(checked, true);
        }
    },

    setHideTooltipParam: function (p) {
        if (this.persist === true) return;
        this.hideTooltipFunction(null, p == 'y' || p == 'true' || p === true);
    },

    persistFunction: function (item, checked) {
        this.persist = checked;
    },

    onLegendItemCheck: function (item, checked) {
        this.legendParam = (checked == true) ? 'y' : 'n';

        if (item) {
            this.fireEvent("paramchange", 'show_legend', this.legendparam);
        } else {
            this.legendItem.setChecked(checked, true);
        }
    },

    setLegendParam: function (p) {
        if (this.persist === true) return;
        this.onLegendItemCheck(null, p == 'y' || p == 'true' || p === true);
    },

    onLogScaleItemCheck: function (item, checked) {
        this.logScaleParam = (checked == true) ? 'y' : 'n';

        if (item) {
            this.fireEvent("paramchange", 'log_scale', this.logScaleParam);
        } else {
            this.logScaleItem.setChecked(checked, true);
        }
    },

    setLogScaleParam: function (p) {
        if (this.persist === true) return;
        this.onLogScaleItemCheck(null, p == 'y' || p == 'true' || p === true);
    },

    onDatasetItemCheck: function (item, checked) {
        this.datasetParam = (checked == true) ? 'timeseries' : 'aggregate';

        if (checked && (this.displayParam == 'pie' || this.displayParam == 'h_bar')) {
            var displayTypeItem = this.items.find(function (i) {
                return i.group == 'display_type' && i.param == 'line';
            });

            if (displayTypeItem) {
                displayTypeItem.setChecked(true, true);
                this.displayParam = displayTypeItem.param;
            }
        }

        if (!checked && (this.displayParam == 'area' )) {
            var displayTypeItem = this.items.find(function (i) {
                return i.group == 'display_type' && i.param == 'bar';
            });

            if (displayTypeItem) {
                displayTypeItem.setChecked(true, true);
                this.displayParam = displayTypeItem.param;
            }
        }

        if (item) {
            this.fireEvent("paramchange", 'dataset_type', this.datasetParam);
        } else {
            this.datasetItem.setChecked(checked, true);
        }

        this.aggregateLabelsItem.setDisabled(this.displayParam == 'datasheet');
        this.errorLabelsItem.setDisabled(this.enableErrorsParam != true || this.displayParam == 'datasheet');
        this.legendItem.setDisabled(this.displayParam == 'datasheet');
        this.errorBarsItem.setDisabled(this.enableErrorsParam != true || this.displayParam == 'pie' || this.displayParam == 'datasheet');
        this.logScaleItem.setDisabled(this.displayParam == 'pie' || this.displayParam == 'datasheet');
        this.guideLinesItem.setDisabled(this.displayParam == 'pie' || this.displayParam == 'datasheet');

        var tl = this.dataCombineMethodParam == 'percentage' || this.displayParam == 'datasheet' || this.enableTrendLineParam != true;
        this.trendLineItem.setDisabled(tl);

        if (tl) this.trendLineParam = 'n';

        if (checked) {
            this.vBarsDisplayType.setDisabled(false);
            this.hBarsDisplayType.setDisabled(true);
            this.lineDisplayType.setDisabled(false);
            this.areaDisplayType.setDisabled(false);
            this.pieDisplayType.setDisabled(true);

            var disableCombineMethods = this.displayParam == 'line' || this.displayParam == 'datasheet';
            if (disableCombineMethods) {
                this.dataCombineMethodParam = 'side';
            }
            this.sideCombineMethod.setDisabled(disableCombineMethods);
            this.stackCombineMethod.setDisabled(disableCombineMethods);
            this.percentageCombineMethod.setDisabled(disableCombineMethods);
            this.overlayCombineMethod.setDisabled(disableCombineMethods);
        } else {
            this.trendLineParam = 'n';
            this.trendLineItem.setDisabled(true);
            this.vBarsDisplayType.setDisabled(false);
            this.hBarsDisplayType.setDisabled(false);
            this.lineDisplayType.setDisabled(false);
            this.areaDisplayType.setDisabled(true);
            this.pieDisplayType.setDisabled(false);

            this.dataCombineMethodParam = 'side';
            this.sideCombineMethod.setDisabled(true);
            this.stackCombineMethod.setDisabled(true);
            this.percentageCombineMethod.setDisabled(true);
            this.overlayCombineMethod.setDisabled(true);
        }
    },

    setDatasetParam: function (p) {
        if (this.persist === true) return;
        this.onDatasetItemCheck(null, p == 'timeseries');
    },

    onDisplayItemChange: function (item, e) {
        if (item && item.param) {
            this.displayParam = item.param;

            if (e) this.fireEvent("paramchange", 'display_type', item.param);
            else {
                var displayTypeItem = this.items.find(function (i) {
                    return i.group == 'display_type' && i.param == item.param;
                });
                if (displayTypeItem) {
                    displayTypeItem.setChecked(true, true);
                    this.displayParam = displayTypeItem.param;
                }
            }
        }
    },

    setDisplayParam: function (p) {
        if (this.persist === true) return;
        this.onDisplayItemChange({
            param: p
        }, null);
    },

    onDataCombineMethodItemChange: function (item, e) {
        if (item && item.param) {
            this.dataCombineMethodParam = item.param;
            if (e)
                this.fireEvent("paramchange", 'data_combine_method', item.param);
            else {
                var dataCombineMethodItem = this.items.find(function (i) {
                    return i.group == 'data_combine_method' && i.param == item.param;
                });
                if (dataCombineMethodItem) {
                    dataCombineMethodItem.setChecked(true, true);
                    this.dataCombineMethodParam = dataCombineMethodItem.param;
                }
            }
        }
    },

    setDataCombineMethodParam: function (p) {
        if (this.persist === true) return;
        this.onDataCombineMethodItemChange({
            param: p
        }, null);
    },

    onErrorBarsItemCheck: function (item, checked) {
        this.errorBarsParam = (checked == true) ? 'y' : 'n';

        if (item) {
            this.fireEvent("paramchange", 'show_error_bars', this.errorBarsParam);
        } else {
            this.errorBarsItem.setChecked(checked, true);
        }
    },

    setErrorBarsParam: function (p) {
        if (this.persist === true) return;
        this.onErrorBarsItemCheck(null, p == 'y' || p == 'true' || p === true);
    },

    onGuideLinesItemCheck: function (item, checked) {
        this.guideLinesParam = (checked == true) ? 'y' : 'n';

        if (item) {
            this.fireEvent("paramchange", 'show_guide_lines', this.guideLinesParam);
        } else {
            this.guideLinesItem.setChecked(checked, true);
        }
    },

    setGuideLinesParam: function (p) {
        if (this.persist === true) return;
        this.onGuideLinesItemCheck(null, p == 'y' || p == 'true' || p === true);
    },

    onTrendLineItemCheck: function (item, checked) {
        this.trendLineParam = (checked == true) ? 'y' : 'n';

        if (item) {
            this.fireEvent("paramchange", 'show_trend_line', this.trendLineParam);
        } else {
            this.trendLineItem.setChecked(checked, true);
        }
    },

    setTrendLineParam: function (p) {
        if (this.persist === true) return;
        this.onTrendLineItemCheck(null, p == 'y' || p == 'true' || p === true);
    },

    onAggregateLabelsItemCheck: function (item, checked) {
        this.aggregateLabelsParam = (checked == true) ? 'y' : 'n';

        if (item) {
            this.fireEvent("paramchange", 'show_aggregate_labels', this.aggregateLabelsParam);
        } else {
            this.aggregateLabelsItem.setChecked(checked, true);
        }
    },

    setAggregateLabelsParam: function (p) {
        if (this.persist === true) return;
        this.onAggregateLabelsItemCheck(null, p == 'y' || p == 'true' || p === true);
    },

    onErrorLabelsItemCheck: function (item, checked) {
        this.errorLabelsParam = (checked == true) ? 'y' : 'n';

        if (item) {
            this.fireEvent("paramchange", 'show_error_labels', this.errorLabelsParam);
        } else {
            this.errorLabelsItem.setChecked(checked, true);
        }
    },

    setErrorLabelsParam: function (p) {
        if (this.persist === true) return;
        this.onErrorLabelsItemCheck(null, p == 'y' || p == 'true' || p === true);
    },

    setEnableErrorsParam: function (p) {
        this.enableErrorsParam = p == 'y' || p == 'true' || p === true;
    },

    setEnableTrendLineParam: function (p) {
        this.enableTrendLineParam = p == 'y' || p == 'true' || p === true;
    },

    initComponent: function () {

        this.legendItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'legend_item_' + this.id,
            scope: this,
            text: 'Legend',
            checked: this.legendParam == 'y' || this.legendParam == 'true' || this.legendParam == true ? true : false,
            checkHandler: this.onLegendItemCheck
        });

        this.logScaleItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'log_scale_item_' + this.id,
            scope: this,
            text: 'Log Scale',
            checked: this.logScaleParam == 'y' || this.logScaleParam == 'true' || this.logScaleParam == true ? true : false,
            checkHandler: this.onLogScaleItemCheck
        });

        this.errorBarsItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'error_bars_item_' + this.id,
            scope: this,
            text: 'Std Err Bars',
            checked: this.errorBarsParam == 'y' || this.errorBarsParam == 'true' || this.errorBarsParam == true ? true : false,
            checkHandler: this.onErrorBarsItemCheck
        });

        this.guideLinesItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'guide_lines_item' + this.id,
            scope: this,
            text: 'Guide Lines',
            checked: this.guideLinesParam == 'y' || this.guideLinesParam == 'true' || this.guideLinesParam == true ? true : false,
            checkHandler: this.onGuideLinesItemCheck
        });

        this.trendLineItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'trend_line_item_' + this.id,
            scope: this,
            text: 'Trend Line',
            checked: this.trendLineParam == 'y' || this.trendLineParam == 'true' || this.trendLineParam == true ? true : false,
            checkHandler: this.onTrendLineItemCheck
        });

        this.aggregateLabelsItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'aggregate_labels_item_' + this.id,
            scope: this,
            text: 'Value Labels',
            checked: this.aggregateLabelsParam == 'y' || this.aggregateLabelsParam == 'true' || this.aggregateLabelsParam == true ? true : false,
            checkHandler: this.onAggregateLabelsItemCheck
        });

        this.errorLabelsItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'error_labels_item_' + this.id,
            scope: this,
            text: 'Std Err Labels',
            checked: this.errorLabelsParam == 'y' || this.errorLabelsParam == 'true' || this.errorLabelsParam == true ? true : false,
            checkHandler: this.onErrorLabelsItemCheck
        });

        this.datasetItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'dataset_item_' + this.id,
            scope: this,
            text: 'Timeseries',
            param: this.datasetParam,
            checked: this.datasetParam == 'timeseries',
            checkHandler: this.onDatasetItemCheck
        });

        this.datasheetDisplayType = new CCR.xdmod.ui.CustomCheckItem({
            id: 'datasheet_display_type_' + this.id,
            scope: this,
            text: 'Datasheet',
            param: 'datasheet',
            checked: this.displayParam == 'datasheet',
            group: 'display_type',
            handler: this.onDisplayItemChange
        });

        this.vBarsDisplayType = new CCR.xdmod.ui.CustomCheckItem({
            id: 'v_bar_display_type_' + this.id,
            scope: this,
            text: 'Bar - Vertical',
            param: 'bar',
            checked: this.displayParam == 'bar',
            group: 'display_type',
            handler: this.onDisplayItemChange
        });

        this.hBarsDisplayType = new CCR.xdmod.ui.CustomCheckItem({
            id: 'h_bar_display_type_' + this.id,
            scope: this,
            text: 'Bar - Horizontal',
            param: 'h_bar',
            checked: this.displayParam == 'h_bar',
            group: 'display_type',
            handler: this.onDisplayItemChange
        });

        this.lineDisplayType = new CCR.xdmod.ui.CustomCheckItem({
            id: 'line_display_type_' + this.id,
            scope: this,
            text: 'Line',
            param: 'line',
            group: 'display_type',
            checked: this.displayParam == 'line',
            handler: this.onDisplayItemChange
        });

        this.areaDisplayType = new CCR.xdmod.ui.CustomCheckItem({
            id: 'area_display_type_' + this.id,
            scope: this,
            text: 'Area',
            param: 'area',
            checked: this.displayParam == 'area',
            group: 'display_type',
            handler: this.onDisplayItemChange
        });

        this.pieDisplayType = new CCR.xdmod.ui.CustomCheckItem({
            id: 'pie_display_type_' + this.id,
            scope: this,
            text: 'Pie',
            param: 'pie',
            checked: this.displayParam == 'pie',
            group: 'display_type',
            handler: this.onDisplayItemChange
        });

        this.autoCombineMethod = new CCR.xdmod.ui.CustomCheckItem({
            id: 'auto_combine_method_' + this.id,
            scope: this,
            text: 'Auto',
            param: 'auto',
            checked: this.dataCombineMethodParam == 'auto',
            group: 'data_combine_method',
            handler: this.onDataCombineMethodItemChange
        });

        this.sideCombineMethod = new CCR.xdmod.ui.CustomCheckItem({
            id: 'side_combine_method_' + this.id,
            scope: this,
            disabled: false,
            text: 'Side',
            param: 'side',
            checked: this.dataCombineMethodParam == 'side',
            group: 'data_combine_method',
            handler: this.onDataCombineMethodItemChange
        });

        this.stackCombineMethod = new CCR.xdmod.ui.CustomCheckItem({
            id: 'stack_combine_method_' + this.id,
            scope: this,
            disabled: true,
            text: 'Stack',
            param: 'stack',
            checked: this.dataCombineMethodParam == 'stack',
            group: 'data_combine_method',
            handler: this.onDataCombineMethodItemChange
        });

        this.percentageCombineMethod = new CCR.xdmod.ui.CustomCheckItem({
            id: 'percentage_combine_method_' + this.id,
            scope: this,
            disabled: true,
            text: 'Percentage',
            param: 'percentage',
            checked: this.dataCombineMethodParam == 'percentage',
            group: 'data_combine_method',
            handler: this.onDataCombineMethodItemChange
        });

        this.overlayCombineMethod = new CCR.xdmod.ui.CustomCheckItem({
            id: 'overlay_combine_method_' + this.id,
            scope: this,
            disabled: true,
            text: 'Overlay',
            param: 'overlay',
            checked: this.dataCombineMethodParam == 'overlay',
            group: 'data_combine_method',
            handler: this.onDataCombineMethodItemChange
        });

        this.resetButton = new Ext.menu.Item({
            id: 'reset_button_' + this.id,
            scope: this,
            text: 'Reset',
            handler: this.resetFunction || function () {}
        });

        this.persistItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'persist_item_' + this.id,
            scope: this,
            checked: false,
            text: 'Persist',
            checkHandler: this.persistFunction || Ext.EmptyFn
        });

        this.hideTooltipItem = new CCR.xdmod.ui.CustomCheckItem({
            id: 'hide_tooltip_item_' + this.id,
            scope: this,
            checked: false,
            text: 'Hide Tooltip',
            checkHandler: this.hideTooltipFunction || Ext.EmptyFn
        });

        var items = [
            this.datasetItem,
            this.logScaleItem,
            this.aggregateLabelsItem,
            this.errorBarsItem,
            this.errorLabelsItem,
            this.guideLinesItem,
            this.trendLineItem,
            this.hideTooltipItem,
            '-',
            this.vBarsDisplayType,
            this.hBarsDisplayType,
            this.lineDisplayType,
            this.areaDisplayType,
            this.pieDisplayType,
            this.datasheetDisplayType,
            '-',
            '<span class="menu-title">Combine Modes:</span><br/>',
            this.sideCombineMethod,
            this.stackCombineMethod,
            this.percentageCombineMethod,
            '-',
            this.resetButton,
            this.persistItem
        ];

        Ext.apply(this, {
            items: items,

            listeners: {
                'beforehide': function (t) {
                    if (t.el) {
                        var menuBox = t.getBox();

                        var ex = Ext.EventObject.getPageX();
                        var ey = Ext.EventObject.getPageY();

                        if (this.temporaryInvisible) {
                            this.temporaryInvisible = false;
                            return true;
                        }
                        return (ex > menuBox.x + menuBox.width || ex < menuBox.x ||
                            ey > menuBox.y + menuBox.height || ey < menuBox.y);
                    }
                    return true;
                }
            }
        });

        CCR.xdmod.ui.ChartConfigMenu.superclass.initComponent.apply(this, arguments);

        this.addEvents("paramchange");
    }
});
