Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    autoScroll: true,
    title: 'Recent Charts and Reports',
    width: 1000,

    initComponent: function () {
        var aspectRatio = 0.8;


        this.chartReportStore = new Ext.data.JsonStore({
            // store configs
            autoDestroy: true,
            url: XDMoD.REST.url + '/summary/chartsreports',
            storeId: 'chartReportStore',
            // reader configs
            root: 'data',
            idProperty: 'name',
            fields: [
                'name',
                'report_id',
                'url',
                'config',
                'type',
                { name: 'recordid', type: 'int' },
                {
                    name: 'ts',
                    convert: function (v, rec) {
                        return Ext.util.Format.date(new Date(rec.ts * 1000).toString(), 'Y-m-d h:i:s');
                    }
                }
            ]
        });
        var searchField = new Ext.form.TwinTriggerField({
            xtype: 'twintriggerfield',
            validationEvent: false,
            validateOnBlur: false,
            trigger1Class: 'x-form-clear-trigger',
            trigger2Class: 'x-form-search-trigger',
            hideTrigger1: true,
            enableKeyEvents: true,
            emptyText: 'Search',
            store: this.chartReportStore,
            onTrigger1Click: function () {
                this.store.clearFilter();
                this.el.dom.value = '';
                this.triggers[0].hide();
            },
            onTrigger2Click: function () {
                var v = this.getRawValue();
                if (v.length < 1) {
                    this.onTrigger1Click();
                    return;
                }
                this.store.filter('name', v, true, true);
                this.triggers[0].show();
            },
            listeners: {
                scope: this,
                specialkey: function (field, e) {
                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.getKey() === e.ENTER) {
                        searchField.onTrigger2Click();
                    }
                }
            }
        });
        this.chartReportGrid = new Ext.grid.GridPanel({
            store: this.chartReportStore,
            border: false,
            monitorResize: true,
            autoScroll: true,
            viewConfig: {
                forceFit: true
            },
            colModel: new Ext.grid.ColumnModel({
                columns: [
                    { header: 'Name', dataIndex: 'name', width: 250 },
                    { header: 'Type', dataIndex: 'type' },
                    { header: 'Last Modified', dataIndex: 'ts' }
                ],
                defaults: {
                    sortable: true,
                    menuDisabled: true
                }
            }),
            tbar: {
                items: [
                    searchField
                ]
            },
            selModel: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    rowselect: function (selModel, index, r) {
                        selModel.clearSelections();
                        if (r.data.type === 'Chart') {                
                            var config = Ext.util.JSON.decode(r.data.config);
                            XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));
                        } else if (r.data.type === 'Report') {
                            CCR.xdmod.ui.reportGenerator.fireEvent('load_report', r.data.report_id);
                        }
                    }
                }
            })
        });
        this.height = this.width * aspectRatio;
        this.items = [this.chartReportGrid];
        this.chartReportStore.reload();
        this.chartReportStore.sort('ts', 'DESC');
        XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        duration_change: function (timeframe) {
            this.chartReportStore.reload();
            this.chartReportStore.sort('ts', 'DESC');
            this.chartReportGrid.getView().refresh();
        }
    }
});

/**
* The Ext.reg call is used to register an xtype for this class so it
* can be dynamically instantiated
*/
Ext.reg('ChartsReportsPortlet', XDMoD.Modules.SummaryPortlets.ChartsReportsPortlet);





XDMoD.Modules.SummaryPortlets.HighChartsPortlet = Ext.extend(Ext.Panel, {

    layout: 'border',
    title: 'Center Director Charts',
    itemId: 'cdChartPanel',

    initComponent: function () {
        var aspectRatio = 0.8;
        var self=this;
        this.hcp = new CCR.xdmod.ui.HighChartPanel({
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

                baseParams: {
                    operation: 'get_data',
                    showContextMenu: false,
                    config: Ext.util.JSON.encode({"data_series":{"data":[{"combine_type":"stack","display_type":"column","filters":{"data":[],"total":0},"group_by":"jobsize","has_std_err":false,"id":2e-14,"ignore_global":false,"log_scale":false,"long_legend":true,"metric":"total_cpu_hours","realm":"Jobs","sort_type":"value_desc","std_err":false,"value_labels":false,"x_axis":false}],"total":1},"global_filters":{"data":[],"total":0},"legend_type":"right_center","limit":20,"show_filters":true,"start":0,"timeseries":true,"title":"","start_date":"2019-02-01","end_date":"2019-02-28","aggregation_unit":"Auto","timeframe_label":"Previous month"}),
                    format: 'hc_jsonstore',
                    public_user: this.public_user,
                    aggregation_unit: "Auto",
                    width: 600,
                    height: 400
                },

                proxy: new Ext.data.HttpProxy({
                    method: 'POST',
                    url: 'controllers/metric_explorer.php'
                })
            })

        }); // hcp


        this.extraParams = {}

        this.cdCharts = new Ext.data.JsonStore({
            // store configs
            autoDestroy: true,
            autoLoad: true,
            url: XDMoD.REST.url + '/summary/cdcharts',
            storeId: 'cdCharts',
            // reader configs
            root: 'data.queue',
            idProperty: 'chart_title',
            fields: [
                'chart_title',
                {
                    name: 'thumbnail_link',
                    convert: function (v, rec) {                       
                        if ('start_date' in self.extraParams && 'end_date' in self.extraParams) {
                            params = {}
                            v_split = v.split('/report_image_renderer.php?')[1].split('&');
                            console.log(v_split);
                            for (var index = 0; index < v_split.length; index++){
                                    tmpk = v_split[index].split('=')[0];
                                    tmpv = v_split[index].split('=')[1];
                                    params[tmpk] = tmpv             
                            }
                            value = '/report_image_renderer.php?' + 'type=' + 'cached' + '&ref=' + params['ref'];
                            value = value + '&start=' + self.extraParams['start_date'] + '&end=' + self.extraParams['end_date'];
                            return '<b style="font-size: 13px;">'+rec.chart_title+'</b><br><img src=' + value + ' alt="Image" width="200" height="133">';

                        } else {
                            var value = v.replace("&token=", "")
                            return '<b style="font-size: 13px;">'+rec.chart_title+'</b><br><img src=' + value + '" alt="Image" width="200" height="133">';

                        }
                    }
                }
            ],
            listeners: {
                load: function () {
                    var cdThumbnails = self.find('itemId', 'cdThumbnails')[0];
                    cdThumbnails.getSelectionModel().selectFirstRow();                   
                }
            }
        });


        this.height = this.width * aspectRatio;


        this.config = {};
        

        this.items = [{
                itemId: 'cdChartCenter',
                region: "center",
                xtype: 'panel',
                html: 'Please select a thumbnail from the left column!',
                margins: '5 5 5 5',
                layout: 'fit',
                tools: [{
                    id: 'gear',
                    hidden: CCR.xdmod.publicUser,
                    qtip: 'Edit in Metric Explorer',
                    scope: this,
                    handler: function (event, toolEl, panel) {
                        var config = this.config;
                        config.font_size = 3;
                        config.title = panel.title;
                        config.featured = true;
                        config.summary_index = (config.preset ? 'summary_' : '') + config.index;
                        XDMoD.Module.MetricExplorer.setConfig(config, config.summary_index, Boolean(config.preset));
                    }
                }],
                items: []
            },{
                region: 'west',
                xtype: 'panel',
                layout: 'fit',
                bodyStyle: '',
                width: 200,
                minSize: 200,
                items: [{
                    itemId: 'cdThumbnails',
                    xtype: 'grid',
                    store: this.cdCharts,
                    hideHeaders: true,
                    autoExpandColumn: 'thumbnail', 
                    colModel: new Ext.grid.ColumnModel({
                        columns: [
                            { dataIndex: 'thumbnail_link', id: 'thumbnail', height: 200 }
                        ],
                        defaults: {
                            sortable: false,
                            menuDisabled: true,
                            fixed: true
                        }
                    }),
                    selModel: new Ext.grid.RowSelectionModel({
                        singleSelect: true,
                        listeners: {
                            rowselect: function (selModel, index, r) {
                                selModel.clearSelections();
                                var cdChartCenter = self.find('itemId', 'cdChartCenter')[0];   
                                cdChartCenter.items.clear();
                                tmpHpc = new CCR.xdmod.ui.HighChartPanel({
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

                                    }),
                                    listeners: {
                                        load: function(cdChartCenter) {
                                            cdChartCenter.items.add(this);
                                            self.doLayout();
                            

                                        }
                                    }

                                }); // hcp

                                split_values = r.json.chart_id.split('&');
                                config = {}
                                for (var x = 0; x < split_values.length; x++) {
                                    var [key, value] = split_values[x].split('=')
                                    if (value) {
                                        if (key === 'global_filters') {
                                            parsed_value = JSON.parse(decodeURIComponent(value));
                                            tmpHpc.store.setBaseParam(key, parsed_value);
                                            config[key] = parsed_value;  

                                        } else if (key === 'data_series') {
                                            parsed_value = JSON.parse(decodeURIComponent(value));
                                            var data_series = {}
                                            data_series["data"] = parsed_value
                                            data_series["total"] = 1
                                            tmpHpc.store.setBaseParam(key, value);
                                            config['data_series'] = data_series
                                        } else {
                                            tmpHpc.store.setBaseParam(key, decodeURIComponent(value));
                                            config[key] = decodeURIComponent(value);  
                                        }

                                    }
                                    
                                }       
                                if ('start_date' in self.extraParams && 'end_date' in self.extraParams) {
                                    config['start_date'] = self.extraParams['start_date'];
                                    config['end_date'] = self.extraParams['end_date'];
                                    config['chart_date_description'] = self.extraParams['start_date'] + ' to ' + self.extraParams['end_date'];
                                    delete config.timeframe_label;
                                    
                                    
                                    tmpHpc.store.setBaseParam('start_date', self.extraParams['start_date']);
                                    tmpHpc.store.setBaseParam('end_date', self.extraParams['end_date'])
                                    tmpHpc.store.setBaseParam('chart_date_description', self.extraParams['start_date'] + ' to ' + self.extraParams['end_date'])
                                    delete tmpHpc.store.timeframe_label;
                                }
                                self.config = config;
                                tmpHpc.fireEvent('load', cdChartCenter);
                            }
                        }
                    })
                }],
                margins: '0 0 0 0'
            }];

        XDMoD.Modules.SummaryPortlets.HighChartsPortlet.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        duration_change: function (timeframe) {
            this.extraParams['start_date'] = timeframe.start_date;
            this.extraParams['end_date'] = timeframe.end_date;
            this.cdCharts.load();
            this.doLayout();
        }
    }
});

/**
* can be dynamically instantiated
*/
Ext.reg('HighChartsPortlet', XDMoD.Modules.SummaryPortlets.HighChartsPortlet);

