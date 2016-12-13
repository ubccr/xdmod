Ext.namespace('XDMoD.Reporting', 'XDMoD.Reporting.Singleton');

XDMoD.Reporting.GetTrackingConfigFromRecord = function(record_ref) {

   return {
      chart_title: truncateText(record_ref.data.chart_title),
      chart_date_description: record_ref.data.chart_date_description,
      chart_drill_details: truncateText(record_ref.data.chart_drill_details),
      chart_timeframe_type: record_ref.data.timeframe_type
   };

};//XDMoD.Reporting.GetTrackingConfigFromRecord

XDMoD.Reporting.GetTrackingConfig = function(store_id, record_id, config) {

   var record_ref = Ext.StoreMgr.lookup(store_id).getById(record_id);

   var trackingConfig = XDMoD.Reporting.GetTrackingConfigFromRecord(record_ref);

   if (config) {
      Ext.apply(trackingConfig, config);
   }

   return trackingConfig;

};//XDMoD.Reporting.GetTrackingConfig

// ---------------------------------------------------------------

XDMoD.Reporting.EditBarReveal = function(i, b) {

   document.getElementById(i).style.visibility = b ? 'visible' : 'hidden';

};//XDMoD.Reporting.EditBarReveal

XDMoD.Reporting.PreviewThumb = function(ref, title, timeframe_desc, store_id, record_id) {

   var active_view = (store_id == 'report_generator_included_charts_store') ? 'Report Editor' : 'Available Charts';

   var trackingConfig = XDMoD.Reporting.GetTrackingConfig(store_id, record_id);
   XDMoD.TrackEvent('Report Generator (' + active_view + ')', 'Clicked on preview icon for chart entry', Ext.encode(trackingConfig));

   new XDMoD.ChartThumbPreview({
      ref: ref,
      title: title,
      timeframe_desc: timeframe_desc
   }).show();

};//XDMoD.Reporting.PreviewThumb

XDMoD.Reporting.Singleton.ChartDateEditor = new XDMoD.Reporting.ChartDateEditor();
XDMoD.Reporting.Singleton.ReportEntryTypeMenu = new XDMoD.Reporting.ReportEntryTypeMenu();

function dismissPlaceholder(placeholder_id) {

   var objPlaceholder = document.getElementById(placeholder_id);

   if (objPlaceholder)
      objPlaceholder.style.display = 'none';

}//dismissPlaceholder

// ---------------------------------------------------------------

XDMoD.Reporting.Singleton.DrillDetails = function(evt, ttid, drill_details) {

    if (drill_details.length == 0) drill_details = 'Category = ' + CCR.xdmod.org_abbrev;

    var details = drill_details.split(' -- ');

    var tbl = '<table border=0 cellspacing=0 class="report_generator_tooltip_drill_details">';

    for (var i = 0; i < details.length; i++){

      var b = (i % 2) ? '#ddddff' : '#eeeeee';

      var param = details[i].split(' = ')[0];
      var param_value = details[i].split(' = ')[1];

      if (param_value != undefined)
         tbl += '<tr bgcolor="' + b + '"><td valign=top width=160><b>' + param + '</b></td><td valign=top width=160>' + param_value + '</td></tr>';
      else
         tbl += '<tr><td width=320 valign=top>' + param + '</td></tr>';

    }//for

    tbl += '</table>';

    var tt = new Ext.ToolTip({

      title: '<table border=0><tr><td><img src="gui/images/info.png"></td><td><b class="report_generator_tooltip_drill_details_header">Chart Details</b></td></tr></table>',
      anchor: 'right',
      width: 220,
      autoHide: true,
      target: ttid,
      dismissDelay: 10000, //(10 seconds)
      html: tbl

    });

    tt.on('hide', function(t) {
      t.destroy();
    });

    tt.show();

};//XDMoD.Reporting.Singleton.DrillDetails

// ---------------------------------------------------------------

XDMoD.Reporting.getParamIn = function(param, haystack, delimiter) {

   if (!delimiter) delimiter = '/';

   if (haystack.indexOf('?') > -1) {
      haystack = haystack.split('?')[1];
   }

   var f = haystack.split(delimiter);

   for (g = 0; g < f.length; g++){

      if (f[g].indexOf(param) > -1) {

         var j = f[g].split('=');
         return j[1];

      }

   }//for

};//XDMoD.Reporting.getParamIn

// ---------------------------------------------------------------

XDMoD.Reporting.renderDateUsingFormat = function (d) {

   var d_day = '' + d.getDate();

   if (d_day.length == 1) d_day = '0' + d_day;

   var d_month = '' + (d.getMonth() + 1);
   if (d_month.length == 1) d_month = '0' + d_month;

   var d_year = d.getFullYear();

   return d_year + '-' + d_month + '-' + d_day;

};//XDMoD.Reporting.renderDateUsingFormat

// ---------------------------------------------------------------

XDMoD.Reporting.colorWrap = function(val, colorHex) {

   return '<span style="color: ' + colorHex + '">' + val + '</span>';

};//XDMoD.Reporting.colorWrap

// ---------------------------------------------------------------

XDMoD.Reporting.chartDetailsRenderer = function(val, metaData, record, rowIndex, colIndex, store){

   var duplication_id = '';

   var entryData = store.getAt(rowIndex).data;

   // =================

   var chartTitle = entryData.chart_title.replace(/^\s+|\s+$/g, "");

   var chartTitleColor = '#00f';

   if (chartTitle.length == 0) {
      chartTitle = 'Untitled Chart';
      chartTitleColor = '#888';
   }

   var cleanedChartTitle = chartTitle;

   if (chartTitle.indexOf('by') == -1)
      chartTitle = chartTitle + '<br />&nbsp;';
   else
      chartTitle = chartTitle.replace('by', '<br /> by');

   /*
   if (record.data['duplicate_id'] != undefined && record.data['duplicate_id'].length > 0) {
      chartTitle = chartTitle + ' (' + record.data['duplicate_id'] + ')';
   }
   */

   // =================

   var s_date = entryData.chart_date_description.split(' to ')[0];
   var e_date = entryData.chart_date_description.split(' to ')[1];

   if (entryData.timeframe_type.toLowerCase() != 'user defined') {

      var date_utils = new DateUtilities();

      var endpoints = date_utils.getEndpoints(entryData.timeframe_type);

      // Overwrite chart_id and chart_date_description as necessary

      s_date = endpoints.start_date;
      e_date = endpoints.end_date;

      entryData.chart_date_description = s_date + ' to ' + e_date;

      // Be sure to update start_date and end_date in the thumbnail link so the correct thumbnail is presented (respective of the dynamic timeframe)

      entryData.thumbnail_link = entryData.thumbnail_link.replace(/start_date=\d{4}-\d{2}-\d{2}/, 'start_date=' + s_date);
      entryData.thumbnail_link = entryData.thumbnail_link.replace(/end_date=\d{4}-\d{2}-\d{2}/, 'end_date=' + e_date);

      //console.log(entryData.thumbnail_link);

   }//if (entryData.timeframe_type.toLowerCase() != 'user defined')

   // =================

   var chartDateEditFloater = '';
   var viewSelector = '';

   var chart_entry_timeframe = '<div style="margin-top: 2px">' + XDMoD.Reporting.colorWrap(entryData.chart_date_description, '#888') + '</div>';

   if (store.storeId == 'report_generator_included_charts_store') {

      // Report chart entries immediately pulled in from 'Available Charts' (prior to report saving) will
      // have a type of 'volatile' (as indication to the backend scripts for to how to handle the image data)

      entryData.thumbnail_link = entryData.thumbnail_link.replace('type=chart_pool', 'type=volatile');

      if (record.data['duplicate_id'] != undefined && record.data['duplicate_id'].length > 0)
         duplication_id = '&did=' + record.data['duplicate_id'];

      // When dealing with the grid responsible for representing charts to be included into a report, insert a [Change] link
      // to allow for changing the timeframe for entries on-the-fly.

      var editorConfig = '{type:\'' + entryData.timeframe_type;


      if (entryData.timeframe_type.toLowerCase() == 'user defined')
         editorConfig += '\', start: \'' + s_date + '\', end: \'' + e_date + '\'';
      else
         editorConfig += '\', window: \'' + entryData.timeframe_type + '\'';


      editorConfig += ', chart_id: \'' + entryData.chart_id + '\'';

      editorConfig += '}';

      // RESET Link ------------

      var recorded_timeframe_label = XDMoD.Reporting.getParamIn('timeframe_label', entryData.chart_id, '&');

      var reset_link = '<br />&nbsp;';
      var use_reset_link = false;

      if (entryData.timeframe_type.toLowerCase() != recorded_timeframe_label.toLowerCase()) {

         use_reset_link = true;

      }

      // If an update to an entry has been made simply by adjusting the original user-defined date,
      // make the 'Reset' link available...

      if (
         (entryData.timeframe_type.toLowerCase() == recorded_timeframe_label.toLowerCase()) &&
         (entryData.timeframe_type.toLowerCase() == 'user defined')
      )
      {

         var orig_start_date = XDMoD.Reporting.getParamIn('start_date', entryData.chart_id, '&');
         var orig_end_date = XDMoD.Reporting.getParamIn('end_date', entryData.chart_id, '&');

         use_reset_link = (s_date != orig_start_date) || (e_date != orig_end_date);

      }

      if (use_reset_link) {
         reset_link = '<br /><a style="text-decoration: none" href="javascript:void(0)" ' +
                      'onClick="XDMoD.Reporting.Singleton.ChartDateEditor.reset(' + editorConfig + ', \'' + [store.storeId, record.id].join('\', \'') + '\')"><img title="Reset Timeframe" style="margin-top: 3px" src="gui/images/arrow_undo.png"></a>';
      }

      // -----------------------

      var thumb_details = {label: 'View As Chart',    image: 'chart_bar.png'};

      if (entryData.type == 'datasheet')
         thumb_details = {label: 'View As Datasheet', image: 'table.png'};

         thumb_details.label = '';
         thumb_details.image = '';

      var display_type = '';

      var timeframe_selector = '<a id="report_generator_timeframe_selector_img" style="text-decoration: none" href="javascript:void(0)" ' +
                               'onClick="XDMoD.Reporting.Singleton.ChartDateEditor.present(' + editorConfig + ', \'' + [store.storeId, record.id].join('\', \'') + '\')">' +
                               '<img title="Change Timeframe" src="gui/images/date_edit.png"></a>' + reset_link;

     chart_entry_timeframe = '<div style="margin-top: 2px"><a id="report_generator_timeframe_selector" href="javascript:void(0)" onClick="XDMoD.Reporting.Singleton.ChartDateEditor.present(' + editorConfig + ', \'' + [store.storeId, record.id].join('\', \'') + '\')">' +
                              entryData.chart_date_description + '</a></div>';

      /*
      viewSelector = '<div>';
      viewSelector += '<div style="float: left"><img src="gui/images/chart_bar.png"></div>';
      viewSelector += '<div>View As Chart [Change]</div>';
      viewSelector += '</div>';
      */

      chartDateEditFloater = '<div style="float: left; padding-right: 2px; margin-top: 0px">' + display_type + timeframe_selector + '</div>';

   }//if (store.storeId == 'report_generator_included_charts_store')

   // =================

   var img_id = 'imgthumb_' + Ext.id();
   var ttip_id = 'info_ttip_' + Ext.id();
   var ebar_id = 'edit_bar_' + Ext.id();

   return '<div>' +

            // Thumbnail

            '<div style="float: right; border: 1px solid #bbb" onMouseOver="XDMoD.Reporting.EditBarReveal(\'' + ebar_id + '\', true)" onMouseOut="XDMoD.Reporting.EditBarReveal(\'' + ebar_id + '\', false)">' +
            '<img width=180 height=84 onload="dismissPlaceholder(\'' + img_id + '\')" src="' + entryData.thumbnail_link + XDMoD.REST.token + duplication_id + '"/>' +


            '<div id="' + ebar_id + '" style="cursor: pointer; visibility: hidden; margin-left: 156px; margin-top: -24px">' +
               '<img src="gui/images/report_thumb_mag.png" onClick="XDMoD.Reporting.PreviewThumb(' +

                '\''     + entryData.thumbnail_link + XDMoD.REST.token + duplication_id +
                '\', \'' + cleanedChartTitle +
                '\', \'' + entryData.chart_date_description + ' (' + entryData.timeframe_type + ')' +

                '\', \'' + store.storeId + '\', \'' + record.id +

                '\')">' +

            '</div>' +

            '</div>' +


            '<div id="' + img_id + '" style="float: right; border: 1px solid #bbb; position: relative; left: 182px">' +
            '<img width=180 height=84 src="gui/images/report_gen_thumbnail_progress.png"/>' +
            '</div>' +

            '<div class="report_generator_grid_chart_item_renderer" style="float: left;>' +
               '<div style="float: left; padding-right: 3px"><img id="' + ttip_id + '" src="gui/images/info.png" onmouseover="XDMoD.Reporting.Singleton.DrillDetails(event, \'' + ttip_id + '\', \'' + entryData.chart_drill_details + '\')"></div><div>' + XDMoD.Reporting.colorWrap(chartTitle, chartTitleColor) + '</div><br />' +

               chartDateEditFloater +
               chart_entry_timeframe +

               '<div style="margin-top: 5px">' + XDMoD.Reporting.colorWrap(entryData.timeframe_type, '#444') + '</div>' +

            '</div>' +

         '</div>';

};//XDMoD.Reporting.chartDetailsRenderer

// ----------------------------------------------------------

/*
* Singleton for managing the state of the report inclusion checkbox
* @author Ryan Gentner
* @author Amin Ghadersohi
* @date 2011-Feb-07
*/

XDMoD.Reporting.CheckboxRegistry = {};

CCR.xdmod.ReportCheckbox = Ext.extend(Ext.form.Checkbox,  {

   loadedChartArgs: '',
   loadedChartTitle: '',
   loadedChartDrillDetails: '',
   loadedChartDateDescription: '',

   storeChartArguments: function(chart_args, title, drill_details, start_date, end_date, included_in_report) {

      var escaped_chart_args = chart_args.replace(title, encodeURIComponent(title).replace(/@/g, '%40').replace(/\*/g, '%2A').replace(/\//g, '%2F').replace(/\+/g, '%2B').replace(/%20/g, '+'));

      XDMoD.Reporting.CheckboxRegistry[this.module]['chart_id'] = escaped_chart_args;

      XDMoD.Reporting.CheckboxRegistry[this.module]['checked'] = included_in_report == 'y' || included_in_report == true;

      // Store the loaded chart's arguments and title, and date description so the 'chart pool' can quickly access this information as needed.
      this.loadedChartArgs = chart_args;
      this.loadedChartTitle = title;
      this.loadedChartDrillDetails = drill_details;
      this.loadedChartDateDescription = start_date + ' to ' + end_date; //Changed the date format to match the rest of XDMoD "Y-m-d to Y-m-d"

      this.un('check', this.toggleReportInclusion);
      this.setValue(included_in_report == 'y' || included_in_report == true);
      this.on('check', this.toggleReportInclusion);

   },//storeChartArguments

   silentCheckToggle: function(checked) {

      this.un('check', this.toggleReportInclusion);
      this.setValue(checked);
      this.on('check', this.toggleReportInclusion);

      XDMoD.Reporting.CheckboxRegistry[this.module]['checked'] = checked;

   },//silentCheckToggle

   toggleReportInclusion: function (checkbox, checked) {

      XDMoD.Reporting.CheckboxRegistry[this.module]['checked'] = checked;

      var conn = new Ext.data.Connection();

      var cb_module = checkbox.module ? checkbox.module : 'metric_explorer';

      var objParams =
      {

         operation: checked ? 'add_to_queue' : 'remove_from_queue',

         chart_id: checkbox.loadedChartArgs,
         chart_title: checkbox.loadedChartTitle,
         chart_drill_details: checkbox.loadedChartDrillDetails,
         chart_date_desc: checkbox.loadedChartDateDescription,
         module: cb_module

      };

      this.fireEvent('toggled_checkbox', checked ? 'add_to_queue' : 'remove_from_queue');

      conn.request({

         url: 'controllers/chart_pool.php',
         params: objParams,
         method: 'POST',

         callback: function (options, success, response)
         {

            if (success)
            {
               success = CCR.checkJSONResponseSuccess(response);
            }

            if (success)
            {
               // Note the tabs are lazily loaded. The chart store only exists after the
               // reporting tab has been opened and may not exist at the time this checkbox
               // is toggled.
               if (CCR.xdmod.ui.reportGenerator.chartPoolStore) {
                   CCR.xdmod.ui.reportGenerator.chartPoolStore.reload();
               }
               CCR.xdmod.ui.toastMessage('Toggle Chart', 'Complete');
            }
            else
            {
               CCR.xdmod.ui.presentFailureResponse(response, {
                  title: 'Report Builder',
                  wrapperMessage: 'There was a problem managing the report queue.'
               });
            }

         }//callback

      });//conn.request

   },//toggleReportInclusion

   initComponent: function() {

      this.addEvents('toggled_checkbox');
      this.on('check', this.toggleReportInclusion);

      this.getModule = function() {
         return this.module;
      };

      Ext.apply(this, {

         boxLabel: 'Available For Report'

      });//Ext.apply

      CCR.xdmod.ReportCheckbox.superclass.initComponent.call(this);

      XDMoD.Reporting.CheckboxRegistry[this.module] = {
         'ref': this,
         'chart_id': '',
         'checked': false
      };

   }//initComponent

});//CCR.xdmod.ReportCheckbox
