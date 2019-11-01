/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Feb-07 (version 1)
 *
 * @author Ryan Gentner
 * @date 2013-Jun-23 (version 2)
 *
 *
 * This class contains functionality for the summary tab of xdmod.
 *
 */
XDMoD.Module.Summary = function (config) {
    XDMoD.Module.Summary.superclass.constructor.call(this, config);
}; // XDMoD.Module.Summary

// ===========================================================================

Ext.extend(XDMoD.Module.Summary, XDMoD.PortalModule, {

    module_id: 'summary',

    usesToolbar: true,

    toolbarItems: {

        durationSelector: true

    },

    refreshRequested: false,

  // ------------------------------------------------------------------

    initComponent: function () {
        var self = this;

        this.public_user = CCR.xdmod.publicUser;

        self.on('role_selection_change', function () {
            self.reload();
        });

        self.on('duration_change', function () {
            self.reload();
        });

        self.on('resize', function () {
            self.reloadPortlets(self.summaryStore);
            self.portal.doLayout();
            self.toolbar.doLayout();
        });

        self.on('request_refresh', function () {
            this.refreshRequested = true;
        });

        self.on('activate', function () {
            if (this.refreshRequested) {
                this.refreshRequested = false;
                this.reload();
            }
        });

    // ----------------------------------------

        this.summaryStore = new CCR.xdmod.CustomJsonStore({

            root: 'data',
            totalProperty: 'totalCount',
            autoDestroy: true,
            autoLoad: false,
            successProperty: 'success',
            messageProperty: 'message',

            fields: [
                'Jobs_job_count',
                'Jobs_active_person_count',
                'Jobs_active_pi_count',
                'Jobs_total_waitduration_hours',
                'Jobs_avg_waitduration_hours',
                'Jobs_total_cpu_hours',
                'Jobs_avg_cpu_hours',
                'Jobs_total_su',
                'Jobs_avg_su',
                'Jobs_min_processors',
                'Jobs_max_processors',
                'Jobs_avg_processors',
                'Jobs_total_wallduration_hours',
                'Jobs_avg_wallduration_hours',
                'Jobs_gateway_job_count',
                'Jobs_active_allocation_count',
                'Jobs_active_institution_count',
                'charts'
            ],

            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: 'controllers/ui_data/summary3.php'
            })

        }); // this.summaryStore

    // ----------------------------------------

        this.summaryStore.on('exception', function (dp, type, action, opt, response, arg) {
            if (response.success == false) {
                // todo: show a re-login box instead of logout
                Ext.MessageBox.alert(
                    'Error',
                    response.message || 'Unknown Error',
                    function () {
                        // Remove Mask on body after closing error
                        CCR.xdmod.ui.Viewer.getViewer().el.unmask();
                    }
                );

                if (response.message == 'Session Expired') {
                    CCR.xdmod.ui.actionLogout.defer(1000);
                }
            }
        }, this);

    // ----------------------------------------

        this.toolbar = new Ext.Toolbar({
            border: false,
            cls: 'xd-toolbar'
        });

        this.portal = new Ext.ux.Portal({
            region: 'center',
            border: false,
            items: []
        });

        this.portalPanel = new Ext.Panel({
            tbar: this.toolbar,
            layout: 'fit',
            region: 'center',
            items: [this.portal]
        });

        var quickFilterButton = XDMoD.DataWarehouse.createQuickFilterButton();
        this.quickFilterButton = quickFilterButton;
        var quickFilterButtonStore = quickFilterButton.quickFilterStore;
        quickFilterButtonStore.on('update', self.reload, self);

        var quickFilterToolbar = XDMoD.DataWarehouse.createQuickFilterToolbar(quickFilterButtonStore, {
            items: [
                quickFilterButton
            ]
        });

        this.mainPanel = new Ext.Panel({
            header: false,
            layout: 'border',
            region: 'center',
            title: '<h3>Summary</h3>',

            tbar: quickFilterToolbar,
            items: [this.portalPanel]
        });

        Ext.apply(this, {
            items: [this.mainPanel]
        });

        XDMoD.Module.Summary.superclass.initComponent.apply(this, arguments);

        this.mainPanel.on('afterrender', function () {
            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) {
                viewer.el.mask('Loading...');
            }

            this.getDurationSelector().disable();

            this.summaryStore.loadStartTime = new Date().getTime();
            this.reload();

            this.summaryStore.on('load', this.updateUsageSummary, this);
        }, this, {
            single: true
        });
    }, // initComponent

  // ------------------------------------------------------------------

    updateUsageSummary: function (store) {
        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.mask('Loading...');
        }

        this.getDurationSelector().disable();

        if (store.getCount() <= 0) {
            CCR.xdmod.ui.toastMessage('Load Data', 'No Results');
            return;
        }

        var record = store.getAt(0);

        var keyStyle = {
            marginLeft: '4px',
            marginRight: '4px',
            fontSize: '11px',
            textAlign: 'center'
        };

        var valueStyle = {
            marginLeft: '2px',
            marginRight: '2px',
            textAlign: 'center',
            fontFamily: 'arial,"Times New Roman",Times,serif',
            fontSize: '11px',
            letterSpacing: '0px'
        };

        var summaryFormat = [

            {

                title: 'Activity',
                items: [

                    {
                        title: 'Users',
                        fieldName: 'Jobs_active_person_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'PIs',
                        fieldName: 'Jobs_active_pi_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'Allocations',
                        fieldName: 'Jobs_active_allocation_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'Institutions',
                        fieldName: 'Jobs_active_institution_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }

                ]

            }, // Activity

            {

                title: 'Jobs',
                items: [

                    {
                        title: 'Total',
                        fieldName: 'Jobs_job_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'Gateway',
                        fieldName: 'Jobs_gateway_job_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }

                ]

            }, // Jobs

            {

                title: 'Service (XD SU)',
                items: [

                    {
                        title: 'Total',
                        fieldName: 'Jobs_total_su',
                        numberType: 'float',
                        numberFormat: '#,#.0'
                    }, {
                        title: 'Avg (Per Job)',
                        fieldName: 'Jobs_avg_su',
                        numberType: 'float',
                        numberFormat: '#,#.00'
                    }

                ]

            }, // Service (XD SU)

            {

                title: 'CPU Time (h)',
                items: [

                    {
                        title: 'Total',
                        fieldName: 'Jobs_total_cpu_hours',
                        numberType: 'float',
                        numberFormat: '#,#.0'
                    }, {
                        title: 'Avg (Per Job)',
                        fieldName: 'Jobs_avg_cpu_hours',
                        numberType: 'float',
                        numberFormat: '#,#.00'
                    }

                ]

            }, // CPU Time (h)

            {

                title: 'Wait Time (h)',
                items: [

                    {
                        title: 'Avg (Per Job)',
                        fieldName: 'Jobs_avg_waitduration_hours',
                        numberType: 'float',
                        numberFormat: '#,#.00'
                    }

                ]

            }, // Wait Time (h)

            {

                title: 'Wall Time (h)',
                items: [{
                    title: 'Total',
                    fieldName: 'Jobs_total_wallduration_hours',
                    numberType: 'float',
                    numberFormat: '#,#.0'
                }, {
                    title: 'Avg (Per Job)',
                    fieldName: 'Jobs_avg_wallduration_hours',
                    numberType: 'float',
                    numberFormat: '#,#.00'
                }]

            }, // Wall Time (h)

            {

                title: 'Processors',
                items: [{
                    title: 'Max',
                    fieldName: 'Jobs_max_processors',
                    numberType: 'int',
                    numberFormat: '#,#'
                }, {
                    title: 'Avg (Per Job)',
                    fieldName: 'Jobs_avg_processors',
                    numberType: 'int',
                    numberFormat: '#,#'
                }]

            } // Processors

        ]; // summaryFormat

        this.toolbar.removeAll();

        Ext.each(summaryFormat, function (itemGroup) {
            var itemTitles = [],
                items = [];

            Ext.each(itemGroup.items, function (item) {
                var itemData = record.get(item.fieldName),
                    itemNumber;

                if (itemData) {
                    if (item.numberType === 'int') {
                        itemNumber = parseInt(itemData, 10);
                    } else if (item.numberType === 'float') {
                        itemNumber = parseFloat(itemData);
                    }

                    itemTitles.push({
                        xtype: 'tbtext',
                        text: item.title + ':',
                        style: keyStyle
                    });

                    items.push({
                        xtype: 'tbtext',
                        text: itemNumber.numberFormat(item.numberFormat),
                        style: valueStyle
                    });
                } // if (itemdata)
            }); // Ext.each(itemGroup.items, ...

            if (items.length > 0) {
                this.toolbar.add({
                    xtype: 'buttongroup',
                    columns: items.length,
                    title: itemGroup.title,
                    items: itemTitles.concat(items)
                });
            } // if (items.length > 0)
        }, this); // Ext.each(summaryFormat, …

        this.reloadPortlets(store);

        this.portal.doLayout();
        this.toolbar.doLayout();

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.unmask();
        }

        this.getDurationSelector().enable();

        var loadTime = (new Date().getTime() - store.loadStartTime) / 1000.0;
        CCR.xdmod.ui.toastMessage('Load Data', 'Complete in ' + loadTime + 's');
    }, // updateUsageSummary

  // ------------------------------------------------------------------

    reload: function () {
        if (!this.getDurationSelector().validate()) {
            return;
        }

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.mask('Processing Query...');
        }

        this.getDurationSelector().disable();

        var startDate = this.getDurationSelector().getStartDate().format('Y-m-d');
        var endDate = this.getDurationSelector().getEndDate().format('Y-m-d');
        var aggregationUnit = this.getDurationSelector().getAggregationUnit();
        var timeframeLabel = this.getDurationSelector().getDurationLabel();

        var filters = {
            data: [],
            total: 0
        };
        this.quickFilterButton.quickFilterStore.each(function (quickFilterRecord) {
            if (!quickFilterRecord.get('checked')) {
                return;
            }

            filters.data.push({
                dimension_id: quickFilterRecord.get('dimensionId'),
                value_id: quickFilterRecord.get('valueId'),
                value_name: quickFilterRecord.get('valueName')
            });
            filters.total++;
        });

        Ext.apply(this.summaryStore.baseParams, {

            start_date: startDate,
            end_date: endDate,
            aggregation_unit: aggregationUnit,
            timeframe_label: timeframeLabel,
            filters: Ext.encode(filters),
            public_user: this.public_user

        });

        this.summaryStore.loadStartTime = new Date().getTime();
        this.summaryStore.removeAll(true);
        this.summaryStore.load();
    }, // reload

  // ------------------------------------------------------------------

    reloadPortlets: function (store) {
        if (store.getCount() <= 0) {
            return;
        }

        this.portal.removeAll(true);

        var portletAspect = 11.0 / 17.0;
        var portletWidth = 580;
        var portletPadding = 25;
        var portalWidth = this.portal.getWidth();
        var portalColumns = [];

        portalColumnsCount = Math.max(1, Math.round(portalWidth / portletWidth));

        portletWidth = (portalWidth - portletPadding) / portalColumnsCount;

      //	alert(portalColumnsCount+' '+this.portal.getWidth()+' '+portletWidth);
        for (var i = 0; i < portalColumnsCount; i++) {
            var portalColumn = new Ext.ux.PortalColumn({
                width: portletWidth,
                style: 'padding:1px 1px 1px 1px'
            });

            portalColumns.push(portalColumn);
            this.portal.add(portalColumn);
        } // for

        var charts = Ext.util.JSON.decode(store.getAt(0).get('charts'));

        var getTrackingConfig = function (panel_ref) {
            return {
                title: truncateText(panel_ref.title),
                index: truncateText(panel_ref.config.index),
                start_date: panel_ref.config.start_date,
                end_date: panel_ref.config.end_date,
                timeframe_label: panel_ref.config.timeframe_label
            };
        }; // getTrackingConfig

        for (var i = 0; i < charts.length; i++) {
            var config = charts[i];

            config = Ext.util.JSON.decode(config);
            config.start_date = this.getDurationSelector().getStartDate().format('Y-m-d');
            config.end_date = this.getDurationSelector().getEndDate().format('Y-m-d');
            config.aggregation_unit = this.getDurationSelector().getAggregationUnit();
            config.timeframe_label = this.getDurationSelector().getDurationLabel();
            config.font_size = 2;

            var title = config.title;
            config.title = '';

            this.quickFilterButton.quickFilterStore.each(function (quickFilterRecord) {
                if (!quickFilterRecord.get('checked')) {
                    return;
                }

                var dimensionId = quickFilterRecord.get('dimensionId');
                var valueId = quickFilterRecord.get('valueId');
                var quickFilterId = dimensionId + '=' + valueId;
                var globalFilterExists = false;
                Ext.each(config.global_filters.data, function (globalFilter) {
                    if (quickFilterId === globalFilter.id) {
                        globalFilterExists = true;
                        return false;
                    }
                });
                if (globalFilterExists) {
                    return;
                }

                config.global_filters.data.push({
                    id: quickFilterId,
                    value_id: valueId,
                    value_name: quickFilterRecord.get('valueName'),
                    dimension_id: dimensionId,
                    checked: true
                });
                config.global_filters.total++;
            });

            var portlet = new Ext.ux.Portlet({

                config: config,
                index: i,

                title: (function () {
                    if (title.length > 60) {
                        return title.substring(0, 57) + '...';
                    }
                    return title;
                }()),

                tools: [

                    {
                        id: 'gear',
                        hidden: this.public_user,
                        qtip: 'Edit in Metric Explorer',
                        scope: this,

                        handler: function (event, toolEl, panel, tc) {
                            var trackingConfig = getTrackingConfig(panel);
                            XDMoD.TrackEvent('Summary', 'Clicked On Edit in Metric Explorer tool', Ext.encode(trackingConfig));

                            var config = panel.config;
                            config.font_size = 3;
                            config.title = panel.title;
                            config.featured = true;
                            config.summary_index = (config.preset ? 'summary_' : '') + config.index;

                            XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));
                        } // handler

                    },

                    {
                        id: 'help'
                    }

                ],

                width: portletWidth,
                height: portletWidth * portletAspect,
                layout: 'fit',
                items: [],

                listeners: {

                    collapse: function (panel) {
                        var trackingConfig = getTrackingConfig(panel);
                        XDMoD.TrackEvent('Summary', 'Collapsed Chart Entry', Ext.encode(trackingConfig));
                    }, // collapse

                    expand: function (panel) {
                        var trackingConfig = getTrackingConfig(panel);
                        XDMoD.TrackEvent('Summary', 'Expanded Chart Entry', Ext.encode(trackingConfig));
                    } // expand

                } // listeners

            }); // portlet

            var hcp = new CCR.xdmod.ui.HighChartPanel({

                credits: false,
                chartOptions: {
                    chart: {
                        animation: this.public_user === true
                    },
                    plotOptions: {
                        series: {
                            animation: this.public_user === true
                        }
                    }
                },
                store: new CCR.xdmod.CustomJsonStore({

                    portlet: portlet,

                    listeners: {

                        load: function (store) {
                            var dimensions = store.getAt(0).get('dimensions');
                            var dims = '';
                            for (dimension in dimensions) {
                                dims += '<li><b>' + dimension + ':</b> ' + dimensions[dimension] + '</li>';
                            }
                            var metrics = store.getAt(0).get('metrics');

                            var mets = '';
                            for (metric in metrics) {
                                mets += '<li><b>' + metric + ':</b> ' + metrics[metric] + '</li>';
                            }
                            var help = this.portlet.getTool('help');
                            if (help && help.dom) {
                                help.dom.qtip = '<ul>' + dims + '</ul><hr/>' + '<ul>' + mets + '</ul>';
                            }
                        }, // load

                        exception: function (thisProxy, type, action, options, response, arg) {
                            if (type === 'response') {
                                var data = CCR.safelyDecodeJSONResponse(response) || {};
                                var errorCode = data.code;

                                if (errorCode === XDMoD.Error.QueryUnavailableTimeAggregationUnit) {
                                    var hcp = this.portlet.items.get(0);

                                    var errorMessageExtraData = '';
                                    var errorData = data.errorData;
                                    if (errorData) {
                                        var extraDataLines = [];
                                        if (errorData.realm) {
                                          extraDataLines.push('Realm: ' + Ext.util.Format.htmlEncode(errorData.realm));
                                      }
                                        if (errorData.unit) {
                                          extraDataLines.push('Unavailable Unit: ' + Ext.util.Format.capitalize(Ext.util.Format.htmlEncode(errorData.unit)));
                                      }

                                        for (var i = 0; i < extraDataLines.length; i++) {
                                          if (i > 0) {
                                              errorMessageExtraData += '<br />';
                                          }
                                          errorMessageExtraData += extraDataLines[i];
                                      }
                                    }

                                    hcp.displayError(
                        'Data not available for the selected aggregation unit.',
                        errorMessageExtraData
                      );
                                }
                            }
                        }

                    }, // listeners

                    autoDestroy: true,
                    root: 'data',
                    autoLoad: true,
                    totalProperty: 'totalCount',
                    successProperty: 'success',
                    messageProperty: 'message',

                    fields: [
                        'chart',
                        'credits',
                        'title',
                        'subtitle',
                        'xAxis',
                        'yAxis',
                        'tooltip',
                        'legend',
                        'series',
                        'dimensions',
                        'metrics',
                        'plotOptions',
                        'reportGeneratorMeta'
                    ],

                    baseParams: {
                        operation: 'get_data',
                        showContextMenu: false,
                        config: Ext.util.JSON.encode(config),
                        format: 'hc_jsonstore',
                        public_user: this.public_user,
                        aggregation_unit: this.getDurationSelector().getAggregationUnit(),
                        width: portletWidth,
                        height: portletWidth * portletAspect
                    },

                    proxy: new Ext.data.HttpProxy({
                        method: 'POST',
                        url: 'controllers/metric_explorer.php'
                    })

                }) // store

            }); // hcp

            portlet.add(hcp);

            portalColumns[i % portalColumnsCount].add(portlet);
        } // for (var i = 0; i < charts.length; i++)
    } // reloadPortlets

}); // XDMoD.Module.Summary
