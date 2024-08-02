/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-Apr (version 1)
 * @date 2013-Dec (version 3)
 *
 *
 * @author Ryan Gentner
 * @date 2013-Jun-23 (version 2)
 *
 * @author Jeanette Sperhac
 * @date 2015-Jan-23 (add realm-based filtering on Filters menu)
 *
 * This class contains the Metric Explorer tab
 *
 * It is traditional for the authors to magnanimously accept the blame for whatever
 * deficiencies remain. We don’t. Any errors, deficiencies, or problems in this code are
 * somebody else’s fault, but we would appreciate knowing about them so as to determine
 * who is to blame.
 *
 */
XDMoD.Module.MetricExplorer = function(config) {

    XDMoD.Module.MetricExplorer.superclass.constructor.call(this, config);

}; //XDMoD.Module.MetricExplorer

// ===========================================================================

Ext.apply(XDMoD.Module.MetricExplorer, {
    CHART_OPTIONS_MAX_TEXT_LENGTH: 17,
    delays: {
        long: 1000,
        medium: 100,
        short: 50,
        tiny: 10
    },

    legend_types: [
        ['top_center', 'Top Center'],
        ['bottom_center', 'Bottom Center (Default)'],
        ['left_center', 'Left'],
        ['left_top', 'Top Left'],
        ['left_bottom', 'Bottom Left'],
        ['right_center', 'Right'],
        ['right_top', 'Top Right'],
        ['right_bottom', 'Bottom Right'],
        ['floating_top_center', 'Floating Top Center'],
        ['floating_bottom_center', 'Floating Bottom Center'],
        ['floating_left_center', 'Floating Left'],
        ['floating_left_top', 'Floating Top Left'],
        ['floating_left_bottom', 'Floating Bottom Left'],
        ['floating_right_center', 'Floating Right'],
        ['floating_right_top', 'Floating Top Right'],
        ['floating_right_bottom', 'Floating Bottom Right'],
        ['off', 'Off']
    ],

    /**
     * A mapping of realms to the categories they belong to.
     *
     * A realm belongs to one category, so the values are strings.
     *
     * @type {Object}
     */
    realmsToCategories: {},

    /**
     * A mapping of dimensions to the realms they apply to.
     *
     * A dimension may apply to multiple realms, so the values are arrays.
     *
     * @type {Object}
     */
    dimensionsToRealms: {},

    /**
     * Used by other modules to display information within the Metric Explorer.
     * Modules that currently use this functionality are:
     *     - Summary
     *     - Usage
     *
     * @param {Object} config which will be used as the data for the newly
     *                        created chart.
     * @param {String} name to be given to the newly created chart.
     * @param {boolean} ensureNameIsUnique (Optional) Controls whether or not
     *                                     the name is ensured to be unique.
     *                                     If the name is not unique and this
     *                                     is false, the existing chart will
     *                                     be loaded. (Defaults to true.)
     * @param {XDMoD.Module.MetricExplorer} (Optional) instance
     */
    setConfig: function(config, name, ensureNameIsUnique, instance) {
        ensureNameIsUnique = Ext.isDefined(ensureNameIsUnique) ? ensureNameIsUnique : true;
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        var tabPanel = Ext.getCmp('main_tab_panel');

        // REMOVE: the listeners that were added in this.loadAll()
        // so that we don't overwrite the work we do here.
        instance.un('dwdesc_loaded', instance.dwdesc_loaded_handler, instance);
        instance.queriesStore.un('load', instance.queries_store_loaded_handler, instance);
        instance.un('afterrender', instance.loadAll);

        // Make sure that the tab knows that we don't want the 'Show Raw Data' window
        // to be showing.
        instance.rawDataShowing = false;

        // We set the active tab to 'metric_explorer' thus triggering an 'activate' event which
        // will now be caught by the listener we specified above.
        tabPanel.setActiveTab('metric_explorer');

        function resetListeners() {
            this.on('afterrender', this.loadAll);
        }

        function loadSummaryChart() {
            instance.mask('Loading...');
            instance.reset(false);

            // If enabled, ensure the given name is unique. If it is not unique
            // append the first available numeric suffix.
            if (ensureNameIsUnique) {
                var uniqueName = name;
                var uniqueNameSuffixNumber = 0;
                while (true) {
                    if (instance.queriesStore.findExact('name', uniqueName) === -1) {
                        break;
                    }
                    uniqueNameSuffixNumber++;
                    uniqueName = name + ' (' + uniqueNameSuffixNumber + ')';
                }
                name = uniqueName;
            }

            instance.createQueryFunc(null, null, name, config, false, config, true);
            instance.reloadChartFunc();
            resetListeners.apply(instance);
        } //loadSummaryChart

        instance.maximizeScale();
        instance.on('dwdesc_loaded', function() {
            instance.queriesStore.on('load', function( /*t, records*/ ) {
                loadSummaryChart();
            }, this, {
                single: true
            });

            this.queriesStore.load();
        }, null, {
            single: true
        });
        instance.dwDescriptionStore.load();
    }, //setConfig

    // ------------------------------------------------------------------
    chartContextMenu: function(event, newchart, instance) {

        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }

        var x, y;

        var xy = Ext.EventObject.getXY();
        x = xy[0];
        y = xy[1];

        XDMoD.TrackEvent('Metric Explorer', 'Clicked on chart to access chart context menu', Ext.encode({
            'x': x,
            'y': y
        }));

        var randomNum = Math.random();
        var fontValue = instance.fontSizeSlider.getValue();
        var fontItems = [];

        for (var c = instance.fontSizeSlider.minValue; c <= instance.fontSizeSlider.maxValue; c++) {
            fontItems.push({
                text: c + ' ',
                value: c,
                checked: fontValue == c,
                xtype: 'menucheckitem',
                group: 'font_size' + randomNum,
                handler: function( /*b*/ ) {
                    instance.fontSizeSlider.setValue(this.value);
                    instance.saveQuery();
                }
            });
        }

        var allLogScale;
        instance.datasetStore.each(function(record) {
            allLogScale = (allLogScale === undefined || allLogScale === true) && record.get('log_scale');
        });

        var menu;
        if (newchart) {
            if(instance.menuRefs.newChart){
                instance.menuRefs.newChart.removeAll(false);
                instance.menuRefs.newChart.destroy();
            }
            menu = new Ext.menu.Menu({
                id: 'metric-explorer-new-chart-context-menu',
                scope: instance,
                showSeparator: false,
                ignoreParentClicks: true,
                items: [
                    '<span class="menu-title">Chart:</span><br/>', {
                        text: 'Add Data',
                        iconCls: 'add_data',
                        menu: instance.metricsMenu
                    }, {
                        text: 'Add Filter',
                        iconCls: 'add_filter',
                        menu: instance.filtersMenu
                    }
                ],
                listeners: {
                    'show': {
                        fn: function(menu) {
                            menu.getEl().slideIn('t', {
                                easing: 'easeIn',
                                duration: 0.2
                            });
                        }
                    }
                }
            });
        }
        else {
            if(instance.menuRefs.chartOptions){
                instance.menuRefs.chartOptions.remove('metric-explorer-chartoptions-add-data', false);
                instance.menuRefs.chartOptions.remove('metric-explorer-chartoptions-add-filter', false);
                instance.menuRefs.chartOptions.destroy();
            }
            var isPie = instance.isPie();
            menu = new Ext.menu.Menu({
                id: 'metric-explorer-chartoptions-context-menu',
                scope: instance,
                showSeparator: false,
                ignoreParentClicks: true,
                listeners: {
                    'show': {
                        fn: function(menu) {
                            menu.getEl().slideIn('t', {
                                easing: 'easeIn',
                                duration: 0.2
                            });
                        }
                    }
                },
                items: [
                    '<span class="menu-title">Chart Options:</span><br/>',
                    {
                        xtype: 'menucheckitem',
                        text: 'Aggregate',
                        checked: !instance.timeseries,
                        disabled: isPie,
                        group: 'dataset_type' + randomNum,
                        listeners: {
                            checkchange: function( /*t, check*/ ) {
                                instance.timeseries = false;
                                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Aggregate option in chart context menu', Ext.encode({
                                    timeseries: instance.timeseries
                                }));
                                instance.datasetTypeRadioGroup.setValue(instance.timeseries ? 'timeseries_cb' : 'aggregate_cb', true);
                            }
                        }
                    }, {
                        xtype: 'menucheckitem',
                        text: 'Timeseries',
                        checked: instance.timeseries,
                        disabled: isPie,
                        group: 'dataset_type' + randomNum,
                        listeners: {
                            checkchange: function( /*t, check*/ ) {
                                instance.timeseries = true;
                                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Timeseries option in chart context menu', Ext.encode({
                                    timeseries: instance.timeseries
                                }));
                                instance.datasetTypeRadioGroup.setValue(instance.timeseries ? 'timeseries_cb' : 'aggregate_cb', true);
                            }
                        }
                    },
                    '-', {
                        text: 'Add Data',
                        iconCls: 'add_data',
                        disabled: instance._getDisplayTypes(true).indexOf('pie') >= 0,
                        id: 'metric-explorer-chartoptions-add-data',
                        menu: instance.metricsMenu
                    }, {
                        text: 'Add Filter',
                        iconCls: 'add_filter',
                        id: 'metric-explorer-chartoptions-add-filter',
                        menu: instance.filtersMenu
                    }, {
                        text: 'Legend',
                        iconCls: 'legend_type',
                        id: 'metric-explorer-chartoptions-legend',
                        menu: {
                            id: 'metric-explorer-chartoptions-legend-options',
                            items: instance.legendMenu
                        }
                    }, {
                        text: 'Font Size',
                        iconCls: 'font_size',
                        menu: fontItems
                    }, {
                        text: 'Log Scale Chart',
                        iconCls: 'log_scale',
                        xtype: 'menucheckitem',
                        checked: allLogScale === true,
                        disabled: isPie,
                        listeners: {
                            checkchange: function(t, check) {
                                instance.datasetStore.each(function(record) {
                                    record.set('log_scale', check);
                                });
                                if (check === false) {
                                    allLogScale = false;
                                }
                            }
                        }
                    }, {
                        text: instance.chartSwapXYField.boxLabel,
                        iconCls: 'swap_xy',
                        xtype: 'menucheckitem',
                        checked: instance.swap_xy,
                        listeners: {
                            checkchange: function(t, check) {
                                instance.chartSwapXYField.setValue(check);
                            }
                        }
                    }, {
                        text: instance.shareYAxisField.boxLabel,
                        iconCls: 'share_y_axis',
                        xtype: 'menucheckitem',
                        checked: instance.share_y_axis,
                        listeners: {
                            checkchange: function(t, check) {
                                instance.shareYAxisField.setValue(check);
                            }
                        }
                    }, {
                        text: instance.hideTooltipField.boxLabel,
                        iconCls: 'hide_tooltip',
                        xtype: 'menucheckitem',
                        checked: instance.hide_tooltip,
                        listeners: {
                            checkchange: function(t, check) {
                                instance.hideTooltipField.setValue(check);
                            }
                        }
                    }, {
                        text: instance.chartShowSubtitleField.boxLabel,
                        iconCls: 'show_filters',
                        xtype: 'menucheckitem',
                        checked: instance.show_filters,
                        listeners: {
                            checkchange: function(t, check) {
                                instance.chartShowSubtitleField.setValue(check);
                            }
                        }
                    },
                    '-', {
                        text: instance.featuredCheckbox.boxLabel,
                        iconCls: 'featured',
                        xtype: 'menucheckitem',
                        checked: instance.featured,
                        listeners: {
                            checkchange: function(t, check) {
                                instance.featuredCheckbox.setValue(check);
                            }
                        }
                    }
                ]

            }); //menu

            if (CCR.xdmod.ui.isDeveloper) {
                var filterConfigForExport = function (config) {
                    var result = JSON.parse(JSON.stringify(config));

                    delete result.featured;
                    delete result.defaultDatasetConfig;

                    var i;
                    var keys = ['x_axis', 'y_axis', 'legend'];
                    for (i = 0; i < keys.length; i++) {
                        if (Object.keys(result[keys[i]]).length === 0) {
                            delete result[keys[i]];
                        }
                    }

                    if (result.timeframe_label !== 'User Defined') {
                        delete result.start_date;
                        delete result.end_date;
                    }

                    if (result.global_filters.total === 0) {
                        delete result.global_filters;
                    }

                    for (i = 0; i < result.data_series.data.length; i++) {
                        delete result.data_series.data[i].category;
                        if (!result.data_series.data[i].std_err) {
                            delete result.data_series.data[i].std_err_labels;
                        }
                    }

                    return result;
                };
                const generatePythonCode = function (config) {
                    let duration;
                    if (config.timeframe_label === 'User Defined' && config.start_date && config.end_date) {
                        duration = `${config.start_date} , ${config.end_date}`;
                    } else if (config.timeframe_label) {
                        duration = config.timeframe_label;
                    } else {
                        duration = 'Previous Month';
                    }
                    const dataType = config.timeseries ? 'timeseries' : 'aggregate';
                    const aggregationUnit = config.aggregation_unit || 'Auto';
                    const swapXY = config.swap_xy;
                    let filters = '';
                    const filterDict = {};
                    let subTitle = '';
                    for (let i = 0; i < config.global_filters.total; i += 1) {
                        const { dimension_id: id, value_name: value } = config.global_filters.data[i];
                        if (filterDict[id]) {
                            filterDict[id].push(value);
                        } else {
                            filterDict[id] = [value];
                        }
                    }
                    for (const id in filterDict) {
                        if (Object.prototype.hasOwnProperty.call(filterDict, id)) {
                            const values = filterDict[id].join("', '");
                            filters += `\n\t\t'${id}': ('${values}'),`;
                            subTitle += `${id}: ${values.replace(/'/g, '')}`;
                        }
                    }
                    const multiChart = [];
                    for (let i = 0; i < config.data_series.total; i += 1) {
                        const {
                            realm = 'Jobs',
                            metric = 'CPU Hours: Total',
                            group_by: dimension = 'none',
                            log_scale: logScale,
                            display_type: displayType
                        } = config.data_series.data[i];
                        let graphType = displayType || 'line';
                        let lineShape = '';
                        if (graphType === 'column') {
                            graphType = 'bar';
                            lineShape = "barmode='group',";
                        } else if (graphType === 'spline') {
                            graphType = 'line';
                            lineShape = "\nline_shape='spline',";
                        } else if (graphType === 'line' && dataType === 'aggregate' && dimension === 'none') {
                            graphType = 'scatter';
                        } else if (graphType === 'areaspline') {
                            graphType = 'area';
                            lineShape = "\nline_shape='spline',";
                        }
                        let axis = '';
                        if (swapXY && graphType !== 'pie') {
                            axis = `\ty= data_${i}.columns[0],\n\tx= data_${i}.columns[1:],`;
                        } else {
                            axis = `labels={"value": dw.describe_metrics('${realm}').loc['${metric}', 'label']},`;
                        }
                        let dataView;

                        if (dataType === 'aggregate') {
                            let graph;
                            if (graphType === 'pie') {
                                graph = `
if(data_${i}.size > 10):
    others_sum=data_${i}[~data_${i}.isin(top_ten)].sum()
    data_${i} = top_ten.combine_first(pd.Series({'Other ' + String(data_${i}.size - 10): others_sum}))\n`;
                            } else {
                                graph = `\n\tdata_${i} = top_ten`;
                            }
                            dataView = `
\n# Process the data series, combine the lower values into a single Other category, and change to series to a dataframe
    top_ten=data_${i}.nlargest(10)
    ${graph}
    data_${i} = data_${i}.to_frame()
    columns_list = data_${i}.columns.tolist()`;
                        } else {
                            dataView = `
\n# Limit the number of data items/source to at most 10 and sort by descending';
    columns_list = data_${i}.columns.tolist()
    if (len(columns_list) > 10):
        column_sums = data_${i}.sum()
        top_ten_columns = column_sums.nlargest(10).index.tolist()
        data_${i} = data_${i}[top_ten_columns]\n`;
                        }

                        const chart = `
    data_${i} = dw.get_data(
        duration=('${duration}'),
        realm='${realm}',
        metric='${metric}',
        dimension='${dimension}',
        filters={${filters}},
        dataset_type='${dataType}',
        aggregation_unit='${aggregationUnit}',
    )\n
    ${dataView}
    ${(swapXY && graphType !== 'pie') ? `\tdata_${i} = data_${i}.reset_index()` : ''}
# Format and draw the graph to the screen\n
    plot = px.${graphType}(
    data_${i}, ${(graphType === 'pie') ? `\nvalues= columns_list[0],\n names= data_${i}.index,` : ''}
    ${axis}
    title='${config.title || 'Untitled Query'}',${subTitle ? '\n&lt;br&gt;&lt;sup&gt;${subTitle}&lt;/sup&gt,' : ''}${logScale ? `log_${swapXY ? 'x' : 'y'}=True,` :''}
    ${lineShape}
    )
    plot.update_layout(
        xaxis_automargin=True,
    )
    plot.show()\n`;
                        multiChart[i] = chart;
                    }
                    let dataCalls = `
import pandas as pd
# Call to Data Analytics Framework requesting data 
with dw:`;
                multiChart.forEach(chart => {
                    dataCalls += chart;
                });
                return dataCalls;
                };

                const chartJSON = JSON.stringify(filterConfigForExport(instance.getConfig()), null, 4);
                menu.add({
                    text: 'View chart json',
                    iconCls: 'json_file',
                    handler: function () {
                        var win = new Ext.Window({
                            title: 'Chart Json',
                            width: 800,
                            height: 600,
                            layout: 'fit',
                            autoScroll: true,
                            closeAction: 'destroy',
                            items: [{
                                autoScroll: true,
                                html: `<pre>${Ext.util.Format.htmlEncode(chartJSON)}</pre>`
                            }],
                            tools: [{
                                id: 'save',
                                qtip: 'Copy Chart JSON',
                                handler: () => {
                                    navigator.clipboard.writeText(chartJSON);
                                }
                            }]
                        });
                        win.show();
                    }
                });
                menu.add({
                    text: 'View python code',
                    iconCls: 'custom_chart',
                    handler: () => {
                        const win = new Ext.Window({
                            title: 'API Code',
                            width: 800,
                            height: 600,
                            layout: 'fit',
                            autoScroll: true,
                            closeAction: 'destroy',
                            items: [{
                                autoScroll: true,
                                html: `<pre>Python API code \n************************************************\n<code>${generatePythonCode(instance.getConfig())} </code>\n************************************************<br></br>The link to the data analytisc API can be found <a href="https://github.com/ubccr/xdmod-data" target="_blank">here</a><br></br>Infomation about the Plotly Express Libary can be found <a href="https://plotly.com/python/plotly-express/" target="_blank">here</a><br></br>Example XDmod API Notebooks can be found <a href="https://github.com/ubccr/xdmod-notebooks" target="_blank">here</a></pre>`
                            }]
                        });
                        win.show();
                    }
                });
                const chartLayoutJSON = JSON.stringify(instance.plotlyPanel.chartOptions.layout, null, 4);
                menu.add({
                    text: 'View Plotly JS chart layout',
                    iconCls: 'chart',
                    handler: () => {
                        const win = new Ext.Window({
                            title: 'Plotly JS Layout Json',
                            width: 800,
                            height: 600,
                            layout: 'fit',
                            autoScroll: true,
                            closeAction: 'destroy',
                            items: [{
                                autoScroll: true,
                                html: `<pre>${Ext.util.Format.htmlEncode(chartLayoutJSON)}</pre>`
                            }],
                            tools: [{
                                id: 'save',
                                qtip: 'Copy Chart Layout',
                                handler: () => {
                                    navigator.clipboard.writeText(chartLayoutJSON);
                                }
                            }]
                        });
                        win.show();
                    }
                });
                const chartDataJSON = JSON.stringify(instance.plotlyPanel.chartOptions.data, null, 4);
                menu.add({
                    text: 'View Plotly JS chart data',
                    iconCls: 'dataset',
                    handler: () => {
                        const win = new Ext.Window({
                            title: 'Plotly JS Data Json',
                            width: 800,
                            height: 600,
                            layout: 'fit',
                            autoScroll: true,
                            closeAction: 'destroy',
                            items: [{
                                autoScroll: true,
                                html: `<pre>${Ext.util.Format.htmlEncode(chartDataJSON)}</pre>`
                            }],
                            tools: [{
                                id: 'save',
                                qtip: 'Copy Chart Data',
                                handler: () => {
                                    navigator.clipboard.writeText(chartDataJSON);
                                }
                            }]
                        });
                        win.show();
                    }
                });
            }
            if (instance.plotlyPanel.chartOptions.layout.zoom) {
                menu.insert(0, {
                    text: 'Reset Zoom',
                    iconCls: 'refresh',
                    xtype: 'menuitem',
                    handler: (t) => {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Reset Zoom data series context menu');
                        const { swapXY } = instance.plotlyPanel.chartOptions.layout;
                        const min = swapXY ? instance.plotlyPanel.chartOptions.layout.xaxis.range[0] : instance.plotlyPanel.chartOptions.layout.yaxis.range[0];
                        const max = swapXY ? instance.plotlyPanel.chartOptions.layout.xaxis.range[1] : instance.plotlyPanel.chartOptions.layout.yaxis.range[1];
                        if (min !== 0 || max !== null) {
                            if (swapXY) {
                                Plotly.relayout(instance.plotlyPanel.id, { 'xaxis.autorange': true, 'xaxis.range': [min, max], 'yaxis.autorange': false });
                            } else {
                                Plotly.relayout(instance.plotlyPanel.id, { 'xaxis.autorange': true, 'yaxis.range': [min, max], 'yaxis.autorange': false });
                            }
                        } else {
                            Plotly.relayout(instance.plotlyPanel.id, { 'xaxis.autorange': true, 'yaxis.autorange': true });
                        }
                    }
                });
                menu.insert(1, '-');
            }
        }

        if(newchart){
            instance.menuRefs.newChart = menu;
        }
        else {
          instance.menuRefs.chartOptions = menu;
        }
        menu.showAt(Ext.EventObject.getXY());

    },
    // ------------------------------------------------------------------

    pointContextMenu: function(point, datasetId, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }

        XDMoD.TrackEvent('Metric Explorer', 'Clicked on chart data point to access context menu', Ext.encode({
            'x-axis': point.data.type === 'pie' ? point.label : point.x,
            'y-axis': point.data.type === 'pie' ? point.label : point.y,
            'series': point.data.name
        }));

        XDMoD.Module.MetricExplorer.seriesContextMenu(point.data, false, datasetId, point, instance);
    },
    // ------------------------------------------------------------------

    seriesContextMenu: function(series, legendItemClick, datasetId, point, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }

        var randomNum = Math.random(),
            record = instance.getDataset(datasetId);

        if (record === null) {
            alert('Could not locate dataset record');
            return;
        }
        XDMoD.TrackEvent('Metric Explorer', 'Clicked on chart data series to access context menu', Ext.encode({
            'series': series ? series.name : datasetId,
            'legendItemClick': legendItemClick
        }));

        var realm = record.get('realm'),
            metric = record.get('metric'),
            dimension = record.get('group_by'),
            drillId = null,
            drillLabel;

        var isRemainder =
            (series && series.isRemainder) ||
            (point && point.data.drillable && !point.data.drillable[point.pointNumber]);

        if (!isRemainder) {
            if (point) {
                const drillInfo = point.data.drilldown;
                if (drillInfo[0]) {
                    drillId = drillInfo[point.pointNumber].id;
                    drillLabel = drillInfo[point.pointNumber].label;
                } else {
                    drillId = drillInfo.id;
                    drillLabel = drillInfo.label;
                }
            } else {
                const drillInfo = series.drilldown;
                drillId = drillInfo.id;
                drillLabel = drillInfo.label;
            }
        }
        var isPie = instance.isPie();

        function createDrillFilter() {
            var realms = [];
            for (var r in instance.realms) {
                if (instance.realms.hasOwnProperty(r)) {
                    var ds = instance.realms[r]['dimensions'];
                    for (var dd in ds) {
                        if (dd == dimension) {
                            realms.push(r);
                            continue;
                        }
                    }
                }
            }

            return {
                id: dimension + '=' + drillId,
                value_id: drillId,
                value_name: drillLabel,
                dimension_id: dimension,
                realms: realms,
                checked: true
            };
        }

        var drillFilter = createDrillFilter();
        var sortItems = [];
        for (var i = 0; CCR.xdmod.ui.AddDataPanel.sort_types.length > i; i++) {
            sortItems.push({
                group: 'sort_types' + randomNum,
                text: CCR.xdmod.ui.AddDataPanel.sort_types[i][1],
                value: CCR.xdmod.ui.AddDataPanel.sort_types[i][0],
                checked: record.get('sort_type') === CCR.xdmod.ui.AddDataPanel.sort_types[i][0],
                xtype: 'menucheckitem',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Sort option in data series context menu', Ext.encode({
                        datasetId: datasetId,
                        sort_type: this.value
                    }));
                    record.set('sort_type', this.value);
                }
            });
        }

        var displayItems = instance.getDisplayTypeItems(record.get('display_type'), 'menucheckitem', 'display_types' + randomNum, function(b) {
            XDMoD.TrackEvent('Metric Explorer', 'Clicked on Display option in data series context menu', Ext.encode({
                datasetId: record.id,
                display_type: b.value
            }));

            var current = record.get('display_type');
            var next = b.value;

            if (current == 'pie' && next !== 'pie') {
                instance.defaultMetricDisplayType = next;
                instance.defaultMetricDisplayTypeField.setValue(next);
            }

            var isValid = instance.validateChart([next]);
            if (isValid) {
                record.set('display_type', next);

            }
        }, this, {
            timeseries: instance.timeseries,
            aggregate: !instance.timeseries
        });

        var combineItems = [];
        for (var q = 0; CCR.xdmod.ui.AddDataPanel.combine_types.length > q; q++) {
            combineItems.push({
                group: 'combine_types' + randomNum,
                text: CCR.xdmod.ui.AddDataPanel.combine_types[q][1],
                value: CCR.xdmod.ui.AddDataPanel.combine_types[q][0],
                checked: record.get('combine_type') === CCR.xdmod.ui.AddDataPanel.combine_types[q][0],
                xtype: 'menucheckitem',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Stacking option in data series context menu', Ext.encode({
                        datasetId: datasetId,
                        combine_type: this.value
                    }));
                    record.set('combine_type', this.value);
                }
            });
        }

        record.store.each(function(r) {
            var z_index = r.get('z_index');
            if (z_index === null || z_index === "" || z_index === undefined) {
                z_index = r.store.indexOf(record);
                r.store.suspendEvents();
                r.set('z_index', z_index);
                r.store.resumeEvents();
            }
        }, this);

        var widthItems = [];
        for (var y = 0; CCR.xdmod.ui.AddDataPanel.line_widths.length > y; y++) {
            widthItems.push({
                group: 'width_items' + randomNum,
                text: '<div class="line-width-item">' +
                    '<span>' +
                    '<svg  xmlns:xlink="http://www.w3.org/1999/xlink"  xmlns="http://www.w3.org/2000/svg" version="1.1"  width="185" height="14">' +
                    '<g fill="none" stroke="black" stroke-width="' + CCR.xdmod.ui.AddDataPanel.line_widths[y][0] + '">' +
                    '<path stroke-dasharray="" d="M 0 6 l 180 0" />' +
                    '</g>' + '</svg>' + CCR.xdmod.ui.AddDataPanel.line_widths[y][1] + '</span>' +
                    '</div>',

                value: CCR.xdmod.ui.AddDataPanel.line_widths[y][0],
                xtype: 'menucheckitem',
                checked: record.get('line_width') === CCR.xdmod.ui.AddDataPanel.line_widths[y][0],
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Line Width option in data series context menu', Ext.encode({
                        datasetId: datasetId,
                        line_width: this.value
                    }));

                    record.set('line_width', this.value);
                }
            });

            instance.lastLineWidth = CCR.exists(instance.lastLineWidth)
                ? instance.lastLineWidth
                : record.get('line_width') ===
                  CCR.xdmod.ui.AddDataPanel.line_widths[i][0]
                  ? CCR.xdmod.ui.AddDataPanel.line_widths[i][0]
                  : undefined;
        }

        var colorIndex = CCR.xdmod.ui.colors[0].indexOf(record.get('color'));
        var colorItems = new Ext.menu.ColorMenu({
            activeItem: colorIndex > -1 ? colorIndex : undefined,
            colors: CCR.xdmod.ui.colors[0],
            handler: function(cm, color) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Color option in data series context menu', Ext.encode({
                    datasetId: datasetId,
                    color: color
                }));

                record.set('color', color);
            }
        });

        var lineTypeItems = [];
        for (var z = 0; CCR.xdmod.ui.AddDataPanel.line_types.length > z; z++) {
            lineTypeItems.push({
                group: 'line_type_items' + randomNum,
                text: '<div class="line-item">' +
                    '<span>' +
                    '<svg  xmlns:xlink="http://www.w3.org/1999/xlink"  xmlns="http://www.w3.org/2000/svg" version="1.1"  width="185" height="14">' +
                    '<g fill="none" stroke="black" stroke-width="2">' +
                    '<path stroke-dasharray="' + CCR.xdmod.ui.AddDataPanel.line_types[z][2] + '" d="M 0 6 l 180 0" />' +
                    '</g>' + '</svg>' + CCR.xdmod.ui.AddDataPanel.line_types[z][1] + '</span>' +
                    '</div>',

                value: CCR.xdmod.ui.AddDataPanel.line_types[z][2],
                xtype: 'menucheckitem',
                checked: (record.get('line_type') === CCR.xdmod.ui.AddDataPanel.line_types[z][2]) || record.get('line_type') === 'Solid',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Line Type option in data series context menu', Ext.encode({
                        datasetId: datasetId,
                        line_type: this.value
                    }));

                    record.set('line_type', this.value);
                }
            });
        }

        var dimensions = instance.realms[realm]['dimensions'];
        var drilldownItems = ['<span class="menu-title">' + (dimensions[dimension].text !== drillLabel ? (dimension !== 'none' ? 'For ' + dimensions[dimension].text + ' = ' + drillLabel + ', ' : '') : '') + 'Drilldown to:</span><br/>'];
        var metrics = instance.realms[realm].metrics;

        if (drillId && drillLabel) {
            for (var dim in dimensions) {
                if (dimensions.hasOwnProperty(dim)) {
                    if (dim === dimension || dim === 'none' || metrics[metric].hidden_groupbys.includes(dim)) {
                        continue;
                    }
                    drilldownItems.push({
                        dim: dim,
                        text: dimensions[dim].text,
                        iconCls: 'drill',
                        handler: function( /*b*/ ) {
                            instance.fireEvent('disable_commit');
                            if (dimensions[dimension].text !== drillLabel && dimension !== 'none') {
                                var filter = drillFilter,
                                    datasetCount = instance.datasetStore.getCount();
                                if (datasetCount === 1) {
                                    instance.filtersStore.add(new instance.filtersStore.recordType(filter));
                                } else if (datasetCount > 1) {
                                    var filters = XDMoD.utils.deepExtend({}, record.get('filters'));
                                    let found = false;
                                    for (let i = 0; i < filters.length; i++) {
                                        if (filters[i].id == filter.id) {
                                            found = true;
                                            break;
                                        }
                                    }
                                    if (!found) {
                                        filters.data.push(filter);
                                        filters.total++;
                                        record.set('filters', filters);
                                    }
                                }
                            }
                            record.set('group_by', this.dim);
                            instance.fireEvent('enable_commit', true);
                        }
                    });
                }
            }
        }
        var metricItems = [];

        for (var met in metrics) {
            if (metrics.hasOwnProperty(met)) {
                if (metric === met || metrics[met].hidden_groupbys.includes(dimension)) {
                    continue;
                }
                metricItems.push({
                    met: met,
                    text: metrics[met].text,
                    iconCls: 'chart',
                    handler: function( /*b*/ ) {
                        record.set('metric', this.met);
                    }
                });
            }
        }
        var compareToItems = [];
        for (var thisMet in metrics) {
            if (metrics.hasOwnProperty(thisMet)) {
                if (metric === thisMet || metrics[thisMet].hidden_groupbys.includes(dimension)) {
                    continue;
                }
                compareToItems.push({
                    met: thisMet,
                    text: metrics[thisMet].text,
                    iconCls: 'chart',
                    handler: function( /*b*/ ) {

                        var config = {},
                            defaultConfig = {
                                id: Math.random(),
                                metric: this.met,
                                color: 'auto'
                           };
                        config = { ...config, ...record.data };
                        config = { ...config, ...defaultConfig };
                        var newRecord = CCR.xdmod.ui.AddDataPanel.initRecord(
                            instance.datasetStore,
                            config,
                            null,
                            instance.timeseries
                        );
                        instance.datasetStore.add(newRecord);
                    }
                });
            }
        }
        var dimensionItems = [];
        for (var thisDim in dimensions) {
            if (dimensions.hasOwnProperty(thisDim)) {
                if (thisDim === dimension || metrics[metric].hidden_groupbys.includes(thisDim)) {
                    continue;
                }
                dimensionItems.push({
                    dim: thisDim,
                    text: thisDim == 'none' ? 'None' : dimensions[thisDim].text,
                    iconCls: 'menu',
                    handler: function( /*b*/ ) {
                        record.set('group_by', this.dim);
                    }
                });
            }
        }

        var menu = new Ext.menu.Menu({
            showSeparator: false,
            ignoreParentClicks: true,
            items: [
                '<span class="menu-title">Data Series: ' + (series ? truncateText(series.name, 50) : '') + '</span><br/>',
                '-'
            ],
            listeners: {
                'show': {
                    fn: function (menu) {
                        menu.getEl().slideIn('t', {
                            easing: 'easeIn',
                            duration: 0.2
                        });
                    }
                }
            }
        }); //menu
        if (series) {
            if (instance.plotlyPanel.chartOptions.layout.zoom) {
                menu.addItem({
                    text: 'Reset Zoom',
                    xtype: 'menuitem',
                    iconCls: 'refresh',
                    handler: (t) => {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Reset Zoom data series context menu');
                        const { swapXY } = instance.plotlyPanel.chartOptions.layout;
                        const min = swapXY ? instance.plotlyPanel.chartOptions.layout.xaxis.range[0] : instance.plotlyPanel.chartOptions.layout.yaxis.range[0];
                        const max = swapXY ? instance.plotlyPanel.chartOptions.layout.xaxis.range[1] : instance.plotlyPanel.chartOptions.layout.yaxis.range[1];
                        if (min !== 0 || max !== null) {
                            if (swapXY) {
                                Plotly.relayout(instance.plotlyPanel.id, { 'xaxis.autorange': true, 'xaxis.range': [min, max], 'yaxis.autorange': false });
                            } else {
                                Plotly.relayout(instance.plotlyPanel.id, { 'xaxis.autorange': true, 'yaxis.range': [min, max], 'yaxis.autorange': false });
                            }
                        } else {
                            Plotly.relayout(instance.plotlyPanel.id, { 'xaxis.autorange': true, 'yaxis.autorange': true });
                        }
                    }
                });
            }
            var visibility = Ext.apply({}, record.get('visibility')),
                originalTitle = series.otitle;
            if (!visibility) {
                visibility = {};
            }

            var visible = true;
            if (visibility[originalTitle] !== undefined && visibility[originalTitle] !== null) {
                visible = visibility[originalTitle];
                if (visible !== true) {
                    visible = false;
                }
            }
            menu.addItem({
                text: 'Show',
                xtype: 'menucheckitem',
                checked: visible,
                listeners: {
                    checkchange: function(t, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Hide Series option in data series context menu', Ext.encode({
                            checked: check
                        }));
                        const displaySeries = check ? true : 'legendonly';
                        if (check) {
                            delete visibility[originalTitle];
                        } else {
                            visibility[originalTitle] = displaySeries;
                        }
                        record.set('visibility', visibility);
                    }
                }
            });
            menu.addItem('-');
        }

        if (drillId) {
            if (CCR.xdmod.ui.jobViewer && legendItemClick === false) {

                var raw_data_disabled = true;
                var raw_data_tooltip = 'Show raw data is only available for the following data realms: ' + CCR.xdmod.ui.rawDataAllowedRealms.join(', ');

                if (CCR.xdmod.ui.rawDataAllowedRealms.indexOf(realm) !== -1) {
                    if (dimension == 'none') {
                        raw_data_disabled = true;
                        raw_data_tooltip = 'Show raw data is only available for drilled-down datasets. <br />' +
                            'Select &quot;Drilldown&quot; below to refine the data.';
                    } else {
                        raw_data_disabled = false;
                        raw_data_tooltip = null;
                    }
                }
                var store = Ext.StoreMgr.lookup('hchart_store_metric_explorer');

                let pointSelected = point.x;
                // Plotly doesn't accept unix timestamps therefore
                // we need to grab the raw timestamp stored on the data object.
                if (point.data.seriesData) {
                    for (let pointIndex = 0; pointIndex < point.data.seriesData.length; pointIndex++) {
                        if (point.data.seriesData[pointIndex].y === point.y) {
                            pointSelected = point.data.seriesData[pointIndex].x;
                            break;
                        }
                    }
                }

                menu.addItem({
                    text: 'Show raw data',
                    iconCls: 'dataset',
                    disabled: raw_data_disabled,
                    tooltip: raw_data_tooltip,
                    handler: function( /*b*/ ) {
                        var opts = {
                            format: 'jsonstore',
                            operation: 'get_rawdata',
                            inline: 'n',
                            datapoint: Number(pointSelected),
                            datasetId: datasetId,
                            limit: 20,
                            start: 0
                        };
                        var parameters = {};
                        Ext.apply(parameters, store.baseParams);
                        Ext.apply(parameters, opts);
                        if (dimension !== "none") {
                            var global_filters = JSON.parse(decodeURIComponent(parameters.global_filters));

                            // Always include the drillFilter which will be the filter that narrows down the
                            // dataset to only that which the user has clicked on. ( i.e. if the user has
                            // clicked on a resource data series, the drillFilter will restrict the dataset
                            // to that particular resource.
                            var filters = [drillFilter];

                            if (global_filters && CCR.isType(global_filters.data, CCR.Types.Array)) {
                                for ( var i = 0; i < global_filters.data.length; i++) {
                                    var global_filter = global_filters.data[i];

                                    // Make sure that we don't include any filters that filter on the same
                                    // dimension that our drillFilter does. This way we preserve any other
                                    // filters the user may have set but restrict the resultant dataset
                                    // appropriately.
                                    if (global_filter.dimension_id &&
                                        global_filter.dimension_id !== drillFilter.dimension_id) {
                                        filters.push(global_filter);
                                    }
                                }
                            }
                            parameters.global_filters = encodeURIComponent(JSON.stringify({data: filters}));
                        }

                        var rawDataStore = new Ext.data.JsonStore({
                            storeId: 'raw_data_store',
                            proxy: new Ext.data.HttpProxy({
                                method: 'POST',
                                url: 'controllers/metric_explorer.php',
                                listeners: {
                                    load: function(o, options) {
                                        if (options.reader.jsonData && options.reader.jsonData.totalAvailable) {
                                            var jobstr = function(njobs) {
                                                return njobs + (njobs == 1 ? " job " : " jobs ");
                                            };

                                            var infomsg = jobstr(options.reader.jsonData.totalAvailable) + "in the dataset.";
                                            if (options.reader.jsonData.totalCount != options.reader.jsonData.totalAvailable) {
                                                if (options.reader.jsonData.totalCount == 0) {
                                                    infomsg += " You do not have permission to view these jobs.";
                                                } else {
                                                    infomsg += " Showing the " + jobstr(options.reader.jsonData.totalCount) + "that you have permission to view.";
                                                }
                                            }
                                            var topbar = instance.rawdataWindow.getTopToolbar();
                                            topbar.removeAll();
                                            topbar.add({
                                                xtype: "label",
                                                text: infomsg,
                                                style: 'font-weight:bold;'
                                            });
                                            topbar.doLayout();
                                        }
                                    }

                                }
                            }),
                            baseParams: parameters,
                            autoLoad: true,
                            root: 'data',
                            idProperty: 'jobid',
                            totalProperty: 'totalCount',
                            fields: ["resource", "name", "local_job_id", "jobid"]
                        });

                        var rawDataGrid = new Ext.grid.GridPanel({
                            id: 'raw_data_grid',
                            region: 'center',
                            store: rawDataStore,
                            autoExpandColumn: "raw_data_username",
                            loadMask: {
                                msg: 'Loading...'
                            },
                            colModel: new Ext.grid.ColumnModel({
                                defaults: {
                                    width: 60,
                                    sortable: false,
                                    menuDisabled: true
                                },
                                columns: [{
                                    id: 'resource',
                                    dataIndex: 'resource',
                                    header: 'Resource',
                                    width: 120
                                }, {
                                    id: 'raw_data_username',
                                    dataIndex: 'name',
                                    header: 'User Name'
                                }, {
                                    id: 'local_job_id',
                                    dataIndex: 'local_job_id',
                                    header: 'Job Id'
                                }]
                            }),
                            listeners: {
                                rowclick: function(grid, row_index /*, event*/ ) {
                                    var record = grid.getStore().getAt(row_index);

                                    var title = instance &&
                                        instance.chartTitleField &&
                                        instance.chartTitleField.getValue &&
                                        instance.chartTitleField.getValue() != ''
                                        ? instance.chartTitleField.getValue()
                                        : 'metric_explorer';

                                    var info = {
                                        realm: realm,
                                        text: record.get('resource') + "-" + record.get('local_job_id'),
                                        local_job_id: record.get('local_job_id'),
                                        job_id: record.get('jobid'),
                                        title: title
                                    };

                                    instance.rawDataShowing = true;
                                    instance.rawdataWindow.hide();

                                    var token = 'job_viewer?job=' + window.btoa(JSON.stringify(info));
                                    Ext.History.add(token);
                                }
                            },
                            bbar: new Ext.PagingToolbar({
                                pageSize: 20,
                                displayInfo: true,
                                displayMsg: 'Showing jobs {0} - {1} of {2}',
                                emptyMsg: 'No jobs to display',
                                store: rawDataStore,
                                listeners: {
                                    load: function(store, records, options) {
                                        this.onLoad(store, records, options);
                                    }
                                }
                            })
                        });

                        instance.rawdataWindow = new Ext.Window({
                            height: 530,
                            width: 480,
                            closable: true,
                            modal: true,
                            title: 'Raw Data',
                            layout: 'fit',
                            autoScroll: true,
                            items: new Ext.Panel({
                                layout: 'border',
                                items: rawDataGrid
                            }),
                            tbar: {
                                items: ['&nbsp;']
                            },
                            listeners: {
                                show: function( /*window*/ ) {
                                    instance.rawDataShowing = true;
                                },
                                close: function( /*window*/ ) {
                                    instance.rawDataShowing = false;
                                }
                            }
                        });
                        instance.rawdataWindow.show();
                    }
                });
            }
            menu.addItem({
                text: 'Drilldown',
                iconCls: 'drill',
                menu: drilldownItems
            });
            if (dimension !== 'none') {
                if (instance.filtersStore.getById(drillFilter.id) === undefined) {
                    var filters = XDMoD.utils.deepExtend({}, record.get('filters'));
                    var found = false;
                    for (let k = 0; k < filters.length; k++) {
                        if (filters[k].id == drillFilter.id) {
                            found = true;
                            break;
                        }
                    }
                    var quickFilterItems = [];

                    if (!found) {
                        quickFilterItems.push({
                            text: 'Dataset',
                            handler: function( /*b*/ ) {
                                filters.data.push(drillFilter);
                                filters.total++;
                                record.set('filters', filters);

                            }
                        });
                    }
                    quickFilterItems.push({
                        text: 'Chart',
                        handler: function( /*b*/ ) {
                            instance.filtersStore.add(new instance.filtersStore.recordType(drillFilter));
                        }
                    });
                    menu.addItem({
                        text: 'Quick Filter: ' + (dimensions[dimension].text !== drillLabel ? (dimension !== 'none' ? dimensions[dimension].text + ' = ' + truncateText(drillLabel, 40) : '') : ''),
                        iconCls: 'add_filter',
                        menu: quickFilterItems
                    });
                    menu.addItem('-');
                }
            }
        }
        menu.addItem({
            text: 'Metric',
            iconCls: 'chart',
            menu: metricItems
        });
        menu.addItem({
            text: 'Group By',
            iconCls: 'menu',
            menu: dimensionItems
        });
        menu.addItem('-');

        menu.addItem({
            text: 'Compare To',
            iconCls: 'chart',
            disabled: isPie,
            menu: compareToItems
        });
        menu.addItem('-');
        menu.addItem({
            text: 'Display',
            iconCls: 'display_type',
            menu: displayItems
        });
        menu.addItem({
            text: 'Stacking',
            iconCls: 'stacking',
            menu: combineItems
        });
        menu.addItem({
            text: 'Sort',
            iconCls: 'sort_type',
            menu: sortItems
        });
        menu.addItem({
            text: 'Color',
            iconCls: 'color_management',
            menu: colorItems
        });

        var moreOptionsMenuItem = {
            text: 'More Options',
            iconCls: 'options',
            menu: [{
                text: 'Line Type',
                iconCls: 'line_type',
                menu: lineTypeItems
            }, {
                text: 'Line Width',
                iconCls: 'line_width',
                menu: widthItems
            }, {
                text: 'Std Err Bars',
                iconCls: 'std_err',

                xtype: 'menucheckitem',
                checked: record.get('std_err'),
                disabled: !instance.realms[record.data.realm]['metrics'][metric].std_err || record.get('log_scale'),
                listeners: {
                    checkchange: function(t, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Std Err Bars option in data series context menu');
                        record.set('std_err', check);
                    }
                }
            }, {
                text: 'Std Err Labels',

                xtype: 'menucheckitem',
                checked: record.get('std_err_labels'),
                disabled: !instance.realms[record.data.realm]['metrics'][metric].std_err || record.get('log_scale'),
                listeners: {
                    checkchange: function(t, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Std Err Labels option in data series context menu');
                        record.set('std_err_labels', check);
                    }
                }
            }, {
                text: 'Log Scale Dataset',
                iconCls: 'log_scale',

                xtype: 'menucheckitem',
                checked: record.get('log_scale'),
                disabled: isPie,
                listeners: {
                    checkchange: function(t, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Log Scale option in data series context menu');
                        record.set('log_scale', check);
                    }
                }
            }, {
                text: 'Value Labels',
                iconCls: 'value_labels',

                xtype: 'menucheckitem',
                checked: record.get('value_labels'),
                listeners: {
                    checkchange: function(t, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Value Labels option in data series context menu');
                        record.set('value_labels', check);
                    }
                }
            }, {
                text: 'Verbose Legend',
                iconCls: 'long_legend',

                xtype: 'menucheckitem',
                checked: record.get('long_legend'),
                listeners: {
                    checkchange: function(t, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Verbose Legend option in data series context menu');
                        record.set('long_legend', check);
                    }
                }
            }]
        };

        menu.addItem(moreOptionsMenuItem);

        menu.addItem('-');
        menu.addItem({
            text: 'Edit Dataset',
            iconCls: 'edit_data',

            handler: function( /*b*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Edit Dataset option in data series context menu');
                instance.editDataset(datasetId);
            }
        });

        var legendValue = instance.legendTypeComboBox.getValue();
        var legendItems = [];
        for (var j = 0; XDMoD.Module.MetricExplorer.legend_types.length > j; j++) {
            legendItems.push({
                text: XDMoD.Module.MetricExplorer.legend_types[j][1],
                value: XDMoD.Module.MetricExplorer.legend_types[j][0],
                checked: legendValue == XDMoD.Module.MetricExplorer.legend_types[j][0],
                xtype: 'menucheckitem',
                group: 'legend_type' + randomNum,
                handler: function( /*b*/ ) {
                    instance.legendTypeComboBox.setValue(this.value);
                    XDMoD.TrackEvent('Metric Explorer', 'Updated legend placement', Ext.encode({
                        legend_type: this.value
                    }));

                    instance.legend_type = this.value;
                    instance.saveQuery();
                }
            });
        }
        if (series) {
            var originalTitle = series.otitle;
            var resetLegendItemTitle = function() {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on reset legend item name option in series context menu');

                delete instance.legend[originalTitle];

                series.name = originalTitle;

                instance.saveQuery();
            };
            menu.addItem('-');
            menu.addItem({
                text: 'Legend',
                iconCls: 'legend_type',
                menu: legendItems
            });
            menu.addItem({
                text: 'Edit Legend Item',
                iconCls: 'edit_legend_item',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on edit legend item option in series context menu');
                    var menu = instance.getTextEditMenu(
                        series.name,
                        'Legend Item',
                        function(text) {
                            if (text !== series.name) {
                                XDMoD.TrackEvent('Metric Explorer', 'Pressed enter in legend item edit field.', Ext.encode({
                                    title: text
                                }));

                                //find old mapping, if one.
                                if (instance.legend[originalTitle]) {
                                    instance.legend[originalTitle].title = text;
                                } else {
                                    instance.legend[originalTitle] = {
                                        title: text
                                    };
                                }

                                series.name = text;

                                instance.saveQuery();
                            }
                            menu.hide();
                        }, originalTitle !== series.name ? {
                            xtype: 'button',
                            text: 'Reset',
                            handler: function() {
                                resetLegendItemTitle.call(this);
                                menu.hide();
                            }
                        } : null
                    );
                    menu.showAt(Ext.EventObject.getXY());

                }
            });
            if (originalTitle !== series.name) {
                menu.addItem({
                    text: 'Reset Legend Item Name',
                    iconCls: 'reset_legend_item_name',
                    handler: resetLegendItemTitle
                });
            }
        }
        menu.addItem('-');
        menu.addItem({
            text: 'Delete Dataset',
            iconCls: 'delete_data',

            handler: function( /*b*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Delete Dataset option in data series context menu');
                instance.removeDataset(datasetId);
            }
        });
        menu.showAt(Ext.EventObject.getXY());

    }, //seriesCo362ntextMenu
    titleContextMenu: function(event, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        var textContent = instance.chartTitleField.getValue();
        var menu = instance.getTextEditMenu(
            textContent,
            'Chart Title',
            function(text) {
                if (text !== textContent) {
                    XDMoD.TrackEvent('Metric Explorer', 'Pressed enter in chart title edit field.', Ext.encode({
                        title: text
                    }));

                    // Because the text is coming to us htmlEncoded'd we need to decode it fully
                    // so that the chartTitleField has the correct value.
                    // We also want to strip out the anchor tag to avoid nefarious links.
                    var decoded = Ext.util.Format.htmlDecode(text);
                    const sanitizedText = decoded.replace(/<(\/?a.*?)>/g, '&lt;$1&gt;');
                    instance.chartTitleField.setValue(sanitizedText);
                    instance.saveQuery();
                }
                menu.hide();
            }
        );
        menu.showAt(Ext.EventObject.getXY());
    },
    subtitleContextMenu: function(event, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        var menu = new Ext.menu.Menu({
                scope: instance,
                showSeparator: false,
                ignoreParentClicks: true,
                listeners: {
                    'show': {
                        fn: function(menu) {
                            menu.getEl().slideIn('t', {
                                easing: 'easeIn',
                                duration: 0.2
                            });
                        }
                    }
                },
                items: [
                    '<span class="menu-title">Subtitle Options:</span><br/>',
                    '-', {
                        text: instance.chartShowSubtitleField.boxLabel,
                        iconCls: 'show_filters',
                        xtype: 'menucheckitem',
                        checked: instance.show_filters,
                        listeners: {
                            checkchange: function(t, check) {
                                instance.chartShowSubtitleField.setValue(check);
                            }
                        }
                    }
                ]
            }); //menu
        menu.showAt(Ext.EventObject.getXY());
    },
    xAxisContextMenu: function(axis, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        var axisIndex = 0,
            axisTitle = axis.title.text,
            originalTitle = axis.otitle,
            defaultTitle = axis.dtitle,
            durationSelector = instance.getDurationSelector(),
            startDate = durationSelector.getStartDate(),
            endDate = durationSelector.getEndDate(),
            menuItems = ['<span class="menu-title">X Axis [' + (axisIndex + 1) + ']</span><br/>'];
        if (instance.timeseries) { ///date controls

            var startDateMenu = new Ext.menu.DateMenu({
                value: startDate,
                maxDate: endDate,
                handler: function(dp, date) {
                    durationSelector.setValues(date.format('Y-m-d'), endDate.format('Y-m-d'), null, null, true);
                }
            });
            var endDateMenu = new Ext.menu.DateMenu({
                value: endDate,
                minDate: startDate,
                handler: function(dp, date) {
                    durationSelector.setValues(startDate.format('Y-m-d'), date.format('Y-m-d'), null, null, true);
                }
            });

            menuItems.push({
                xtype: 'menuitem',
                scope: durationSelector,

                text: durationSelector.getCannedDateText(),
                tooltip: 'Configure time frame',
                fieldLabel: 'Predefined Duration',
                iconCls: 'custom_date',
                menu: durationSelector.cannedDateMenu
            });
            menuItems.push({
                text: 'Start: ' + startDate.format('Y-m-d'),
                iconCls: 'calendar',
                menu: startDateMenu
            });

            menuItems.push({
                text: 'End: ' + endDate.format('Y-m-d'),
                iconCls: 'calendar',
                menu: endDateMenu
            });
        }

        var menu = new Ext.menu.Menu({
            scope: instance,
            showSeparator: false,
            ignoreParentClicks: true,
            items: menuItems,
            listeners: {
                'show': {
                    fn: function(menu) {
                        menu.getEl().slideIn('t', {
                            easing: 'easeIn',
                            duration: 0.2
                        });
                    }
                }
            }
        });

        if (axisTitle == null || axisTitle == '') {
            menu.addItem('-');
            menu.addItem({
                text: 'Edit Title',
                iconCls: 'edit_title',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Edit Title option in x axis context menu');
                    var menu = instance.getTextEditMenu(
                        '',
                        'X Axis [' + (axisIndex + 1) + '] Title',
                        function(text) {
                            if (text !== originalTitle) {
                                XDMoD.TrackEvent('Metric Explorer', 'Pressed enter in x axis title edit field.', Ext.encode({
                                    title: text
                                }));
                                instance.setXAxisTitle(axis, text);
                            }
                            menu.hide();
                        }
                    );
                    menu.showAt(Ext.EventObject.getXY());
                }
            });
        }
        if (axisTitle !== originalTitle && originalTitle !== defaultTitle) {
            menu.addItem('-');
            menu.addItem({
                text: 'Reset Title',
                iconCls: 'reset_title',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Reset Title option in x axis context menu');
                    instance.resetXAxisTitle(axis);
                }
            });
        }
        menu.showAt(Ext.EventObject.getXY());
    },
    xAxisLabelContextMenu: function(axis, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        XDMoD.Module.MetricExplorer.xAxisContextMenu(axis, instance);
    },
    xAxisTitleContextMenu: function(axis, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        var originalTitle = axis.otitle;
        var axisTitle = axis.title.text;
        const axisTitleText = axisTitle.length !== 0 ? axisTitle.substring(3, axisTitle.length - 4) : axisTitle;
        var axisIndex = 0;
        var menu = instance.getTextEditMenu(
            axisTitleText,
            'X Axis [' + (axisIndex + 1) + '] Title',
            function(text) {
                XDMoD.TrackEvent('Metric Explorer', 'Pressed enter in x axis [' + (axisIndex + 1) + '] title field.', Ext.encode({
                    title: text
                }));

                instance.setXAxisTitle(axis, text);

                menu.hide();
            },
            axisTitle !== originalTitle ? {
                text: 'Reset',
                xtype: 'button',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Reset Title option in x axis context menu');
                    instance.resetXAxisTitle(axis);
                    menu.hide();
                }
            } : null
        );

        menu.showAt(Ext.EventObject.getXY());
    },
    yAxisTitleContextMenu: function(axis, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        var originalTitle = axis.otitle;
        var axisTitle = axis.title.text;
        if (axisTitle.length > 0) {
            axisTitle = axisTitle.substring(3, axisTitle.length - 4);
        }
        var axisIndex = axis.index;
        var menu = instance.getTextEditMenu(
            axisTitle,
            'Y Axis [' + (axisIndex + 1) + '] Title',
            function (text) {
                XDMoD.TrackEvent('Metric Explorer', 'Pressed enter in y axis [' + (axisIndex + 1) + '] title field.', Ext.encode({
                    title: text
                }));
                instance.setYAxisTitle(axis, text);
                menu.hide();
            },
            axisTitle !== originalTitle ? {
                text: 'Reset',
                xtype: 'button',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Reset Title option in y axis context menu');
                    instance.resetYAxisTitle(axis);
                    menu.hide();
                }
            } : null
        );

        menu.showAt(Ext.EventObject.getXY());
    },

    yAxisContextMenu: function(axis, series, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        var handler;
        var axisIndex = axis.index;
        var axisTitle = axis.title.text;
        if (axisTitle.length > 0) {
            axisTitle = axisTitle.substring(3, axisTitle.length - 4);
        }
        var originalTitle = axis.otitle;
        var defaultTitle = axis.dtitle;
        var minField = new Ext.form.NumberField({
            value: axis.range[0],
            listeners: {
                specialkey: function(field, e) {
                    if (e.getKey() == e.ENTER) {
                        handler();
                    }
                },
                afterrender: function(field) {
                    field.focus(true, 700);
                }
            }
        });
        var maxField = new Ext.form.NumberField({
            value: axis.range[1],
            listeners: {
                specialkey: function(field, e) {
                    if (e.getKey() == e.ENTER) {
                        handler();
                    }
                }
            }
        });

        var yAxisDatasetIds = [];
        for (var s = 0; s < series.length; s++) {
            const trace = series[s];
            if (trace.yaxis && trace.name !== 'gap connector' && trace.name !== 'area fix') {
                const yAxisIndex = Number(trace.yaxis.slice(-1)) - 1;
                if (yAxisIndex === axisIndex) {
                    yAxisDatasetIds.push(trace.datasetId);
                }
            }
        }

        var allLogScale;
        instance.datasetStore.each(function(record) {
            for (var i = 0; i < yAxisDatasetIds.length; i++) {
                if (Math.abs(yAxisDatasetIds[i] - record.data.id) < 1e-14) {
                    allLogScale = (allLogScale === undefined || allLogScale === true) && record.get('log_scale');
                }
            }
        });

        if (axis.type === 'log') {
            allLogScale = true;
        }

        var setLog = new Ext.form.Checkbox({
            checked: allLogScale === true,
            value: allLogScale, // value to initialize field
            boxLabel: 'Log Scale Y Axis',
            iconCls: 'log_scale',
            xtype: 'checkbox',
            listeners: {
                specialkey: function(field, e) {
                    if (e.getKey() == e.ENTER) {
                        handler();
                    }
                },
                check: function(t, ch) {
                    t.setValue(ch);
                    if (ch === false) {
                        allLogScale = false;
                    }
                }
            }
        });

        var menu = new Ext.menu.Menu({
            scope: instance,
            showSeparator: false,
            ignoreParentClicks: true,
            items: [
                '<span class="menu-title">Y Axis [' + (axisIndex + 1) + ']</span><br/>',
                '<span class="menu-title">min:</span><br/>',
                minField,
                '<span class="menu-title">max:</span><br/>',
                maxField,
                setLog // log scaling checkbox
            ],
            listeners: {
                'show': {
                    fn: function(menu) {
                        menu.getEl().slideIn('t', {
                            easing: 'easeIn',
                            duration: 0.2
                        });
                    }
                },
                'hide': {
                    fn: function(menu) {
                        menu.destroy();
                    }
                }
            }
        });

        /**
         * Calculate the new maximum number based on the smallest
         * power of the base that is still larger than the
         * current maximum value.
         */
        function calcMax(base, cur_max) {
            var result = 0;
            var exponent = 1;
            while (result < cur_max) {
                result = Math.pow(base, exponent);
                exponent += 1;
            }
            return result;
        }

        handler = function() {
            var oldMin = axis.range[0],
                oldMax = axis.range[1],
                allLog = setLog.getValue(),
                axisType = null,
                newMin = minField.getValue(),
                newMax = maxField.getValue();

            // disable the undo stack so this can be treated as a single change:
            instance.fireEvent('disable_commit');

            // Set log_scale to the value of allLog
            instance.datasetStore.each(function(record) {
                for (var i = 0; i < yAxisDatasetIds.length; i++) {
                    if (Math.abs(yAxisDatasetIds[i] - record.data.id) < 1e-14) {
                        record.set('log_scale', allLog);
                    }
                }
            });

            // Calculate best min and max for log plot
            if (allLog) {

                axisType = 'log';

                /* This is only pre-defined until we decide to either
                 * update the tick-value for log-axis charts, or give
                 * the user the ability to update them.
                 */
                var defaultBase = 10;

                /* Calculate a new maximum value so things look right on the screen. */
                newMax = calcMax(defaultBase, newMax);

                /* Do not permit minimum <= 0 */
                if (newMin == "" || newMin <= 0) {
                    newMin = null;
                }

            } else {

                axisType = 'linear';

                if (newMin == "") {
                    newMin = 0;
                }
            } // if (allLog)

            if (newMax == "") {
                newMax = null;
            }

            if ((newMin == null || newMax == null) ||
                (newMax > newMin && (newMin !== oldMin || newMax !== oldMax))) {

                XDMoD.TrackEvent('Metric Explorer', 'Pressed enter in y axis [' + (axisIndex + 1) + '] min/max field.', Ext.encode({
                    min: newMin,
                    max: newMax
                }));

                //find a config mapping, if one.
                if (instance.yAxis['original' + axisIndex]) {
                    instance.yAxis['original' + axisIndex].min = newMin;
                    instance.yAxis['original' + axisIndex].max = newMax;
                    instance.yAxis['original' + axisIndex].chartType = axisType;
                } else {
                    instance.yAxis[`original${axisIndex}`] = {
                        title: axisTitle,
                        min: newMin,
                        max: newMax,
                        chartType: axisType
                    };
                }

                instance.saveQuery();
            }

            // re-enable the undo stack to treat this as a single change
            instance.fireEvent('enable_commit', true);

            menu.destroy();
        };


        if (axisTitle == null || axisTitle == '') {
            menu.addItem('-');
            menu.addItem({
                text: 'Edit Title',
                iconCls: 'edit_title',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Edit Title option in y axis context menu');
                    var menu = instance.getTextEditMenu(
                        '',
                        'Y Axis [' + (axisIndex + 1) + '] Title',
                        function(text) {
                            if (text !== originalTitle) {
                                XDMoD.TrackEvent('Metric Explorer', 'Pressed enter in y axis title edit field.', Ext.encode({
                                    title: text
                                }));
                                instance.setYAxisTitle(axis, text);
                            }
                            menu.hide();
                        }
                    );
                    menu.showAt(Ext.EventObject.getXY());
                }
            });
        }
        if (axisTitle !== originalTitle && originalTitle !== defaultTitle) {
            menu.addItem('-');
            menu.addItem({
                text: 'Reset Title',
                iconCls: 'reset_title',
                handler: function( /*b*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Reset Title option in y axis context menu');
                    instance.resetYAxisTitle(axis);
                }
            });
        } else {
            menu.addItem('-');
        }
        menu.addItem({
            text: 'Reset Range',
            xtype: 'menuitem',
            handler: function(t) {
                XDMoD.TrackEvent('Metric Explorer', `Clicked on Reset Range in y axis [${axisIndex + 1}] title field.`);
                if (instance.yAxis[`original${axisIndex}`]) {
                    instance.yAxis[`original${axisIndex}`].min = 0;
                    instance.yAxis[`original${axisIndex}`].max = null;
                    instance.yAxis[`original${axisIndex}`].chartType = 'linear';
                } else {
                    instance.yAxis[`original${axisIndex}`] = {
                        min: 0,
                        max: null,
                        chartType: 'linear'
                    };
                }

                instance.saveQuery();
            }
        });
        menu.addItem({
            xtype: 'panel',
            layout: 'hbox',
            border: false,
            baseCls: 'x-plain',
            layoutConfig: {
                pack: 'end',
                align: 'middle'
            },
            items: [{
                xtype: 'button',
                text: 'Ok',
                handler: function() {
                    handler.call(this);
                }
            }, {
                xtype: 'button',
                text: 'Cancel',
                handler: () => {
                    menu.destroy();
                }
            }]
        });
        menu.showAt(Ext.EventObject.getXY());

        if (menu.keyNav) {
            /**
             * Override the 'doRelay' handler for the X-Axis context menu
             * since the default handler was swallowing the navigation keys.
             * @param  {Ext.EventObject} event that is to be relayed.
             * @param  {function} handler to be used in the relaying, if the
             *                            execution is not filtered.
             * @return {dependent}        return type of handler
             */
            menu.keyNav.doRelay = function (event, handler) {

                var key = event.getKey();

                if (!this.menu.activeItem && event.isNavKeyPress()) {
                    this.menu.tryActivate(0, 1);
                    return true;
                }

                return handler.call(this.scope || this, event, this.menu);
            };

            /**
             * Override the 'left' handler for the X-Axis context menu
             * since the default handler was now allowing navigation to
             * occur as expected.
             * @param  {Ext.EventObject} event [description]
             * @param  {Ext.menu.Menu} menu  [description]
             * @return {Boolean} always returns true since we just want
             * allow the arrow keys to perform their normal function.
             */
            menu.keyNav.left = function( /*event, menu*/ ) {
                return true;
            };

            /**
             * Override the 'right' handler for the X-Axis context menu
             * since the default handler was now allowing navigation to
             * occur as expected.
             * @param  {Ext.EventObject} event [description]
             * @param  {Ext.menu.Menu} menu  [description]
             * @return {Boolean} always returns true since we just want
             * allow the arrow keys to perform their normal function.
             */
            menu.keyNav.right = function( /*event, menu*/ ) {
                return true;
            };
        }

    },
    yAxisLabelContextMenu: function(axis, instance) {
        if (instance === undefined) {
            instance = CCR.xdmod.ui.metricExplorer;
        }
        XDMoD.Module.MetricExplorer.yAxisContextMenu(axis, instance);
    },

    /**
     * Get the category that a given realm belongs to.
     *
     * @param  {string} realm The realm to look up the category for.
     * @return {string}       The category the realm belongs to.
     */
    getCategoryForRealm: function (realm) {
        return XDMoD.Module.MetricExplorer.realmsToCategories[realm];
    },

    /**
     * Set the category that a given realm belongs to.
     *
     * @param  {string} realm    The realm to set the category for.
     * @param  {string} category The category the realm belongs to.
     */
    setCategoryForRealm: function (realm, category) {
        XDMoD.Module.MetricExplorer.realmsToCategories[realm] = category;
    },

    /**
     * Reset the mapping of realms to categories.
     */
    resetRealmCategoryMapping: function () {
        XDMoD.Module.MetricExplorer.realmsToCategories = {};
    },

    /**
     * Get the realms that a given dimension applies to.
     *
     * @param  {string} dimension The dimension to look up the realms for.
     * @return {array}            The realms the dimension applies to.
     */
    getRealmsForDimension: function (dimension) {
        return XDMoD.Module.MetricExplorer.dimensionsToRealms[dimension];
    },

    /**
     * Add a realm that a given dimension applies to.
     *
     * @param  {string} dimension The dimension to add the realm for.
     * @param  {string} realm     The realm the dimension applies to.
     */
    addRealmToDimension: function (dimension, realm) {
        if (!XDMoD.Module.MetricExplorer.dimensionsToRealms.hasOwnProperty(dimension)) {
            XDMoD.Module.MetricExplorer.dimensionsToRealms[dimension] = [];
        }
        XDMoD.Module.MetricExplorer.dimensionsToRealms[dimension].push(realm);
    },

    /**
     * Reset the mapping of dimensions to realms.
     */
    resetDimensionRealmMapping: function () {
        XDMoD.Module.MetricExplorer.dimensionsToRealms = {};
    },
}); //Ext.apply(XDMoD.Module.MetricExplorer

// ===========================================================================

Ext.extend(XDMoD.Module.MetricExplorer, XDMoD.PortalModule, {

    module_id: 'metric_explorer',

    usesToolbar: true,

    toolbarItems: {

        durationSelector: true,
        exportMenu: true,
        printButton: true,
        reportCheckbox: true,
        chartLinkButton: true

    },
    show_filters: true,
    show_warnings: true,
    font_size: 3,
    legend_type: 'bottom_center',
    swap_xy: false,
    share_y_axis: false,
    hide_tooltip: false,
    show_remainder: false,
    timeseries: true,
    featured: false,
    yAxis: {},
    xAxis: {},
    legend: {},
    defaultDatasetConfig: {},

    clientSideDataSeriesKeys: ['visibility'],

    // ------------------------------------------------------------------

    getDataSeries: function() {

        var data = [];

        this.datasetStore.each(function(record) {
            data.push(record.data);
        });

        return data;

    }, //getDataSeries

    // ------------------------------------------------------------------

    getGlobalFilters: function() {

        var ret = [];

        this.filtersStore.each(function(record) {
            ret.push(record.data);
        });

        return {
            data: ret,
            total: ret.length
        };

    }, //getGlobalFilters

    // ------------------------------------------------------------------

    getConfig: function() {

        var dataSeries = this.getDataSeries();
        var dataSeriesCount = dataSeries.length;
        var title = this.chartTitleField.getValue();

        var config = {

            featured: this.featuredCheckbox.getValue(),
            trend_line: this.trendLineCheckbox.getValue(),
            x_axis: this.xAxis,
            y_axis: this.yAxis,
            legend: this.legend,
            defaultDatasetConfig: this.defaultDatasetConfig,

            swap_xy: this.chartSwapXYField.getValue(),
            share_y_axis: this.shareYAxisField.getValue(),
            hide_tooltip: this.hideTooltipField.getValue(),
            show_remainder: this.showRemainderCheckbox.getValue(),
            timeseries: this.timeseries,
            title: title,
            legend_type: this.legendTypeComboBox.getValue(),
            font_size: this.fontSizeSlider.getValue(),
            show_filters: this.chartShowSubtitleField.getValue(),
            show_warnings: this.showWarningsCheckbox.getValue(),
            data_series: {
                data: dataSeries,
                total: dataSeriesCount
            },
            aggregation_unit: this.getDurationSelector().getAggregationUnit(),
            global_filters: this.getGlobalFilters(),
            timeframe_label: this.getDurationSelector().getDurationLabel(),
            start_date: this.getDurationSelector().getStartDate().format('Y-m-d'),
            end_date: this.getDurationSelector().getEndDate().format('Y-m-d'),
            start: this.show_remainder ? 0 : this.chartPagingToolbar.cursor,
            limit: this.chartPagingToolbar.pageSize

        }; //config

        return config;

    }, //getConfig

    filterStoreLoad: function() {
        this.saveQuery();
    },
    // ------------------------------------------------------------------

    reset: function(preserveFilters) {

        this.disableSave = true;
        this.timeseries = true;
        this.xAxis = {};
        this.yAxis = {};
        this.legend = {};
        this.defaultDatasetConfig = {};
        this.datasetTypeRadioGroup.setValue(this.timeseries ? 'timeseries_cb' : 'aggregate_cb', true);
        this.chartTitleField.setValue(null);
        this.chartOptionsButton.setText(null);
        this.chartOptionsButton.setTooltip('');
        this.chartNameTextbox.setValue(null);

        //perhaps these should be saved to the user profile separately
        this.setLegendValue('bottom_center', false);
        this.fontSizeSlider.setValue(3);
        this.featuredCheckbox.setValue(false);
        this.chartSwapXYField.setValue(false);
        this.shareYAxisField.setValue(false);
        this.datasetStore.removeAll(false);

        if (!preserveFilters) {
            this.filtersStore.removeAll(false);
        }

        this.disableSave = false;
    }, //reset

    // ------------------------------------------------------------------

    createQueryFunc: function(b, em, queryName, config, preserveFilters, initialConfig, insert) {
        insert = CCR.exists(insert) ? insert : true;
        var instance = CCR.xdmod.ui.metricExplorer || this;

        var sm = this.queriesGridPanel.getSelectionModel();
        sm.clearSelections();

        var findQueriesStoreIndexByName = function(name) {
            return this.queriesStore.findBy(function(record) {
                if (record.get('name') === name) {
                    return true;
                }
            });
        };

        var index = null;
        if (Ext.isEmpty(queryName)) {
            var i = 1;
            while (true) {
                queryName = 'untitled query ' + i;
                index = findQueriesStoreIndexByName.call(this, queryName);
                if (index === -1) {
                    break;
                }
                i++;
            }
        }

        if (!config) {

            config = this.getConfig();
            if (initialConfig) {
                Ext.apply(config, initialConfig);
            }
            config.title = queryName;
        }

        if (index === null) {
            index = findQueriesStoreIndexByName.call(this, queryName);
        }

        var selectRowByIndex = function(index, silent) {
            silent = Ext.isDefined(silent) ? silent : true;

            if (silent) {
                instance.queriesGridPanelSM.un('rowselect', this.queriesGridPanelSMRowSelect, this);
            }
            sm.selectRow(index);
            if (silent) {
                instance.queriesGridPanelSM.on('rowselect', this.queriesGridPanelSMRowSelect, this);
            }
            var view = instance.queriesGridPanel.getView();

            view.focusRow(index);
        };

        if (index > -1) {
            selectRowByIndex.call(this, index, false);
        } else {
            this.loadQuery(config, true);

            var r = new instance.queriesStore.recordType({
                name: queryName,
                config: Ext.util.JSON.encode(this.getConfig()),
                // No idea why the ts from the server side has to be multiplied
                // by 1000 and thus this number divided by 1000. But, that's
                // why this is here.
                ts: Date.now() / 1000

            });

            r.stack = new XDMoD.ChangeStack({
                listeners: {
                    'update': function(changeStack, record, action) {
                        instance.handleChartModification(changeStack, record, action);
                    }
                }
            });

            if (insert) {
                instance.queriesStore.addSorted(r);
                index = instance.queriesStore.indexOf(r);
                selectRowByIndex.call(this, index);
                instance.currentQueryRecord = r;
                r.stack.add(r.data);
            } else {
                sm.clearSelections();
                instance.handleChartModification(r.stack, r.data, 'chartselected');
            }

            instance.currentQueryRecord = r;

        }

        this.chartNameTextbox.setValue(Ext.util.Format.htmlDecode(queryName));
        this.chartOptionsButton.setText(truncateText(queryName, XDMoD.Module.MetricExplorer.CHART_OPTIONS_MAX_TEXT_LENGTH));
        this.chartOptionsButton.setTooltip(queryName);
    }, //createQueryFunc

    // ------------------------------------------------------------------

    saveQueryFunc: function(commitChanges) {

        commitChanges = commitChanges || false;

        var config = this.getConfig();
        var rec = this.getCurrentRecord();

        if (config.featured === JSON.parse(this.currentQueryRecord.data.config).featured &&
          !this.currentQueryRecord.stack.isMarked()) {
            CCR.xdmod.ui.tgSummaryViewer.fireEvent('request_refresh');
        }

        var recordUpdated = false;

        if (rec.get('config') != Ext.util.JSON.encode(config)) {
            var newConfig = Ext.util.JSON.decode(rec.get('config'));
            Ext.apply(newConfig, config); //apply the new setting onto the old one so that any hidden properties can be preserved.

            var valid = this.validateChartType(newConfig);
            if (!valid) {
                return false;
            }

            if (rec.phantom && !rec.store) {
                this.queriesStore.addSorted(rec);
                var index = this.queriesStore.indexOf(rec);
                this.selectRowByIndex.call(this, index);
            }

            rec.set('config', Ext.util.JSON.encode(newConfig));
            rec.stack.add(rec.data);
            recordUpdated = true;
        }

        if (commitChanges) {
            var index = this.queriesStore.indexOf(this.currentQueryRecord);
            if (index < 0) {
                this.queriesStore.addSorted(rec);
                this.queriesStore.save();
                index = this.queriesStore.indexOf(rec);
                this.selectRowByIndex.call(this, index);
            } else {
                // update the last-modified timestamp on the chart definition:
                rec.set('ts', Date.now() / 1000);
                this.queriesStore.save();
            }
            rec.stack.mark();
        }

        return recordUpdated;
    }, //saveQueryFunc

    // ------------------------------------------------------------------

    loadQuery: function(config, reload) {

        if (!config) {
            return;
        }

        this.disableSave = true;

        var selectedIndex = /*CCR.exists(this.selectedIndex) ? this.selectedIndex : 0*/ 0; // This always seems to be 0??

        this.getDurationSelector().setValues(config.start_date, config.end_date, config.aggregation_unit, config.timeframe_label);

        this.timeseries = config.timeseries ? true : false;

        var dataSeriesIsObject = CCR.isType(config.data_series, CCR.Types.Object);
        var dataSeriesIsArray = CCR.isType(config.data_series, CCR.Types.Array);
        var trendLineValue = false;
        var data, record;
        if (dataSeriesIsObject) {
            data = config.data_series.data;
            record = CCR.exists(data) ? data[selectedIndex] : null;
            trendLineValue = CCR.exists(record) ? record.trend_line : false;
        } else if (dataSeriesIsArray) {
            data = config.data_series[selectedIndex];
            record = CCR.exists(data) ? data[selectedIndex] : null;
            trendLineValue = CCR.exists(record) ? record.trend_line : false;
        }

        var showWarningsValue = !Ext.isEmpty(config.show_warnings) ? config.show_warnings : true;
        var chartPageSizeLimitValue = config.limit ? config.limit : 10;

        this._simpleSilentSetValue(this.showWarningsCheckbox, showWarningsValue);
        this._simpleSilentSetValue(this.chartPageSizeField, chartPageSizeLimitValue);

        this._simpleSilentSetValue(this.trendLineCheckbox, trendLineValue);
        this._simpleSilentSetValue(this.chartTitleField, config.title);
        this.setLegendValue(config.legend_type, false);
        this._simpleSilentSetValue(this.fontSizeSlider, config.font_size);
        this._simpleSilentSetValue(this.chartSwapXYField, config.swap_xy);
        this._simpleSilentSetValue(this.shareYAxisField, config.share_y_axis);
        this._simpleSilentSetValue(this.hideTooltipField, config.hide_tooltip);
        this._simpleSilentSetValue(this.showRemainderCheckbox, config.show_remainder);
        this._simpleSilentSetValue(this.chartShowSubtitleField, config.show_filters);
        this._simpleSilentSetValue(this.featuredCheckbox, config.featured);

        this._noEvents(this.datasetTypeRadioGroup.items.items, function(component, timeseries) {
            var value = timeseries ? 'timeseries_cb' : 'aggregate_cb';
            component.setValue(value, true);
        }, this.datasetTypeRadioGroup, this.timeseries);

        this.chartPagingToolbar.cursor = config.start ? config.start : 0;
        this.chartPagingToolbar.pageSize = config.limit ? config.limit : 10;

        this.xAxis = config.x_axis ? config.x_axis : {};
        this.yAxis = config.y_axis ? config.y_axis : {};
        this.legend = config.legend ? config.legend : {};
        this.show_remainder = config.show_remainder || false;
        this.hide_tooltip = config.hide_tooltip || false;
        this.share_y_axis = config.share_y_axis || false;
        this.featured = config.featured || false;
        this.swap_xy = config.swap_xy || false;
        this.show_filters = config.show_filters;
        this.defaultDatasetConfig = config.defaultDatasetConfig ? config.defaultDatasetConfig : {};
        this.defaultMetricDisplayType = this.defaultDatasetConfig.display_type || 'line';

        this._simpleSilentSetValue(this.defaultMetricDisplayTypeField, this.defaultMetricDisplayType);

        this.disableSave = false;
        this.filtersStore.un('load', this.filterStoreLoad, this);
        this.filtersStore.loadData(
            config.global_filters ? config.global_filters : {
                data: [],
                total: 0
            }, false);
        this.filtersStore.on('load', this.filterStoreLoad, this);
        var dataset;
        if (dataSeriesIsArray || (dataSeriesIsObject && config.data_series.data)) {
            dataset = config.data_series;
        } else if (dataSeriesIsObject && !record) {
            dataset = {
                data: []
            };
        }
        this.datasetStore.loadData(dataset, false);
        this.validateChartType(config);
        if (reload) {
            this.reloadChart.call(this);
        }
    }, //loadQuery

    mask: function(message) {
        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (!viewer.el) {
            return;
        }

        viewer.el.mask(message);
    },

    unmask: function() {
        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (!viewer.el) {
            return;
        }

        // If a mask is present, but the mask should not be removed,
        // just remove the message in the mask.
        if (viewer.el.isMasked() && CCR.xdmod.ui.Viewer.dontUnmask) {
            viewer.el.mask();
            return;
        }

        viewer.el.unmask();
    },

    resetXAxisTitle: function(axis) {
        var originalTitle = axis && axis.otitle ? axis.otitle : '';
        var defaultTitle = axis && axis.dtitle ? axis.dtitle : '';

        var newTitle = originalTitle === defaultTitle ? '' : originalTitle;
        this.setXAxisTitle(axis, newTitle);
    },
    setXAxisTitle: function(axis, newTitle) {
        var originalTitle = axis && axis.otitle ? axis.otitle : '';

        //find old mapping, if one.
        if (this.xAxis[originalTitle]) {
            this.xAxis[originalTitle].title = newTitle;
        } else {
            this.xAxis[originalTitle] = {
                title: newTitle
            };
        }

        this.saveQuery();
    },
    resetYAxisTitle: function(axis) {
        var originalTitle = axis && axis.otitle ? axis.otitle : '';
        var defaultTitle = axis && axis.dtitle ? axis.dtitle : '';

        var newTitle = originalTitle === defaultTitle ? '' : originalTitle;
        this.setYAxisTitle(axis, newTitle);
    },
    setYAxisTitle: function(axis, newTitle) {
        var axisIndex = axis.index;

        //find old mapping, if one.
        if (this.yAxis['original' + axisIndex]) {
            this.yAxis['original' + axisIndex].title = newTitle;
        } else {
            this.yAxis['original' + axisIndex] = {
                title: newTitle
            };
        }

        this.saveQuery();
    },
    getTextEditMenu: function(textContent, label, handler, resetButton) {
        var width = 16;
        if (textContent.length > width) {
            width = Math.min(textContent.length, 40);
        }
        var field = new Ext.form.TextField({
            value: Ext.util.Format.htmlDecode(textContent),
            width: width.toString() + 'em',
            listeners: {
                specialkey: function(field, e) {
                    if (e.getKey() == e.ENTER) {
                        var text = Ext.util.Format.htmlEncode(field.getValue());
                        handler.call(this, text);
                    }
                },
                afterrender: function(field) {
                    field.focus(true, 700);
                }
            }
        });
        var buttons = [];

        if (resetButton) {
            buttons.push(resetButton);
        }

        buttons.push({
            xtype: 'button',
            text: 'Ok',
            handler: function() {
                handler.call(this, Ext.util.Format.htmlEncode(field.getValue()));
            }
        });
        buttons.push({
            xtype: 'button',
            text: 'Cancel',
            handler: function() {
                menu.hide();
            }
        });

        var menu = new Ext.menu.Menu({
            scope: this,
            keyNab: false,
            showSeparator: false,
            ignoreParentClicks: true,
            items: [
                '<span class="menu-title">Edit ' + label + ':</span><br/>',
                field, {
                    xtype: 'panel',
                    layout: 'hbox',
                    border: false,
                    baseCls: 'x-plain',
                    layoutConfig: {
                        pack: 'end',
                        align: 'middle'
                    },
                    items: buttons
                }
            ],
            listeners: {
                'show': {
                    fn: function(menu) {
                        if (menu.keyNav) {
                            menu.keyNav.disable();
                        }
                        menu.getEl().slideIn('t', {
                            easing: 'easeIn',
                            duration: 0.2
                        });
                    }
                }
            }
        });
        return menu;
    },
    getDisplayTypeItems: function(displayType, xtype, group, handler, scope, extraConfig) {
        var displayItems = [];
        for (var i = 0; CCR.xdmod.ui.AddDataPanel.display_types.length > i; i++) {
            var isNew = CCR.exists(extraConfig) && CCR.exists(extraConfig.newChart) && extraConfig.newChart;
            var isTimeSeries = CCR.exists(extraConfig) && CCR.exists(extraConfig.timeseries) && extraConfig.timeseries;
            if (isNew && isTimeSeries && CCR.xdmod.ui.AddDataPanel.display_types[i][0] === 'pie') {
                continue;
            }
            var config = {
                group: group,
                text: '<img class="x-panel-inline-icon ' + CCR.xdmod.ui.AddDataPanel.display_types[i][0] + '" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> ' + CCR.xdmod.ui.AddDataPanel.display_types[i][1],
                value: CCR.xdmod.ui.AddDataPanel.display_types[i][0],
                checked: displayType === CCR.xdmod.ui.AddDataPanel.display_types[i][0],
                xtype: xtype ? xtype : 'menucheckitem',
                scope: scope,
                handler: function(b) {
                    handler.call(scope, b);
                }
            };
            Ext.apply(config, extraConfig);
            displayItems.push(config);

            this.lastDisplayType = CCR.exists(this.lastDisplayType) ? this.lastDisplayType : displayType === CCR.xdmod.ui.AddDataPanel.display_types[i][0] ? CCR.xdmod.ui.AddDataPanel.display_types[i][0] : undefined;
        }

        return displayItems;
    },
    // ------------------------------------------------------------------
    initComponent: function() {

        var self = this;

        var chartScale = 1;
        var chartThumbScale = 0.45;
        var chartWidth = 740;
        var chartHeight = 345;
        let plotlyPanel;

        /*
         * Start Legend Menu
         */
         this.legendMenu = [];
         this.setLegendValue = function(value, save){
             self.legendTypeComboBox.setValue(value);
             var menuLen = self.legendMenu.length,
                 thisLegendItem;
             while(menuLen--) {
                 thisLegendItem = self.legendMenu[menuLen];
                 if(thisLegendItem.value === value){
                     thisLegendItem.checked = true;
                 }
                 if(thisLegendItem.value !== value){
                     thisLegendItem.checked = false;
                 }
             }
             if(save){
                XDMoD.TrackEvent('Metric Explorer', 'Updated legend placement', Ext.encode({
                    legend_type: value
                }));
                this.saveQuery();
             }
         };

         var legendLength = XDMoD.Module.MetricExplorer.legend_types.length,
            legendHandler = function(/*thisMenuItem, event*/){
                self.setLegendValue(this.value, true);
            },
            thisLegendValue,
            thisLegendText,
            thisLegendId;

        for(var i = 0; i < legendLength; i++){
            thisLegendValue = XDMoD.Module.MetricExplorer.legend_types[i][0];
            thisLegendText = XDMoD.Module.MetricExplorer.legend_types[i][1];
            thisLegendId = thisLegendText.toLowerCase().replace(/ /g,'-');
            this.legendMenu.push({
                xtype: 'menucheckitem',
                group: 'legend_type',
                id: 'metric-explorer-chartoptions-legend-' + thisLegendId,
                text: thisLegendText,
                value: thisLegendValue,
                checked: thisLegendValue === 'bottom_center',
                handler: legendHandler
            });
        }

        /*
         * End Legend Menu
         */

        this.realms = [];
        this.menuRefs = {
          chartOptions: null,
          newChart: null,
          dataSeries: null
        };
        this.metricsMenu = new Ext.menu.Menu({
            id: 'metric-explorer-chartoptions-add-data-menu',
            showSeparator: false,
            ignoreParentClicks: true
        });

        this.filtersMenu = new Ext.menu.Menu({
            id: 'metric-explorer-chartoptions-add-filter-menu',
            showSeparator: false,
            ignoreParentClicks: true,
            items: ['<span class="menu-title">Add Filter:</span><br/>', '-'],
            listeners: {
                scope: this,
                afterrender: function(thisMenu) {
                    thisMenu.tip = new Ext.ToolTip({
                        target: thisMenu.getEl().getAttribute("id"),
                        delegate: ".x-menu-item",
                        trackMouse: true,
                        renderTo: document.body,
                        text: "text",
                        title: "title",
                        listeners: {
                            beforeshow: function updateTip(tip) {
                                var menuItem = thisMenu.findById(tip.triggerElement.id);
                                if (!menuItem.initialConfig.categories) {
                                    return false;
                                }

                                tip.header.dom.firstChild.innerHTML = "Categories";
                                tip.body.dom.innerHTML = menuItem.initialConfig.categories;
                            }
                        }
                    });
                }
            }
        });

        this.allDimensions = [];
        this.allMetrics = [];

        // ---------------------------------------------------------

        this.dwDescriptionStore = new CCR.xdmod.CustomJsonStore({

            url: 'controllers/metric_explorer.php',
            fields: ['realms'],
            root: 'data',
            totalProperty: 'totalCount',
            idProperty: 'name',
            messageProperty: 'message',
            scope: this,

            baseParams: {
                'operation': 'get_dw_descripter'
            },
            listeners: {
                exception: function (proxy, type, action, exception, response) {
                    CCR.xdmod.ui.presentFailureResponse(response);
                }
           }

        }); //dwDescriptionStore

        this.dwDescriptionStore.on('beforeload', function() {

            this.mask('Loading...');

        }, this);

        this.dwDescriptionStore.on('load', function(store) {

            var filterItems = [];
            var filterMap = {};

            this.allDimensions = [];
            this.allMetrics = [];

            XDMoD.Module.MetricExplorer.resetRealmCategoryMapping();
            XDMoD.Module.MetricExplorer.resetDimensionRealmMapping();

            this.metricsMenu.removeAll(true);
            this.filtersMenu.removeAll(true);
            this.metricsMenu.add('<span class="menu-title">Add Data:</span><br/>');
            this.metricsMenu.add('-');
            this.filtersMenu.add('<span class="menu-title">Add Filter:</span><br/>');
            this.filtersMenu.add('-');
            if (store.getCount() > 0) {

                this.realms = store.getAt(0).get('realms');

                var dataCatalogRoot = this.dataCatalogTree.getRootNode();
                dataCatalogRoot.removeAll();

                var categories = [];
                var categoryNodes = {};
                var categoryMetricMenuItems = {};
                for (var realm in this.realms) {
                    if (this.realms.hasOwnProperty(realm)) {

                        var category = this.realms[realm].category;
                        var realm_metrics = this.realms[realm]['metrics'];
                        var realm_dimensions = this.realms[realm]['dimensions'];

                        XDMoD.Module.MetricExplorer.setCategoryForRealm(realm, category);

                        var categoryNode;
                        var categoryMetricMenuItem;
                        var categoryPreviouslyFound = categoryNodes.hasOwnProperty(category);
                        if (categoryPreviouslyFound) {
                            categoryNode = categoryNodes[category];
                            categoryNode.realm += ',' + realm;
                            categoryMetricMenuItem = categoryMetricMenuItems[category];
                        } else {
                            categories.push(category);
                            categoryNode = categoryNodes[category] = new Ext.tree.TreeNode({
                                id: category,
                                leaf: false,
                                singleClickExpand: true,
                                type: 'category',
                                text: category,
                                realm: realm,
                                iconCls: 'realm'
                            });
                            categoryMetricMenuItem = categoryMetricMenuItems[category] = [
                                '<b class="menu-title">' + category + ' Metrics:</b><br/>'
                            ];
                        }

                        for (var rm in realm_metrics) {
                            if (realm_metrics.hasOwnProperty(rm)) {
                                if (realm_metrics[rm].text === undefined) {
                                    continue;
                                }

                                var metric_dimensions = {};
                                for (var dimension in realm_dimensions) {
                                    if (!realm_metrics[rm].hidden_groupbys.includes(dimension)) {
                                        metric_dimensions[dimension] = realm_dimensions[dimension];
                                    }
                                }

                                var metricNode = new Ext.tree.TreeNode({
                                    id: realm + '_' + rm,
                                    dimensions: metric_dimensions,
                                    leaf: true,
                                    singleClickExpand: true,
                                    type: 'metric',
                                    text: realm_metrics[rm].text,
                                    has_std_err: realm_metrics[rm].std_err,
                                    iconCls: 'chart',
                                    category: category,
                                    realm: realm,
                                    metric: rm,
                                    listeners: {
                                        click: {
                                            scope: this,
                                            fn: function(n) {
                                                var menu = new Ext.menu.Menu({
                                                    scope: this,
                                                    showSeparator: false,
                                                    ignoreParentClicks: true,
                                                    listeners: {
                                                        'show': {
                                                            fn: function(menu) {
                                                                menu.getEl().slideIn('t', {
                                                                    easing: 'easeIn',
                                                                    duration: 0.25
                                                                });
                                                            }
                                                        }
                                                    }
                                                });
                                                menu.add('<span>Add To Chart: </span><span class="menu-title">' + n.text + '</span><br/>');
                                                menu.add('<span class="menu-title">Select Group By:</span><br/>');
                                                menu.add('-');
                                                for (var d in n.attributes.dimensions) {
                                                    if (n.attributes.dimensions.hasOwnProperty(d)) {
                                                        var config = {
                                                            group_by: d,
                                                            metric: n.attributes.metric,
                                                            realm: n.attributes.realm,
                                                            has_std_err: n.attributes.has_std_err,
                                                            category: n.attributes.category
                                                        };
                                                        Ext.apply(config, this.defaultDatasetConfig);
                                                        menu.add({
                                                            text: d == 'none' ? 'None' : n.attributes.dimensions[d].text,
                                                            iconCls: 'menu',
                                                            config: config,
                                                            scope: this,
                                                            handler: function(t) {
                                                                t.config.display_type = self.defaultMetricDisplayType;
                                                                var record = CCR.xdmod.ui.AddDataPanel.initRecord(t.scope.datasetStore, t.config, null, this.timeseries);
                                                                var displayTypes = [record.get('display_type')];

                                                                var valid = this.validateChart(displayTypes, 1);
                                                                if (valid) {
                                                                    t.scope.datasetStore.add(record);
                                                                } else {
                                                                    return false;
                                                                }
                                                            }
                                                        });
                                                    }
                                                }
                                                menu.show(n.ui.textNode, 'tl-br?');
                                            }
                                        }
                                    }
                                }); // var metricNode

                                categoryNode.appendChild(metricNode);
                                this.allMetrics.push([rm, realm_metrics[rm].text]);

                                categoryMetricMenuItem.push({
                                    text: realm_metrics[rm].text,
                                    iconCls: 'chart',
                                    realm: realm,
                                    metric: rm,
                                    scope: this,
                                    handler: function(b /*, e*/ ) {
                                        XDMoD.TrackEvent('Metric Explorer', 'Selected a metric from the Add Data menu', Ext.encode({
                                            realm: b.realm,
                                            metric: b.text
                                        }));
                                        addDataButtonHandler.call(b.scope, b.scope.datasetsGridPanel.toolbars[0].el, b.metric, b.realm, this.realms);
                                    }
                                }); // categoryMetricMenuItem.push
                            }
                        } //for(rm in realm_metrics)

                        // Construct the list of filters for user selection:
                        for (var rdFilter in realm_dimensions) {
                            if (realm_dimensions.hasOwnProperty(rdFilter)) {
                                if (realm_dimensions[rdFilter].text === undefined) {
                                    continue;
                                }
                                if (rdFilter == 'none') {
                                    continue;
                                }

                                XDMoD.Module.MetricExplorer.addRealmToDimension(rdFilter, realm);
                                this.allDimensions.push([rdFilter, realm_dimensions[rdFilter].text]);

                                // Define one element in the filterMap for each filter name:
                                if (filterMap[rdFilter] === undefined) {

                                    // assign the filter's index in the filterMap
                                    filterMap[rdFilter] = filterItems.length;

                                    // Define the filter element's contents
                                    filterItems.push({
                                        text: realm_dimensions[rdFilter].text,
                                        iconCls: 'menu',
                                        categories: [category],
                                        realms: [realm],
                                        dimension: rdFilter,
                                        scope: this,
                                        disabled: false,
                                        handler: function(b /*, e*/ ) {
                                            XDMoD.TrackEvent('Metric Explorer', 'Selected a filter from the Create Filter menu', b.text);
                                            // Limit the results to the realms which
                                            // have metrics on the chart. (An empty
                                            // list of realms will get results for all
                                            // realms a filter applies to.)
                                            var applicableRealms = [];
                                            Ext.each(b.realms, function(realm) {
                                                self.datasetStore.each(function(dataset) {
                                                    if (dataset.get("realm") !== realm) {
                                                        return;
                                                    }

                                                    applicableRealms.push(realm);
                                                    return false;
                                                });
                                            });

                                            filterButtonHandler.call(b.scope, b.scope.filtersGridPanel.toolbars[0].el, b.dimension, b.text, applicableRealms);
                                        }
                                    }); // filterItems.push

                                } else {
                                    // Pick up the realm categories and names for multi-realm filters.
                                    // Ensure we have all the applicable realms listed for each filter.
                                    if (filterItems[filterMap[rdFilter]].categories.indexOf(category) == -1) {
                                        filterItems[filterMap[rdFilter]].categories.push(category);
                                    }
                                    if (filterItems[filterMap[rdFilter]].realms.indexOf(realm) == -1) {
                                        filterItems[filterMap[rdFilter]].realms.push(realm);
                                    }
                                }
                            }
                        } //for(var rdFilter in realm_dimensions)
                    }
                } //for(var realm in realms)

                Ext.each(categories, function(category) {
                    dataCatalogRoot.appendChild(categoryNodes[category]);

                    var categoryMetricMenuItem = categoryMetricMenuItems[category];
                    this.metricsMenu.add({
                        text: category,
                        iconCls: 'realm',
                        menu: categoryMetricMenuItem,
                        disabled: categoryMetricMenuItem.length <= 0
                    });
                }, this);

                // Sort the filter entries in the Add Filter drop-down list.
                // Perform the sort on the text attribute.
                // JMS, 16 Jan 15
                filterItems.sort(function(a, b) {

                    var nameA = a.text.toLowerCase(),
                        nameB = b.text.toLowerCase();

                    if (nameA < nameB) {
                        //sort string ascending
                        return -1;
                    }
                    if (nameA > nameB) {
                        return 1;
                    }

                    return 0; //default return value (no sorting)

                }); // filterItems.sort()

                this.filtersMenu.addItem(filterItems);

                this.dimensionsCombo.store.loadData(this.allDimensions, false);
                this.metricsCombo.store.loadData(this.allMetrics, false);

                this.dataCatalogTreeOnDatasetLoad.call(this);
            } //if(store.getCount() > 0)

            this.unmask();
            this.fireEvent('dwdesc_loaded');

        }, this); //dwDescriptionStore.on('load',…

        // ---------------------------------------------------------

        this.on('duration_change', function (duration) {
            if (duration.changed) {
                this.saveQuery();
            } else {
                this.reloadChart();
            }
        });

        // ---------------------------------------------------------

        this.datasetTypeRadioGroup = new Ext.form.RadioGroup({
            id: 'me_dataset_type',
            fieldLabel: 'Dataset Type',

            items: [{
                id: 'aggregate_cb',
                boxLabel: 'Aggregate',
                name: 'dataset_group',
                inputValue: 1,
                checked: !this.timeseries
            }, {
                id: 'timeseries_cb',
                boxLabel: 'Timeseries',
                name: 'dataset_group',
                inputValue: 2,
                checked: this.timeseries
            }],

            listeners: {

                scope: this,

                /**
                 *
                 * @param {Ext.form.RadioGroup} radioGroup
                 * @param {Ext.form.Radio} checkedRadio
                 * @returns {boolean}
                 */
                'change': function(radioGroup, checkedRadio) {
                        XDMoD.TrackEvent('Metric Explorer', 'Changed Dataset Type', Ext.encode({
                            type: checkedRadio.boxLabel
                        }));

                        var valid = self.validateChart(self._getDisplayTypes(true), 0, checkedRadio.inputValue !== 2);
                        if (!valid) {
                            radioGroup.setValue([1, 0]);
                            return false;
                        }

                        this.timeseries = checkedRadio.inputValue == 2;
                        var cm = this.datasetsGridPanel.getColumnModel();
                        var ind = cm.getIndexById('x_axis');
                        if (ind > -1) {
                            cm.setHidden(ind, this.timeseries);
                        }
                        ind = cm.getIndexById('trend_line');
                        if (ind > -1) {
                            cm.setHidden(ind, !this.timeseries);
                        }

                        this.saveQuery();
                    } //change

            } //listeners

        }); //this.datasetTypeRadioGroup

        // ---------------------------------------------------------
        this.defaultMetricDisplayTypeField = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.display_types, ['id', 'text'], 'id', 'text', false, 'Default Metric Display Type');
        this.defaultMetricDisplayTypeField.value = this.defaultMetricDisplayType;
        this.defaultMetricDisplayTypeField.fieldLabel = 'Default Metric<br/>Display Type';
        this.defaultMetricDisplayTypeField.addListener(
            'select',
            function(combo, record) {
                var newDefault = record.get('id');
                this.defaultMetricDisplayType = newDefault;
                this.defaultDatasetConfig.display_type = newDefault;

                XDMoD.TrackEvent('Metric Explorer', 'Updated Default Metric Display Type', Ext.encode({
                    default_metric_display_type: newDefault
                }));

                this.saveQuery();
            },
            self
        );

        this.chartTitleField = new Ext.form.TextField({
            id: 'me_chart_title',
            fieldLabel: 'Title',
            name: 'title',
            emptyText: 'Chart Title',
            validationDelay: 1000,
            enableKeyEvents: true,

            listeners: {

                scope: this,

                change: function(textfield, newValue, oldValue) {
                    if (newValue != oldValue) {

                        XDMoD.TrackEvent('Metric Explorer', 'Updated chart title', textfield.getValue());
                        this.saveQuery();
                    }

                }, //change

                specialkey: function(t, e) {
                    if (t.isValid(false) && (e.getKey() == e.ENTER || e.getKey() == e.TAB)) {
                        this.saveQuery();
                    }
                } //specialkey

            } //listeners

        }); //this.chartTitleField

        // ---------------------------------------------------------

        this.chartShowSubtitleField = new Ext.form.Checkbox({
            id: 'me_show_subtitle',
            fieldLabel: 'Filters',
            name: 'show_filters',
            boxLabel: 'Show Chart Filters in Subtitle',
            checked: this.show_filters,

            listeners: {

                scope: this,

                'check': function(checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Show Chart Filters in subtitle checkbox', Ext.encode({
                            checked: check
                        }));

                        this.show_filters = check;
                        this.saveQuery();
                    } //check
            } //listeners

        }); //this.chartShowSubtitleField

        // ---------------------------------------------------------

        this.showWarningsCheckbox = new Ext.form.Checkbox({
            id: 'me_show_warnings',
            fieldLabel: 'Warnings',
            name: 'show_warnings',
            boxLabel: 'Show Warnings',
            checked: this.show_warnings,

            listeners: {
                check: {
                    fn: function(checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on Show Warnings checkbox', Ext.encode({
                            checked: check
                        }));

                        this.show_warnings = check;
                        this.saveQuery();
                    },
                    scope: this
                }
            }
        }); //this.showWarningsCheckbox

        // ---------------------------------------------------------

        this.chartSwapXYField = new Ext.form.Checkbox({
            id: 'me_chart_swap_xy',
            fieldLabel: 'Invert Axis',
            name: 'swap_xy',
            boxLabel: 'Swap the X and Y axis',
            checked: this.swap_xy,

            listeners: {

                scope: this,

                'check': function(checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on the Invert Axis checkbox', Ext.encode({
                            checked: check
                        }));

                        this.swap_xy = check;
                        this.saveQuery();
                    } //check

            } //listeners

        }); //this.chartSwapXYField

        // ---------------------------------------------------------

        this.shareYAxisField = new Ext.form.Checkbox({
            id: 'me_share_yaxis',
            fieldLabel: 'Share Y Axis',
            name: 'share_y_axis',
            boxLabel: 'Single Y axis',
            checked: this.share_y_axis,

            listeners: {

                scope: this,

                'check': function(checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on the Share Y Axis checkbox', Ext.encode({
                            checked: check
                        }));

                        this.share_y_axis = check;
                        this.saveQuery();
                    } //check

            } //listeners

        }); //this.shareYAxisField

        // ---------------------------------------------------------

        this.hideTooltipField = new Ext.form.Checkbox({
            id: 'me_hide_tooltip',
            fieldLabel: 'Hide Tooltip',
            name: 'hide_tooltip',
            boxLabel: 'Hide Tooltip',
            checked: this.hide_tooltip,

            listeners: {

                scope: this,

                'check': function(checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Clicked on the Hide Tooltip checkbox', Ext.encode({
                            checked: check
                        }));

                        this.hide_tooltip = check;
                        this.saveQuery();
                    } //check

            } //listeners

        }); //this.hideTooltipField

        // ---------------------------------------------------------

        this.legendTypeComboBox = new Ext.form.ComboBox({
            id: 'me_legend_type',
            fieldLabel: 'Legend',
            name: 'legend_type',
            xtype: 'combo',
            mode: 'local',
            editable: false,
            tpl: '<tpl for="."><div ext:qtip="{text}" class="x-combo-list-item">{text}</div></tpl>',

            store: new Ext.data.ArrayStore({

                id: 0,

                fields: [
                    'id',
                    'text'
                ],

                data: XDMoD.Module.MetricExplorer.legend_types

            }), //store

            disabled: false,
            value: this.legend_type,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',

            listeners: {
                scope: self,
                select: function(combo, record/*, index */) {
                    self.setLegendValue(record.get('id'), true);
                }, //select

            } //listeners

        }); //this.legendTypeComboBox

        // ---------------------------------------------------------

        this.fontSizeSlider = new Ext.slider.SingleSlider({
            id: 'me_font_size_slider',
            fieldLabel: 'Font Size',
            name: 'font_size',
            minValue: -5,
            maxValue: 10,
            value: this.font_size,
            increment: 1,
            plugins: new Ext.slider.Tip(),
            listeners: {
                scope: this,
                'changecomplete': function(slider, newValue, thumb) {

                    XDMoD.TrackEvent('Metric Explorer', 'Used the font size slider', Ext.encode({
                        font_size: slider.getValue()
                    }));

                    this.font_size = slider.getValue();
                    this.saveQuery();
                }
            } //listeners
        }); //this.fontSizeSlider

        // ---------------------------------------------------------

        var summaryTab = Ext.getCmp('tg_summary');
        var summaryTabName = summaryTab ? summaryTab.title : 'Summary';

        this.featuredCheckbox = new Ext.form.Checkbox({
            id: 'me_featured',
            fieldLabel: 'Featured',
            name: 'featured',
            boxLabel: 'Show in ' + summaryTabName + ' tab',
            checked: this.featured,
            listeners: {
                scope: this,
                'check': function(checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Toggled Show in ' + summaryTabName + ' tab checkbox', Ext.encode({
                            checked: check
                        }));

                        this.featured = check;
                        this.saveQuery();
                    } //check
            } //listeners
        }); //this.featuredCheckbox

        this.trendLineCheckbox = new Ext.form.Checkbox({
            id: 'me_trend_line',
            fieldLabel: 'Trend Line',
            name: 'trend_line',
            boxLabel: 'Show Trend Line',
            tooltip: 'Show Trend Line',
            checked: this.trendLineEnabled,
            disabled: !this.isTrendLineAvailable(),
            listeners: {
                scope: this,
                check: function(checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Toggled Show Trend Line checkbox', Ext.encode({
                        checked: check
                    }));
                    this.fireEvent('disable_commit');

                    // UPDATE: the data set
                    this.datasetStore.each(function(record) {
                        record.set('trend_line', check);
                    });

                    this.fireEvent('enable_commit', true);
                }
            }
        }); // this.trendLineCheckbox

        this.showRemainderCheckbox = new Ext.form.Checkbox({
            fieldLabel: 'Remainder',
            name: 'show_remainder',
            boxLabel: 'Show Remainder (Disable Paging)',
            tooltip: 'When enabled, data series beyond the limit will be displayed as a single, summarized series. This will disable paging.',
            checked: this.show_remainder,
            listeners: {
                check: {
                    scope: this,
                    fn: function(checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Toggled "Show Remainder" Checkbox', Ext.encode({
                            checked: check
                        }));

                        this.show_remainder = check;

                        this.saveQuery();
                    }
                }
            }
        });

        this.chartNameTextbox = new Ext.form.TextField({
            id: 'me_chart_name',
            fieldLabel: 'Name',
            name: 'chart_name',
            boxLabel: 'Chart Name',
            tooltip: 'Name assigned to the current chart.',
            allowBlank: false,
            enableKeyEvents: true,
            listeners: {
                scope: this,

                /**
                 *
                 * @param {Ext.form.TextField} textbox
                 * @param {String} newValue
                 * @param {String} oldValue
                 */
                change: function(textbox, newValue, oldValue) {

                    var isValid = this.chartNameTextbox.validate();
                    if (!isValid) {
                        this.chartNameTextbox.focus();
                        return;
                    }

                    var newHtml = Ext.util.Format.htmlEncode(newValue);

                    XDMoD.TrackEvent('Metric Explorer', 'Updated the Chart Name', Ext.encode({
                        original_name: oldValue,
                        new_name: newValue
                    }));

                    if (this.currentQueryRecord) {
                        this.chartOptionsButton.setText(truncateText(newHtml, XDMoD.Module.MetricExplorer.CHART_OPTIONS_MAX_TEXT_LENGTH));
                        this.chartOptionsButton.setTooltip(newHtml);
                        this.currentQueryRecord.set('name', newHtml);
                        this.currentQueryRecord.stack.add(this.currentQueryRecord.data);
                    }
                } // change
            }
        });
        // ---------------------------------------------------------

        var getBaseParams = function() {
            var baseParams = {},
                title = this.chartTitleField.getValue();

            baseParams.show_title = 'n';
            baseParams.timeseries = this.timeseries ? 'y' : 'n';
            baseParams.aggregation_unit = this.getDurationSelector().getAggregationUnit();
            baseParams.start_date = this.getDurationSelector().getStartDate().format('Y-m-d');
            baseParams.end_date = this.getDurationSelector().getEndDate().format('Y-m-d');
            baseParams.global_filters = encodeURIComponent(Ext.util.JSON.encode(this.getGlobalFilters()));
            baseParams.title = title;
            baseParams.show_filters = this.show_filters;
            baseParams.show_warnings = this.show_warnings;
            baseParams.show_remainder = this.show_remainder;
            return baseParams;

        }; //getBaseParams

        // ---------------------------------------------------------
        var enabledCheckColumn = new Ext.grid.CheckColumn({

            id: 'enabled_dataset',
            sortable: false,
            dataIndex: 'enabled',
            header: 'Enabled',
            scope: this,
            width: 70,
            checkchange: function(rec, data_index, checked) {
                    XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                        column: data_index,
                        realm: rec.data.realm,
                        metric: rec.data.metric,
                        checked: checked
                    }));
                    enabledCheckColumn.checked = checked;
                } //checkchange

        }); //enabledCheckColumn

        var onMouseDown = enabledCheckColumn.onMouseDown;
        var validatedOnMouseDown = function(e, t) {
            self.selectedDataSeriesIndex = this.grid.getView().findRowIndex(t);
            var record = this.grid.store.getAt(self.selectedDataSeriesIndex);
            var enabled = record.get('enabled');
            // we negate the enabled value because the change hasn't occured yet
            // so the future value will be !currentValue.
            var modifier = !enabled ? 1 : 0;
            var ignoredColumns = !enabled ? [] : [self.selectedDataSeriesIndex];
            var includedColumns = !enabled ? [self.selectedDataSeriesIndex] : [];
            var valid = self.validateChart(self._getDisplayTypes(true, ignoredColumns, includedColumns), modifier);
            if (!valid) {
                return false;
            }

            onMouseDown.call(enabledCheckColumn, e, t);
        };
        enabledCheckColumn.onMouseDown = validatedOnMouseDown;

        // ---------------------------------------------------------
        var logScaleCheckColumn = new Ext.grid.CheckColumn({

            id: 'log_scale',
            sortable: false,
            dataIndex: 'log_scale',
            header: 'Log',

            tooltip: 'Use a logarithmic scale for this data',
            scope: this,
            width: 30,

            checkchange: function(rec, data_index, checked) {

                XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                    column: data_index,
                    realm: rec.data.realm,
                    metric: rec.data.metric,
                    checked: checked
                }));
            }, //checkchange

            renderer: function(v, p, record) {
                var isPie = self.isPie();
                p.css += ' x-grid3-check-col-td';
                if ((this.disabledDataIndex && !record.data[this.disabledDataIndex]) || (this.enabledNotDataIndex && record.data[this.enabledNotDataIndex])) {
                    return String.format('<div class="{0}">&#160;</div>', this.createId());
                } else if (!isPie) {
                    return String.format('<div class="x-grid3-check-col{0} {1}">&#160;</div>', v ? '-on' : '', this.createId());
                } else {
                    return String.format('<div class="x-grid3-check-col{0} {1} x-item-disabled unselectable=on">&#160;</div>', v ? '-on' : '', this.createId());
                }
            }

        }); //logScaleCheckColumn

        var logScaleMouseDown = logScaleCheckColumn.onMouseDown;
        logScaleCheckColumn.onMouseDown = function(event, comp) {
            var isPie = self.isPie();
            if (isPie) {
                return false;
            } else {
                logScaleMouseDown.apply(logScaleCheckColumn, [event, comp]);
            }
        };

        // ---------------------------------------------------------

        var valueLabelsCheckColumn = new Ext.grid.CheckColumn({

            id: 'value_labels',
            sortable: false,
            dataIndex: 'value_labels',
            header: 'Labels',

            tooltip: 'Show value labels in the chart',
            scope: this,
            width: 50,

            checkchange: function(rec, data_index, checked) {

                    XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                        column: data_index,
                        realm: rec.data.realm,
                        metric: rec.data.metric,
                        checked: checked
                    }));
                } //checkchange

        }); //valueLabelsCheckColumn

        // ---------------------------------------------------------

        var stdErrCheckColumn = new Ext.grid.CheckColumn({

            id: 'std_err',
            sortable: false,
            dataIndex: 'std_err',
            disabledDataIndex: 'has_std_err',
            enabledNotDataIndex: 'log_scale',
            header: 'Err Bars',

            tooltip: 'Show Standard Error Bars (Where applicable and non log scale)',
            scope: this,
            width: 60,

            checkchange: function(rec, data_index, checked) {

                    XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                        column: data_index,
                        realm: rec.data.realm,
                        metric: rec.data.metric,
                        checked: checked
                    }));
                } //checkchange

        }); //stdErrCheckColumn

        // ---------------------------------------------------------

        var stdErrLabelsCheckColumn = new Ext.grid.CheckColumn({

            id: 'std_err_labels',
            sortable: false,
            dataIndex: 'std_err_labels',
            disabledDataIndex: 'has_std_err',
            enabledNotDataIndex: 'log_scale',
            header: 'Err Labels',

            tooltip: 'Show Standard Error Labels (Where applicable and non log scale)',
            scope: this,
            width: 60,

            checkchange: function(rec, data_index, checked) {

                    XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                        column: data_index,
                        realm: rec.data.realm,
                        metric: rec.data.metric,
                        checked: checked
                    }));
                } //checkchange

        }); //stdErrLabelsCheckColumn

        // ---------------------------------------------------------

        var longLegendCheckColumn = new Ext.grid.CheckColumn({

            id: 'long_legend',
            sortable: false,
            dataIndex: 'long_legend',
            header: 'Verbose Legend',

            tooltip: 'Show filters in legend',
            scope: this,
            width: 100,

            checkchange: function(rec, data_index, checked) {

                    XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                        column: data_index,
                        realm: rec.data.realm,
                        metric: rec.data.metric,
                        checked: checked
                    }));
                } //checkchange

        }); //longLegendCheckColumn

        // ---------------------------------------------------------

        var ignoreGlobalFiltersCheckColumn = new Ext.grid.CheckColumn({

            id: 'ignore_global',
            sortable: false,
            dataIndex: 'ignore_global',
            header: 'Ignore Chart Filters',
            tooltip: 'Ingore Chart Filters',
            scope: this,
            width: 140,

            checkchange: function(rec, data_index, checked) {

                    XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                        column: data_index,
                        realm: rec.data.realm,
                        metric: rec.data.metric,
                        checked: checked
                    }));
                } //checkchange

        }); //ignoreGlobalFiltersCheckColumn

        // ---------------------------------------------------------

        var trendLineCheckColumn = new Ext.grid.CheckColumn({

            id: 'trend_line',
            sortable: false,
            dataIndex: 'trend_line',
            header: 'Trend Line',
            tooltip: 'Show Trend Line',
            scope: this,
            width: 80,
            hidden: !this.timeseries,

            checkchange: function(rec, data_index, checked) {

                    XDMoD.TrackEvent('Metric Explorer', 'Toggled option in Data grid', Ext.encode({
                        column: data_index,
                        realm: rec.data.realm,
                        metric: rec.data.metric,
                        checked: checked
                    }));
                } //checkchange

        }); //trendLineCheckColumn

        // ---------------------------------------------------------

        this.datasetStore = new Ext.data.JsonStore({

            root: 'data',
            autoDestroy: true,
            idIndex: 0,

            fields: [
                'id',
                'metric',
                'category',
                'realm',
                'group_by',
                'x_axis',
                'log_scale',
                'has_std_err',
                'std_err',
                'std_err_labels',
                'value_labels',
                'display_type',
                'line_type',
                'line_width',
                'combine_type',
                'sort_type',
                'filters',
                'ignore_global',
                'long_legend',
                'trend_line',
                'color',
                'shadow',
                'visibility', {
                    name: 'z_index',
                    convert: function(v /*, record*/ ) {
                        if (v === null || v === undefined || v === "") {
                            return null;
                        }
                        return v;
                    }
                }, {
                    name: 'enabled',
                    convert: function(v /*, record*/ ) {
                        return v !== false;
                    }
                }
            ]

        }); //this.datasetStore

        // ---------------------------------------------------------

        //delay saving on updated records, chances are user is moving mouse to
        //next checkbox

        /**
         *
         * @param {Ext.data.Store} s
         * @param {Ext.data.Record} record
         * @param {Object} operation
         **/
        this.datasetStoreOnUpdate = function(s, record, operation) {
            var saved = this.saveQueryFunc(false);
            if (saved === false) {
                return false;
            }
            this.dataCatalogTreeOnDatasetUpdate.call(this, record);
        }; // datasetStoreOnUpdate()
        this.datasetStore.on('update', this.datasetStoreOnUpdate, this);

        // ---------------------------------------------------------

        //load the stuff quickly when the query is loaded
        this.datasetStore.on('load', function(s, records, opts) {
            this.dataCatalogTreeOnDatasetLoad.call(this, records);
        }, this);
        // ---------------------------------------------------------

        this.datasetStore.on('clear', function(s, records, opts) {
            var catalogRoot = this.dataCatalogTree.getRootNode();
            this.dataCatalogTreeOnDatasetClear.call(this, records, catalogRoot);
        }, this);
        // ---------------------------------------------------------

        //on adding the dataset, make it appear fast
        this.datasetStoreOnAdd = function(s, records, index) {
            this.saveQueryFunc(false);
            this.dataCatalogTreeOnDatasetAdd.call(this, records, index);

        }; // datasetStoreOnAdd()
        this.datasetStore.on('add', this.datasetStoreOnAdd, this);

        // ---------------------------------------------------------

        //on removing the dataset, make it disappear fast
        this.datasetStoreOnRemove = function(s, record, index) {
            this.saveQuery();
            this.dataCatalogTreeOnDatasetRemove.call(this, record, index);

        }; // datasetStoreOnRemove
        this.datasetStore.on('remove', this.datasetStoreOnRemove, this);

        // ---------------------------------------------------------

        this.addDatasetButton = new Ext.Button({
            iconCls: 'add_data',
            text: 'Add Data',
            tooltip: 'Add a new metric to the currently loaded chart',
            menu: this.metricsMenu,
            handler: function( /*i, e*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Add Data button');
            }
        }); //this.addDatasetButton

        // ---------------------------------------------------------

        this.addFilterButton = new Ext.Button({
            scope: this,
            iconCls: 'add_filter',
            text: 'Add Filter',
            tooltip: 'Add a global filter to the currently loaded chart',
            menu: this.filtersMenu,
            handler: function( /*i, e*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Create Filter button');
            }
        }); //this.addFilterButton

        // ---------------------------------------------------------

        this.getDataset = function(datasetId) {
            var record = null;
            var sm = this.datasetsGridPanel.getSelectionModel();

            //datasetId provides override for interation with chart series
            if (datasetId !== undefined && datasetId !== null) {

                //find row where id - datasetId;
                var datasetIndex = this.datasetStore.findBy(function(_record, _id) {

                    if (Math.abs(datasetId - _record.data.id) < 1e-14) {
                        return true;
                    }

                }, this);

                if (datasetIndex < 0) {
                    return null;
                }

                sm.selectRow(datasetIndex);
                record = sm.getSelected();

            }
            return record;
        };

        // ---------------------------------------------------------

        this.editDataset = function(datasetId) {
            var self = this;
            var record = null;
            var sm = this.datasetsGridPanel.getSelectionModel();

            //datasetId provides override for interation with chart series
            if (datasetId !== undefined && datasetId !== null) {

                //find row where id - datasetId;
                var datasetIndex = this.datasetStore.findBy(function(_record, _id) {

                    if (Math.abs(datasetId - _record.data.id) < 1e-14) {
                        return true;
                    }

                }, this);

                if (datasetIndex < 0) {
                    return;
                }

                sm.selectRow(datasetIndex);
                record = sm.getSelected();

            } else {
                record = sm.getSelected();
            }

            // ---------------------------------------------------------

            var addDataPanel = new CCR.xdmod.ui.AddDataPanel({
                scope: this,
                update_record: true,
                record: record,
                realms: this.realms,
                timeseries: this.timeseries,
                dimensionsCombo: this.dimensionsCombo,

                cancel_function: function() {

                    this.scope.fireEvent('enable_commit', false);
                    addDataMenu.closable = true;
                    addDataMenu.close();

                },

                add_function: function() {

                    this.scope.fireEvent('enable_commit', true);
                    addDataMenu.closable = true;
                    addDataMenu.close();

                    if (this.record.data.display_type === 'pie') {
                        this.scope.fireEvent('disable_pie');
                    } else {
                        this.scope.fireEvent('enable_pie');
                    }

                    // ADD: a change to the stack ( if there's been a change ).
                    this.scope.saveQuery();
                },

                validate: function(field, value) {
                    if (!CCR.isType(field, CCR.Types.String)) {
                        return false;
                    }
                    if (field === 'display_type') {
                        this._updateDisplayType(field, value);
                        return this._validateDisplayType(value);
                    }
                    return true;
                },

                _updateDisplayType: function(field, next) {
                    var current = this.record.get(field);
                    if (current === 'pie' && next !== 'pie') {
                        self.defaultMetricDisplayType = next;
                        self.defaultMetricDisplayTypeField.setValue(next);
                    }
                },

                _validateDisplayType: function(value) {
                    var isAggregate = self.isAggregate();
                    return self.validateChart([value], 0, isAggregate);
                }

            }); //addDataPanel

            // Retrieving a reference to the original select listener so that we
            // can use it in the default case
            var originalListener = addDataPanel.displayTypeCombo.events.select.listeners[0];

            /**
             *
             * @param {Ext.form.ComboBox} combo
             * @param {Ext.data.Record} record
             * @param {Integer} index
             */
            var displayTypeSelect = function(combo, record, index) {
                var valid = self.validateChart(self._getDisplayTypes(true));
                if (valid) {
                    originalListener.fn.call(addDataPanel, combo, record, index);
                }
            };

            addDataPanel.on('render', function() {
                addDataPanel.displayTypeCombo.purgeListeners();
                addDataPanel.displayTypeCombo.on('select', displayTypeSelect);
            });

            // ---------------------------------------------------------

            var addDataMenu = new Ext.Window({

                showSeparator: false,
                resizable: false,
                items: [addDataPanel],
                closable: false,
                scope: this,
                ownerCt: this,

                listeners: {

                    'beforeclose': function(t) {
                        return t.closable;
                    },
                    'close': function(t) {
                        CCR.xdmod.ui.Viewer.dontUnmask = false;
                        addDataPanel.hideMenu();
                        t.scope.unmask();
                    },
                    'show': function(t) {
                        CCR.xdmod.ui.Viewer.dontUnmask = true;
                        t.scope.mask();
                        t.scope.fireEvent('disable_commit');
                    }

                } //listeners

            }); //addDataMenu

            // ---------------------------------------------------------

            Ext.menu.MenuMgr.hideAll();
            addDataMenu.show();

            // ---------------------------------------------------------

            var xy = addDataMenu.el.getAlignToXY(this.datasetsGridPanel.toolbars[0].el, 'tl-bl?');
            xy = addDataMenu.el.adjustForConstraints(xy);
            addDataMenu.setPosition(xy);

        }; //this.editDataset

        // ---------------------------------------------------------

        var editDatasetButton = new Ext.Button({

            iconCls: 'edit_data',
            text: 'Edit',
            tooltip: 'Edit selected dataset',
            disabled: true,
            scope: this,

            handler: function( /*i, e*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Dataset Edit button');
                this.editDataset.call(this);
            }

        }); //editDatasetButton

        // ---------------------------------------------------------

        this.removeDataset = function(datasetId) {

            var sm = this.datasetsGridPanel.getSelectionModel();

            //datasetId providex over ride for interation with chart series
            if (datasetId !== undefined && datasetId !== null) {

                //find row where id - datasetId;
                var datasetIndex = this.datasetStore.findBy(function(_record, _id) {
                    var dif = Math.abs(datasetId - _record.data.id);
                    if (dif < 0.00000000000001) {
                        return true;
                    }

                }, this);

                if (datasetIndex < 0) {
                    return;
                }

                sm.selectRow(datasetIndex);
                var record = sm.getSelected();
                this.datasetStore.remove(record);

            } else {

                var records = this.datasetsGridPanel.getSelectionModel().getSelections();
                this.datasetStore.remove(records);

            }

        }; //this.removeDataset

        // ---------------------------------------------------------

        var removeDatasetButton = new Ext.Button({

            iconCls: 'delete_data',
            text: 'Delete',
            tooltip: 'Delete selected dataset',
            disabled: true,
            scope: this,

            handler: function(i /*, e*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Dataset Delete button');
                i.scope.removeDataset.call(i.scope);
            }

        }); //removeDatasetButton

        // ---------------------------------------------------------

        this.dimensionsCombo = CCR.xdmod.ui.getComboBox(this.allDimensions, ['id', 'text'], 'id', 'text', false, 'None');
        this.metricsCombo = CCR.xdmod.ui.getComboBox(this.allMetrics, ['id', 'text'], 'id', 'text', false, 'None');

        this.displayTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.display_types, ['id', 'text'], 'id', 'text', false, 'None');
        this.lineTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.line_types, ['id', 'text'], 'id', 'text', false, 'Solid');
        this.lineWidthsCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.line_widths, ['id', 'text'], 'id', 'text', false, 2);
        this.combineTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.combine_types, ['id', 'text'], 'id', 'text', false, 'None');
        this.sortTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.sort_types, ['id', 'text'], 'id', 'text', false, 'None');

        // ---------------------------------------------------------
        this.datasetsGridPanelSMOnSelectionChange = function(sm) {
            var disabled = sm.getCount() <= 0;
            if (sm.getCount() == 1) {
                var record = sm.getSelections()[0],
                    sel = record.data;
                this.dataCatalogTreeOnDatasetSelect.call(this, record);
                XDMoD.TrackEvent('Metric Explorer', 'Selected a dataset from the list', sel.realm + ' -> ' + sel.metric);
            }
            removeDatasetButton.setDisabled(disabled);
            editDatasetButton.setDisabled(disabled);
        };

        this.datasetsGridPanelSM = new Ext.grid.RowSelectionModel({
            singleSelect: true,
            listeners: {
                selectionchange: {
                    scope: this,
                    fn: this.datasetsGridPanelSMOnSelectionChange
                }
            }
        });
        this.datasetsGridPanel = new Ext.grid.GridPanel({

            header: false,
            height: 200,
            id: 'grid_datasets_' + this.id,
            useArrows: true,
            autoScroll: true,
            sortable: false,
            enableHdMenu: false,
            margins: '0 0 0 0',
            loadMask: true,

            sm: this.datasetsGridPanelSM,

            plugins: [

                //this.metricsGridPanelEditor,
                enabledCheckColumn,
                // xAxisCheckColumn,
                logScaleCheckColumn,
                valueLabelsCheckColumn,
                stdErrCheckColumn,
                stdErrLabelsCheckColumn,
                longLegendCheckColumn,
                ignoreGlobalFiltersCheckColumn,
                trendLineCheckColumn //,
                //new Ext.ux.plugins.ContainerBodyMask ({ msg:'No data selected.<br/> Click on <img class="x-panel-inline-icon add_data" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to add data.', masked:true})

            ], //plugins

            listeners: {

                scope: this,

                rowdblclick: function(t, rowIndex, e) {
                    XDMoD.TrackEvent('Metric Explorer', 'Double-clicked on dataset entry in list');
                    this.editDataset.call(this);
                }

            }, //listeners

            store: this.datasetStore,

            viewConfig: {

                emptyText: 'No data selected.<br/> Click on <img class="x-panel-inline-icon add_data" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to add data.'

            }, //viewConfig

            columns: [
                enabledCheckColumn,
                {
                    id: 'category',
                    tooltip: 'The category the metric belongs to.',
                    width: 80,
                    header: 'Category',

                    // Use the realm to lookup the category.
                    dataIndex: 'realm',
                    renderer: {
                        fn: function(value) {
                            return Ext.util.Format.htmlEncode(
                                XDMoD.Module.MetricExplorer.getCategoryForRealm(value)
                            );
                        },
                        scope: this
                    }
                },
                {
                    id: 'metric',
                    tooltip: 'Metric',
                    width: 200,
                    header: 'Metric',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.metricsCombo),
                    dataIndex: 'metric'
                },
                {
                    id: 'group_by',
                    tooltip: 'Group the results by this dimension',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.dimensionsCombo),
                    width: 60,
                    header: 'Grouping',
                    dataIndex: 'group_by'
                },
                logScaleCheckColumn,
                valueLabelsCheckColumn,
                stdErrCheckColumn,
                stdErrLabelsCheckColumn,
                {
                    id: 'display_type',
                    tooltip: 'Display Type',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.displayTypesCombo),
                    width: 60,
                    header: 'Display',
                    dataIndex: 'display_type'
                },
                {
                    id: 'line_type',
                    tooltip: 'Line Type',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.lineTypesCombo),
                    width: 60,
                    header: 'Line',
                    dataIndex: 'line_type'
                },
                {
                    id: 'line_width',
                    tooltip: 'Line Width',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.lineWidthsCombo),
                    width: 60,
                    header: 'Line Width',
                    dataIndex: 'line_width'
                },
                {
                    id: 'combine_type',
                    tooltip: 'Dataset Alignment - How the data bar/lines/areas are aligned relative to each other',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.combineTypesCombo),
                    width: 100,
                    header: 'Align',
                    dataIndex: 'combine_type'
                },
                {
                    id: 'sort_type',
                    tooltip: 'Sort Type - How the data will be sorted',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.sortTypesCombo),
                    width: 130,
                    header: 'Sort',
                    dataIndex: 'sort_type'
                },
                longLegendCheckColumn,
                ignoreGlobalFiltersCheckColumn,
                trendLineCheckColumn
            ], //columns
            tbar: [
                editDatasetButton,
                '-',
                removeDatasetButton
            ]
        }); //this.datasetsGridPanel

        // ---------------------------------------------------------

        this.filtersStore = XDMoD.DataWarehouse.createFilterStore();

        this.filtersStore.on('load', this.filterStoreLoad, this);

        this.filtersStore.on('add', function () {
            this.saveQuery();
        }, this);

        this.filtersStore.on('remove', function () {
            this.saveQuery();
        }, this);

        this.filtersStore.on('clear', function () {
            this.saveQuery();
        }, this);
        // ---------------------------------------------------------

        var selectAllButton = new Ext.Button({
            text: 'Select All',
            scope: this,
            handler: function( /*b, e*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Select All in Chart Filters pane');

                this.filtersStore.each(function (r) {
                    r.set('checked', true);
                });
            } // handler
        }); // selectAllButton

        // ---------------------------------------------------------

        var clearAllButton = new Ext.Button({
            text: 'Clear All',
            scope: this,
            handler: function( /*b, e*/ ) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on Clear All in Chart Filters pane');

                this.filtersStore.each(function (r) {
                    r.set('checked', false);
                });
            } // handler
        }); // clearAllButton

        // ---------------------------------------------------------

        var activeFilterCheckColumn = new Ext.grid.CheckColumn({
            id: 'checked',
            sortable: false,
            dataIndex: 'checked',
            header: 'Global',
            tooltip: 'Check this column to apply filter globally',
            scope: this,
            width: 50,
            hidden: false,
            checkchange: function (record, data_index, checked) {
                XDMoD.TrackEvent('Metric Explorer', 'Toggled filter checkbox', Ext.encode({
                    dimension: record.data.dimension_id,
                    value: record.data.value_name,
                    checked: checked
                }));
            } // checkchange
        }); // activeFilterCheckColumn

        // ---------------------------------------------------------

        var removeFilterItem = new Ext.Button({
            iconCls: 'delete_filter',
            tooltip: 'Delete selected filter(s)',
            text: 'Delete',
            disabled: true,
            scope: this,
            handler: function( /*i, e*/ ) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Delete in Chart Filters pane');

                    var records = this.filtersGridPanel.getSelectionModel().getSelections();

                    for (var t = 0; t < records.length; t++) {
                        XDMoD.TrackEvent('Metric Explorer', 'Confirmed deletion of filter', Ext.encode({
                            dimension: records[t].data.dimension_id,
                            value: records[t].data.value_name
                        }));
                    } //for (each record selected)

                    this.filtersGridPanel.store.remove(records);
                } //handler
        }); //removeFilterItem

        //----------------------------------------------------------

        var applyFilterSelection = new Ext.Button({
            tooltip: 'Apply selected filter(s)',
            text: 'Apply',
            scope: this,
            handler: function () {
                XDMoD.TrackEvent('Metic Explorer', 'Clicked on Apply filter in Chart Filters pane');
                self.saveQuery();
                this.filtersStore.commitChanges();
            } // handler
        }); // applyFilterSelection

        var cancelFilterSelection = new Ext.Button({
            tooltip: 'Cancel filter selections',
            text: 'Cancel',
            scope: this,
            handler: function () {
                this.filtersStore.rejectChanges();
            }
        });

        // ---------------------------------------------------------

        this.filtersGridPanel = new Ext.grid.GridPanel({
            header: false,
            height: 200,
            id: 'grid_filters_' + this.id,
            useArrows: true,
            autoScroll: true,
            sortable: false,
            enableHdMenu: false,
            margins: '0 0 0 0',
            buttonAlign: 'left',
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: false,
                listeners: {
                    'rowselect': function(sm, row_index, record) {
                        XDMoD.TrackEvent('Metric Explorer', 'Selected a chart filter', Ext.encode({
                            dimension: record.data.dimension_id,
                            value: record.data.value_name
                        }));
                    },
                    'rowdeselect': function(sm, row_index, record) {
                        XDMoD.TrackEvent('Metric Explorer', 'De-selected a chart filter', Ext.encode({
                            dimension: record.data.dimension_id,
                            value: record.data.value_name
                        }));
                    },
                    'selectionchange': function(sm) {
                        removeFilterItem.setDisabled(sm.getCount() <= 0);
                    }
                } //listeners
            }), //Ext.grid.RowSelectionModel
            plugins: [
                activeFilterCheckColumn
            ],
            autoExpandColumn: 'value_name',
            store: this.filtersStore,
            loadMask: true,
            view: new Ext.grid.GroupingView({
                groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})',
                emptyText: 'No filters created.<br/> Click on <img class="x-panel-inline-icon add_filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to create filters.<br/><br/>' + 'Filters will apply to chart data, where the realm of the filter matches the data.'
            }),
            columns: [
                activeFilterCheckColumn, {
                    id: 'dimension',
                    tooltip: 'Dimension',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.dimensionsCombo),
                    width: 150,
                    header: 'Dimension',
                    dataIndex: 'dimension_id'
                }, {
                    id: 'value_name',
                    tooltip: 'Filter',
                    width: 150,
                    header: 'Filter',
                    dataIndex: 'value_name'
                }, {
                    id: 'categories',
                    tooltip: 'Categories that this filter applies to',
                    width: 150,
                    header: 'Applicable Categories',

                    // Find the applicable categories using the dimension.
                    dataIndex: 'dimension_id',
                    renderer: {
                        fn: function(value) {
                            var realms = XDMoD.Module.MetricExplorer.getRealmsForDimension(value);

                            var categoryMapping = {};
                            Ext.each(realms, function(realm) {
                                var category = XDMoD.Module.MetricExplorer.getCategoryForRealm(realm);
                                categoryMapping[category] = true;
                            }, this);

                            var categories = Object.keys(categoryMapping);
                            categories.sort();
                            categories = categories.map(Ext.util.Format.htmlEncode);
                            return categories.join(', ');
                        },
                        scope: this
                    }
                }
            ],
            tbar: [
                removeFilterItem
            ],
            fbar: [
                clearAllButton,
                '-',
                selectAllButton,
                '->',
                applyFilterSelection,
                '-',
                cancelFilterSelection
            ]
        }); // this.filtersGridPanel

        // ---------------------------------------------------------

        var filterButtonHandler = function(el, dim_id, dim_label, realms) {

            if (!dim_id || !dim_label) {
                return;
            }

            var addFilterMenu = XDMoD.DataWarehouse.createAddFilterWindow(
                dim_id,
                dim_label,
                realms,
                this.filtersStore,
                'Metric Explorer',
                ''
            );

            this.fireEvent('disable_commit');

            addFilterMenu.items.items[0].on('ok', function() {
                self.fireEvent('enable_commit', true);
            });
            addFilterMenu.items.items[0].on('cancel', function() {
                self.fireEvent('enable_commit', false);
            });

            addFilterMenu.show();
            addFilterMenu.center();
            var xy = addFilterMenu.el.getAlignToXY(this.filtersGridPanel.toolbars[0].el, 'tl-bl?');

            //constrain to the viewport.
            xy = addFilterMenu.el.adjustForConstraints(xy);
            addFilterMenu.setPosition(xy);

        }; //filterButtonHandler

        // ---------------------------------------------------------

        var addDataButtonHandler = function(el, metric, realm, realms) {
            var config = Ext.apply({}, this.defaultDatasetConfig);
            Ext.apply(config, {
                realm: realm,
                metric: metric
            });
            var addDataPanel = new CCR.xdmod.ui.AddDataPanel({
                config: config,
                store: this.datasetsGridPanel.store,
                realms: realms,
                timeseries: this.timeseries,
                dimensionsCombo: this.dimensionsCombo,
                cancel_function: function() {
                    addDataMenu.closable = true;
                    addDataMenu.close();
                }, //cancel_function
                add_function: function() {
                        addDataMenu.scope.datasetsGridPanel.store.add(this.record);
                        addDataMenu.closable = true;
                        addDataMenu.close();
                    } //add_function
            }); //addDataPanel

            // Retrieving a reference to the original select listener so that we
            // can use it in the default case
            var originalListener = addDataPanel.displayTypeCombo.events.select.listeners[0];

            /**
             *
             * @param {Ext.form.ComboBox} combo
             * @param {Ext.data.Record} record
             * @param {Integer} index
             */
            var displayTypeSelect = function(combo, record, index) {
                var chartType = combo.getValue();
                var valid = self.validateChart(
                    [chartType]
                );
                if (valid) {
                    originalListener.fn.call(addDataPanel, combo, record, index);
                }
            };

            addDataPanel.on('render', function() {
                addDataPanel.displayTypeCombo.purgeListeners();
                addDataPanel.displayTypeCombo.on('select', displayTypeSelect);
            });

            var addDataMenu = new Ext.Window({
                showSeparator: false,
                items: [addDataPanel],
                closable: false,
                scope: this,
                ownerCt: this,
                resizable: false,
                listeners: {
                    'beforeclose': function(t) {
                        return t.closable;
                    },
                    'close': function(t) {
                        CCR.xdmod.ui.Viewer.dontUnmask = false;
                        addDataPanel.hideMenu();
                        t.scope.unmask();
                    },
                    'show': function(t) {
                        CCR.xdmod.ui.Viewer.dontUnmask = true;
                        t.scope.mask();
                    }
                } //listeners
            }); //addDataMenu
            addDataMenu.show();
            addDataMenu.center();
            var xy = addDataMenu.el.getAlignToXY(el, 'tl-bl?');
            xy = addDataMenu.el.adjustForConstraints(xy);
            addDataMenu.setPosition(xy);
        }; //addDataButtonHandler

        // ---------------------------------------------------------

        this.queriesStore = new CCR.xdmod.CustomJsonStore({
            restful: true,
            autoSave: false,
            proxy: new Ext.data.HttpProxy({
                api: {
                    read: {
                        url: 'rest/v1/metrics/explorer/queries',
                        method: 'GET'
                    },
                    create: {
                        url: 'rest/v1/metrics/explorer/queries',
                        method: 'POST'
                    }, // Server MUST return idProperty of new record

                    update: {
                        url: 'rest/v1/metrics/explorer/queries',
                        method: 'POST'
                    },

                    destroy: {
                        url: 'rest/v1/metrics/explorer/queries',
                        method: 'DELETE'
                    }
                }
            }), //proxy

            idProperty: 'recordid',
            root: 'data',

            fields: [{
                    name: 'name',
                    sortType: Ext.data.SortTypes.asUCString
                },
                'config',
                'ts',
                'recordid'
            ],
            sortInfo: {
                field: 'ts',
                direction: 'DESC'
            },
            writer: new Ext.data.JsonWriter({
                encode: true,
                writeAllFields: false

            }), //writer
            listeners: {
                scope: this,
                write: function(store, action, result, res, rs) {
                    var sm = this.queriesGridPanel.getSelectionModel();
                    var recIndex = store.findBy(function(record) {
                        if (record.get('recordid') === rs.id) {
                            return true;
                        }
                    });
                    if (recIndex > -1 && action === 'create') {
                        sm.selectRow(recIndex, false);
                    }
                },
                beforesave: function(store, data) {
                    for (var key in data) {
                        if (data.hasOwnProperty(key)) {
                            var found = null;
                            for (var i = 0; i < data[key].length; i++) {
                                if (this.currentQueryRecord === data[key][i]) {
                                    found = i;
                                    break;
                                }
                            }
                            if (found !== null) {
                                data[key].splice(0, found);
                                data[key].splice(1, data[key].length);
                            } else {
                                data[key].splice(0, data[key].length);
                            }
                        }
                    }
                }
            } //listeners
        }); //new CCR.xdmod.CustomJsonStore

        var newChartHandler = function(b) {
                XDMoD.TrackEvent('Metric Explorer', 'Clicked on New Chart button');

                var createQueryHandler = function() {
                    var text = Ext.util.Format.htmlEncode(nameField.getValue());

                    if (text === '') {
                        Ext.Msg.alert('Name Invalid', 'Please enter a valid name');
                    } else {
                        var recIndex = this.queriesStore.findBy(function(record) {
                            if (record.get('name') === text) {
                                return true;
                            }
                        });
                        if (recIndex < 0) {
                            var preserve = preserveFilters.getValue();

                            // Make sure that we reset the UI / state prior to
                            // attempting to create a new query / chart.
                            this.reset(preserve);

                            var initialConfig = {
                                show_warnings: true,
                                timeseries: b.timeseries,
                                defaultDatasetConfig: {
                                    display_type: b.value
                                }
                            };
                            this.createQueryFunc(null, null, text, undefined, preserve, initialConfig);
                            win.close();
                        } else {
                            Ext.Msg.alert('Name in use', 'Please enter a unique name');
                        }
                    }
                };

                var preserveFilters = new Ext.form.Checkbox({
                    xtype: 'checkbox',
                    listeners: {
                        scope: this,
                        'check': function(checkbox, check) {
                                XDMoD.TrackEvent('Metric Explorer', 'New Chart -> Clicked the Preserve Filters checkbox', Ext.encode({
                                    checked: check
                                }));
                            } //check
                    } //listeners
                });

                var nameField = new Ext.form.TextField({
                    fieldLabel: 'Chart Name',
                    listeners: {
                        scope: this,
                        specialkey: function(field, e) {
                            // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                            // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                            if (e.getKey() == e.ENTER) {
                                XDMoD.TrackEvent('Metric Explorer', 'New Chart -> Pressed enter in textbox', Ext.encode({
                                    input_text: field.getValue()
                                }));
                                createQueryHandler.call(this);
                            }
                        },
                        afterrender: function( /*field, l*/ ) {
                            nameField.focus(true, 100);
                        }
                    }
                });
                var win = new Ext.Window({
                    width: 300,
                    //height: 100,
                    resizable: false,
                    modal: true,
                    title: 'New Chart',
                    layout: 'fit',
                    scope: this,
                    items: nameField,
                    listeners: {
                        close: function() {
                            XDMoD.TrackEvent('Metric Explorer', 'New Chart prompt closed');
                        }
                    },
                    buttons: [
                        new Ext.Toolbar.TextItem('Preserve Filters'),
                        preserveFilters, {
                            text: 'Ok',
                            scope: this,
                            handler: function() {
                                XDMoD.TrackEvent('Metric Explorer', 'New Chart -> Clicked on Ok');
                                createQueryHandler.call(this);
                            }
                        }, {
                            text: 'Cancel',
                            handler: function() {
                                XDMoD.TrackEvent('Metric Explorer', 'New Chart -> Clicked on Cancel');
                                win.close();
                            }
                        }
                    ]
                });
                win.show(this);
            }; //handler
        var newChartMenuItems = [];
        Ext.each(['Timeseries', 'Aggregate'], function(pmi) {
            var r = {
                id: 'new record',
                timeseries: pmi === 'Timeseries',
                display_type: this.display_type
            };
            newChartMenuItems.push({
                text: pmi,
                xtype: 'menuitem',
                menu: new Ext.menu.Menu({
                    id: 'me_new_chart_submenu_' + pmi.replace(/\s/g, '_'),
                    ignoreParentClicks: true,
                    items: this.getDisplayTypeItems(undefined,
                        'menuitem',
                        undefined,
                        newChartHandler,
                        this,
                        {
                          newChart: true,
                          timeseries: 'Timeseries' === pmi
                        })
                })
            });
        }, this);
        this.createQuery = new Ext.Button({
            xtype: 'button',
            tooltip: 'Create a New Chart',
            text: 'New Chart',
            iconCls: 'new_ue',
            scope: this,
            menu: new Ext.menu.Menu({
                id: 'me_new_chart_menu',
                ignoreParentClicks: true,
                items: newChartMenuItems
            })
        }); //this.createQuery

        // ---------------------------------------------------------

        this.saveAsQuery = new Ext.Button({

            xtype: 'button',
            text: 'Save As',
            tooltip: 'Save the current chart under a new name',
            iconCls: 'save_as',
            scope: this,

            handler: function(b) {

                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Save As button');

                    Ext.Msg.prompt(
                        'Save As', 'Please enter a name for the chart:',
                        function(btn, text) {
                            if (btn == 'ok') {
                                XDMoD.TrackEvent('Metric Explorer', 'Save As -> Confirmed new name', Ext.encode({
                                    text_field: text
                                }));
                                if (text === '') {
                                    Ext.Msg.alert('Name Invalid', 'Please enter a valid name');
                                } else {
                                    var recIndex = this.queriesStore.findBy(function(record) {
                                        if (record.get('name') === text) {
                                            return true;
                                        }
                                    }); //.find('name', text, 0, false, true);
                                    if (recIndex < 0) {
                                        var isPhantom = self.currentQueryRecord.phantom;
                                        if (isPhantom) {
                                            // IF: it the current record is a
                                            // phantom.
                                            // THEN:
                                            //   - set the current name to the newly
                                            //     provided text.
                                            //   - save the changes that were made.
                                            self.queriesStore.suspendEvents();
                                            self.currentQueryRecord.set('name', text);
                                            self.fireEvent('save_changes');
                                            self.queriesStore.resumeEvents();
                                        } else {
                                            // IF: it's not a phantom and there are
                                            // changes.
                                            // THEN:
                                            //   - retrieve the current config to be
                                            //     used in creating a new record.
                                            //   - discard the changes on the
                                            //     current record.
                                            //   - create a new record w/ the
                                            //     the changed content & new text
                                            //   - save the newly created record.
                                            var config = self.getConfig();
                                            self.createQueryFunc(null, null, text, config);
                                            self.fireEvent('save_changes');
                                        }
                                    } else {
                                        Ext.Msg.alert('Name in use', 'Please enter a unique name');
                                    }
                                }
                            } //if (btn == 'ok')
                            else {
                                XDMoD.TrackEvent('Metric Explorer', 'Closed Save As prompt');
                            }
                        }, //function(btn, text)
                        this,
                        false
                    ); //Ext.Msg.prompt
                } //handler
        }); //this.saveAsQuery

        // ---------------------------------------------------------

        this.deleteQuery = new Ext.Button({
            xtype: 'button',
            text: 'Delete',
            iconCls: 'delete2',
            tooltip: 'Delete the currently selected chart',
            scope: this,
            handler: function(b, e) {
                    XDMoD.TrackEvent('Metric Explorer', 'Clicked on Delete selected chart button');

                    var sm = this.queriesGridPanel.getSelectionModel();
                    var rec = sm.getSelected();
                    var deletionMessageSubject;
                    if (rec) {
                        deletionMessageSubject = "\"" + rec.get("name") + "\"";
                    } else {
                        deletionMessageSubject = "your scratchpad chart";
                    }

                    Ext.Msg.show({
                        scope: this,
                        maxWidth: 800,
                        minWidth: 400,
                        title: 'Delete Selected Chart',
                        icon: Ext.MessageBox.QUESTION,
                        msg: 'Are you sure you want to delete ' + deletionMessageSubject + '?<br><b>This action cannot be undone.</b>',
                        buttons: Ext.Msg.YESNO,
                        fn: function(resp) {
                                if (resp === 'yes') {
                                    var trackingData;
                                    if (rec) {
                                        trackingData = rec.data.name;
                                        this.queriesStore.remove(rec);
                                        this.queriesStore.save();
                                    } else {
                                        trackingData = "(scratchpad chart)";
                                    }
                                    this.reset();
                                    this.createQueryFunc.call(this, null, null, null, null, null, null, false);
                                    this.reloadChart.call(this);

                                    XDMoD.TrackEvent('Metric Explorer', 'Confirmed deletion of chart', trackingData);
                                } //if (resp === 'yes')
                                else {
                                    XDMoD.TrackEvent('Metric Explorer', 'Dismissed chart deletion confirm dialog');
                                }
                            } //fn
                    }); //Ext.Msg.show
                } //handler
        }); //this.deleteQuery

        // ---------------------------------------------------------

        var searchField = new Ext.form.TwinTriggerField({
            xtype: 'twintriggerfield',
            validationEvent: false,
            validateOnBlur: false,
            trigger1Class: 'x-form-clear-trigger',
            trigger2Class: 'x-form-search-trigger',
            hideTrigger1: true,
            enableKeyEvents: true,
            emptyText: 'Search',
            onTrigger1Click: function() {
                XDMoD.TrackEvent('Metric Explorer', 'Cleared chart search field');

                this.store.clearFilter();
                this.el.dom.value = '';
                this.triggers[0].hide();
            }, //onTrigger1Click
            onTrigger2Click: function() {
                var v = this.getRawValue();
                if (v.length < 1) {
                    this.onTrigger1Click();
                    return;
                }

                XDMoD.TrackEvent('Metric Explorer', 'Using chart search field', Ext.encode({
                    search_string: v
                }));

                this.store.filter('name', v, true, true);
                this.triggers[0].show();
            }, //onTrigger2Click
            listeners: {
                scope: this,
                'specialkey': function(field, e) {
                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.getKey() == e.ENTER) {
                        searchField.onTrigger2Click();
                    }
                }
            } //listeners

        }); //searchField

        // ---------------------------------------------------------

        this.queriesGridPanelSMRowSelect = function(t, rowIndex, r) {

            XDMoD.TrackEvent('Metric Explorer', 'Selected chart from list', r.data.name);
            Ext.menu.MenuMgr.hideAll();

            this.chartNameTextbox.setValue(Ext.util.Format.htmlDecode(r.data.name));
            this.chartOptionsButton.setText(truncateText(r.data.name, XDMoD.Module.MetricExplorer.CHART_OPTIONS_MAX_TEXT_LENGTH));
            this.chartOptionsButton.setTooltip(r.data.name);

            this.loadQuery(Ext.util.JSON.decode(r.data.config), true);
            this.selectedIndex = rowIndex;
            this.currentQueryRecord = r;

            if (!this.currentQueryRecord.stack) {
                // reset the record with the current chart config so that all defaults are populated
                this.currentQueryRecord.set('config', Ext.util.JSON.encode(this.getConfig()));
                this.currentQueryRecord.stack = new XDMoD.ChangeStack({
                    baseParams: r.data,
                    listeners: {
                        'update': function(changeStack, record, action) {
                            self.handleChartModification(changeStack, record, action);
                        }
                    }
                });
                this.currentQueryRecord.stack.mark();
            }
            this.handleChartModification(this.currentQueryRecord.stack, r.data, 'chartselected');
        };

        this.queriesGridPanelSM = new Ext.grid.RowSelectionModel({

            singleSelect: true,
            // private (supposedly...)
            handleMouseDown: function(g, rowIndex, e) { //prevent deselection of only selected row.
                if (e.button !== 0 || this.isLocked()) {
                    return;
                }
                var view = this.grid.getView();
                if (e.shiftKey && !this.singleSelect && this.last !== false) {
                    var last = this.last;
                    this.selectRange(last, rowIndex, e.ctrlKey);
                    this.last = last; // reset the last
                    view.focusRow(rowIndex);
                } else {
                    var isSelected = this.isSelected(rowIndex);
                    if (e.ctrlKey && isSelected) {
                        //this.deselectRow(rowIndex);
                    } else if (!isSelected || this.getCount() > 1) {
                        this.selectRow(rowIndex, e.ctrlKey || e.shiftKey);
                        view.focusRow(rowIndex);
                    }
                }
            }
        }); //sm

        this.queriesGridPanelSM.on('rowselect', this.queriesGridPanelSMRowSelect, this);

        this.queriesGridPanel = new Ext.grid.GridPanel({
            tbar: [
                searchField
            ],
            height: 300,
            autoScroll: true,
            rowNumberer: true,
            border: true,
            stripeRows: true,
            enableHdMenu: false,
            autoExpandColumn: 'name',
            scope: this,
            viewConfig: {
                getRowClass: function(record, index, rowParams /*, store*/ ) {
                    if (record.stack && !record.stack.isMarked()) {
                        rowParams.tstyle += "color: #AA0000;";
                    } else {
                        rowParams.tstyle += "color: #000000;";
                    }
                    return "";
                }
            },
            flex: 80,
            store: this.queriesStore,
            columns: [{
                header: 'Chart Name',
                id: 'name',
                dataIndex: 'name',
                renderer: function (name, metaData/* record, rowIndex, colIndex, store */) {
                    // if the name is (~arbitrarily) long, place it in a tooltip. This length is relative
                    // to the width of the GridPanel.
                    if (name.length > 73) {
                        /* eslint-disable no-param-reassign */
                        metaData.attr += 'ext:qtip="' + Ext.util.Format.htmlEncode(name) + '"';
                        /* eslint-enable no-param-reassign */
                    }
                    return name;
                },
                sortable: true
            }, {
                header: 'Last Modified',
                width: 140,
                dataIndex: 'ts',
                align: 'center',
                renderer: function(ts, metaData, record /*, rowIndex, colIndex, store*/ ) {
                    // if unsaved chart record, display icon and tooltip:
                    if (record.stack && !record.stack.isMarked()) {
                        /* eslint-disable no-param-reassign */
                        metaData.css = 'metric-explorer-dirty-chart-record';
                        metaData.attr += 'ext:qtip="Unsaved Chart"';
                        /* eslint-enable no-param-reassign */
                    }
                    return Ext.util.Format.date(new Date(ts * 1000).toString(), 'Y-m-d H:i:s');
                },
                sortable: true
            }], //columns
            sm: this.queriesGridPanelSM
        }); //this.queriesGridPanel

        // ---------------------------------------------------------

        searchField.store = this.queriesStore; // have to "late bind"

        this.dataCatalogTree = new Ext.tree.TreePanel({
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,
            containerScroll: true,
            margins: '0 0 0 0',
            border: false,
            region: 'center',
            rootVisible: false,
            root: {
                text: 'Data',
                type: 'root',
                nodeType: 'node',
                draggable: false
            },
            listeners: {
                checkchange: {
                    scope: this,
                    /**
                     *
                     * @param   {Ext.tree.TreeNode} node
                     * @param   {boolean}           checked
                     * @returns {boolean}
                     */
                    fn: function(node, checked) {
                        var record_id = node.id;
                        var record_index = this.datasetStore.findBy(function(record) {
                            if (record.get('id') === record_id) {
                                return true;
                            }
                        });
                        if (record_index > -1) {
                            var additionalDatasets = checked ? 1 : 0;
                            var record = this.datasetStore.getAt(record_index);
                            var valid = self.validateChart([record.get('display_type') || ''], additionalDatasets);
                            if (valid) {
                                this.datasetStore.un('update', this.datasetStoreOnUpdate, this);
                                record.set('enabled', checked == true);
                                this.saveQuery(true);
                                this.datasetStore.on('update', this.datasetStoreOnUpdate, this);
                                return true;
                            }
                            // reset the node to it's previous state and cancel
                            // any further event processing for this event
                            // chain
                            var toggleCheck = node.ui.toggleCheck;
                            node.ui.toggleCheck = function(checked) {
                                var cb = this.checkbox;
                                if (!cb) {
                                    return false;
                                }
                                if (checked === undefined) {
                                    checked = this.isChecked() === false;
                                }
                                if (checked === true) {
                                    Ext.fly(cb).replaceClass('x-tree-node-grayed', 'x-tree-node-checked');
                                } else if (checked !== false) {
                                    Ext.fly(cb).replaceClass('x-tree-node-checked', 'x-tree-node-grayed');
                                } else {
                                    Ext.fly(cb).removeClass(['x-tree-node-checked', 'x-tree-node-grayed']);
                                }
                                this.node.attributes.checked = checked;
                                return checked;
                            };
                            node.ui.toggleCheck(!checked);
                            node.ui.toggleCheck = toggleCheck;

                            return false;
                        }
                    }
                }
            }
        }); // this.dataCatalogTree

        // --- dataCatalogTreeOnDataset[Action] functions ---

        this.dataCatalogTreeOnDatasetSelect = function(record) {
            //console.log("this.dataCatalogTreeOnDatasetSelect");
            if (!record) {
                return;
            }
            var catalogRoot = this.dataCatalogTree.getRootNode(),
                nodeToSelect = catalogRoot.findChild('id', record.id, true);
            if (!nodeToSelect) {
                return;
            }
            this.dataCatalogTree.getSelectionModel().select(nodeToSelect);
        }; // dataCatalogTreeOnDatasetSelect()

        // -----------------------------------------------------------------

        this.dataCatalogTreeOnDatasetLoad = function(records) {
            //console.log("this.dataCatalogTreeOnDatasetLoad");
            var catalogRoot = this.dataCatalogTree.getRootNode();

            this.dataCatalogTreeOnDatasetClear.call(this, records, catalogRoot);

            var addFunc = function(record) {
                this.addDatasetToCatalogTree.call(this, record, catalogRoot);
            };

            if (records) {
                Ext.each(records, addFunc, this);
            } else {
                this.datasetStore.each(addFunc, this);
            }

            // JMS: update the filter list:
            this.updateAvailableFilterList();

        }; // dataCatalogTreeOnDatasetLoad()

        // -----------------------------------------------------------------

        this.dataCatalogTreeOnDatasetClear = function(records, catalogRoot) {
            //console.log("this.dataCatalogTreeOnDatasetClear");
            catalogRoot.cascade(function(node) {
                if (node.attributes.type && node.attributes.type == 'metric') {
                    node.removeAll(true);
                    node.collapse();
                }
            }, this);
            this.dataCatalogTree.collapseAll();
        }; //  dataCatalogTreeOnDatasetClear()

        // -----------------------------------------------------------------

        this.dataCatalogTreeOnDatasetUpdate = function(record) {
            //console.log("this.dataCatalogTreeOnDatasetUpdate");
            var catalogRoot = this.dataCatalogTree.getRootNode();

            this.dataCatalogTreeOnDatasetRemove.call(this, record);
            this.addDatasetToCatalogTree.call(this, record, catalogRoot);
        }; // dataCatalogTreeOnDatasetUpdate()

        // -----------------------------------------------------------------

        // Create and return an array of realm names that have metrics
        // plotted in the current plot.
        // JMS Jan 15
        this.getEnabledRealmsList = function(records) {

            var enabledList = [];

            var getEnabled = function(record) {
                var realm = record.get('realm');

                // Create a list of distinct names of enabled realms:
                if (record.get('enabled') === true && enabledList.indexOf(realm) === -1) {
                    enabledList.push(realm);
                }
            };

            // Iterate through the records or datasetStore:
            if (records) {
                Ext.each(records, getEnabled, this);
            } else {
                this.datasetStore.each(getEnabled, this);
            }

            //console.log("this.getEnabledRealmsList, enabledList: " + enabledList);
            return enabledList;
        }; //  getEnabledRealmsList()

        /**
         * Updates the 'Show Raw Data' window's visibility based on the
         * boolean parameter 'show'. true results in the window's 'show' function
         * being called while false results in the window's 'hide' function being
         * called. Both of these functions will only be called if and only if
         * the instance variable 'rawDataShowing' is also true else no action will be
         * taken.
         *
         * @param {Boolean} show
         */
        this.updateRawDataWindowVisibility = function(show) {
            show = show !== undefined ? show : CCR.xdmod.ui.metricExplorer.rawDataShowing;
            if (typeof show === 'boolean' &&
                CCR.xdmod.ui.metricExplorer.rawdataWindow) {
                if (show === true) {
                    CCR.xdmod.ui.metricExplorer.rawdataWindow.show();
                } else {
                    CCR.xdmod.ui.metricExplorer.rawdataWindow.hide();
                }
            }
        };

        // -----------------------------------------------------------------

        // Update the enabled/disabled filters in the filter list
        // based on the realms represented in the current plot
        // JMS Jan 15
        this.updateAvailableFilterList = function() {
            //console.time("update available");

            // get a list of the currently plotted realms:
            var enabledRealms = this.getEnabledRealmsList();

            var hidden_groupbys = [];
            var instance = CCR.xdmod.ui.metricExplorer;

            this.datasetStore.each(function (record) {
              hidden_groupbys[record.get('realm')] = instance.realms[record.get('realm')].metrics[record.get('metric')].hidden_groupbys;
            });

            // for each item in the filtersMenu,
            this.filtersMenu.items.each(function (item) {
                // Get the item's realms array, if it exists.
                var iRealms = item.realms;
                if (iRealms === undefined) {
                    return;
                }

                // If there are no enabled realms, always show the filter.
                if (Ext.isEmpty(enabledRealms)) {
                    item.show();
                    return;
                }

                // If the filterMenu list item realm contains the current record's realm name,
                // enable the filter list item (it's fast):
                var realmInItem = false;
                for (var i = 0; i < enabledRealms.length; i++) {
                    realmInItem = iRealms.indexOf(enabledRealms[i]) > -1;
                    if (realmInItem) {
                        break;
                    }
                }
                item.setVisible(realmInItem);

                for (var iRealm in iRealms) {
                  if (hidden_groupbys[iRealms[iRealm]] !== undefined && hidden_groupbys[iRealms[iRealm]].includes(item.dimension)) {
                    item.setVisible(false);
                  }
                }
            });
            //console.timeEnd("update available");
        };

        // -----------------------------------------------------------------

        this.addDatasetToCatalogTree = function(r, catalogRoot) {

            var realm = r.get('realm'),
                dimension = r.get('group_by'),
                metric = r.get('metric'),
                enabled = r.get('enabled'),
                metricNodeId = realm + '_' + metric,
                // Use the realm to find the category node as the record may
                // predate categories.
                categoryNode = catalogRoot.findChild(
                    'id',
                    XDMoD.Module.MetricExplorer.getCategoryForRealm(realm)
                ),
                datasetNodeText = dimension != 'none' ?
                'by ' + this.realms[realm]['dimensions'][dimension].text :
                'Summary';

            categoryNode.expand(false, false, function() {
                var metricNode = categoryNode.findChild('id', metricNodeId);
                metricNode.expand(false, false, function() {
                    var datasetNode = {
                        type: 'dataset',
                        id: r.get('id'),
                        text: datasetNodeText,
                        checked: enabled !== false,
                        leaf: true,
                        iconCls: 'metric',
                        listeners: {
                            click: {
                                scope: this,
                                fn: function(n) {
                                    XDMoD.Module.MetricExplorer.seriesContextMenu(null, false, n.id, undefined, this);
                                }
                            }
                        }
                    };
                    metricNode.appendChild(datasetNode);
                }, this);
            }, this);
        }; // addDatasetToCatalogTree()

        // -----------------------------------------------------------------

        this.dataCatalogTreeOnDatasetAdd = function(records, index) {
            //console.log('this.dataCatalogTreeOnDatasetAdd');
            var catalogRoot = this.dataCatalogTree.getRootNode();

            Ext.each(records, function(r, i, recs) {
                this.addDatasetToCatalogTree.call(this, r, catalogRoot);
                // JMS: update the filter list to include filters for this dataset:
                //this.updateAvailableFilterList(r);
            }, this);

            // JMS: update the filter list:
            this.updateAvailableFilterList();

        }; // dataCatalogTreeOnDatasetAdd()

        // -----------------------------------------------------------------

        this.dataCatalogTreeOnDatasetRemove = function(record) {
            var recId = record.get('id'),
                catalogRoot = this.dataCatalogTree.getRootNode(),
                datasetNode = catalogRoot.findChild('id', recId, true);

            if (datasetNode) {
                datasetNode.remove(true);
            }
            // JMS: update the filter list:
            this.updateAvailableFilterList();
        }; // dataCatalogTreeOnDatasetRemove()

        // -----------------------------------------------------------------

        // Define Metric Catalog panel
        var leftPanel = new Ext.Panel({
            title: 'Metric Catalog',
            split: true,
            collapsible: true,
            collapsed: true,
            collapseFirst: false,
            pinned: false,
            width: 375,
            layout: 'border',
            region: 'west',
            margins: '2 0 2 2',
            border: true,
            plugins: new Ext.ux.collapsedPanelTitlePlugin('Metric Catalog'),
            items: [this.dataCatalogTree],
            listeners: {
                collapse: function( /*p*/ ) {},
                expand: function(p) {
                    if (p.pinned) {
                        p.getTool('pin').hide()
                        p.getTool('unpin').show();
                    } else {
                        p.getTool('pin').show()
                        p.getTool('unpin').hide();
                    }
                }
            },
            tools: [{
                id: 'pin',
                qtip: 'Prevent auto hiding of Metric Catalog',
                hidden: false,
                handler: function(ev, toolEl, p /*, tc*/ ) {
                    p.pinned = true;
                    if (p.collapsed) {
                        p.expand(false);
                    }
                    p.getTool('pin').hide();
                    p.getTool('unpin').show();
                }
            }, {
                id: 'unpin',
                qtip: 'Allow auto hiding of Metric Catalog',
                hidden: true,
                handler: function(ev, toolEl, p /*, tc*/ ) {
                    p.pinned = false;
                    p.getTool('pin').show();
                    p.getTool('unpin').hide();
                }
            }]
        }); //leftPanel

        // ---------------------------------------------------------

        var chartStore = new CCR.xdmod.CustomJsonStore({
            storeId: 'hchart_store_' + this.id,
            autoDestroy: false,
            root: 'data',
            totalProperty: 'totalCount',
            successProperty: 'success',
            messageProperty: 'message',
            fields: [
                'chart',
                'plotOptions',
                'dimensions',
                'metrics',
                'layout',
                'data',
                'exporting',
                'reportGeneratorMeta'
            ],
            baseParams: {
                operation: 'get_data'
            },
            proxy: new Ext.data.HttpProxy({
                method: 'POST',
                url: 'controllers/metric_explorer.php'
            })
        }); //chartStore

        // ---------------------------------------------------------

        function initBaseParams() {
            chartStore.baseParams = {};
            Ext.apply(chartStore.baseParams, getBaseParams.call(this));

            chartStore.baseParams.start = this.show_remainder ? 0 : this.chartPagingToolbar.cursor;
            chartStore.baseParams.limit = this.chartPagingToolbar.pageSize;

            this.maximizeScale.call(this);
            var dataSeries = this.getDataSeries();
            chartStore.dataSeriesLength = dataSeries.length;

            chartStore.baseParams.timeframe_label = this.getDurationSelector().getDurationLabel();
            chartStore.baseParams.operation = 'get_data';
            chartStore.baseParams.data_series = encodeURIComponent(Ext.util.JSON.encode(dataSeries));
            chartStore.baseParams.swap_xy = this.swap_xy;
            chartStore.baseParams.share_y_axis = this.share_y_axis;
            chartStore.baseParams.hide_tooltip = this.hide_tooltip;
            chartStore.baseParams.show_guide_lines = 'y';
            chartStore.baseParams.show_title = 'y';
            chartStore.baseParams.showContextMenu = 'y';
            chartStore.baseParams.scale = 1;
            chartStore.baseParams.format = 'hc_jsonstore';
            chartStore.baseParams.width = chartWidth * chartScale;
            chartStore.baseParams.height = chartHeight * chartScale;
            chartStore.baseParams.legend_type = this.legendTypeComboBox.getValue();
            chartStore.baseParams.font_size = this.fontSizeSlider.getValue();
            chartStore.baseParams.featured = this.featured;
            chartStore.baseParams.trendLineEnabled = this.trendLineEnabled;
            chartStore.baseParams.x_axis = encodeURIComponent(Ext.util.JSON.encode(this.xAxis));
            chartStore.baseParams.y_axis = encodeURIComponent(Ext.util.JSON.encode(this.yAxis));
            chartStore.baseParams.legend = encodeURIComponent(Ext.util.JSON.encode(this.legend));
            chartStore.baseParams.defaultDatasetConfig = encodeURIComponent(Ext.util.JSON.encode(this.legend));

            chartStore.baseParams.controller_module = self.getReportCheckbox().getModule();
        }

        chartStore.on('beforeload', function() {
            if (!this.getDurationSelector().validate()) {
                return;
            }

            this.mask('Loading...');
            plotlyPanel.un('resize', onResize, this);

            initBaseParams.call(this);

        }, this); //chartStore.on('beforeload', ...

        // ---------------------------------------------------------

        chartStore.on('load', function(chartStore) {
            this.firstChange = true;

            if (chartStore.getCount() != 1) {
                this.unmask();
                return;
            }

            var noData = chartStore.dataSeriesLength === 0;

            this.chartViewPanel.getLayout().setActiveItem(noData ? 1 : 0);
            if (noData) {
                leftPanel.expand();
            } else if (!leftPanel.pinned) {
                leftPanel.collapse();
            }

            self.getExportMenu().setDisabled(noData);

            self.getPrintButton().setDisabled(noData);

            self.getReportCheckbox().setDisabled(noData);

            self.getChartLinkButton().setDisabled(noData);

            var reportGeneratorMeta = chartStore.getAt(0).get('reportGeneratorMeta');

            self.getReportCheckbox().storeChartArguments(reportGeneratorMeta.chart_args,
                reportGeneratorMeta.title,
                reportGeneratorMeta.params_title,
                reportGeneratorMeta.start_date,
                reportGeneratorMeta.end_date,
                reportGeneratorMeta.included_in_report);

            var has_title = reportGeneratorMeta.title && reportGeneratorMeta.title.length > 0;
            self.setExportDefaults(has_title);

            plotlyPanel.on('resize', onResize, this); //re-register this after loading/its unregistered beforeload

            this.chartPagingToolbar.setPagingEnabled(!this.show_remainder);

            var pagingData = this.chartPagingToolbar.getPageData();

            if (pagingData.activePage > pagingData.pages) {
                this.chartPagingToolbar.changePage(1);
            }

            if (noData) {
                updateDescription(null);
            } else {
                updateDescription(chartStore);
            }

            this.unmask();

        }, this); //chartStore.on('load', ...

        // ---------------------------------------------------------

        chartStore.on('exception', function(thisProxy, type, action, options, response, arg) {

            if (type === 'response') {

                var data = CCR.safelyDecodeJSONResponse(response) || {};

                var errorCode = data.code;

                if (errorCode === XDMoD.Error.QueryUnavailableTimeAggregationUnit) {
                    this.unmask();

                    var durationToolbar = self.getDurationSelector();
                    durationToolbar.setAggregationUnit('Auto');
                    durationToolbar.onHandle();

                    var errorMessage = 'The selected aggregation unit is not available for all enabled metrics.<br />The aggregation unit has been reset to auto.';

                    var errorData = data.errorData;
                    if (errorData) {
                        var errorMessageExtraData = '';
                        if (errorData.realm) {
                            errorMessageExtraData += '<br />Realm: ' + Ext.util.Format.htmlEncode(errorData.realm);
                        }
                        if (errorData.unit) {
                            errorMessageExtraData += '<br />Unavailable Unit: ' + Ext.util.Format.capitalize(Ext.util.Format.htmlEncode(errorData.unit));
                        }

                        if (errorMessageExtraData) {
                            errorMessage += '<br />' + errorMessageExtraData;
                        }
                    }

                    Ext.MessageBox.alert(
                        'Metric Explorer',
                        errorMessage
                    );

                    return;
                }
            }

            var responseMessage = CCR.xdmod.ui.extractErrorMessageFromResponse(response);
            if (responseMessage === null) {
                responseMessage = 'Unknown Error';
            }

            plotlyPanel.displayError(
                'An error occurred while loading the chart.',
                responseMessage
            );
            this.chartViewPanel.getLayout().setActiveItem(this.plotlyPanel.getId());

            this.unmask();

        }, this); //chartStore.on('exception', ...

        // ---------------------------------------------------------

        this.reloadChartFunc = function() {
            chartStore.load({
                callback: function() {
                    this.queriesGridPanel.view.refresh();
                },
                scope: this
            });
        };

        var reloadChartTask = new Ext.util.DelayedTask(this.reloadChartFunc, this);

        this.reloadChart = function(delay) {
            reloadChartTask.delay(delay || XDMoD.Module.MetricExplorer.delays.medium);
        };

        this.saveQuery = function(commitChanges) {
            if (this.disableSave || this.disableSave === true) {
                return;
            }
            this.saveQueryFunc(commitChanges);
        };

        if (!this.chartDefaultPageSize) {
            this.chartDefaultPageSize = 10;
        }

        // ---------------------------------------------------------

        this.chartPageSizeField = new Ext.form.NumberField({
            id: 'chart_size_field_' + this.id,
            fieldLabel: 'Chart Size',
            name: 'chart_size',
            minValue: 1,
            maxValue: 50,
            allowDecimals: false,
            decimalPrecision: 0,
            incrementValue: 1,
            alternateIncrementValue: 2,
            accelerate: true,
            width: 45,
            value: this.chartDefaultPageSize,
            listeners: {
                scope: this,
                'change': function(t, newValue, oldValue) {
                    if (t.isValid(false) && newValue != t.ownerCt.pageSize) {
                        XDMoD.TrackEvent('Metric Explorer', 'Changed page limit', newValue);

                        t.ownerCt.pageSize = newValue;
                        this.saveQuery();
                        t.ownerCt.doRefresh();
                    }
                }, //change
                'specialkey': function(t, e) {
                        if (t.isValid(false) && e.getKey() == e.ENTER) {
                            XDMoD.TrackEvent('Metric Explorer', 'Changed page limit', t.getValue());

                            t.ownerCt.pageSize = t.getValue();

                            this.saveQuery();

                            t.ownerCt.doRefresh();
                        }
                    } //specialkey
            } //listeners
        }); //this.chartPageSizeField

        // ---------------------------------------------------------
        var viewer = CCR.xdmod.ui.Viewer.getViewer(),
            datasetsMenuDefaultWidth = 1150,
            viewerWidth = viewer.getWidth();

        this.queriesMenu = new Ext.menu.Menu({
            showSeparator: false,
            items: this.queriesGridPanel,
            width: 500,
            renderTo: document.body //pre renders panel that doesnt show on first load.
        });
        this.queriesButton = new Ext.Button({
            text: 'Load Chart',
            iconCls: 'loadsave',
            tooltip: 'Load a previously created chart',
            menu: this.queriesMenu
        });

        this.chartOptionsMenu = new Ext.menu.Menu({
            showSeparator: false,
            items: {
                xtype: 'fieldset',
                autoHeight: true,
                layout: 'form',
                hideLabels: false,
                border: false,
                defaults: {
                    anchor: '0' // '-20' // leave room for error icon
                },
                items: [
                    this.datasetTypeRadioGroup,
                    this.chartNameTextbox,
                    this.chartTitleField,
                    this.chartShowSubtitleField,
                    this.showWarningsCheckbox,
                    this.legendTypeComboBox,
                    this.defaultMetricDisplayTypeField,
                    this.fontSizeSlider,
                    this.chartSwapXYField,
                    this.shareYAxisField,
                    this.hideTooltipField,
                    this.featuredCheckbox,
                    this.trendLineCheckbox
                ]

            },
            width: 370,
            renderTo: document.body
        });

        if (this.chartOptionsMenu.keyNav) {
            /**
             * Override the default 'doRelay' event handler for this particular
             * menus' keynav. This ensures that the navigational keypresses:
             * left, right, up and down work as expected.
             *
             * @param  {Ext.EventObject} e the event to be relayed.
             * @param  {function} h a handler function will be used in the case
             *                      that the event is passed on.
             * @return the result of calling the handler function w/ the
             *                    provided event and menu.
             */
            this.chartOptionsMenu.keyNav.doRelay = function(e, h) {
                var k = e.getKey();

                if (this.menu.activeItem && this.menu.activeItem.isFormField && k != e.TAB) {
                    return false;
                }

                if (!this.menu.activeItem && e.isNavKeyPress()) {
                    this.menu.tryActivate(0, 1);
                    return true;
                }

                // Ensure that we only call the KeyNav handlers if this is a NavKey Press.
                if (e.isNavKeyPress()) {
                    return h.call(this.scope || this, e, this.menu);
                }

                return true;
            };

            /**
             * Override the default 'down' event handler for this particular
             * menus' keynav. This ensures that
             *
             * @param  {Ext.EventObject} e the 'down' event that is being
             *                             handled
             * @param  {Ext.Menu} m the menu from which the event was generated
             */
            this.chartOptionsMenu.keyNav.down = function(e /*, m*/ ) {

                var key = e.getKey();
                var target = e.target;

                if (key >= 32 && key <= 126) {
                    target.value += String.fromCharCode(key);
                }
            };
        }

        this.chartStatusButton = new XDMoD.Module.MetricExplorer.StatusButton({
            text: 'Save',
            disabled: true
        });

        this.relayEvents(this.chartStatusButton, ['save_changes', 'discard_changes']);

        this.undoButton = new Ext.Button({
            text: 'Undo',
            id: 'metric_explorer_undo',
            iconCls: 'x-btn-text-icon',
            icon: '../gui/images/arrow_undo.png',
            tooltip: 'Undo the previous action',
            disabled: true,
            handler: function() {
                self.currentQueryRecord.stack.undo();
            }
        });

        this.redoButton = new Ext.Button({
            text: 'Redo',
            id: 'metric_explorer_redo',
            iconCls: 'x-btn-text-icon',
            icon: '../gui/images/arrow_redo.png',
            tooltip: 'Revert the most recent undo',
            disabled: true,
            handler: function() {
                self.currentQueryRecord.stack.redo();
            }
        });
        this.chartOptionsButton = new Ext.Button({
            iconCls: 'chartoptions',
            menu: this.chartOptionsMenu
        });
        this.chartDataMenu = new Ext.menu.Menu({
            showSeparator: false,
            items: this.datasetsGridPanel,
            width: viewerWidth < datasetsMenuDefaultWidth ? viewerWidth : datasetsMenuDefaultWidth,
            renderTo: document.body
        });
        this.chartDataButton = new Ext.Button({
            text: 'Data',
            iconCls: 'metric',
            tooltip: 'Lists the metrics that are being displayed for the currently selected chart',
            menu: this.chartDataMenu
        });
        this.chartFiltersMenu = new Ext.menu.Menu({
            showSeparator: false,
            items: this.filtersGridPanel,
            width: viewerWidth < datasetsMenuDefaultWidth ? viewerWidth : datasetsMenuDefaultWidth,
            renderTo: document.body
        });
        this.chartFiltersButton = new Ext.Button({
            text: 'Filters',
            iconCls: 'filter',
            tooltip: 'Display the list of global filters that have been applied to the currently selected chart',
            menu: this.chartFiltersMenu
        });
        viewer.on('resize', function(t) {
            var w = t.getWidth();
            if (w > datasetsMenuDefaultWidth) {
                w = datasetsMenuDefaultWidth;
            }
            var wi = w - 8;
            this.chartDataMenu.setWidth(w);
            this.datasetsGridPanel.setWidth(wi);
            this.chartFiltersMenu.setWidth(w);
            this.filtersGridPanel.setWidth(wi);
        }, this);

        this.chartPagingToolbar = new CCR.xdmod.ui.CustomPagingToolbar({
            pageSize: this.chartDefaultPageSize,
            store: chartStore,
            beforePageText: 'Page',
            displayInfo: true,
            displayMsg: 'Data Series {0} - {1} of {2}',
            scope: this,
            showRefresh: false,
            preItems: ['->'],
            items: [
                '-',
                this.showRemainderCheckbox,
                '-',
                'Page Limit',
                this.chartPageSizeField,
                'Data Series'
            ],

            updateInfo: function() {

                    if (this.displayItem) {
                        var count = this.store.getCount();
                        var msg;
                        if (count > 0) {
                            msg = String.format(
                                this.displayMsg,
                                this.cursor + 1, Math.min(this.store.getTotalCount(), this.cursor + this.pageSize), this.store.getTotalCount()
                            );
                        }
                        this.displayItem.setText(msg);
                    } //if(this.displayItem)
                } //updateInfo
        }); //this.chartPagingToolbar

        // ---------------------------------------------------------

        this.firstChange = true;

        this.chartPagingToolbar.on('afterlayout', function() {
            this.chartPagingToolbar.on('change', function(total, pageObj) {
                XDMoD.TrackEvent('Metric Explorer', 'Loaded page of data', pageObj.activePage + ' of ' + pageObj.pages);

                if (this.firstChange) {
                    this.firstChange = false;
                }
                return true;
            }, this);
        }, this, {
            single: true
        });

        // ---------------------------------------------------------

        var assistPanel = new CCR.xdmod.ui.AssistPanel({
            region: 'center',
            border: false,
            headerText: 'No data is available for viewing',
            subHeaderText: 'Please refer to the instructions below:',
            graphic: 'gui/images/metric_explorer_instructions.png',
            userManualRef: 'metric+explorer',
            listeners: {
                afterrender: {
                    scope: self,
                    fn: function(comp) {
                        var element = comp.getEl();
                        element.on('click', function() {
                            XDMoD.Module.MetricExplorer.chartContextMenu(null, true, self);
                        });
                    }
                } // afterrender
            } // listeners
        }); //assistPanel

        // ---------------------------------------------------------
        plotlyPanel = new CCR.xdmod.ui.PlotlyPanel({
            id: 'plotly-panel' + this.id,
            baseChartOptions: {
                metricExplorer: true
            },
            store: chartStore
        }); //assistPanel

        this.plotlyPanel = plotlyPanel;
        // ---------------------------------------------------------

        var quickFilterButton = XDMoD.DataWarehouse.createQuickFilterButton({
            autoAddMostPrivilegedRoleFilters: false
        });
        this.quickFilterButton = quickFilterButton;

        var relayQuickFilterUpdateToGlobalFilters = function(quickFilterStore, quickFilterRecord) {
            var dimensionId = quickFilterRecord.get('dimensionId');
            var valueId = quickFilterRecord.get('valueId');
            var filterId = dimensionId + '=' + valueId;
            var existingFilterIndex = this.filtersStore.find(
                'id',
                filterId
            );

            var nowChecked = quickFilterRecord.get('checked');
            if (existingFilterIndex >= 0) {
                var existingFilterRecord = this.filtersStore.getAt(existingFilterIndex);
                if (nowChecked === existingFilterRecord.get('checked')) {
                    return;
                }

                existingFilterRecord.set('checked', nowChecked);
            } else {
                if (!nowChecked) {
                    return;
                }

                this.filtersStore.addSorted(new this.filtersStore.recordType({
                    id: filterId,
                    value_id: valueId,
                    value_name: quickFilterRecord.get('valueName'),
                    dimension_id: dimensionId,
                    checked: true
                }));
            }
        };
        quickFilterButton.quickFilterStore.on('update', relayQuickFilterUpdateToGlobalFilters, this);

        var updateQuickFilters = function() {
            quickFilterButton.quickFilterStore.un('update', relayQuickFilterUpdateToGlobalFilters, this);
            quickFilterButton.quickFilterStore.each(function(quickFilterRecord) {
                var dimensionId = quickFilterRecord.get('dimensionId');
                var valueId = quickFilterRecord.get('valueId');
                var filterId = dimensionId + '=' + valueId;
                var existingFilterIndex = this.filtersStore.find(
                    'id',
                    filterId
                );

                if (existingFilterIndex < 0) {
                    quickFilterRecord.set('checked', false);
                    return;
                }

                var existingFilter = this.filtersStore.getAt(existingFilterIndex);
                quickFilterRecord.set('checked', existingFilter.get('checked'));
            }, this);
            quickFilterButton.quickFilterStore.on('update', relayQuickFilterUpdateToGlobalFilters, this);
        };
        this.filtersStore.on('add', updateQuickFilters, this);
        this.filtersStore.on('load', updateQuickFilters, this);
        this.filtersStore.on('remove', updateQuickFilters, this);
        this.filtersStore.on('update', updateQuickFilters, this);
        quickFilterButton.quickFilterStore.on('add', updateQuickFilters, this);

        // ---------------------------------------------------------

        this.chartViewPanel = new Ext.Panel({

            frame: false,
            layout: 'card',
            activeItem: 0,
            tbar: new Ext.Toolbar({
                enableOverflow: true,
                items: [
                    this.queriesButton,
                    '-',
                    this.createQuery,
                    this.saveAsQuery,
                    this.chartStatusButton,
                    this.undoButton,
                    this.redoButton,
                    '-',
                    this.deleteQuery,
                    '-',
                    this.addDatasetButton,
                    this.chartDataButton,
                    '-',
                    quickFilterButton,
                    this.addFilterButton,
                    this.chartFiltersButton,
                    '-',
                    '->',
                    '-',
                    this.chartOptionsButton
                ]
            }),
            bbar: this.chartPagingToolbar,
            region: 'center',
            border: true,
            items: [
                plotlyPanel,
                assistPanel
            ]
        }); //chartViewPanel

        // ---------------------------------------------------------

        var descriptionPanel = new Ext.Panel({
            region: 'south',
            autoScroll: true,
            collapsible: true,
            split: true,
            border: true,
            title: 'Description',
            height: 120,
            plugins: [new Ext.ux.collapsedPanelTitlePlugin()]
        });

        var commentsTemplate = new Ext.XTemplate(
            '<table class="xd-table">',
            '<tr>',
            '<td width="100%">',
            '<span class="comments_subnotes">{subnotes}</span>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="comments_description">{comments}</span>',
            '</td>',
            '</tr>',
            '</table>'
        ); //commentsTemplate

        function updateDescription(chartStore) {

            var dimsDesc = '<ul>';
            var metricsDesc = '<ul>';

            if (chartStore !== null) {
                for (var i = 0; i < chartStore.getCount(); i++) {
                    var md = chartStore.getAt(i).get('dimensions');
                    for (var d in md) {
                        if (md.hasOwnProperty(d)) {
                            dimsDesc += "<li><b>" + d + ":</b> " + md[d] + "</li>\n";
                        }
                    }
                }

                for (var k = 0; k < chartStore.getCount(); k++) {
                    var md2 = chartStore.getAt(k).get('metrics');
                    for (var e in md2) {
                        if (md2.hasOwnProperty(e)) {
                            metricsDesc += "<li><b>" + e + ":</b> " + md2[e] + "</li>\n";
                        }
                    }
                }
            }

            dimsDesc += "</ul>\n";
            metricsDesc += '</ul>';

            commentsTemplate.overwrite(descriptionPanel.body, {
                'comments': dimsDesc + metricsDesc,
                'subnotes': ""
            });

        } //updateDescription

        // ---------------------------------------------------------

        var viewGrid = new Ext.ux.DynamicGridPanel({

            id: 'view_grid_' + this.id,
            storeUrl: 'controllers/metric_explorer.php',
            autoScroll: true,
            rowNumberer: true,
            region: 'center',
            remoteSort: true,
            showHdMenu: false,
            border: false,
            usePaging: true,
            lockingView: false

        }); //viewGrid

        // ---------------------------------------------------------

        var reloadViewGridFunc = function() {

            viewGrid.store.baseParams = {};
            Ext.apply(viewGrid.store.baseParams, getBaseParams.call(this));

            viewGrid.store.baseParams.operation = 'get_data';
            viewGrid.store.baseParams.offset = viewGrid.bottomToolbar.cursor;
            viewGrid.store.baseParams.limit = viewGrid.bottomToolbar.pageSize;
            viewGrid.store.baseParams.format = 'jsonstore';
            viewGrid.store.load();

        }; //reloadViewGridFunc

        // ---------------------------------------------------------

        var reloadViewTask = new Ext.util.DelayedTask(reloadViewGridFunc, this);

        this.reloadViewGrid = function(delay) {

            reloadViewTask.delay(delay || 2300);

        };

        // ---------------------------------------------------------

        var view = new Ext.Panel({

            region: 'center',
            layout: 'border',
            margins: '2 2 2 0',
            border: false,
            items: [this.chartViewPanel, descriptionPanel]

        }); //view

        // ---------------------------------------------------------

        self.on('print_clicked', function() {

            initBaseParams.call(self);
            var parameters = chartStore.baseParams;

            parameters['operation'] = 'get_data';
            parameters['scale'] = 1; //CCR.xdmod.ui.hd1280Scale;
            parameters['format'] = 'png';
            parameters['start'] = this.chartPagingToolbar.cursor;
            parameters['limit'] = this.chartPagingToolbar.pageSize;
            parameters['width'] = 757 * 2;
            parameters['height'] = 400 * 2;
            parameters['show_title'] = 'y';

            var params = '';

            for (var i in parameters) {
                if (parameters.hasOwnProperty(i)) {
                    params += i + '=' + parameters[i] + '&';
                }
            }

            params = params.substring(0, params.length - 1);

            Ext.ux.Printer.print({

                getXTypes: function() {
                    return 'html';
                },
                html: '<img src="/controllers/metric_explorer.php?' + params + '" />'

            });

        }); //self.on('print_clicked', ...

        // ---------------------------------------------------------

        self.on('export_option_selected', function(opts) {

            initBaseParams.call(self);
            var parameters = chartStore.baseParams;

            Ext.apply(parameters, opts);

            CCR.invokePost("controllers/metric_explorer.php", parameters);

        }); //self.on('export_option_selected', ...

        // ---------------------------------------------------------

        self.on('chart_link_clicked', function () {
            const encodedData = window.btoa(JSON.stringify(this.getConfig()));
            const link = `${window.location.protocol}//${window.location.host}/#main_tab_panel:metric_explorer?config=${encodedData}`;
            const msg = `Use the following link to share the current chart. Note that the link does not override the access controls. So if you send the link to someone who does not have access to the data, they will still not be able to see the data. <br><b>${link}</b>`;
            Ext.Msg.show({
                title: 'Link to Chart',
                minWidth: 700,
                msg: msg,
                buttons: { ok: 'Copy', cancel: 'Cancel' },
                icon: Ext.MessageBox.INFO,
                fn: (buttonId) => {
                    if (buttonId === 'ok') {
                        navigator.clipboard.writeText(link);
                    }
                }
            });
        }); // self.on('chart_link_clicked', ...

        // ---------------------------------------------------------

        this.loadAll = function() {
            this.queries_store_loaded_handler = function() {
                this.createQueryFunc.call(this, null, null, null, null, null, null, false);
                this.reloadChart.call(this);
                this.maximizeScale.call(this);
            };

            this.dwdesc_loaded_handler = function() {
                this.queriesStore.on('load', this.queries_store_loaded_handler, this);
                this.queriesStore.load();
            };

            this.on('dwdesc_loaded', this.dwdesc_loaded_handler, this);

            this.dwDescriptionStore.load();
        }; //loadAll

        this.on('afterrender', this.loadAll, this, {
            single: true
        });

        // ---------------------------------------------------------

        this.maximizeScale = function() {

            chartWidth = this.chartViewPanel ? this.chartViewPanel.getWidth() : chartWidth;
            chartHeight = this.chartViewPanel ? this.chartViewPanel.getHeight() - (this.chartViewPanel.tbar ? this.chartViewPanel.tbar.getHeight() : 0) : chartHeight;

        }; //maximizeScale

        // ---------------------------------------------------------

        function onResize(t, adjWidth, adjHeight, rawWidth, rawHeight) {
            this.maximizeScale.call(this);
            const chartDiv = document.getElementById(`plotly-panel${this.id}`);
            if (chartDiv) {
                Plotly.relayout(`plotly-panel${this.id}`, { width: adjWidth, height: adjHeight });
                const update = relayoutChart(chartDiv, adjHeight, false);
                Plotly.relayout(`plotly-panel${this.id}`, update);
            }
        } //onResize

        // ---------------------------------------------------------

        Ext.apply(this, {
            items: [leftPanel, view]
        }); //Ext.apply

        XDMoD.Module.MetricExplorer.superclass.initComponent.apply(this, arguments);

        this.addEvents("dwdesc_loaded");
    }, //initComponent

    listeners: {
        activate: function( /*panel*/ ) {
            this.updateRawDataWindowVisibility();
            if (location.hash.split('config=')[1]) {
                var config = JSON.parse(window.atob(location.hash.split('config=')[1]));
                XDMoD.Module.MetricExplorer.setConfig(config, config.title, true);
            }
        }, // activate

        deactivate: function( /*panel*/ ) {
            this.updateRawDataWindowVisibility(false);
        }, // deactivate

        save_changes: function() {
            this.saveQueryFunc(true);
        }, // save_changes

        discard_changes: function() {
            this.currentQueryRecord.stack.revertToMarked();
        }, // discard_changes

        disable_commit: function() {
            this.currentQueryRecord.stack.disableAutocommit();
        },

        enable_commit: function(keepChanges) {
            if (keepChanges) {
                this.currentQueryRecord.stack.commit();
            }
            this.currentQueryRecord.stack.enableAutocommit();
        },

        enable_pie: function() {
            this.addDatasetButton.enable();
            this.dataCatalogTree.enable();
            this.showRemainderCheckbox.enable();
        }, // enable_pie

        disable_pie: function() {
            this.addDatasetButton.disable();
            this.dataCatalogTree.disable();
            this.showRemainderCheckbox.disable();
        }, // disable_pie

        invalid_chart_display_type: function(msg) {
            CCR.error('Invalid Chart Display Type', msg);
        }

    }, // listeners

    _simpleSilentSetValue: function(component, value) {
        this._noEvents(component, function(component, value) {
            component.setValue(value);
        }, value);
    },

    /**
     * @function handleChartModification
     *
     * Updates the metric explorer state due to a change in the chart.
     *
     * @param {XDMoD.ChangeStack} changeStack the changestack object for the current chart
     * @param {Object} chartData  the current chart data
     * @param {string} action     the reason for the chart change
     *
     */
    handleChartModification: function(changeStack, chartData, action) {

        switch (action) {
            case 'undo':
            case 'redo':
            case 'reverttomarked':
                this.currentQueryRecord.beginEdit();
                for (var value in chartData) {
                    if (chartData.hasOwnProperty(value)) {
                        this.currentQueryRecord.set(value, chartData[value]);
                    }
                }
                this.currentQueryRecord.endEdit();
                this.chartNameTextbox.setValue(Ext.util.Format.htmlDecode(chartData.name));
                this.chartOptionsButton.setText(truncateText(chartData.name, XDMoD.Module.MetricExplorer.CHART_OPTIONS_MAX_TEXT_LENGTH));
                this.chartOptionsButton.setTooltip(chartData.name);
                this.loadQuery(JSON.parse(chartData.config), true);
                break;
            case 'add':
                this.reloadChart();
                break;
            default:
                // do nothing
        }

        this.undoButton.setDisabled(!changeStack.canUndo());
        this.redoButton.setDisabled(!changeStack.canRedo());

        this.chartStatusButton.setDisabled(changeStack.empty() || changeStack.isMarked());
        this.chartStatusButton.setButtonState(!changeStack.isMarked(), changeStack.canRevert());

        this.saveAsQuery.setDisabled(changeStack.empty());
    },

    /**
     *
     * @param {Ext.Component[]|Ext.Component} components
     * @param {Function} fn
     * @private
     */
    _noEvents: function(components, fn) {
        var args;
        if (CCR.isType(components, CCR.Types.Array)) {
            this._map(components, 'suspendEvents');

            args = this._filterObject(arguments, function(value, index) {
                return index > 1;
            });
            args = args.concat(components);

            fn.apply(this, args);

            this._map(components, 'resumeEvents');

        } else if (CCR.isType(components, CCR.Types.Object)) {
            components.suspendEvents();
            args = this._filterObject(arguments, function(value, index) {
                return index > 1;
            });
            args = [components].concat(args);
            fn.apply(this, args);
            components.resumeEvents();
        }
    },

    /**
     *
     * @param {Object} object
     * @param {Function[]|Function} filters
     * @param {Boolean} [as_array=true]
     * @return {Object|Array}
     * @private
     */
    _filterObject: function(object, filters, as_array) {
        as_array = as_array || true;

        var result = as_array ? [] : {};
        var value;
        if (object && object.length) {
            for (var i = 0; i < object.length; i++) {
                value = this._applyFilters(filters, object[i], i);
                if (value !== null && as_array) {
                    result.push(object[i]);
                } else if (value !== null && !as_array) {
                    result[i] = object[i];
                }
            }
        } else {
            for (var key in object) {
                if (object.hasOwnProperty(key)) {
                    value = this._applyFilters(filters, object[key], key);
                    if (value !== null && as_array) {
                        result.push(object[key]);
                    } else if (value !== null && !as_array) {
                        result[key] = object[key];
                    }
                }
            }
        }
        return result;
    },

    /**
     *
     * @param {Function[]|Function} filters
     * @param {String|Number} value
     * @param {Number} index
     * @return {String|Number|null}
     * @private
     */
    _applyFilters: function(filters, value, index) {
        var result = value;
        var passes;
        if (CCR.isType(filters, CCR.Types.Array)) {
            for (var i = 0; i < filters.length; i++) {
                var filter = filters[i];
                if (CCR.isType(filter, CCR.Types.Function)) {
                    passes = filter(value, index);
                    if (!passes) {
                        return null;
                    }
                }
            }
        } else if (CCR.isType(filters, CCR.Types.Function)) {
            passes = filters(value, index);
            if (!passes) {
                return null;
            }
        }
        return result;
    },

    /**
     *
     * @param {Object[]} objects
     * @param {String} fn_name
     * @private
     */
    _map: function(objects, fn_name) {
        if (CCR.isType(objects, CCR.Types.Array)) {
            for (var i = 0; i < objects.length; i++) {
                var item = objects[i];
                if (item && item[fn_name]) {
                    item[fn_name]();
                }
            }
        }
    },

    /**
     *
     * @param {boolean} [enabled=false]
     * @param {Integer[]} [ignore_indexes=[]]
     * @param {Integer[]} [include_indexes=[]]
     * @returns {Array}
     * @private
     */
    _getDisplayTypes: function(enabled, ignore_indexes, include_indexes) {
        var self = this;
        enabled = enabled || false;
        ignore_indexes = ignore_indexes || [];
        include_indexes = include_indexes || [];

        var data = [];
        if (!enabled) {
            data = this.datasetStore.getRange(0, this.datasetStore.getTotalCount());
        } else {
            this.datasetStore.each(function(record) {
                var index = self.datasetStore.find('id', record.id);
                var enabled = record.get('enabled');
                var ignoreIndex = ignore_indexes.indexOf(index) >= 0;
                var includeIndex = include_indexes.indexOf(index) >= 0;
                if (enabled === true && !ignoreIndex || includeIndex) {
                    data.push(record);
                }
            });
        }

        var results = [];
        for (var i = 0; i < data.length; i++) {
            var item = data[i];
            var displayType = item.get('display_type');
            if (results.indexOf(displayType) < 0) {
                results.push(displayType);
            }
        }
        return results;
    },

    /**
     *
     * @param {String[]} [displayTypes=[]]
     * @param {int} [additionalDataSets=0]
     * @param {boolean} [isAggregate=this.isAggregate()]
     * @returns {boolean}
     */
    validateChart: function(displayTypes, additionalDataSets, isAggregate) {
        displayTypes = displayTypes || [];
        additionalDataSets = additionalDataSets || 0;

        isAggregate = CCR.exists(isAggregate) ? isAggregate : this.isAggregate();
        var datasetsEnabled = this.datasetsEnabled() + additionalDataSets;
        var isPie = displayTypes.indexOf('pie') >= 0;
        var currentDefaultMetricDisplayType = this.defaultMetricDisplayType;

        var reasons = [];
        if (isPie && !isAggregate) {

            reasons.push(
                'You cannot display timeseries data in a pie chart.' +
                '<br/> Please change the dataset or display type.<br/>'
            );
        }

        if (isPie && datasetsEnabled > 1) {

            reasons.push(
                'Cannot display more than one dataset in pie format <br/>' +
                'Please disable datasets such that there is only one enabled.<br/>'
            );
        }

        if (currentDefaultMetricDisplayType === 'pie' && isPie && datasetsEnabled > 1) {
            reasons.push(
                'Cannot add a metric of display type "pie" to a chart with ',
                'other display types currently enabled.',
                'Please change the "Default Metric Display Type" ',
                'before attempting to add another metric.<br/>'
            );
        }

        if (reasons.length > 0) {
            Ext.menu.MenuMgr.hideAll();
            var msg = reasons.join('<br/>');
            this.fireEvent('squish_spider');
            this.fireEvent('invalid_chart_display_type', msg);

            return false;
        }
        return true;
    },

    validateChartType: function(config) {

        var datasetsEnabled = this.datasetsEnabled();

        var displayTypes = this.getDisplayTypes(datasetsEnabled, config);

        var isPie = this.isPie(displayTypes, datasetsEnabled, config);

        var valid = this.validateChart(displayTypes);

        if (valid) {
            if (isPie === true && datasetsEnabled > 0) {
                this.fireEvent('disable_pie');
            } else {
                this.fireEvent('enable_pie');
            }

            this.syncTrendLineComponents();
        }

        return valid;
    },

    getDisplayTypes: function(datasetsEnabled, config) {
        var record = this.currentQueryRecord || {};
        record.data = record.data || {};
        record.data.config = record.data.config || {};

        datasetsEnabled = datasetsEnabled || this.datasetsEnabled();
        config = config || record.data.config;
        config.defaultDatasetConfig = config.defaultDatasetConfig || {};

        var displayTypes = [];
        if (datasetsEnabled > 0) {
            displayTypes = this._getDisplayTypes(true);
        } else if (CCR.exists(config.defaultDatasetConfig) && CCR.exists(config.defaultDatasetConfig.display_type)) {
            displayTypes = [config.defaultDatasetConfig.display_type];
        }
        return displayTypes;
    },

    isPie: function(displayTypes, datasetsEnabled, config) {
        displayTypes = displayTypes || this.getDisplayTypes(datasetsEnabled, config);

        return displayTypes.indexOf('pie') >= 0;
    },

    isAggregate: function() {
        var value = CCR.xdmod.ui.metricExplorer && CCR.xdmod.ui.metricExplorer.timeseries || false;
        return !value;
    },

    /**
     * Check if trend lines are available for display.
     *
     * @return {Boolean} True if trend lines can be displayed. Otherwise, false.
     */
    isTrendLineAvailable: function() {
        return !this.isAggregate();
    },

    /**
     * Set trend line components to the appropriate configuration.
     */
    syncTrendLineComponents: function() {
        var trendLineAvailable = this.isTrendLineAvailable();

        this.trendLineCheckbox.setDisabled(!trendLineAvailable);
    },

    datasetsEnabled: function() {
        var count = 0;
        var metricExplorer = CCR.xdmod.ui.metricExplorer;
        var data = metricExplorer && metricExplorer.datasetStore ? metricExplorer.datasetStore : null;
        if (data !== null) {
            for (var i = 0; i < data.getCount(); i++) {
                if (data.getAt(i).get('enabled')) {
                    count++;
                }
            }
        }
        return count;
    },

    getCurrentRecord: function() {
        var sm = this.queriesGridPanel.getSelectionModel();

        var selected = this.currentQueryRecord;

        if (!selected) {
            selected = sm.getSelected();
        }
        if (!selected) {
            selected = this.queriesStore.getAt(this.selectedIndex);
        }

        return selected;
    },

    selectRowByIndex: function(index) {
        var sm = this.queriesGridPanel.getSelectionModel();
        this.queriesGridPanelSM.un('rowselect', this.queriesGridPanelSMRowSelect, this);
        sm.selectRow(index);
        this.queriesGridPanelSM.on('rowselect', this.queriesGridPanelSMRowSelect, this);

        var view = this.queriesGridPanel.getView();

        view.focusRow(index);
    }
}); //XDMoD.Module.MetricExplorer
