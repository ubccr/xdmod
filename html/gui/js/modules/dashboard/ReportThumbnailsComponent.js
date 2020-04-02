/**
 * XDMoD.Module.Dashboard.ReportThumbnailsComponent
 *
 */

Ext.namespace('XDMoD.Module.Dashboard');

XDMoD.Module.Dashboard.ReportThumbnailsComponent = Ext.extend(Ext.Panel, {
    layout: 'fit',
    header: false,
    cls: 'images-view',
    /**
     *
     */
    initComponent: function () {
        var self = this;

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
        var timeframe_label = this.config.timeframe;
        this.timeframe = filterRange(ranges, timeframe_label);
        if (Object.keys(this.timeframe).length === 0) {
            this.timeframe.start_date = null;
            this.timeframe.end_date = null;
            timeframe_label = 'Report default';
        }

        this.store = new Ext.data.JsonStore({
            url: XDMoD.REST.url + '/dashboard/rolereport',
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
                        var value;
                        if (!(self.timeframe.start_date === null && self.timeframe.end_date === null)) {
                            value = '/report_image_renderer.php?type=cached&ref=' + params.ref;
                            value = value + '&start=' + self.timeframe.start_date + '&end=' + self.timeframe.end_date + '&token=';
                        } else {
                            value = '/report_image_renderer.php?type=report&ref=' + params.ref;
                            value += '&token=';
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
                data.shortName = Ext.util.Format.ellipsis(data.chart_title, 50);
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
                        var config = JSON.parse(JSON.stringify(dataView.store.data.items[index].json.chart_id));

                        if (config.controller_module !== 'metric_explorer') {
                            // Only metric explorer charts are supported
                            return;
                        }

                        var win; // Window to display the chart
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
                                }),
                                listeners: {
                                    load: function () {
                                        win.el.unmask();
                                    }
                                }

                            })

                        }); // hcp

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
                        } else {
                            var timeframe = filterRange(ranges, config.timeframe_label);
                            config.start_date = timeframe.start_date;
                            config.end_date = timeframe.end_date;
                            this.tmpHpc.store.setBaseParam('start_date', timeframe.start_date);
                            this.tmpHpc.store.setBaseParam('end_date', timeframe.end_date);
                        }

                        this.tmpHpc.store.setBaseParam('operation', 'get_data');

                        win = new Ext.Window({
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
                                    win.destroy();
                                    XDMoD.Module.MetricExplorer.setConfig(config, config.title, true);
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
                                    win.el.mask('Loading...');
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
        this.tools = [
            {
                id: 'help',
                qtip: [
                    '<ul>',
                    '<li style="padding-top:6px;margin-bottom:6px;">',
                    '<span style="width:20px;background:#ff0000;display:inline-block">&nbsp;</span>',
                    '<span><b>Failed Runs</b></span>',
                    '<ul>',
                    '<li style="margin-left:6px;">A run in which the app kernel failed to complete successfully.</li>',
                    '</ul>',
                    '</li>',
                    '<li style="margin-top:6px;margin-bottom:6px;">',
                    '<span style="width: 20px;background:#ffb336;display:inline-block">&nbsp;</span>',
                    '<span><b>Under Performing Runs</b></span>',
                    '<ul>',
                    '<li style="margin-left:6px;">A run in which the app kernel completed successfully but performed below the established control region.</li>',
                    '</ul>',
                    '</li>',
                    '<li style="margin-top:6px;margin-bottom:6px;">',
                    '<span style="width: 20px;background:#50b432;display:inline-block ">&nbsp;</span>',
                    '<span><b>In Control Runs</b></span>',
                    '<ul>',
                    '<li style="margin-left:6px;">A run in which the app kernel completed successfully and performed within the established control region.</li>',
                    '</ul>',
                    '</li>',
                    '<li style="margin-top:6px;padding-bottom:6px;">',
                    '<span style="width: 20px;background:#3c86ff;display:inline-block">&nbsp;</span>',
                    '<span><b>Over Performing Runs</b></span>',
                    '<ul>',
                    '<li style="margin-left:6px;">A run in which the app kernel completed successfully and performed better than the established control region.</li>',
                    '</ul>',
                    '</li>',
                    '</ul>'
                ].join(' '),
                qwidth: 60
            }
        ];
        this.tbar = {
            items: [
                {
                    xtype: 'tbtext',
                    text: 'Time Range'
                },
                {
                    xtype: 'button',
                    text: timeframe_label,
                    iconCls: 'custom_date',
                    menu: [{
                        text: '30 day',
                        checked: timeframe_label === '30 day',
                        group: 'timeframe',
                        listeners: {
                            click: function (comp) {
                                var today = new Date();
                                var lastMonth = today.add(Date.DAY, -30);
                                var start = lastMonth;
                                var end = today;
                                this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
                                this.ownerCt.ownerCt.ownerCt.items.items[1].setText('30 day');
                                this.ownerCt.ownerCt.ownerCt.items.items[2].setText('<b>' + self.timeframe.start_date + ' to ' + self.timeframe.end_date + '</b>');
                            }
                        }
                    },
                    {
                        text: '1 year',
                        checked: timeframe_label === '1 year',
                        group: 'timeframe',
                        listeners: {
                            click: function () {
                                var today = new Date();
                                var lastYear = today.add(Date.YEAR, -1);
                                var start = lastYear;
                                var end = today;
                                this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
                                this.ownerCt.ownerCt.ownerCt.items.items[1].setText('1 year');
                                this.ownerCt.ownerCt.ownerCt.items.items[2].setText('<b>' + self.timeframe.start_date + ' to ' + self.timeframe.end_date + '</b>');
                            }
                        }
                    },
                    {
                        text: '5 year',
                        checked: timeframe_label === '5 year',
                        group: 'timeframe',
                        listeners: {
                            click: function () {
                                var today = new Date();
                                var last5Year = today.add(Date.YEAR, -5);
                                var start = last5Year;
                                var end = today;
                                this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change', start, end);
                                this.ownerCt.ownerCt.ownerCt.items.items[1].setText('5 year');
                                this.ownerCt.ownerCt.ownerCt.items.items[2].setText('<b>' + self.timeframe.start_date + ' to ' + self.timeframe.end_date + '</b>');
                            }
                        }
                    },
                    {
                        text: 'Report default',
                        checked: timeframe_label === 'Report default',
                        group: 'timeframe',
                        listeners: {
                            click: function (comp) {
                                this.ownerCt.ownerCt.ownerCt.ownerCt.fireEvent('timeframe_change');
                                this.ownerCt.ownerCt.ownerCt.items.items[1].setText('Report default');
                                this.ownerCt.ownerCt.ownerCt.items.items[2].setText('');
                            }
                        }
                    }]
                },
                {
                    xtype: 'tbtext',
                    text: (self.timeframe.start_date !== null && self.timeframe.end_date !== null ? '<b>' + self.timeframe.start_date + ' to ' + self.timeframe.end_date + '</b>' : '')
                },
                '->',
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
        };
        XDMoD.Module.Dashboard.ReportThumbnailsComponent.superclass.initComponent.apply(this, arguments);
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
Ext.reg('xdmod-dash-reportthumb-cmp', XDMoD.Module.Dashboard.ReportThumbnailsComponent);
