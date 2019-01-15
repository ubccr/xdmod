/**
 * XDMoD.Modules.SummaryPortlets.ChartPortlet
 *
 */

Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.ChartPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    tools: [{
        id: 'gear',
        hidden: CCR.xdmod.publicUser,
        qtip: 'Edit in Metric Explorer',
        scope: this,
        handler: function (event, toolEl, panel) {
            var config = panel.config;
            config.font_size = 3;
            config.title = panel.title;
            config.featured = true;
            config.summary_index = (config.preset ? 'summary_' : '') + config.index;

            XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));
        }
    }, {
        id: 'help'
    }],

    initComponent: function () {
        var self = this;

        this.title = this.config.title;
        if (this.title.length > 60) {
            this.title = this.title.substring(0, 57) + '...';
        }

        var highchartConfig = {};
        jQuery.extend(true, highchartConfig, this.config);
        highchartConfig.title = '';

        this.store = new CCR.xdmod.CustomJsonStore({
            portlet: self,
            listeners: {
                load: function (store) {
                    var dimensions = store.getAt(0).get('dimensions');
                    var dims = '';
                    var dimension;
                    for (dimension in dimensions) {
                        if (dimensions.hasOwnProperty(dimension)) {
                            dims += '<li><b>' + dimension + ':</b> ' + dimensions[dimension] + '</li>';
                        }
                    }
                    var metrics = store.getAt(0).get('metrics');

                    var mets = '';
                    var metric;
                    for (metric in metrics) {
                        if (metrics.hasOwnProperty(metric)) {
                            mets += '<li><b>' + metric + ':</b> ' + metrics[metric] + '</li>';
                        }
                    }
                    var help = this.portlet.getTool('help');
                    if (help && help.dom) {
                        help.dom.qtip = '<ul>' + dims + '</ul><hr/><ul>' + mets + '</ul>';
                    }
                },
                exception: function (thisProxy, type, action, options, response) {
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
            messageProperty: 'message',
            url: 'controllers/metric_explorer.php',

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
                config: Ext.util.JSON.encode(highchartConfig),
                format: 'hc_jsonstore',
                public_user: CCR.xdmod.publicUser,
                aggregation_unit: this.config.aggregation_unit,
                width: this.width,
                height: this.height
            }
        });

        this.items = [new CCR.xdmod.ui.HighChartPanel({
            credits: false,
            chartOptions: {
                chart: {
                    animation: CCR.xdmod.publicUser === true
                },
                plotOptions: {
                    series: {
                        animation: CCR.xdmod.publicUser === true
                    }
                }
            },
            store: this.store,
            listeners: {
                render: function (panel) {
                    this.loadMask = new Ext.LoadMask(panel.getEl(), {
                        msg: 'Loading...',
                        store: self.store
                    });
                },
                single: true
            }
        })];

        this.height = (this.width * 11.0) / 17.0;

        XDMoD.Modules.SummaryPortlets.ChartPortlet.superclass.initComponent.apply(this, arguments);
    },

    listeners: {
        duration_change: function (timeframe) {
            this.store.load({
                params: {
                    start_date: timeframe.start_date,
                    end_date: timeframe.end_date
                }
            });
        }
    }
});

Ext.reg('ChartPortlet', XDMoD.Modules.SummaryPortlets.ChartPortlet);
