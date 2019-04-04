/**
 * XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet
 *
 */

Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet = Ext.extend(Ext.Panel, {
    layout: 'fit',
    header: false,
    itemId: 'testid',
    cls: 'images-view',
    tbar: {
        items: [
            'Time Range:',
            ' ',
            {
                text: 'Previous Quarter',
                listeners: {
                    click: function (comp) {
                        var today = new Date();
                        var lastQuarter = today.add(Date.DAY, -90);
                        var start = lastQuarter;
                        var end = today;
                        this.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
                    }
                }

            },
            {
                text: 'Previous Year',
                listeners: {
                    click: function () {
                        var today = new Date();
                        var oneYearAgoStart = new Date(today.getFullYear() - 1, 0, 1);
                        var oneYearAgoEnd = new Date(today.getFullYear() - 1, 11, 31);
                        var start = oneYearAgoStart;
                        var end = oneYearAgoEnd;
                        this.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
                    }
                }
            },
            {
                text: '5 Year',
                listeners: {
                    click: function () {
                        var today = new Date();
                        var last5Year = today.add(Date.YEAR, -5);
                        var start = last5Year;
                        var end = today;
                        this.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
                    }
                }
            },
            ' ',
            '|',
            ' ',
            {
                text: 'Download Report',
                icon: 'gui/images/report_generator/pdf_icon.png',
                cls: 'x-btn-text-icon'
            }
        ]
    },

    /**
     *
     */
    initComponent: function () {

        var self = this;
        var today = new Date();

        // default is previous Quarter
        var start_date = today.add(Date.DAY, -90);

        this.timeframe = {
            'start_date': start_date.format('Y-m-d'),
            'end_date': today.format('Y-m-d')
        };


        this.store = new Ext.data.JsonStore({
            url: XDMoD.REST.url + '/summary/cdcharts',
            root: 'data.queue',
            fields: [
                'chart_title',
                {
                    name: 'thumbnail_link',
                    convert: function (v, rec) {

                        params = {}
                        v_split = v.split('/report_image_renderer.php?')[1].split('&');
                        for (var index = 0; index < v_split.length; index++) {
                            tmpk = v_split[index].split('=')[0];
                            tmpv = v_split[index].split('=')[1];
                            params[tmpk] = tmpv
                        }
                        value = '/report_image_renderer.php?' + 'type=' + 'cached' + '&ref=' + params['ref'];
                        value = value + '&start=' + self.timeframe['start_date'] + '&end=' + self.timeframe['end_date'] + '&token=';
                        return value;

                    }
                }

            ]
        });
        this.store.load();

        var tpl = new Ext.XTemplate(
            '<tpl for=".">',
            '<div class="thumb-wrap" id="{chart_title}">',
            '<span class="x-editable">{shortName}</span>',
            '<div class="thumb"><img src="{thumbnail_link}' + XDMoD.REST.token + '" title="{chart_title}"></div>',
            '</div>',
            '</tpl>',
            '<div class="x-clear"></div>'
        );

        this.panel = new Ext.DataView({
            store: this.store,
            tpl: tpl,
            autoHeight: true,
            multiSelect: false,
            overClass: 'x-view-over',
            itemSelector: 'div.thumb-wrap',
            emptyText: 'No images to display',

            prepareData: function (data) {
                data.shortName = Ext.util.Format.ellipsis(data.chart_title, 40);
                return data;
            },

            listeners: {
                click: {
                    fn: function (dataView, index, node, e) {
                        this.tmpHpc = new CCR.xdmod.ui.HighChartPanel({
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

                                proxy: new Ext.data.HttpProxy({
                                    method: 'POST',
                                    url: 'controllers/metric_explorer.php'
                                })

                            })

                        }); // hcp


                        config_split = dataView.store.data.items[index].json.chart_id.split('&');
                        config = {}
                        this.tmpHpc.store.removeAll();
                        for (var x = 0; x < config_split.length; x++) {
                            var [key, value] = config_split[x].split('=')
                            if (value) {
                                if (key === 'global_filters') {
                                    parsed_value = JSON.parse(decodeURIComponent(value));
                                    this.tmpHpc.store.setBaseParam(key, parsed_value);
                                    config[key] = parsed_value;

                                } else if (key === 'data_series') {
                                    parsed_value = JSON.parse(decodeURIComponent(value));
                                    console.log(parsed_value);
                                    var data_series = {}
                                    data_series["data"] = parsed_value;
                                    data_series["total"] = parsed_value.length;
                                    this.tmpHpc.store.setBaseParam(key, value);
                                    config['data_series'] = data_series
                                } else {
                                    this.tmpHpc.store.setBaseParam(key, decodeURIComponent(value));
                                    config[key] = decodeURIComponent(value);
                                }

                            }

                        }
                        config['start_date'] = self.timeframe['start_date'];
                        config['end_date'] = self.timeframe['end_date'];
                        config['timeframe_label'] = 'User Defined';
                        this.tmpHpc.store.setBaseParam('start_date', self.timeframe['start_date']);
                        this.tmpHpc.store.setBaseParam('end_date', self.timeframe['end_date']);
                        this.tmpHpc.store.setBaseParam('timeframe_label', 'User Defined');
                        console.log('config', config);

                        var win = new Ext.Window({
                            layout: 'fit',
                            width: 800,
                            height: 600,
                            closeAction: 'hide',
                            plain: true,
                            title: dataView.store.data.items[index].json.chart_title,

                            items: [this.tmpHpc],

                            buttons: [{
                                text: 'Open in Metric Explorer',
                                handler: function () {
                                    config.font_size = 3;
                                    config.featured = true;
                                    config.summary_index = (config.preset ? 'summary_' : '') + config.index;
                                    win.hide();
                                    XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));

                                }
                            }, {
                                text: 'Close',
                                handler: function () {
                                    win.hide();
                                }
                            }],
                            listeners: {
                                show: function (eOpts, tmpHpc) {
                                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                                    if (viewer.el) {
                                        viewer.el.mask();
                                    }
                                },
                                hide: function (eOpts) {
                                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                                    viewer.el.unmask();
                                }
                            }
                        });

                        win.show(Ext.getBody());


                    }
                }
            }
        });




        this.items = [this.panel];

        XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        timeframe_change: function (start_date, end_date) {
            this.timeframe['start_date'] = start_date.format('Y-m-d');
            this.timeframe['end_date'] = end_date.format('Y-m-d');
            this.store.load()
        }
    }

});


/**
 * The Ext.reg call is used to register an xtype for this class so it
 * can be dynamically instantiated
 */
Ext.reg('ChartThumbnailPortlet', XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet);
