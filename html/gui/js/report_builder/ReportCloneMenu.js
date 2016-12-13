Ext.namespace('XDMoD.Reporting');

/*
* XDMoD.Reporting.ReportCloneMenu
* @author Ryan Gentner
* @date 2012-5-7
*/

XDMoD.Reporting.ReportCloneMenu = Ext.extend(Ext.Button,  {

   // The following need to be overridden in order for instances
   // of ReportCloneMenu to be useful:

   selectedReportHandler: Ext.emptyFn,
   selectedTemplateHandler: Ext.emptyFn,

   // ==============================================

   initComponent: function(){

      var self = this;

      var mnuTemplates = new Ext.menu.Menu({

         items: []

      });

      var selectedReportOption = {
         iconCls: 'btn_selected_report',
         text: 'Selected Report',
         handler: function() {
            self.selectedReportHandler();
         }
      };

      var reportTemplates = [];

      // -------------------------------------------------------------------

      var enumerateTemplates = function() {

         var conn = new Ext.data.Connection();

         conn.request({

            url: 'controllers/report_builder.php',
            params: {operation: 'enum_templates'},
            method: 'POST',

            callback: function(options, success, response) {

               if (success) {

                  var reportData = Ext.decode(response.responseText);

                  for (t = 0; t < reportData.templates.length; t++) {

                     reportTemplates.push({

                        iconCls: 'btn_report_template',
                        text: 'Template: <b>' + reportData.templates[t].name + '</b>',
                        templateId: reportData.templates[t].id,
                        useSubmenu: reportData.templates[t].use_submenu

                     });

                  }//for

                  self.templateLoadHandler(reportData.templates.length);

               }
               //else
                  //Ext.MessageBox.alert('Report Pool', 'There was a problem attempting to enumerate report templates');

               // After the template listing has been generated, render the template menu (UI)
               self.toggleReportSelection(false);

            }//callback

         });//conn.request

      };//enumerateTemplates

      // -------------------------------------------------------------------

      self.setSelectedReport = function (n) {

         selectedReportOption.text = 'Selected Report: <b>' + n + '</b>';

      };//self.setSelectedReport

      // -------------------------------------------------------------------

      var getResourceProviderSubmenu = function(template_id) {

         var resourceProviderSubmenu = new Ext.menu.Menu();
         var assignedRPs = CCR.xdmod.enumAssignedResourceProviders();

         var submenuConfig = {
            rpCount: 0
         };

         for(rp in assignedRPs) {

            submenuConfig.rpCount++;

            if (submenuConfig.rpCount == 1)
               submenuConfig.first_rp_id = rp;

            resourceProviderSubmenu.addItem({
               text: assignedRPs[rp],
               iconCls: 'btn_resource_provider',
               rp_id: rp,
               handler: function() {

                  XDMoD.TrackEvent('Report Generator (My Reports)', 'New Based On button clicked', 'SP Quarterly Report -> ' + this.text + ' (RP_ID: ' + this.rp_id + ')');

                  self.selectedTemplateHandler(template_id, this.rp_id);

               }
            });

         }//for(rp in assignedRPs)

         submenuConfig.submenu = resourceProviderSubmenu;

         return submenuConfig;

      };//getResourceProviderSubmenu

      // -------------------------------------------------------------------

      self.toggleReportSelection = function(b) {

         mnuTemplates.removeAll();

         if (b == true) {

            mnuTemplates.addItem(selectedReportOption);
            mnuTemplates.addItem('-');

         }

         for (i = 0; i < reportTemplates.length; i++) {

            var menuConfig = getResourceProviderSubmenu(reportTemplates[i].templateId);

            if (menuConfig.rpCount > 1 && reportTemplates[i].useSubmenu == 1)
            {

               Ext.apply(reportTemplates[i], {menu: menuConfig.submenu});

            }
            else
            {

               // If there is only one resource provider associated with the account OR the template is not configured
               // to use a submenu, then the template menu item itself is the only entry presented (that is, with no submenu).

               Ext.apply(reportTemplates[i], {

                  template_id: reportTemplates[i].templateId,
                  template_text: reportTemplates[i].text.replace(/<\/*b>/g, ''),

                  handler: function(){

                     XDMoD.TrackEvent('Report Generator', 'New Based On button clicked', this.template_text + ' (RP_ID: ' + menuConfig.first_rp_id + ')');

                     self.selectedTemplateHandler(this.template_id, menuConfig.first_rp_id);

                  }

               });

            }

            mnuTemplates.addItem(reportTemplates[i]);

         }//for

      };//self.toggleReportSelection

      // -------------------------------------------------------------------

      Ext.apply(this, {

         iconCls: 'btn_new_based_on',
         text: 'New Based On',
         tooltip: 'Uses a copy of the selected report or template as the basis for a new report.',

         menu: mnuTemplates

      });

       XDMoD.Reporting.ReportCloneMenu.superclass.initComponent.call(this);

       enumerateTemplates();

   }//initComponent

});//XDMoD.Reporting.ReportCloneMenu
