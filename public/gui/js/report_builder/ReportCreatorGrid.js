/* eslint-disable indent, no-trailing-spaces, no-use-before-define */

Ext.namespace('XDMoD');

XDMoD.ReportCreatorGrid = Ext.extend(Ext.Panel,  {

   id: 'ReportCreatorGrid',
   qstore: null,
   modal: true,
   resizable:true,
   closeAction:'close',
   cls: 'report_editor_charts',

   reportStore: null,
   reportChartsGrid: null,   //References queueGrid

   initComponent: function(){

      var self = this;

       var footer_text = new Ext.Toolbar.TextItem({
          cls: 'fbar_report_chart_status',
          text: 'No charts'
       });

       var updateChartCountText = function() {
         var suffix = (queueGrid.store.data.length != 1) ? 's' : '';
         var prefix = (queueGrid.store.data.length != 0) ? queueGrid.store.data.length : 'No';
         footer_text.setText(prefix + ' chart' + suffix);
       };

      // ===========================================================

      self.resetChartCount = function() {

         updateChartCountText();

      };

      this.reportStore = new Ext.data.JsonStore({

         storeId: 'report_generator_included_charts_store',
         autoDestroy: false,
         root: 'queue',

         fields: [
            'chart_id',
            'thumbnail_link',
            'chart_title',
            'chart_drill_details',
            'chart_date_description',
            //'timeframe_details',
            'type',
            'timeframe_type',
            'duplicate_id'
         ],

         listeners: {

            load: {

               fn: function(store, records, options) {
                  updateChartCountText();
               }

            }

         }

         /*,
         proxy: new Ext.data.HttpProxy({
            method: 'POST',
            url: 'controllers/report_builder.php'
         })
         */

      });//this.reportStore

      // ===========================================================

      // Can bypass the use of a proxy by returning 'false' in the 'beforeload' event handler

      this.reportStore.on('beforeload', function(s, o) {

         return false;

      });

      // ---------------------------------------------------------------

      var grid_rowclick_suppression_flag = false;

      var checkBoxSelMod = new Ext.grid.CheckboxSelectionModel({

         singleSelect: false,

         listeners:{

            beforerowselect: function(selectionModel, rowIndex, keepExisting, record){

               grid_rowclick_suppression_flag = true;

            },

            rowselect : function( selectionModel, rowIndex, record){

               var selectedRows = selectionModel.getSelections();

               btnDeleteCharts.setDisabled(selectedRows.length == 0);
               btnTimeFrameEdit.setDisabled(selectedRows.length < 1);

            },

            rowdeselect : function( selectionModel, rowIndex, record){

               var selectedRows = selectionModel.getSelections();

               btnDeleteCharts.setDisabled(selectedRows.length == 0);
               btnTimeFrameEdit.setDisabled(selectedRows.length < 1);

            }

         }

      });//checkBoxSelMod

      // ---------------------------------------------------------------

      var reportChartsEmptyText = 'No charts are currently associated with this report.<br>' +
                                  'To add charts, drag entries from <b>Available Charts</b> into this area.<br/><br/>' +
                                  '<img src="gui/images/adding_charts_to_report.png"><br /><br />' +
                                  CCR.xdmod.ui.createUserManualLink('selection+model');

      var queueGrid = new Ext.grid.GridPanel({

         id: 'CurrentNewchartbaseTab_queueGrid',
         store: this.reportStore,
         selModel : checkBoxSelMod,
         width: 500,
         height: 460,
         autoScroll: true,
         enableHdMenu: false,

         columns: [
            //checkBoxSelMod,
            {header: 'Chart ID', width: 0, dataIndex: 'chart_id', sortable: false, hidden: true},
            {header: 'Chart', width: 220, renderer: XDMoD.Reporting.chartDetailsRenderer, sortable: false}
         ],

         loadMask: true,

         resizable:true,
         minHeight:460,

         ddGroup: 'reportChartDestination',
         enableDragDrop: true,
         ddText: 'Move to new position in report',
         enableRowBody: true,

         singleSelect:true,

         viewConfig: {
            emptyText: reportChartsEmptyText,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            forceFit: true
         },

         listeners: {

            "render": {

               scope: this,

               fn: function(queueGrid) {

                  var ddrow = new Ext.dd.DropTarget(queueGrid.container, {

                     ddGroup : 'reportChartDestination',
                     copy:false,

                     // Drag-drop within same grid (for reordering purposes)

                     notifyDrop : function(dd, e, data){

                        var ds = queueGrid.store;
                        var sm = queueGrid.getSelectionModel();
                        var rows = sm.getSelections();

                        if(rows.length > 1) {
                           CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'Drag and drop re-ordering works with one chart at a time.');
                           return;
                        }

                        var destination_slot = '';

                        if(dd.getDragData(e)) {

                           var cindex = dd.getDragData(e).rowIndex;

                           if(typeof(cindex) != "undefined") {

                              destination_slot = cindex;

                              for(i = 0; i < rows.length; i++) {
                                 ds.remove(ds.getById(rows[i].id));
                              }

                              ds.insert(cindex,data.selections);
                              sm.clearSelections();

                           }

                           // Tell the parent that the report specifics have changed (dirty state)
                           self.parentRef.dirtyConfig();

                        }//if(dd.getDragData(e))
                        else {

                           destination_slot = 'end of list';

                           // Drop targeted past end of list

                           for(i = 0; i < rows.length; i++)
                              ds.remove(ds.getById(rows[i].id));

                           ds.add(data.selections[0]);
                           sm.clearSelections();

                           // Tell the parent that the report specifics have changed (dirty state)
                           self.parentRef.dirtyConfig();

                        }

                        var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(data.selections[0]);
                            trackingConfig.destination_slot = destination_slot;

                        XDMoD.TrackEvent('Report Generator (Report Editor)', 'Drag-drop re-order', Ext.encode(trackingConfig));

                     }//notifyDrop

                  });//ddrow

                  queueGrid.store.load();

               }//fn

            }//render

         }//listeners

      });//queueGrid

      queueGrid.getSelectionModel().on('rowselect', function(sm, row_index, rec) {
         var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(rec);
         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Included Charts -> entry selected', Ext.encode(trackingConfig));
      });

      queueGrid.getSelectionModel().on('rowdeselect', function(sm, row_index, rec) {
         var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(rec);
         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Included Charts -> entry de-selected', Ext.encode(trackingConfig));
      });

      self.reportChartsGrid = queueGrid;

      queueGrid.store.on('add', function() {
         updateChartCountText();
      });

      queueGrid.store.on('remove', function() {
         updateChartCountText();
      });

      queueGrid.on('render', function() {

         // Allow items from the 'Chart Pool' to be dropped onto this grid.

         var secondGridDropTargetEl = queueGrid.getView().scroller.dom;

         var secondGridDropTarget = new Ext.dd.DropTarget(secondGridDropTargetEl, {

            ddGroup    : 'reportChartSource',

            notifyDrop : function(ddSource, e, data){

               var chartsDropped = false;

               // --------------------------

               Ext.each(data.selections, function(r) {

                  var duplicateFound = false;

                  queueGrid.store.data.each(function() {

                     // If the current entry being considered for addition into a report already exists in the target store (queueGrid),
                     // do not add that entry.

                     if (r.data['chart_id'] == this.data['chart_id']) {
                        duplicateFound = true;
                        return;
                     }

                  });//queueGrid.store.data.each...

                  if(!duplicateFound) {

                     chartsDropped = true;
                     queueGrid.store.add(r);

                     var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(r);
                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart introduced via Available Charts', Ext.encode(trackingConfig));

                  }
                  else {

                     chartsDropped = true;

                     // As of 12/2012, we are now allowing duplicate chart entries in a report (as it is sensible to have
                     // the same metrics viewed over different timeframe slices in the same report).

                     // With that being said, each chart entry in a report must have a unique chart id.  The logic below
                     // ensures that the duplicate chart id "sensed" is transformed into a unique chart id.

                     var d = new Date();
                     var epoch = '_d' + (d.getTime() - d.getMilliseconds())/ 1000;

                     var duplicateRecord = r.copy();

                     duplicateRecord.data['chart_id'] = duplicateRecord.data['chart_id'] + '&duplicate_id=' + epoch;
                     duplicateRecord['id'] = Ext.data.Record.id(r);

                     duplicateRecord.data['duplicate_id'] = epoch;

                     queueGrid.store.add(duplicateRecord);

                     var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(duplicateRecord);
                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Duplicate chart introduced via Available Charts', Ext.encode(trackingConfig));

                  }

               });//Ext.each(data.selections...

               // --------------------------

               // Tell the parent that the report specifics have changed (dirty state)
               if (chartsDropped == true) {
                  self.parentRef.dirtyConfig();
               }
               else {

                  if (data.selections.length > 1)
                     CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'All of the charts you have selected are currently present in this report.');
                  else
                     CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'The chart you have selected is currently present in this report.');

               }

              return true;

            }//notifyDrop

         });//secondGridDropTarget

      });//queueGrid.on('render', ...)

      // ----------------------------------------------------

      queueGrid.on("rowclick", function(grid, rowIndex, e) {

         if (grid_rowclick_suppression_flag == true) {
            grid_rowclick_suppression_flag = false;
            return;
         }


         var target = e.getTarget();

         // Toggle the record selection only if a click is made on the row's checkbox

         var sm = grid.getSelectionModel();

         if (target.className == 'x-grid3-row-checker') {

            if (sm.isSelected(rowIndex))
               sm.deselectRow(rowIndex, false);

         }

      });//queueGrid

      // ----------------------------------------------------

      var deleteSelectedCharts = function(){

         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on the Remove button');

         var sm = queueGrid.getSelectionModel();

         if (sm.hasSelection()){

            var getData = sm.getSelections();

            var submessage = (getData.length > 1) ? 'these selected charts' : 'this selected chart';
            var plural_suffix = (getData.length > 1) ? 's' : '';

            Ext.Msg.show({

               maxWidth: 800,
               minWidth: 400,

               title: 'Remove Selected Chart' + plural_suffix,
               msg: 'Are you sure you want to remove ' + submessage + ' from the report?',
               buttons: Ext.Msg.YESNO,

               fn: function(resp) {

                  if (resp == 'yes') {

                     var selected_chart = [];
                         var getData = queueGrid.getSelectionModel().getSelections();

                     for (var i = 0; i < getData.length; i++) {

                        var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(getData[i]);
                        XDMoD.TrackEvent('Report Generator (Report Editor)', 'Confirmed deletion of chart entry', Ext.encode(trackingConfig));

                        self.reportStore.remove(getData[i]);

                     }//for

                     // Tell the parent that the report specifics have changed (dirty state)
                     self.parentRef.dirtyConfig();

                     self.initGridFunctions();

                  }//if (resp == 'yes')
                  else {

                    XDMoD.TrackEvent('Report Generator (Report Editor)', 'Cancelled chart entry removal confirmation dialog');

                  }

               },

               icon: Ext.MessageBox.QUESTION

            });//Ext.Msg.show

         }
         else {

               Ext.MessageBox.show({

                  title: 'Warning',
                  msg: 'Please Select a Chart',
                  width:150,
                  buttons: Ext.MessageBox.OK

               });

           }

      };//deleteSelectedCharts

      // ----------------------------------------------------

        var resolveDateEndpointsFromChartEntryConfig = function(chartConfig) {

           var date_utils = new DateUtilities();
           var s_date = chartConfig.chart_date_description.split(' to ')[0];
           var e_date = chartConfig.chart_date_description.split(' to ')[1];

           if (chartConfig.timeframe_type.toLowerCase() != 'user defined') {

              var endpoints = date_utils.getEndpoints(chartConfig.timeframe_type);

              s_date = endpoints.start_date;
              e_date = endpoints.end_date;

           }

          return {start_date: s_date, end_date: e_date};

        };//resolveDateEndpointsFromChartEntryConfig

      // ----------------------------------------------------

        var batchEditChartTimeframes = function (selections) {
            var chartEditorConfigs = [];

            for (var i = 0; i < selections.length; i++) {
                var dates = resolveDateEndpointsFromChartEntryConfig(selections[i].data);

                chartEditorConfigs.push({

                    chart_id: selections[i].data.chart_id,
                    type: selections[i].data.timeframe_type,
                    window: selections[i].data.timeframe_type,
                    start: dates.start_date,
                    end: dates.end_date

                });

                var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(selections[i]);
                XDMoD.TrackEvent('Report Generator (Report Editor)', 'Selected Chart for batch timeframe edit', Ext.encode(trackingConfig));
            }

            XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on the Edit Timeframe of Selected Charts button');
            XDMoD.Reporting.Singleton.ChartDateEditor.present(chartEditorConfigs, '', '', true);
        };

      // ----------------------------------------------------

      var Current_NewaddChart = function() {
         var reportNewer_chartPool= new XDMoD.NewChartBaseTab();
         reportNewer_chartPool.show();
      };

      // ----------------------------------------------------

       var btnTimeFrameEdit = new Ext.Button({
          iconCls: 'btn_timeframe_edit',
          text: 'Edit Timeframe of Selected Chart(s)',
          disabled: true,

          handler: function () {
              var sm = queueGrid.getSelectionModel();
              if (sm.hasSelection()) {
                  var getData = sm.getSelections();
                  if (getData.length >= 2) {
                      batchEditChartTimeframes(getData);
                  } else {
                      var select = getData[0];
                      var dates = resolveDateEndpointsFromChartEntryConfig(select.data);

                      var config = {
                          chart_id: select.data.chart_id,
                          type: select.data.timeframe_type,
                          window: select.data.timeframe_type,
                          start: dates.start_date,
                          end: dates.end_date
                      };

                      XDMoD.Reporting.Singleton.ChartDateEditor.present(config, 'report_generator_included_charts_store', select.id, false);
                  }
              } else {
                  Ext.MessageBox.show({
                      title: 'Warning',
                      msg: 'Please select at least 1 chart',
                      width: 150,
                      buttons: Ext.MessageBox.OK
                  });
              }
          }
       });

      var btnSelectMenu = new Ext.Button({

         iconCls: 'btn_select',
         text: 'Select',

         menu: new Ext.menu.Menu({

            items: [

               {
                  text: 'All Charts',
                  iconCls: 'btn_all_charts',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on Select -> All Charts');
                     queueGrid.getSelectionModel().selectAll();

                  }
               },

               {
                  text: 'No Charts',
                  iconCls: 'btn_no_charts',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on Select -> No Charts');
                     queueGrid.getSelectionModel().clearSelections();

                  }
               },

               {

                  text: 'Invert Selection',
                  iconCls: 'btn_invert_selection',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on Select -> Invert Selection');

                     var rowIndex = 0;
                     var currentSelections = queueGrid.getSelectionModel().getSelections();
                     var pendingSelections = [];

                     var map = {};

                     for (var s = 0; s < currentSelections.length; s++)
                        map[currentSelections[s].id] = true;

                     queueGrid.getStore().each(function(rec) {

                        if (map[rec.id] == undefined) pendingSelections.push(rowIndex);
                        rowIndex++;

                     });

                     queueGrid.getSelectionModel().clearSelections();
                     queueGrid.getSelectionModel().selectRows(pendingSelections);

                  }
               }


            ]

         })

      });//btnSelectMenu

      var btnDeleteCharts = new Ext.Button({
         iconCls: 'btn_delete',
         text: 'Remove',
         disabled: true,
         handler: deleteSelectedCharts
      });

      // ----------------------------------------------------

      self.initGridFunctions = function() {

         btnDeleteCharts.setDisabled(true);
         btnTimeFrameEdit.setDisabled(true);

      };
      
      // ----------------------------------------------------

      Ext.apply(this, {

         layout: 'fit',
         autoScroll: true,
         items:[ queueGrid ],

         tbar: {
            items: [
               btnSelectMenu,
               '-',
               btnTimeFrameEdit,
               '->',
               btnDeleteCharts
            ]
         },

         bbar: {

            items: [
               footer_text
            ]

         }

      });//Ext.apply

      //this.reportStore.reload();
      XDMoD.ReportCreatorGrid.superclass.initComponent.call(this);

   },//initComponent

   onRender : function(ct, position){

      XDMoD.ReportCreatorGrid.superclass.onRender.call(this, ct, position);

   }

});//XDMoD.ReportCreatorGrid
