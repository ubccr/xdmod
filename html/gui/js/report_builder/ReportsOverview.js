Ext.namespace('XDMoD');

XDMoD.ReportsOverview = Ext.extend(Ext.Panel,  {

   firstLoad: false,

   initComponent: function(){

      var self = this;

      var reportBreakdown = [];

      var eReport = function() {

         var exceptionReports = [

            {
               type: 'Monthly Compliance Report',
               canDeriveFrom: false,
               canEdit: false,
               canPreview: false,
               multiFormat: false
            }

         ];

         return {

            isExceptionReport: function(t) {

               var i = 0;

               for(i = 0; i < exceptionReports.length; i++) {
                  if (exceptionReports[i].type == t) return exceptionReports[i];
               }

               return false;

            }

         };

      }();//eReport

       var footer_text = new Ext.Toolbar.TextItem({
          cls: 'fbar_report_status',
          text: 'No reports'
       });

      this.reportStore = new Ext.data.JsonStore({

         autoDestroy: false,
         root: 'queue',
         baseParams: { operation: 'enum_reports' },

         fields: [
               'report_id',
               'report_name',
               'creation_method',
               'report_title',
               'charts_per_page',
               'report_format',
               'report_schedule',
               'report_delivery',
               'chart_count'
         ],

         proxy: new Ext.data.HttpProxy({
            method: 'POST',
            url: 'controllers/report_builder.php'
         })

      });//this.reportStore

      this.reportStore.on('load', function(s, r, o) {

         mnuNewBasedOn.toggleReportSelection(false);

         btnNewBasedOn.setDisabled(true);
         btnEditReport.setDisabled(true);
         btnPreviewReport.setDisabled(true);

         mnuSendReport.setDisabled(true);
         mnuDownloadReport.setDisabled(true);

         btnSendReport.setVisible(false);
         btnDownloadReport.setVisible(false);

         mnuSendReport.setVisible(true);
         mnuDownloadReport.setVisible(true);

         btnDeleteReport.setDisabled(true);

         reportBreakdown = s.reader.jsonData.reports_in_other_roles;

         updateReportCountText();

         if (self.firstLoad) {

            self.firstLoad = false;

            if (r.length == 0) {

               // No saved reports currently exist
               // Auto-switch view to the Report Editor

               // Now disabled with the advent of report templates
               // newReport();


            }

         }

         CCR.xdmod.ui.tgSummaryViewer.fireEvent('request_refresh');
      });

       var updateReportCountText = function() {

         var suffix = (self.reportStore.getTotalCount() != 1) ? 's' : '';
         var prefix = (self.reportStore.getTotalCount() != 0) ? self.reportStore.getTotalCount() : 'No';
         footer_text.setText(prefix + ' report' + suffix);

       };//updateReportCountText

      // ----------------------------------------------------

      var adjustButtonAccessibilty = function(selectionModel, rowIndex, record) {

         var selectedRows = selectionModel.getSelections();

         if (selectedRows.length == 1) {

            mnuNewBasedOn.setSelectedReport(record.data.report_name);

         }

         mnuNewBasedOn.toggleReportSelection(selectedRows.length == 1);

         btnNewBasedOn.setDisabled(selectedRows.length != 1);
         btnEditReport.setDisabled(selectedRows.length != 1);
         btnPreviewReport.setDisabled(selectedRows.length != 1);

         mnuSendReport.setDisabled(selectedRows.length != 1);
         mnuDownloadReport.setDisabled(selectedRows.length != 1);

         btnSendReport.setVisible(false);
         mnuSendReport.setVisible(true);

         btnDownloadReport.setVisible(false);
         mnuDownloadReport.setVisible(true);

         btnDeleteReport.setDisabled(selectedRows.length == 0);

         var exceptionDetails = eReport.isExceptionReport(record.data.creation_method);

         if (selectedRows.length == 1 && exceptionDetails !== false) {

            mnuNewBasedOn.toggleReportSelection(exceptionDetails.canDeriveFrom);
            btnEditReport.setDisabled(!exceptionDetails.canEdit);
            btnPreviewReport.setDisabled(!exceptionDetails.canPreview);

            if (!exceptionDetails.multiFormat) {

               btnSendReport.setVisible(true);
               mnuSendReport.setVisible(false);

               btnDownloadReport.setVisible(true);
               mnuDownloadReport.setVisible(false);

            }

         }

      };//adjustButtonAccessibilty

      // ----------------------------------------------------

      var checkBoxSelMod = new Ext.grid.CheckboxSelectionModel({

         listeners:{

            rowselect : adjustButtonAccessibilty,
            rowdeselect : adjustButtonAccessibilty

         }//listeners

      });//checkBoxSelMod

      // ----------------------------------------------------

      var numChartsColumnRenderer = function(val, metaData, record, rowIndex, colIndex, store){

         var entryData = store.getAt(rowIndex).data;

         if (entryData.creation_method == 'Monthly Compliance Report')
            return 'Not applicable';
         else
            return val + ' <span style="color: #00f">(' + entryData.charts_per_page + ' per page)</span>';

      };//numChartsColumnRenderer

      // ----------------------------------------------------

      var reportFormatColumnRenderer = function(v) {

         var format = 'Unknown format';
         var icon = 'report_general_info';

         if (v == 'Pdf') {
            format = 'PDF';
            icon = 'pdf_icon';
         }
         else if (v == 'Doc') {
            format = 'Word Document';
            icon = 'msword_icon';
         }

         return '<div><div style="float: left"><img src="gui/images/report_generator/' + icon +
                '.png"></div><div style="margin-left: 20px; padding-top: 2px">' + format + '</div></div>';

      };//reportFormatColumnRenderer

      // ----------------------------------------------------

      var reportsEmptyText = 'You currently have no reports.<br />' +
                             'To create a report, click on the <b>New</b> button above this message.<br /><br />' +
                             '<img src="gui/images/create_new_report.png">' +
                              CCR.xdmod.ui.createUserManualLink('report+generator');

      // ----------------------------------------------------

      var editReport;

      var queueGrid = new Ext.grid.GridPanel({

         store: this.reportStore,
         //id: 'reportPool_queueGrid' + Ext.id(),
         itemId: 'reportQueueGrid',

         viewConfig: {
            emptyText: reportsEmptyText,
            forceFit: true
         },

         autoScroll: true,

         enableHdMenu: false,
         selModel : checkBoxSelMod,
         loadMask: true,

         columns: [
            //checkBoxSelMod,
            {header: 'ID', width: 10, dataIndex: 'report_id', sortable: false, hidden: true},
            {header: 'Name', width: 200, dataIndex: 'report_name', sortable: true},
            {header: 'Derived From', width: 100, dataIndex: 'creation_method', sortable: true},
            {header: 'Schedule', width: 70, dataIndex: 'report_schedule', sortable: true},
            {header: 'Delivery Format', width: 70, dataIndex: 'report_format', sortable: true, renderer: reportFormatColumnRenderer},
            {header: '# Charts', width: 70, dataIndex: 'chart_count', sortable: true, renderer: numChartsColumnRenderer}
         ],
         listeners: {
            load_report: function (reportId) {
               this.store.load({
                  callback: function (records, operation, success) {
                     var index = queueGrid.store.find('report_id', reportId);
                     queueGrid.getSelectionModel().selectRow(index);
                     if ((self.parent.reportCreator.report_id !== reportId) && (self.parent.reportCreator.isDirty() === true)) {
                        Ext.Msg.show({
                           title: 'Cannot open another report!',
                           msg: 'You cannot open another report because this report has unsaved changes.',
                           buttons: Ext.Msg.OK
                        });
                     } else {
                        editReport();
                     }
                  }
               });
            }
         }

      });//queueGrid

      queueGrid.getSelectionModel().on('rowselect', function(sm, row_index, rec) {
         XDMoD.TrackEvent('Report Generator (My Reports)', 'Selected Report', Ext.encode({report_name: rec.data.report_name}));
      });

      queueGrid.getSelectionModel().on('rowdeselect', function(sm, row_index, rec) {
         XDMoD.TrackEvent('Report Generator (My Reports)', 'De-Selected Report', Ext.encode({report_name: rec.data.report_name}));
      });

      CCR.xdmod.catalog['report_generator']['1'] = {
         highlight: queueGrid.id,
         title: 'Your reports',
         width: 300,
         height: 400,
         description: 'Any reports that you have created and saved appear to the left.  In order to edit an existing report, do one of the following:<br /><br />' +
                      '1. Select a report from the left, then click on <b>Edit</b><br />' +
                      '2. Double click on a report' +
                      '<br /><br />' +
                      'It is worth noting that Compliance Reports can not be edited.'
      };

      // Double-clicking on a record pertaining to an existing report will retrieve the report data of interest
      // and present that data in the report editor

       queueGrid.on('rowdblclick', function(queueGrid, rowIdx, e) {

          var getData = queueGrid.getSelectionModel().getSelections();

          if (getData.length == 1) {

             XDMoD.TrackEvent('Report Generator (My Reports)', 'Double-clicked on report entry', getData[0].data.report_name);

             editReport();

         }

      });//queueGrid.on('rowdblclick', ...

      // ----------------------------------------------------

      var deleteReport = function(){

         var getData = queueGrid.getSelectionModel().getSelections();

         var submessage = (getData.length > 1) ? 'these selected reports' : 'this selected report';
         var plural_suffix = (getData.length > 1) ? 's' : '';

         Ext.Msg.show({

            maxWidth: 800,
            minWidth: 400,
            title: 'Delete Selected Report' + plural_suffix,
            msg: 'Are you sure you want to delete ' + submessage + '?<br><b>This action cannot be undone.</b>',
            buttons: Ext.Msg.YESNO,

            fn: function(resp) {

               if (resp == 'yes'){

                  var selected_report = [];

                  for (var i = 0; i < getData.length; i++) {

                     XDMoD.TrackEvent('Report Generator (My Reports)', 'Confirmed deletion of report', getData[i].get('report_name'));
                     selected_report[i] = getData[i].get('report_id');

                  }

                  var selected_report_id= selected_report.join(';');

                  var objParams = {
                     operation: 'remove_report_by_id',
                     selected_report: selected_report_id
                  };

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
                              title: 'Report Pool',
                              wrapperMessage: 'There was a problem regarding the report pool.'
                           });
                        }
                     }

                  });//conn.request

               }//if (resp == 'yes')
               else {

                  XDMoD.TrackEvent('Report Generator (My Reports)', 'Cancelled report entry delete confirmation dialog');

               }

            },//fn

            icon: Ext.MessageBox.QUESTION

         });//Ext.Msg.show

      };//deleteReport

      // ----------------------------------------------------

      editReport = function () {
         var record = queueGrid.getSelectionModel().getSelected();

         XDMoD.TrackEvent('Report Generator (My Reports)', 'Attempting to edit report', record.data.report_name);

         var exceptionDetails = eReport.isExceptionReport(record.data.creation_method);

         if (exceptionDetails !== false && exceptionDetails.canEdit == false) {

            Ext.MessageBox.alert('Report Generator (My Reports)', 'You cannot edit this type of report');
            return;

         }

         var objParams = {
            operation: 'fetch_report_data',
            selected_report: record.data.report_id
         };

         var conn = new Ext.data.Connection();

         conn.request({

            url: 'controllers/report_builder.php',
            params: objParams,
            method: 'POST',

            callback: function(options, success, response) {
               var reportData;
               if (success) {
                  reportData = CCR.safelyDecodeJSONResponse(response);
                  success = CCR.checkDecodedJSONResponseSuccess(reportData);
               }

               if (success) {
                  self.parent.reportCreator.setReportID(reportData.results.report_id);

                  var reportGeneralData = reportData.results.general;

                  self.parent.reportCreator.setReportName(reportGeneralData.name);
                  self.parent.reportCreator.setReportTitle(reportGeneralData.title);
                  self.parent.reportCreator.setReportHeader(reportGeneralData.header);
                  self.parent.reportCreator.setReportFooter(reportGeneralData.footer);

                  self.parent.reportCreator.setChartsPerPage(reportGeneralData.charts_per_page);

                  self.parent.reportCreator.setReportFormat(reportGeneralData.format);
                  self.parent.reportCreator.setReportSchedule(reportGeneralData.schedule);
                  self.parent.reportCreator.setReportDelivery(reportGeneralData.delivery);

                  // Populate 'Included Charts' accordingly...

                  self.parent.reportCreator.reportCharts.reportStore.loadData(reportData.results);

                  self.parent.reportCreator.initReportGrid();

                  self.parent.reportCreator.allowSaveAs(true);

                  self.parent.switchView(1);

                  XDMoD.TrackEvent('Report Generator', 'Report opened in editor', record.data.report_name);
               } else {
                  CCR.xdmod.ui.presentFailureResponse(response, {
                     title: 'Report Pool',
                     wrapperMessage: 'There was a problem trying to prepare the report editor.'
                  });
               }
            }//callback

         });//conn.request

      };//editReport

      // ----------------------------------------------------

      var newReport = function(){

         //clear_selected_row();

         XDMoD.TrackEvent('Report Generator (My Reports)', 'New button clicked');

         var conn = new Ext.data.Connection();

         conn.request({

            url: 'controllers/report_builder.php',
            params: {operation: 'get_new_report_name'},
            method: 'POST',

            callback: function(options, success, response) {

               var responseData = Ext.decode(response.responseText);

               if (responseData.success) {

                  self.parent.reportCreator.initializeFields(responseData.report_name);
                  self.parent.reportCreator.setReportID('');

                  self.parent.reportCreator.reportCharts.reportStore.removeAll();
                  self.parent.reportCreator.setChartsPerPage(1);
                  self.parent.reportCreator.reportCharts.resetChartCount();

                  self.parent.reportCreator.allowSaveAs(false);

                  self.parent.switchView(1);

               }//if (responseData.success)

            }//callback

         });//conn.request

      };//newReport

      // ----------------------------------------------------

      var clear_selected_row = function (){

         var objParams = { operation: 'clear_selected_row' };
         var conn = new Ext.data.Connection();

         conn.request({
            url: 'controllers/report_builder.php',
            params: objParams,
            method: 'POST'
         });

      };//clear_selected_row

      // ----------------------------------------------------

      var previewReport = function () {

         var rowData = queueGrid.getSelectionModel().getSelected().data;

         self.parent.reportPreview.initPreview(rowData.report_name, rowData.report_id, XDMoD.REST.token, 0, undefined, rowData.charts_per_page);

         self.parent.switchView(2);

      };//previewReport

      // ----------------------------------------------------

      var constructReportFromTemplate = function(template_id, resource_provider) {

         var objParams = {
            operation: 'build_from_template',
            template_id: template_id,
            resource_provider: resource_provider
         };

         //console.log(objParams);
         //return;

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
                     title: 'Report Creator',
                     wrapperMessage: 'There was a problem trying to build a report from this template.'
                  });
               }
            }//callback

         });//conn.request

      };//constructReportFromTemplate

      // ----------------------------------------------------

      var newReportBasedOn = function() {

         XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on New Based On -> Selected Report');

         var objParams = {
            operation: 'fetch_report_data',
            based_on_other: 'true',
            selected_report: queueGrid.getSelectionModel().getSelected().data.report_id
         };

         var conn = new Ext.data.Connection();

         conn.request({

            url: 'controllers/report_builder.php',
            params: objParams,
            method: 'POST',

            callback: function(options, success, response) {
               var reportData;
               if (success) {
                  reportData = CCR.safelyDecodeJSONResponse(response);
                  success = CCR.checkDecodedJSONResponseSuccess(reportData);
               }

               if (success) {
                  self.parent.reportCreator.setReportID(reportData.results.report_id);

                  var reportGeneralData = reportData.results.general;

                  self.parent.reportCreator.setReportName(reportGeneralData.name);
                  self.parent.reportCreator.setReportTitle(reportGeneralData.title);
                  self.parent.reportCreator.setReportHeader(reportGeneralData.header);
                  self.parent.reportCreator.setReportFooter(reportGeneralData.footer);

                  self.parent.reportCreator.setReportFormat(reportGeneralData.format);
                  self.parent.reportCreator.setReportSchedule(reportGeneralData.schedule);
                  self.parent.reportCreator.setReportDelivery(reportGeneralData.delivery);

                  self.parent.reportCreator.setChartsPerPage(reportGeneralData.charts_per_page);

                  // Populate 'Included Charts' accordingly...

                  self.parent.reportCreator.reportCharts.reportStore.loadData(reportData.results);

                  self.parent.reportCreator.dirtyConfig(); // Require the user to save the report by default

                  self.parent.reportCreator.initReportGrid();

                  self.parent.switchView(1);

                  XDMoD.TrackEvent('Report Generator', 'Report copy opened in editor', reportGeneralData.name);
               } else {
                  CCR.xdmod.ui.presentFailureResponse(response, {
                     title: 'Report Pool',
                     wrapperMessage: 'There was a problem trying to prepare the report editor.'
                  });
               }
            }//callback

         });//conn.request

      };//newReportBasedOn

      // ----------------------------------------------------

      var sendReport = function(build_only, format) {

         var rowData = queueGrid.getSelectionModel().getSelected().data;

         self.parent.buildReport(rowData.report_name, rowData.report_id, self, build_only, format);

      };//sendReport

       // ----------------------------------------------------

      var btnSelectMenu = new Ext.Button({

         iconCls: 'btn_select',
         text: 'Select',

         menu: new Ext.menu.Menu({

            items: [

               {
                  text: 'All Reports',
                  iconCls: 'btn_all_reports',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Select -> All Reports');
                     queueGrid.getSelectionModel().selectAll();

                  }
               },

               {
                  text: 'No Reports',
                  iconCls: 'btn_no_reports',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Select -> No Reports');
                     queueGrid.getSelectionModel().clearSelections();

                  }
               },

               {

                  text: 'Invert Selection',
                  iconCls: 'btn_invert_selection',
                  handler: function() {

                     XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Select -> Invert Selection');

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

       var btnNewReport = new Ext.Button({
         iconCls: 'btn_new',
         text: 'New',
         handler: newReport
       });

       var btnNewBasedOn = new Ext.Button({

         iconCls: 'btn_new_based_on',
         text: 'New Based On',
         tooltip: 'Uses a copy of the selected report as the basis for a new report.',
         handler: newReportBasedOn

       });
       btnNewBasedOn.setVisible(false);


       var mnuNewBasedOn = new XDMoD.Reporting.ReportCloneMenu({

          tooltip: 'Uses a copy of the selected report or template as the basis for a new report.',
          selectedReportHandler: newReportBasedOn,
          selectedTemplateHandler: constructReportFromTemplate,

          templateLoadHandler: function(t) {
             self.enableTemplateMode(t > 0);
          }

       });
       mnuNewBasedOn.setVisible(false);

       var btnEditReport = new Ext.Button({

         iconCls: 'btn_edit',
         text: 'Edit',

         handler: function() {

            XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Edit button');
            editReport();

          }

       });

       var btnPreviewReport = new Ext.Button({

         iconCls: 'btn_preview',
         text: 'Preview',
         tooltip: 'See a visual representation of the selected report.',

         handler: function() {

            XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Preview button');
            previewReport();

          }

       });

      var mnuSendReport = new XDMoD.Reporting.ReportExportMenu({
         instance_module: 'My Reports',
         sendMode: true,
         exportItemHandler: sendReport
      });

      var btnSendReport = new Ext.Button({

         text: 'Send Now',
         iconCls: 'btn_send',
         tooltip: 'Builds and e-mails the selected report.',

         handler: function() {

            XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Send Now button');
            sendReport(false, 'pdf');

         },

         hidden: true

      });

      var mnuDownloadReport = new XDMoD.Reporting.ReportExportMenu({
         instance_module: 'My Reports',
         exportItemHandler: sendReport
      });

      var btnDownloadReport = new Ext.Button({

         text: 'Download',
         iconCls: 'btn_download',
         tooltip: 'Builds and presents the selected report as an attachment.',

         handler: function() {

            XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Download button');
            sendReport(true, 'pdf');

         },

         hidden: true

      });

       var btnDeleteReport = new Ext.Button({

         iconCls: 'btn_delete',
         text: 'Delete',

         handler: function() {

            XDMoD.TrackEvent('Report Generator (My Reports)', 'Clicked on Delete button');
            deleteReport();

          }

       });

       self.enableTemplateMode = function(b) {

          btnNewBasedOn.setVisible(!b);
          mnuNewBasedOn.setVisible(b);

       };//self.enableTemplateMode

       // ----------------------------------------------------

      Ext.apply(this, {

         title: 'My Reports',

         layout: 'fit',
         cls: 'report_overview',

         items:[ queueGrid ],

         plugins: [
            new Ext.ux.plugins.ContainerMask ({ masked:false }),
            new XDMoD.Plugins.ContextSensitiveHelper('report+generator')
         ],

         tbar: {

            items: [

               btnSelectMenu,
               '-',

               btnNewReport,
               btnNewBasedOn,
               mnuNewBasedOn,

               btnEditReport,
               btnPreviewReport,

               mnuSendReport,
               btnSendReport,

               mnuDownloadReport,
               btnDownloadReport,

               '->',

               btnDeleteReport

            ]

         },

         bbar: {

            items: [
               footer_text
            ]

         }

      });//Ext.apply

      this.reportStore.reload();
      XDMoD.ReportsOverview.superclass.initComponent.call(this);

   },

   onRender : function(ct, position){

      this.firstLoad = true;
      this.reportStore.reload();

      XDMoD.ReportsOverview.superclass.onRender.call(this, ct, position);

   }

});//XDMoD.ReportsOverview
