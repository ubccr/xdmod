Ext.namespace('XDMoD');

// ==================================================

XDMoD.ReportPreview = Ext.extend(Ext.Panel,  {

   prevViewIndex: 0,
   iconCls: 'report_preview_top_icon',


   // --------------------------------------

   initComponent: function(){

      var self = this;

      self.initPreview = function(file_name, report_id, token, previous_view_index, preview_meta_data, charts_per_page) {

         XDMoD.TrackEvent('Report Generator (Preview)', 'Previewing report', file_name);

         this.report_file_name = file_name;
         this.report_id = report_id;

         if (preview_meta_data) file_name = file_name + ', Unsaved';

         this.setTitle('Report Preview (<span style="color: #00f">' + file_name + '</span>)');

         this.prevViewIndex = previous_view_index;

         var return_label = '';

         if (previous_view_index == 0) return_label = 'Reports Overview';
         if (previous_view_index == 1) return_label = 'Report Editor';

         btnSwitchToPrevious.setText('Return To <b>' + return_label + '</b>');

         mnuSendReport.setDisabled(preview_meta_data ? true : false);
         mnuDownloadReport.setDisabled(preview_meta_data ? true : false);

         this.setTemplate(charts_per_page);

         if (preview_meta_data) {

            this.previewStore.loadData(preview_meta_data);

         }
         else {

            this.previewStore.load({
               params: {
                  'report_id': report_id,
                  'token': token,
                  'charts_per_page': charts_per_page
               }
            });

         }

      };//self.initPreview

      // ---------------------------------------------------

      self.previewStore = new Ext.data.JsonStore({

         storeId: 'chart_store_' + this.id,
         autoDestroy: false,
         root: 'charts',
         totalProperty: 'totalCount',
         successProperty: 'success',
         messageProperty: 'message',

         fields: [

            'report_title',

            'chart_title_0',
            'chart_drill_details_0',
            'chart_timeframe_0',
            'chart_id_0',

            'chart_title_1',
            'chart_drill_details_1',
            'chart_timeframe_1',
            'chart_id_1',

            'header_text',
            'footer_text'

         ],

         baseParams: {
            operation: 'get_preview_data'
         },

         proxy: new Ext.data.HttpProxy({
            method: 'POST',
            url: 'controllers/report_builder.php'
         })

      });//self.previewStore

      // ---------------------------------------------------

      //var activePage = {width: 618, height:800};  // Letter

      var activePage = {width: 850, height:1100};

      var templatePresets = {

        /* 1 Chart Per Page */

        onePerPage: [

            '<tpl for=".">',
               '<center>',
                  '<table width=' + activePage.width + ' height=' + activePage.height + ' border=0 style="border: 1px solid #999; background-color: #fff; padding: 10px">',
                     '<tr><td valign="top" style="padding-top: 10px" height=35 align="center"><i>{header_text}</i></td></tr>',
                     '<tr><td valign="top" height=50 align="center"><div style="width: 600px; word-wrap: break-word">{report_title}</div></td></tr>',

                     '<tr><td valign=top align=center>',
                        '<h2><span style="color: #000">{chart_title_0}</span></h2>',
                        '<span class="date_range">{chart_drill_details_0}</span><br /><br />',
                        '<span style="font-size: 10px">{chart_timeframe_0}</span><br />',
                        '<img width=600 height=450 src="{chart_id_0}&width=600&height=450" style="border: 0px solid #bbb"/>',
                     '</td></tr>',

                     '<tr><td valign="bottom" style="padding-bottom: 10px" align="center"><i>{footer_text}</i></td></tr>',
                  '</table>',
               '</center><br />',
            '</tpl>'

         ],

         /* 2 Charts Per Page */

         twoPerPage: [

            '<tpl for=".">',
               '<center>',
                  '<table width=' + activePage.width + ' height=' + activePage.height + ' border=0 style="border: 1px solid #999; background-color: #fff; padding: 10px">',
                     '<tr><td valign="top" style="padding-top: 10px" height=35 align="center"><i>{header_text}</i></td></tr>',
                     '<tr><td valign="top" height=50 align="center"><div style="width: 600px; word-wrap: break-word">{report_title}</div></td></tr>',

                     '<tr><td valign=top align=center>',
                        '<h2><span style="color: #000">{chart_title_0}</span></h2>',
                        '<span class="date_range">{chart_drill_details_0}</span><br /><br />',
                        //'<span style="font-size: 10px">{chart_timeframe_0}</span><br />',
                        '<img width=500 height=375 src="{chart_id_0}&width=500&height=375" style="border: 0px solid #bbb"/>',
                     '</td></tr>',

                     '<tr><td style="background-color: #fff"><div style="height: 3px"></div></td></tr>',

                     '<tr><td valign=top align=center>',
                        '<h2><span style="color: #000">{chart_title_1}</span></h2>',
                        '<span class="date_range">{chart_drill_details_1}</span><br /><br />',
                        //'<span style="font-size: 10px">{chart_timeframe_1}</span><br />',
                        '<img width=500 height=375 src="{chart_id_1}&width=500&height=375" style="border: 0px solid #bbb"/>',
                     '</td></tr>',

                     '<tr><td valign="bottom" style="padding-bottom: 10px" align="center"><i>{footer_text}</i></td></tr>',
                  '</table>',
               '</center><br />',
            '</tpl>'

         ]

      };//templatePresets

      // Note: XTemplates need to be compiled during initComponent invocation.

      var xtOnePerPage = new Ext.XTemplate(templatePresets.onePerPage);
      var xtTwoPerPage = new Ext.XTemplate(templatePresets.twoPerPage);

      var view = new Ext.DataView(
      {
         id: 'dv_report_preview',
         title: 'Chart',
         cls: 'custom_report_viewer',
         loadingText: "Loading...",
         emptyText: 'sorry, no preview yet',
         itemSelector: '',
         style: 'overflow:auto',
         store: self.previewStore,
         autoScroll: true,
         height: 400
      });

      //----------------------------------------------

      self.setTemplate = function(id) {

         if (id == 1) view.tpl = xtOnePerPage;
         if (id == 2) view.tpl = xtTwoPerPage;

      };//setTemplate

      var switchToPreviousView = function () {

         self.parent.switchView(self.prevViewIndex);

      };//switchToPreviousView

      //----------------------------------------------

      var sendReport = function(build_only, format) {

         self.parent.buildReport(self.report_file_name, self.report_id, self, build_only, format);

      };//sendReport

      //----------------------------------------------

      var mnuSendReport = new XDMoD.Reporting.ReportExportMenu({
         instance_module: 'Preview',
         sendMode: true,
         exportItemHandler: sendReport
      });

      var mnuDownloadReport = new XDMoD.Reporting.ReportExportMenu({
         instance_module: 'Preview',
         exportItemHandler: sendReport
      });

      var btnSwitchToPrevious = new Ext.Button({

         iconCls: 'btn_return_to_previous',

         handler: function() {

            XDMoD.TrackEvent('Report Generator (Preview)', 'Clicked on ' + btnSwitchToPrevious.getText().replace(/<\/*b>/g, ''));
            switchToPreviousView();

         }

      });

      //----------------------------------------------

      Ext.apply(this, {

         title: 'Report Preview',
         layout: 'fit',
         cls: 'report_preview',

         items: [view],

         plugins: [new Ext.ux.plugins.ContainerMask ({ masked:false })],

         tbar: {

            items: [

               mnuSendReport,
               mnuDownloadReport,

               '->',

               btnSwitchToPrevious

            ]

         }

      });

      XDMoD.ReportPreview.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.ReportPreview
