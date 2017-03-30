Ext.namespace('XDMoD');

XDMoD.AvailableCharts = Ext.extend(Ext.Panel,  {

   qstore: null,
   modal: true,

   minSize: 500,

   resizable:true,
   closeAction:'close',
   cls: 'available_charts',

   plugins: [
      new XDMoD.Plugins.ContextSensitiveHelper('available+charts')
   ],

   reportStore: null,

   reloadQueue: function() { this.reportStore.reload(); },

   initComponent: function(){

      var self = this;

       var footer_text = new Ext.Toolbar.TextItem({
          cls: 'fbar_report_chart_status',
          text: 'No charts'
       });

      // ----------------------------------------------------

      this.reportStore = new Ext.data.JsonStore({

         autoDestroy: false,
         id: Ext.id(),
         root: 'queue',
         baseParams: { operation: 'enum_available_charts' },
         fields: [
            'chart_id',
            'thumbnail_link',
            'chart_title',
            'chart_drill_details',
            'chart_date_description',
            //'timeframe_details',
            'type',
            'timeframe_type'
         ],
         valueField: 'chart_id',

         proxy: new Ext.data.HttpProxy({
            method: 'POST',
            url: 'controllers/report_builder.php'
         }),

         listeners: {

            load: {

               fn: function(store, records, options) {

                  btnDeleteChart.setDisabled(true);

                  updateChartCountText();

                   /*
                  if (store.getTotalCount() > 0)
                     self.hideMask();
                  else
                     self.showMask();
                   */

               }

            }

         }

      });//this.reportStore

      // ----------------------------------------------------

       var updateChartCountText = function() {

         var suffix = (self.reportStore.getTotalCount() != 1) ? 's' : '';
         var prefix = (self.reportStore.getTotalCount() != 0) ? self.reportStore.getTotalCount() : 'No';
         footer_text.setText(prefix + ' chart' + suffix);

       };//updateChartCountText

      // ----------------------------------------------------

      var queueAction = function(action) {

         var objParams = { operation: action };
         var conn = new Ext.data.Connection();

         conn.request({

              url: 'controllers/report_builder.php',
             params: objParams,
             method: 'POST',

            callback: function(options, success, response) {
               if (success) {
                  success = CCR.checkJSONResponseSuccess(response);
               }

               if (success) {
                  self.reportStore.reload();
               } else {
                  CCR.xdmod.ui.presentFailureResponse(response, {
                     title: 'Chart Pool',
                     wrapperMessage: 'There was a problem regarding the chart pool.'
                  });
               }
            } //callback

         });//conn.request

        };//queueAction

      // ----------------------------------------------------

      var grid_rowclick_suppression_flag = false;

      var checkBoxSelMod = new Ext.grid.CheckboxSelectionModel({

         singleSelect: false,

         listeners:{

            beforerowselect: function(selectionModel, rowIndex, keepExisting, record){

               grid_rowclick_suppression_flag = true;

            },

            rowselect : function( selectionModel, rowIndex, record){

               var selectedRows = selectionModel.getSelections();

               btnDeleteChart.setDisabled(selectedRows.length == 0);

            },//rowselect

            rowdeselect : function( selectionModel, rowIndex, record){

               var selectedRows = selectionModel.getSelections();

               btnDeleteChart.setDisabled(selectedRows.length == 0);

            }//rowdeselect

         }//listeners

      });//checkBoxSelMod

      // ----------------------------------------------------

      var gridEmptyText = '<b>No charts have been added.</b><br/><br/>' +
                          'Charts can be added in one of the following ways:<br/><br/>' +

                          '<div style="padding: 5px; background-color: #faffc2; border: 1px solid #ccc">' +
                          'Go to the <b>Usage</b> tab, navigate to a chart of interest, then ' +
                          'check the <b>Available For Report</b> checkbox in the top toolbar.</div>' +

                          '<br/>' +

                          '<div style="padding: 5px; background-color: #faffc2; border: 1px solid #ccc">' +
                          'Go to the <b>Metric Explorer</b> tab to generate a chart, then ' +
                          'check the <b>Available For Report</b> checkbox in the top toolbar.</div>' +

                          '<br/><br/><center><img src="gui/images/available_for_report.png"></center><br /><br/>' +

                          CCR.xdmod.ui.createUserManualLink('chart+configuration+toolbar');

      var queueGrid = new Ext.grid.GridPanel({

         store: this.reportStore,
         viewConfig: { forceFit: true, emptyText: gridEmptyText },
            width: 300,
         autoScroll: true,

         enableHdMenu: false,
         selModel : checkBoxSelMod,

         ddGroup: 'reportChartSource',
         ddText: 'Drag To <b>Included Charts</b>',
         enableDragDrop: true,

         columns: [
            //checkBoxSelMod,
            {header: 'Chart ID', width: 10, dataIndex: 'chart_id', sortable: false, hidden: true},
            {header: 'Chart', width: 200, renderer: XDMoD.Reporting.chartDetailsRenderer,  sortable: true}
         ],

         loadMask: true

      });//queueGrid

      queueGrid.getSelectionModel().on('rowselect', function(sm, row_index, rec) {
         var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(rec);
         XDMoD.TrackEvent('Report Generator (Available Charts)', 'Entry selected', Ext.encode(trackingConfig));
      });

      queueGrid.getSelectionModel().on('rowdeselect', function(sm, row_index, rec) {
         var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(rec);
         XDMoD.TrackEvent('Report Generator (Available Charts)', 'Entry de-selected', Ext.encode(trackingConfig));
      });

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

      //queueGrid.getView().dragZone.getDragData

      // ----------------------------------------------------

      var deleteChart = function(){

         var sm = queueGrid.getSelectionModel();
         var getData = queueGrid.getSelectionModel().getSelections();

         if (sm.hasSelection()){

            var submessage = (getData.length > 1) ? 'these selected charts' : 'this selected chart';
            var plural_suffix = (getData.length > 1) ? 's' : '';

            Ext.Msg.show({

               maxWidth: 800,
               minWidth: 400,
               title: 'Delete Selected Chart' + plural_suffix,
               msg: 'Are you sure you want to delete ' + submessage + '?',
               buttons: Ext.Msg.YESNO,
               fn: function(resp) {

                  if (resp == 'yes') {

                     var selected_chart = [];

                     var objParams = { operation: 'remove_chart_from_pool' };

                     for (var i = 0; i < getData.length; i++) {

                        var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(getData[i]);
                        XDMoD.TrackEvent('Report Generator (Available Charts)', 'Confirmed deletion of chart entry', Ext.encode(trackingConfig));

                        objParams['selected_chart_' + i] = getData[i].get('chart_id');

                     }//for

                     var conn = new Ext.data.Connection();

                     conn.request({

                        url: 'controllers/report_builder.php',
                        params: objParams,
                        method: 'POST',

                        callback: function (options, success, response) {
                           var json;
                           if (success) {
                              json = CCR.safelyDecodeJSONResponse(response);
                              success = CCR.checkDecodedJSONResponseSuccess(json);
                           }

                           if (success) {
                              self.reportStore.reload();

                              for (var module_id in XDMoD.Reporting.CheckboxRegistry) {

                                 if (json.dropped_entries[module_id] !== undefined) {

                                    for (d = 0; d < json.dropped_entries[module_id].length; d++) {

                                       if (json.dropped_entries[module_id][d] == XDMoD.Reporting.CheckboxRegistry[module_id].chart_id) {

                                          XDMoD.Reporting.CheckboxRegistry[module_id].ref.silentCheckToggle(false);

                                       }

                                    }//for

                                 }//if

                              }//for
                           } else {
                              CCR.xdmod.ui.presentFailureResponse(response, {
                                 title: 'Chart Pool',
                                 wrapperMessage: 'There was a problem regarding the chart pool.'
                              });
                           }
                        }//callback

                     });//conn.request

                  }//if (resp == 'yes')
                  else {

                     XDMoD.TrackEvent('Report Generator (Available Charts)', 'Cancelled delete confirmation dialog');

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

      };//deleteChart

      // ----------------------------------------------------

      var btnSelectMenu = new Ext.Button({

         iconCls: 'btn_select',
         text: 'Select',

         menu: new Ext.menu.Menu({

            items: [

               {
                  text: 'All Charts',
                  iconCls: 'btn_all_charts',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (Available Charts)', 'Clicked on Select -> All Charts');
                     queueGrid.getSelectionModel().selectAll();

                  }

               },

               {
                  text: 'No Charts',
                  iconCls: 'btn_no_charts',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (Available Charts)', 'Clicked on Select -> No Charts');
                     queueGrid.getSelectionModel().clearSelections();

                  }

               },

               {

                  text: 'Invert Selection',
                  iconCls: 'btn_invert_selection',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (Available Charts)', 'Clicked on Select -> Invert Selection');

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

      var btnDeleteChart = new Ext.Button({

         iconCls: 'btn_delete',
         text: 'Delete',
         disabled: true,

         handler: function() {

            XDMoD.TrackEvent('Report Generator (Available Charts)', 'Clicked on the Delete button');
            deleteChart();

         }

      });

      // ----------------------------------------------------

      Ext.apply(this, {

         title: 'Available Charts',
         layout: 'fit',

         id : 'chart_pool_panel',

         items:[queueGrid],

         tbar: {
            items: [
              btnSelectMenu,
               '->',
              btnDeleteChart
            ]
         },

         bbar: {

            items: [
               footer_text
            ]

         }

      });//Ext.apply

      this.reportStore.reload();
       XDMoD.AvailableCharts.superclass.initComponent.call(this);

   },//initComponent

   onRender : function(ct, position){
      this.reportStore.reload();
      XDMoD.AvailableCharts.superclass.onRender.call(this, ct, position);
   }


});//XDMoD.AvailableCharts
