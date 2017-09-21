Ext.namespace('XDMoD.Reporting');

// XDMoD.Reporting.ChartDateEditor
//
// Panel for editing / updating of timeframes for charts in any given report.

// ============================================================

function dateRangeValidator(start_date, end_date) {

   var date_utils = new DateUtilities();

   date_utils.isValidDateFormat(start_date);

   if (!date_utils.isValidDateFormat(start_date)) {
      return {success: false, message: 'Valid start date required' };
   }

   if (!date_utils.isValidDateFormat(end_date)) {
      return {success: false, message: 'Valid end date required' };
   }

   if (start_date >= end_date) {
      return {success: false, message: 'The start date must be earlier than the end date' };
   }

   if (start_date > date_utils.getCurrentDate()) {
      return {success: false, message: 'The start date cannot be ahead of today' };
   }

   return {success: true};

}//dateRangeValidator

// ============================================================

XDMoD.Reporting.ChartDateEditor = Ext.extend(Ext.Window,  {

   title: 'Edit Chart Timeframe',
   width: 300,
   height: 110,
   resizable: false,
   modal: true,
   draggable: false,
   layout: 'fit',
   bodyStyle: 'padding: 5px;',
   closeAction: 'hide',

   cls: 'chart_date_editor',

   activeChartID: -1,
   reportCreatorPanel: null,

   // -----------------------------

   // setCreatorPanel:  used to assign the instance of ReportCreator (reference) so the ChartDateEditor knows
   //                   what store to consult during date updates.

   setCreatorPanel: function(creatorPanel) {

      this.reportCreatorPanel = creatorPanel;

   },

   getDefaultChartTimeframe: function(chart_id) {

      var recorded_timeframe_label = XDMoD.Reporting.getParamIn('timeframe_label', chart_id, '&');

      var timeframe = {};

      // ====================================

      if (recorded_timeframe_label.toLowerCase() == 'user defined') {

         // If the original timeframe was user-defined, then the respective start/end dates will be found
         // in the chart id itself

         timeframe.start_date = XDMoD.Reporting.getParamIn('start_date', chart_id, '&');
         timeframe.end_date = XDMoD.Reporting.getParamIn('end_date', chart_id, '&');

      }
      else {

         var date_utils = new DateUtilities();

         var endpoints = date_utils.getEndpoints(recorded_timeframe_label);

         timeframe.start_date = endpoints.start_date;
         timeframe.end_date = endpoints.end_date;

      }

      timeframe.type = recorded_timeframe_label;

      return timeframe;

   },

   reset: function (config, store_id, record_id) {

      var default_timeframe = this.getDefaultChartTimeframe(config.chart_id);

      var trackingConfig = XDMoD.Reporting.GetTrackingConfig(store_id, record_id, {

         original_chart_timeframe_type: default_timeframe.type,
         original_start_date: default_timeframe.start_date,
         original_end_date: default_timeframe.end_date

      });

      XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on the Reset Timeframe icon', Ext.encode(trackingConfig));

      // ====================================

      this.updateChartEntry({
         chart_id: config.chart_id,
         timeframe_type: default_timeframe.type,
         start_date: default_timeframe.start_date,
         end_date: default_timeframe.end_date
      });

      this.reportCreatorPanel.dirtyConfig();

   },

   updateChartEntry: function (config) {

      // Targets an entry in the store (associated with the 'Included Charts' grid) by config.chart_id, and
      // updates that entry's timeframe information, chart date description, and chart thumbnail link with the
      // proper information (denoted by the remaining parameters in config:)

      // config:  {chart_id: ..., timeframe_type: ..., start_date: ...., end_date: ... }

      // Only the local (cached) data of the store is altered, then reloaded so the grid bound to the store gets
      // updated with these (local) changes.

      var store = this.reportCreatorPanel.reportCharts.reportStore;

      var localContent = {};
      localContent.queue = [];

      var editor_ref = this;

      store.data.each(function() {

         if (config.chart_id == this.data['chart_id']) {

            // Update chart date description and thumbnail link so the grid cell renderer can show the updated timeframe and thumbnail

            this.data['timeframe_type'] = config.timeframe_type;
            this.data['chart_date_description'] = config.start_date + ' to ' + config.end_date;

            var renderer_params = '&start=' + config.start_date + '&end=' + config.end_date;

            // Case when a chart, already saved to a report, has its timeframe adjusted (and prior to re-saving with the new timeframe)

            this.data['thumbnail_link'] = this.data['thumbnail_link'].replace('type=report', 'type=cached' + renderer_params);
            this.data['thumbnail_link'] = this.data['thumbnail_link'].replace(/type=cached&start=(.+)&end=(.+)&ref=/, 'type=cached' + renderer_params + '&ref=');

            // Case when a chart is dragged in from the 'Available Charts' area and, prior to saving the report, the timeframe has been adjusted.

            if (this.data['thumbnail_link'].indexOf('start=') == -1)
               this.data['thumbnail_link'] = this.data['thumbnail_link'].replace('type=volatile', 'type=volatile' + renderer_params);

            this.data['thumbnail_link'] = this.data['thumbnail_link'].replace(/type=volatile&start=(.+)&end=(.+)&ref=/, 'type=volatile' + renderer_params + '&ref=');

         }//if (config.chart_id == this.data['chart_id'])

         localContent.queue.push(this.data);

      });//store.data.each

      store.loadData(localContent);

   },

   mnuPeriodicTimeframe: null,

   start_date_field: null,
   end_date_field: null,

   // -----------------------------

   initComponent: function(){

      var self = this;

      var today = new Date();

      var date_utils = new DateUtilities();

      // ---------------------------

      self.getMode = function() {

         if (rdoGrpTimeframeMode.items.get(0).getValue())
            return 'Specific';

         if (rdoGrpTimeframeMode.items.get(1).getValue())
            return 'Periodic';

      };//self.getMode

      // ---------------------------

      self.present = function(config, store_id, record_id, batch_mode) {

         var coords = Ext.EventObject.getXY();

         var x_offset = coords[0];
         var y_offset = coords[1];

         var chart_config;

         this.show();
         this.setPagePosition(x_offset, y_offset - this.height - 10);

         // ================================

        if(batch_mode == undefined) batch_mode = false;
        this.batchMode = batch_mode;

        if (batch_mode == true) {

             this.setTitle('Edit Multiple Chart Timeframes');

             this.batchItems = config;

             XDMoD.TrackEvent('Report Generator (Report Editor)', 'Opened Chart Date Editor (batch mode)', this.batchItems.length + ' entries');

             chart_config = config[0];

        }
        else {

            var trackingConfig = XDMoD.Reporting.GetTrackingConfig(store_id, record_id);
            XDMoD.TrackEvent('Report Generator (Report Editor)', 'Opened Chart Date Editor', Ext.encode(trackingConfig));

            this.setTitle('Edit Chart Timeframe');

            this.activeChartID = config.chart_id;

            chart_config = config;

        }

        /* eslint-disable */
        if (chart_config.type && chart_config.type.toLowerCase() === 'user defined') {
            rdoGrpTimeframeMode.items.get(0).setValue(true);
            this.start_date_field.setValue(chart_config.start);
            this.end_date_field.setValue(chart_config.end);
        } else {
            rdoGrpTimeframeMode.items.get(1).setValue(true);
            this.mnuPeriodicTimeframe.setText(chart_config.window);
        }
        /* eslinit-enable */
      };//present

      // ---------------------------

      self.mnuPeriodicTimeframe = new Ext.Button({

         scope: this,
         width: 90,
         text: 'Month To Date',
         tooltip: 'Set the periodic window',

         cls: 'no-icon-menu',

         menu: new Ext.menu.Menu({

               plain: true,
               showSeparator: false,
               cls: 'no-icon-menu',

               items: [
                  {text: 'Yesterday'},
                  {text: '7 Day'},
                  {text: '30 Day'},
                  {text: '90 Day'},
                  {text: 'Month To Date <b style="color: #00f">('    + date_utils.displayTimeframeDates('Month To Date')     + ')</b>'},
                  {text: 'Quarter To Date <b style="color: #00f">('  + date_utils.displayTimeframeDates('Quarter To Date')   + ')</b>'},
                  {text: 'Year To Date <b style="color: #00f">('     + date_utils.displayTimeframeDates('Year To Date')      + ')</b>'},
                  {text: 'Previous Month <b style="color: #00f">('   + date_utils.getPreviousMonthName()                     + ')</b>'},
                  {text: 'Previous Quarter <b style="color: #00f">(' + date_utils.displayTimeframeDates('Previous Quarter')  + ')</b>'},
                  {text: 'Previous Year <b style="color: #00f">('    + (today.getFullYear() - 1)                             + ')</b>'},
                  {text: '1 Year'},
                  {text: '2 Year'},
                  {text: '3 Year'},
                  {text: '5 Year'},
                  {text: '10 Year'},
                  {text: today.getFullYear()},
                  {text: today.getFullYear() - 1},
                  {text: today.getFullYear() - 2},
                  {text: today.getFullYear() - 3},
                  {text: today.getFullYear() - 4},
                  {text: today.getFullYear() - 5},
                  {text: today.getFullYear() - 6}
               ],

               listeners: {

                  itemclick: {

                     fn: function(baseItem, e) {

                        var selectedItem = baseItem.text.toString().split(' <b')[0];

                        XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart Date Editor -> Selected Periodic Timeframe from drop-down menu', selectedItem);

                        self.mnuPeriodicTimeframe.setText(selectedItem);

                     }

                  }

               }

            })

      });//mnuPeriodicTimeframe

      this.start_date_field = new Ext.form.DateField({ height: 30, width: 90, format: 'Y-m-d', id: 'report_generator_edit_date_start_date_field' });
      this.end_date_field = new Ext.form.DateField({ height: 30, width: 90, format: 'Y-m-d', id: 'report_generator_edit_date_end_date_field' });

      this.start_date_field.on('select', function(dp, sel_date){
         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart Date Editor -> Selected start date from date picker', dp.getRawValue());
         dp.startValue = sel_date;
      });

      this.end_date_field.on('select', function(dp, sel_date){
         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart Date Editor -> Selected end date from date picker', dp.getRawValue());
         dp.startValue = sel_date;
      });

      this.start_date_field.on('change', function(dp, new_val, old_val){
         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart Date Editor -> Manually entered start date', dp.getRawValue());
      });

      this.end_date_field.on('change', function(dp, new_val, old_val){
         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart Date Editor -> Manually entered end date', dp.getRawValue());
      });

      var pnlTimeframeMode = new Ext.Panel({

         region: 'center',
         height: 30,
         layout: 'card',
         activeItem: 1,
         border: false,

         baseCls:'x-plain',

         items: [

            new Ext.Panel({

               anchor:'100%',
               baseCls:'x-plain',
               layout:'hbox',

               defaults: {
                  margins:'7 0 0 4'
               },

               items: [
                  self.mnuPeriodicTimeframe
               ]

            }),

            new Ext.Panel({

               anchor:'100%',
               baseCls:'x-plain',
               layout:'hbox',

               defaults: {
                  margins:'8 0 0 4'
               },

               items: [
                  self.start_date_field,
                  {xtype: 'tbtext', text: 'to', margins: '10 0 0 3' },
                  self.end_date_field
               ]

            })

         ]

      });

      // -------------------------------------------------------------------

      var presentOverlay = function(message, customdelay) {

         var delay = customdelay ? customdelay : 2000;

         btnCancel.setDisabled(true);
         btnUpdate.setDisabled(true);

         cPanel.getEl().mask('<div class="overlay_message" style="color: #f00">' + message + '</div>');

         (function() {

            btnCancel.setDisabled(false);
            btnUpdate.setDisabled(false);
            cPanel.getEl().unmask();

         }).defer(delay);

      };//presentOverlay

      // -------------------------------------------------------------------
      /* eslint-disable */
      var btnCancel = new Ext.Button({
        iconCls: 'chart_date_editor_cancel_button',
        text: 'Cancel',
        handler: function() { 
            self.hide(); 
        }
      });//btnCancel
      /* eslint-enable */

      // -------------------------------------------------------------------

      var btnUpdate = new Ext.Button({

         iconCls: 'chart_date_editor_update_button',

         text: 'Update',

         handler: function(){

            XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart Date Editor -> Clicked on Update button');

            var new_start_date = '';
            var new_end_date = '';
            var new_timeframe_label = '';

            // ====================================

            if (self.getMode() == 'Specific') {

               var response = dateRangeValidator(
                        self.start_date_field.getRawValue(),
                        self.end_date_field.getRawValue()
               );

               if (response.success == false) {

                  presentOverlay(response.message);
                  //alert(response.message);
                  return;
               }

               new_timeframe_label = 'User Defined';
               new_start_date = self.start_date_field.getRawValue();
               new_end_date = self.end_date_field.getRawValue();

            }//if (Specific)

            // ====================================

            if (self.getMode() == 'Periodic') {

               var endpoints = date_utils.getEndpoints(self.mnuPeriodicTimeframe.getText());

               new_timeframe_label = self.mnuPeriodicTimeframe.getText();
               new_start_date = endpoints.start_date;
               new_end_date = endpoints.end_date;

            }//if (Periodic)

            // ====================================

            XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated timeframe of selected chart(s)', new_start_date + ' to ' + new_end_date + ' (' + new_timeframe_label + ')');

            if (self.batchMode == true) {

            for (var c = 0; c < self.batchItems.length; c++) {

               self.updateChartEntry({
                  chart_id: self.batchItems[c].chart_id,
                  timeframe_type: new_timeframe_label,
                  start_date: new_start_date,
                  end_date: new_end_date
               });

            }

            }
            else {

               self.updateChartEntry({
                  chart_id: self.activeChartID,
                  timeframe_type: new_timeframe_label,
                  start_date: new_start_date,
                  end_date: new_end_date
               });

            }

            self.hide();
            self.reportCreatorPanel.dirtyConfig();

         }//handler

      });//btnUpdate

      // -------------------------------------------------------------------

      var rdoGrpTimeframeMode = new Ext.form.RadioGroup({

         height: 200,
         defaultType: 'radio',
         columns: 1,
         cls: 'custom_search_mode_group',
         width: 70,
         vertical: true,
         region:'west',

         items: [

            {
               boxLabel: 'Specific',
               checked: true,
               name: 'report_creator_chart_entry',
               inputValue: 'Specific'
            },

            {
               boxLabel: 'Periodic',
               name: 'report_creator_chart_entry',
               inputValue: 'Periodic'
            }

         ],

         listeners: {

            change: function(rg, rc) {

               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Chart Date Editor -> Selected ' + rc.inputValue + ' option');

               if (rc.inputValue == 'Specific') pnlTimeframeMode.getLayout().setActiveItem(1);
               if (rc.inputValue == 'Periodic') pnlTimeframeMode.getLayout().setActiveItem(0);

            }

         }

      });

      var cPanel = new Ext.Panel({

         layout: 'border',
         border: false,

         items: [

            rdoGrpTimeframeMode,
            pnlTimeframeMode

         ]//items

      });//cPanel

      self.on('hide', function() {

         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Closed Chart Date Editor');

      });

      // -------------------------------------------------------------------

      Ext.apply(this, {

         iconCls: 'custom_date',
         cls: 'chart_date_editor',

         items: [

            cPanel

         ],

         bbar: {

            items: [

               btnUpdate,
               '->',
               btnCancel

            ]//items

         }//bbar

      });

      XDMoD.Reporting.ChartDateEditor.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.Reporting.ChartDateEditor
