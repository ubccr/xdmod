Ext.namespace('XDMoD.Reporting');

XDMoD.Reporting.ReportExportMenu = Ext.extend(Ext.Button,  {

   // If sendMode is set to 'true', then the report will be built and sent as opposed to solely being built
   sendMode: false,

   // Default placeholder
   exportItemHandler: function(b, f) { alert('handler for format ' + f); },

   initComponent: function(){

      var self = this;

      if (self.instance_module == undefined) self.instance_module = '';

      var preHandler = function(output_format) {

         XDMoD.TrackEvent('Report Generator (' + self.instance_module + ')', 'Clicked on ' + ((self.sendMode == true) ? 'Send Now' : 'Download') + ' -> ' + output_format);

      };//preHandler

      // -------------------------------------------------------------------

      Ext.apply(this, {

         iconCls: (self.sendMode == true) ? 'btn_send' : 'btn_download',
         text: (self.sendMode == true) ? 'Send Now' : 'Download',
         tooltip: (self.sendMode == true) ? 'Builds and e-mails the selected report.' : 'Builds and presents the selected report as an attachment.',

         menu: new Ext.menu.Menu({
            items: [

               {iconCls: 'pdf_icon',     text: 'As PDF',             handler: function() {

                  preHandler('As PDF');
                  self.exportItemHandler(!(self.sendMode), 'pdf');

               } },

               {iconCls: 'msword_icon',  text: 'As Word Document',   handler: function() {

                  preHandler('As Word Document');
                  self.exportItemHandler(!(self.sendMode), 'doc');

               } }

            ]
         })

      });

      XDMoD.Reporting.ReportExportMenu.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.Reporting.ReportExportMenu
