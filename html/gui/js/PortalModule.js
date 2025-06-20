/*

   XDMoD.PortalModule

   Author: Ryan Gentner
   Last Updated: Wednesday, June 12, 2013 
   
   Each of the tabs / modules in XDMoD extend XDMoD.PortalModule.  This class provides functional UI components
   which are common among most tabs / modules, such as
   
   - Duration selector
   - Filter dialog (?)
   - Display menu (?)
   - Export menu
   - Add to report checkbox
   
*/

Ext.namespace('XDMoD');

XDMoD.ToolbarItem = {
   
   DURATION_SELECTOR: 1,
   EXPORT_MENU: 2,
   PRINT_BUTTON: 3,
   REPORT_CHECKBOX: 4,
   CHART_LINK_BUTTON: 5,
   OPEN_AS_JUPYTER_NB_BUTTON: 6
   
};//XDMoD.ToolbarItem

// ===========================================================================

XDMoD.ExportOption = {
   
   CSV: 0,
   XML: 1,
   PNG: 2,
   PNG_WITH_TITLE: 3,
   PNG_SMALL: 4,
   PNG_SMALL_WITH_TITLE: 5,
   PNG_HD: 6,
   PNG_HD_WITH_TITLE: 7,
   PNG_POSTER: 8,
   PNG_POSTER_WITH_TITLE: 9, 
   SVG: 10,
   SVG_WITH_TITLE: 11
   
};//XDMoD.ExportOption

// ===========================================================================

XDMoD.PortalModule = Ext.extend(Ext.Panel,  {

   usesToolbar: false,
  
   toolbarItems: {
      
      durationSelector: false,
      exportMenu: false,
      printButton: false,
      reportCheckbox: false,
      chartLinkButton: false,
      openAsNBButton: false,
      
   },//toolbarItems
   
   // customOrder: The top-town order of entries in this array corresponds to 
   //              the left-right ordering of components in the top toolbar.
        
   customOrder: [
 
      XDMoD.ToolbarItem.DURATION_SELECTOR,
      XDMoD.ToolbarItem.EXPORT_MENU,
      XDMoD.ToolbarItem.PRINT_BUTTON,
      XDMoD.ToolbarItem.REPORT_CHECKBOX,
      XDMoD.ToolbarItem.CHART_LINK_BUTTON,
      XDMoD.ToolbarItem.OPEN_AS_JUPYTER_NB_BUTTON

   ],   

   // ------------------------------------------------------------------
      
   initComponent: function(){
       
      var self = this;
      
      self.addEvents('duration_change', 'export_option_selected', 'print_clicked');
      
      // ----------------------------------------  

      var createExportMenu = function(config) {

          var exportPanel = new CCR.xdmod.ui.ExportPanel({ 
              config: config, 
              cancel_function: function(){ 
                  exportDialog.hide(); 
              },
              export_function: function(parameters){ 
                  XDMoD.TrackEvent(self.title, 'Export Menu Used', Ext.encode(parameters), true);
                  self.fireEvent('export_option_selected', parameters);
                  exportDialog.hide();
              } 
          });

          var exportDialog = new Ext.Window({
              height: 250,
              width: 390,
              closable: true,
              closeAction : 'hide', 
              modal: true,
              title: 'Export',
              layout: 'fit',
              items: exportPanel
          });

          var exportButton = new Ext.Button({
              text: 'Export',
              iconCls: 'export',
              tooltip: 'Export chart data',
              handler: function(b) {
                  exportDialog.show();
              }

          });

          self.getExportMenu = function() {
              return exportButton;
          };

          self.setImageExport = function(allowImgExport) {
              exportPanel.allowImageExport.call(exportPanel, allowImgExport);
          };

          self.setExportDefaults = function(show_title) {
              exportPanel.showTitleCheckbox.setValue(show_title);
              exportPanel.settings.show_title = show_title;
          }

          return exportButton;

      };//createExportMenu
      
      // ----------------------------------------  
       
      var createPrintButton = function() {
      
         var printButton = new Ext.Button({ 

            text: 'Print',
            iconCls: 'print',
            tooltip: 'Print chart',
            //disabled: true,
            scope: this,
            handler: function() {

               XDMoD.TrackEvent(self.title, 'Print Button Clicked');

               self.fireEvent('print_clicked');

            }//handler

         });//printButton

         self.getPrintButton = function() {
            return printButton;
         };
         
         return printButton;		

      };//createPrintButton
      
      // ----------------------------------------  

      var createReportCheckbox = function(module_id) {
      
         var reportCheckbox = new CCR.xdmod.ReportCheckbox({
            disabled: false, 
            hidden: false, 
            module: module_id
         });
         
         reportCheckbox.on('toggled_checkbox', function(v) {
         
            XDMoD.TrackEvent(self.title, 'Clicked on Available For Report checkbox', v);
                  
         });//reportCheckbox.on('toggled_checkbox',...
         
         self.getReportCheckbox = function() {
            return reportCheckbox;
         };
         
         return reportCheckbox;	

      };//createReportCheckbox
      
      // ----------------------------------------
      
      var createChartLinkButton = function () {
         var chartLinkButton = new Ext.Button({

             text: 'Link to Current Chart',
             iconCls: 'chart_bar_link',
             tooltip: 'Link to Current Chart',
             scope: this,
             handler: function () {
                 self.fireEvent('chart_link_clicked');
             } // handler

         }); // chartLinkButton

         self.getChartLinkButton = function () {
             return chartLinkButton;
         };

         return chartLinkButton;
     }; // createChartLinkButton


     var createOpenAsNBButton = function () {
      var openAsNBButton = new Ext.Button({

          text: 'Open in Jupyter',
          iconCls: 'chart_bar_link',
          tooltip: 'Open as a Jupyter Notebook',
          scope: this,
          handler: function () {
            self.fireEvent('open_in_nb')
          } // handler

      }); // openAsNBButton

      self.getOpenAsNBButton = function () {
          return openAsNBButton;
      };

      return openAsNBButton;
  }; //createOpenAsNBButton

     // ----------------------------------------
      var moduleConfig = {
      
         layout: 'border',
         frame: false,
         border: false
 
      };
      
      if (self.usesToolbar == true) {
      
         moduleConfig.tbar = new Ext.Toolbar({
            items: []
         });

         var tbItemIndex = 0;
         
         for (tbItemIndex = 0; tbItemIndex < self.customOrder.length; tbItemIndex++) {
         
            var currentItem = self.customOrder[tbItemIndex];
            
            var employSeparator = true;
            
            if (currentItem.item !== undefined) {

               employSeparator = (currentItem.separator !== undefined) ? currentItem.separator : true;
               
               currentItem = currentItem.item;

            }
            
            switch(currentItem) {
                  
               case XDMoD.ToolbarItem.DURATION_SELECTOR:

                  var durationConfig = {};
                  
                  if (self.toolbarItems.durationSelector != undefined && self.toolbarItems.durationSelector.enable != undefined) {
                     
                     if (self.toolbarItems.durationSelector.config != undefined)
                        durationConfig = self.toolbarItems.durationSelector.config;
                     
                     self.toolbarItems.durationSelector = self.toolbarItems.durationSelector.enable;
                     
                  }//if (self.toolbarItems.durationSelector['enable'] != undefined)
                                    
                  // ----------------------------------
                  
                  if (self.toolbarItems.durationSelector == true) {
                  
                     var previousItems = [];
   
                     moduleConfig.tbar.items.each(function(item) {
                     
                        previousItems.push(item);
                        
                     });
   
                     if (previousItems.length > 0 && employSeparator)
                        previousItems.push('-');

                     var baseConfig = {
                         
                        items: previousItems,
                           
                        handler: function(d) {
                           
                           XDMoD.TrackEvent(self.title, 'Timeframe updated', Ext.encode(d));

                           self.fireEvent('duration_change', d);
                           
                        }
                           
                     };//baseConfig
                     
                     Ext.apply(baseConfig, durationConfig);
               
                     var durationToolbar = new CCR.xdmod.ui.DurationToolbar(baseConfig);
                     
                     self.getDurationSelector = function() {
                        return durationToolbar;
                     };
                     
                     moduleConfig.tbar = durationToolbar;
                  
                  }//if (self.toolbarItems.durationSelector == true)
                  
                  break;
                  
               case XDMoD.ToolbarItem.EXPORT_MENU:

                  var exportConfig = [];
                  
                  if (self.toolbarItems.exportMenu != undefined && self.toolbarItems.exportMenu.enable != undefined) {
                     
                     if (self.toolbarItems.exportMenu.config != undefined)
                        exportConfig = self.toolbarItems.exportMenu.config;
                     
                     self.toolbarItems.exportMenu = self.toolbarItems.exportMenu.enable;
                     
                  }//if (self.toolbarItems.exportMenu['enable'] != undefined)
  
                  // ----------------------------------
  
                  if (self.toolbarItems.exportMenu == true) {

                     if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                        moduleConfig.tbar.addItem('-');
            
                     moduleConfig.tbar.addItem(createExportMenu(exportConfig));

                  }//if (self.toolbarItems.exportMenu == true)
                  
                  break;
                  
               case XDMoD.ToolbarItem.PRINT_BUTTON:

                  if (self.toolbarItems.printButton == true) {

                     if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                        moduleConfig.tbar.addItem('-');
            
                     moduleConfig.tbar.addItem(createPrintButton());
                        
                  }
                     
                  break;
                  
               case XDMoD.ToolbarItem.REPORT_CHECKBOX:

                  if (self.toolbarItems.reportCheckbox == true) {

                     if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                        moduleConfig.tbar.addItem('-');
            
                     moduleConfig.tbar.addItem(createReportCheckbox(self.module_id));
                             
                  }
                  
                  break;

               case XDMoD.ToolbarItem.CHART_LINK_BUTTON:

                  if (self.toolbarItems.chartLinkButton === true) {
                        if (moduleConfig.tbar.items.getCount() > 1 && employSeparator) {
                           moduleConfig.tbar.addItem('-');
                        }
                        moduleConfig.tbar.addItem(createChartLinkButton(self.module_id));
                  }

                  break;

                  case XDMoD.ToolbarItem.OPEN_AS_JUPYTER_NB_BUTTON:

                     if (self.toolbarItems.openAsNBButton === true) {
                        if (moduleConfig.tbar.items.getCount() > 1 && employSeparator) {
                           moduleConfig.tbar.addItem('-');
                        }
                        moduleConfig.tbar.addItem(createOpenAsNBButton(self.module_id));
                     }

                  break;

               default:

                  if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                     moduleConfig.tbar.addItem('-');

                  moduleConfig.tbar.addItem(currentItem);
                                 
                  break;
                  
            }//switch
            
         }//for
         
      }//if (self.usesToolbar == true)
     
      // ----------------------------------------      

      Ext.apply(this, moduleConfig);//Ext.apply
      
      XDMoD.PortalModule.superclass.initComponent.call(this);
   
   }//initComponent

});//XDMoD.PortalModule
