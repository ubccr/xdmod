Ext.namespace('XDMoD.Reporting');

XDMoD.Reporting.ReportEntryTypeMenu = Ext.extend(Ext.menu.Menu,  {

   reportCreatorPanel: null,
   config: '',

   // -----------------------------

   // setCreatorPanel:  used to assign the instance of ReportCreator (reference) so the ChartDateEditor knows
   //                   what store to consult during date updates.

   setCreatorPanel: function(creatorPanel) {

      this.reportCreatorPanel = creatorPanel;

   },

   present: function(evt, original_type, config) {

      this.config = config;

      var x_offset = (evt.pageX) ? evt.pageX : evt.clientX;
      var y_offset = (evt.pageY) ? evt.pageY : evt.clientY;

      this.optionDisplayAsChart.setDisabled(original_type == 'image');
      this.optionDisplayAsDatasheet.setDisabled(original_type == 'datasheet');

      this.showAt([x_offset, y_offset]);

   },

   initComponent: function(){

      var self = this;

      self.optionDisplayAsChart = new Ext.menu.Item({

         disabled: true,
         text: 'View As Chart',
         value: 'image',
         iconCls: 'icon_chart'

      });//self.optionDisplayAsChart

      self.optionDisplayAsDatasheet = new Ext.menu.Item({

         disabled: true,
         text: 'View As Table',
         value: 'datasheet',
         iconCls: 'icon_table'

      });//self.optionDisplayAsDatasheet

      // -------------------------------------------------------------------

      Ext.apply(this, {

         iconCls: 'custom_date',
         cls: 'chart_date_editor',

         items: [
            self.optionDisplayAsChart,
            self.optionDisplayAsDatasheet
         ],

         listeners: {

            itemclick: {

               fn: function(baseItem, e) {

                  var store = this.reportCreatorPanel.reportCharts.reportStore;

                  var localContent = {};
                  localContent.queue = [];

                  store.data.each(function() {

                     if (self.config.chart_id == this.data['chart_id']) {

                        this.data['type'] = baseItem.value.toString();

                     }//if (config.chart_id == this.data['chart_id'])

                     localContent.queue.push(this.data);

                  });//store.data.each

                  store.loadData(localContent);

                  self.reportCreatorPanel.dirtyConfig();

               }

            }//itemclick

         }//listeners

      });

       XDMoD.Reporting.ReportEntryTypeMenu.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.Reporting.ReportEntryTypeMenu
