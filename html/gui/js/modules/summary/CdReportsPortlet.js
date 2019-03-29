/**
 * XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet
 *
 */

Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet = Ext.extend(Ext.Panel, {

    layout: 'fit',
    header: false,
    tbar: {
        items: [
            'Time Range:',
            ' ',
            {
                text: 'Previous Quarter'
            },
            {
                text: 'Previous Year'
            },
            {
                xtype: 'splitbutton',
                text: 'Custom'
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
        var aspectRatio = 0.6;

        var self = this;

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
                            for (var index = 0; index < v_split.length; index++){
                                    tmpk = v_split[index].split('=')[0];
                                    tmpv = v_split[index].split('=')[1];
                                    params[tmpk] = tmpv             
                            }
                            value = '/report_image_renderer.php?' + 'type=' + 'cached' + '&ref=' + params['ref'];
                            value = value + '&start=' + self.extraParams['start_date'] + '&end=' + self.extraParams['end_date'];
                            // return '<b style="font-size: 8px;">'+rec.chart_title.split(':')[0]+'</b><br><b style="font-size: 8px;">'+rec.chart_title.split(':')[1]+'</b><br><img src=' + value + ' alt="Image" width="150" height="100">';
                            return '<b style="font-size: 12px;">'+rec.chart_title.split(':')[1]+'</b><br><img src=' + value + ' alt="Image" width="400" height="266">';


                        } else {
                            var value = v.replace("&token=", "")
                            return '<b style="font-size: 12px;">'+rec.chart_title.split(':')[0]+'</b><br><b style="font-size: 12px;">'+rec.chart_title.split(':')[1]+'</b><br><img src=' + value + '" alt="Image" width="400" height="266">';

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
        

        var panel = new Ext.Panel({
            height: 600,
            border: false,
            header: false,
            layout: 'fit',

            items: [

                {
                region:'center',
                margins:'35 5 5 0',
                layout:'column',
                autoScroll:true,
                items:[{


                columnWidth:.35, 
                items: [{
                    itemId: 'cdThumbnails',
                    xtype: 'grid',
                    height: 600,
                    store: self.cdCharts,
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
                                            console.log(cdChartCenter.items);
                                            cdChartCenter.items.add(this);
                                            self.doLayout();
                            

                                        }
                                    }

                                }); // hcp

                                split_values = r.json.chart_id.split('&');
                                config = {}
                                tmpHpc.store.removeAll();
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
                                    console.log('duration_change');
                                    config['start_date'] = self.extraParams['start_date'];
                                    config['end_date'] = self.extraParams['end_date'];
                                    config['timeframe_label'] = 'User Defined';
                                    tmpHpc.store.setBaseParam('start_date', self.extraParams['start_date']);
                                    tmpHpc.store.setBaseParam('end_date', self.extraParams['end_date']);
                                    tmpHpc.store.setBaseParam('timeframe_label', 'User Defined');

                                    console.log(config);

                                } else {
                                    console.log('non duration change');
                                    var startDate = r.json['chart_date_description'].split(' to ')[0]
                                    var endDate = r.json['chart_date_description'].split(' to ')[1]
                                    
                                    config['start_date'] = startDate;
                                    config['end_date'] = endDate;
                                    config['timeframe_label'] = 'User Defined';
                                    tmpHpc.store.setBaseParam('start_date', startDate);
                                    tmpHpc.store.setBaseParam('end_date', endDate)
                                    console.log(config);


                                }
                                self.config = config;
                                tmpHpc.fireEvent('load', cdChartCenter);
                            }
                        }
                    })
                }],
                margins: '0 0 0 0'
            },{
                        columnWidth:.65, 
                        height: 600,
                        bodyStyle:'padding:5px 0 5px 5px',
                        itemId: 'cdChartCenter',
                        xtype: 'panel',
                        html: 'Please select a thumbnail from the left column!',
                        // margins: '5 5 5 5',
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
                        
                    }]
                }

            ]


        });

        this.items = [panel];

        XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet.superclass.initComponent.apply(this, arguments);
    }
});

/**
 * The Ext.reg call is used to register an xtype for this class so it
 * can be dynamically instantiated
 */
Ext.reg('ChartThumbnailPortlet', XDMoD.Modules.SummaryPortlets.ChartThumbnailPortlet);
