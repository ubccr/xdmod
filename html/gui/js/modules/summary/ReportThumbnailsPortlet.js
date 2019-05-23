/**
 * XDMoD.Modules.SummaryPortlets.ReportThumbnailsPortlet
 *
 */

Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.ReportThumbnailsPortlet = Ext.extend(Ext.Panel, {
    layout: 'fit',
    header: false,
    itemId: 'testid',
    cls: 'images-view',
    tbar: {
        items: [
            {
                xtype: 'button',
                text: 'Time Range',
                menu: [{
                    text: '30 day',
                    listeners: {
                        click: function (comp) {
                            var today = new Date();
                            var lastMonth = today.add(Date.DAY, -30);
                            var start = lastMonth;
                            var end = today;
                            this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
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
                            this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
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
                            this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
                        }
                    }
                },
                {
                    text: 'Report Default',
                    listeners: {
                        click: function (comp) {
                            this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change');
                        }
                    }
                }]
            },
            ' ',
            '|',
            ' ',
            {
                text: 'Download Report',
                icon: 'gui/images/report_generator/pdf_icon.png',
                cls: 'x-btn-text-icon',
                listeners: {
                    click: function () {
                        var viewer = CCR.xdmod.ui.Viewer.getViewer();
                        viewer.el.mask(
                            '<center>Preparing report for download<br /><b>' +
                            '</b><br /><img src="gui/images/progbar_2.gif">' +
                            '<br />Please Wait</center>'
                        );
                        var report_id = this.ownerCt.ownerCt.store.data.items[0].data.report_id;
                        var start_date = this.ownerCt.ownerCt.timeframe.start_date;
                        var end_date = this.ownerCt.ownerCt.timeframe.end_date;
                        var format = 'pdf';
                        var conn = new Ext.data.Connection({
                            // allow for generous 'execution time' so that lengthy
                            // reports can be compiled (10 min.)
                            timeout: 600000
                        });
                        conn.request({
                            url: 'controllers/report_builder.php',

                            params: {
                                operation: 'send_report',
                                report_id: report_id,
                                build_only: true,
                                export_format: format,
                                start_date: start_date,
                                end_date: end_date
                            },

                            method: 'POST',

                            callback: function (options, success, response) {
                                if (success) {
                                    var responseData = CCR.safelyDecodeJSONResponse(response);
                                    var successResponse = CCR.checkDecodedJSONResponseSuccess(responseData);
                                    if (successResponse) {
                                        var location = 'controllers/report_builder.php/' +
                                            responseData.report_name +
                                            '?operation=download_report&report_loc=' +
                                            responseData.report_loc + '&format=' + format;

                                        var w = new Ext.Window({
                                            title: 'Report Built',
                                            width: 220,
                                            height: 120,
                                            resizable: false,
                                            closeAction: 'destroy',
                                            layout: 'border',
                                            cls: 'wnd_report_built',

                                            listeners: {
                                                show: function () {
                                                    if (viewer.el) {
                                                        viewer.el.mask();
                                                    }
                                                },
                                                destroy: function () {
                                                    viewer.el.unmask();
                                                }
                                            },

                                            items: [
                                                new Ext.Panel({
                                                    region: 'west',
                                                    width: 70,
                                                    html: '<img src="gui/images/report_icon_wnd.png">',
                                                    baseCls: 'x-plain'
                                                }),
                                                new Ext.Panel({
                                                    region: 'center',
                                                    width: 150,
                                                    layout: 'border',
                                                    margins: '5 5 5 5',
                                                    items: [
                                                        new Ext.Panel({
                                                            region: 'center',
                                                            html: 'Your report has been built and can now be viewed.',
                                                            baseCls: 'x-plain'
                                                        }),
                                                        new Ext.Button({
                                                            region: 'south',
                                                            text: 'View Report',
                                                            handler: function () {
                                                                XDMoD.TrackEvent(
                                                                    'Report Generator',
                                                                    'Clicked on View Report button in Report Built window'
                                                                );
                                                                window.open(location);
                                                            }
                                                        })
                                                    ]
                                                })
                                            ]
                                        });
                                        w.show();
                                    }
                                }
                            }
                        });
                    }
                }
            }
        ]
    },
    /**
     *
     */
    initComponent: function () {
        var self = this;
        function isPrimary(item) {
            return item.is_primary === '1';
        }
        var role = CCR.xdmod.ui.allRoles.filter(isPrimary)[0].param_value.split(':')[0];

        function filterRange(arr, label) {
            var dateRange = {};
            for (var i = 0; i < arr.length; i++) {
                if (arr[i].text === label) {
                    dateRange = {
                        start_date: arr[i].start.format('Y-m-d'),
                        end_date: arr[i].end.format('Y-m-d')
                    };
                }
            }
            return dateRange;
        }
        var ranges = CCR.xdmod.ui.DurationToolbar.getDateRanges();
        var timeframe_label = this.config[role];
        this.timeframe = filterRange(ranges, timeframe_label);

        this.store = new Ext.data.JsonStore({
            url: XDMoD.REST.url + '/summary/rolereport',
            root: 'data.queue',
            fields: [
                'chart_title',
                {
                    name: 'thumbnail_link',
                    convert: function (v, rec) {
                        var params = {};
                        var v_split = v.split('/report_image_renderer.php?')[1].split('&');
                        for (var index = 0; index < v_split.length; index++) {
                            var tmpk = v_split[index].split('=')[0];
                            var tmpv = v_split[index].split('=')[1];
                            params[tmpk] = tmpv;
                        }
                        if (!(self.timeframe.start_date === null && self.timeframe.end_date === null)) {
                            var value = '/report_image_renderer.php?type=cached&ref=' + params.ref;
                            value = value + '&start=' + self.timeframe.start_date + '&end=' + self.timeframe.end_date + '&token=';
                        } else {
                            var value = '/report_image_renderer.php?type=report&ref=' + params.ref;
                            value = value + '&token=';
                        }
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
                var params = {};
                var v_split = data.thumbnail_link.split('/report_image_renderer.php?')[1].split('&');
                for (var index = 0; index < v_split.length; index++) {
                    var tmpk = v_split[index].split('=')[0];
                    var tmpv = v_split[index].split('=')[1];
                    params[tmpk] = tmpv;
                }
                data.report_id = params.ref.split(';')[0];
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

                        var config = dataView.store.data.items[index].json.chart_id;
                        this.tmpHpc.store.removeAll();
                        for (var key in config) {
                            if (key === 'data_series') {
                                this.tmpHpc.store.setBaseParam(key, Ext.util.JSON.encode(config[key]));
                                var data_series = {};
                                data_series.data = config[key];
                                data_series.total = config[key].length;
                                config.data_series = data_series;
                            } else if (key === 'global_filters') {
                                this.tmpHpc.store.setBaseParam(key, Ext.util.JSON.encode(config[key]));
                            } else {
                                this.tmpHpc.store.setBaseParam(key, config[key]);
                            }
                        }
                        if (!(self.timeframe.start_date === null && self.timeframe.end_date === null)) {
                            config.start_date = self.timeframe.start_date;
                            config.end_date = self.timeframe.end_date;
                            config.timeframe_label = 'User Defined';
                            this.tmpHpc.store.setBaseParam('start_date', self.timeframe.start_date);
                            this.tmpHpc.store.setBaseParam('end_date', self.timeframe.end_date);
                            this.tmpHpc.store.setBaseParam('timeframe_label', 'User Defined');
                        }

                        this.tmpHpc.store.setBaseParam('operation', 'get_data');

                        var win = new Ext.Window({
                            layout: 'fit',
                            width: 800,
                            height: 600,
                            closeAction: 'destroy',
                            plain: true,
                            title: dataView.store.data.items[index].json.chart_title,
                            items: [this.tmpHpc],
                            buttons: [{
                                text: 'Open in Metric Explorer',
                                handler: function () {
                                    config.font_size = 3;
                                    config.featured = true;
                                    config.summary_index = (config.preset ? 'summary_' : '') + config.index;
                                    win.destroy();
                                    XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));
                                }
                            }, {
                                text: 'Close',
                                handler: function () {
                                    win.destroy();
                                }
                            }],
                            listeners: {
                                show: function () {
                                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                                    if (viewer.el) {
                                        viewer.el.mask();
                                    }
                                },
                                destroy: function () {
                                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                                    viewer.el.unmask();
                                }
                            }
                        });
                        win.show();
                    }
                }
            }
        });
        this.items = [this.panel];
        XDMoD.Modules.SummaryPortlets.ReportThumbnailsPortlet.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        timeframe_change: function (start_date, end_date) {
            if (start_date !== undefined && end_date !== undefined) {
                this.timeframe.start_date = start_date.format('Y-m-d');
                this.timeframe.end_date = end_date.format('Y-m-d');
            } else {
                this.timeframe.start_date = null;
                this.timeframe.end_date = null;
            }
            this.store.load();
        }
    }
});


/**
 * The Ext.reg call is used to register an xtype for this class so it
 * can be dynamically instantiated
 */
Ext.reg('ReportThumbnailsPortlet', XDMoD.Modules.SummaryPortlets.ReportThumbnailsPortlet);
