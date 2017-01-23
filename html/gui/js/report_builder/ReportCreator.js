Ext.namespace('XDMoD');

XDMoD.ReportCreator = Ext.extend(Ext.form.FormPanel, {

    /**
     * A base configuration object for the report name text field.
     *
     * This is shared between this form and the report Save As form, and
     * each form adds their own properties on top of this configuration.
     */
    nameFieldConfig: {
        name: 'report_name',
        fieldLabel: 'File Name',
        emptyText: 'Required, ' + XDMoD.constants.maxReportNameLength + ' max',
        msgTarget: 'under',

        allowBlank: false,
        blankText: 'This field is required.',
        maxLength: XDMoD.constants.maxReportNameLength,
        maxLengthText: 'Maximum length (' + XDMoD.constants.maxReportNameLength + ' characters) exceeded.'
    },

    initComponent: function () {
        this.expandGeneralInfo = true;

        this.needsSave = false;

        var self = this;

        // Public methods

        self.initializeFields = function (report_name) {
            txtReportName.setValue(report_name);
            txtReportTitle.setValue('');
            txtReportHeader.setValue('');
            txtReportFooter.setValue('');

            cmbFont.setValue('Arial');
            cmbFormat.setValue('Pdf');
            cmbSchedule.setValue('Once');
            cmbDelivery.setValue('E-mail');

            self.expandGeneralInfo = true;
            self.reportCharts.initGridFunctions();
        };

        self.initReportGrid = function () {
            self.expandGeneralInfo = true;
            self.reportCharts.initGridFunctions();
        };

        self.setReportName = function (value) {
            txtReportName.setValue(value);
        };

        self.setReportTitle = function (value) {
            txtReportTitle.setValue(value);
        };

        self.setReportHeader = function (value) {
            txtReportHeader.setValue(value);
        };

        self.setReportFooter = function (value) {
            txtReportFooter.setValue(value);
        };

        self.setReportFont = function (value) {
            cmbFont.setValue(value);
        };

        self.setReportFormat = function (value) {
            cmbFormat.setValue(value);
        };

        self.setReportSchedule = function (value) {
            cmbSchedule.setValue(value);
        };

        self.setReportDelivery = function (value) {
            cmbDelivery.setValue(value);
        };

        self.setReportID = function (id) {
            self.report_id = id;
        };

        self.getReportID = function () {
            return self.report_id;
        };

        var inputwidth = 250;

        var thumbnailChartLayoutPreview =
            'gui/images/report_generator/report_layout_1_up.png';

        var layoutThumbnailId = Ext.id();

        this.setChartsPerPage = function (value) {

            // Need to temporarily disable the change event handler for
            // the chart layout radio group's 'change' event, since a
            // report that was just loaded shouldn't appear as "dirty"

            rdoChartLayout.removeListener(
                'change',
                rdoChartLayout.changeEventHandler
            );

            rdoChartLayout.setValue(value  + '_up');

            thumbnailChartLayoutPreview =
                'gui/images/report_generator/report_layout_' +
                value +
                '_up.png';

            var containerChartLayoutPreview =
                document.getElementById(layoutThumbnailId);

            if (containerChartLayoutPreview) {
                containerChartLayoutPreview.src = thumbnailChartLayoutPreview;
            }

            (function () {
                rdoChartLayout.on('change', rdoChartLayout.changeEventHandler);
            }).defer(400);
        };

        this.on('activate', function () {
            document.getElementById(layoutThumbnailId).src =
                thumbnailChartLayoutPreview;
        }, this, {single: true});

        this.dirtyConfig = function (field, nv, ov) {
            CCR.xdmod.reporting.dirtyState = true;
            self.needsSave = true;
            btnSaveReport.setDisabled(false);
            btnSaveReportAs.setDisabled(false);
        };

        this.isDirty = function () {
            return self.needsSave;
        };

        this.reportCharts = new XDMoD.ReportCreatorGrid({
            region: 'center',
            title: 'Included Charts',
            parentRef: self,
            width: '35%'
        });

        this.on('activate', function () {
            if (self.reportCharts.reportStore.data.length == 0) {
                (function () {
                    self.reportCharts.reportStore.removeAll();
                }).defer(100);
            }
        });

        var font_store = new Ext.data.SimpleStore({
            fields: ['name'],
            data: [['Arial']]
        });

        var cmbFont = new Ext.form.ComboBox({
            editable: false,
            width: 140,
            fieldLabel: 'Font',
            mode: 'local',
            store: font_store,
            triggerAction: 'all',
            displayField: 'name',
            hidden: true,
            valueField: 'name',
            emptyText: 'No Font Selected',
            listeners: {
                change: self.dirtyConfig
            }
        });

        cmbFont.setValue(1);

        var format_store = new Ext.data.SimpleStore({
            fields: ['name', 'format'],
            data: [
                ['PDF', 'Pdf'],
                ['Word Document', 'Doc']
            ]
        });

        var cmbFormat = new Ext.form.ComboBox({
            editable: false,
            width: 140,
            fieldLabel: 'Delivery Format',
            mode: 'local',
            store: format_store,
            triggerAction: 'all',
            displayField: 'name',
            valueField: 'format',
            emptyText: 'No Format Selected',

            listeners: {
                select: function (combo, record, index) {
                    var newValue = record.get(this.initialConfig.valueField);
                    if (this.startValue != newValue) {
                        XDMoD.TrackEvent(
                            'Report Generator (Report Editor)',
                            'Updated delivery format',
                            combo.getRawValue()
                        );
                        self.dirtyConfig();
                    }
                }
            }
        });

        cmbFormat.setValue(0);

        var schedule_store = new Ext.data.SimpleStore({
            fields: ['schedule'],
            data: [
                ['Once'],
                ['Daily'],
                ['Weekly'],
                ['Monthly'],
                ['Quarterly'],
                ['Semi-annually'],
                ['Annually']
            ]
        });

        var cmbSchedule = new Ext.form.ComboBox({
            id: 'report_generator_report_schedule',
            editable: false,
            width: 140,
            fieldLabel: 'Schedule',
            mode: 'local',
            store: schedule_store,
            triggerAction: 'all',
            displayField: 'schedule',
            valueField: 'schedule',
            emptyText: 'No Schedule Selected',

            listeners: {
                select: function (combo, record, index) {
                    var newValue = record.get(this.initialConfig.valueField);
                    if (this.startValue != newValue) {
                        XDMoD.TrackEvent(
                            'Report Generator (Report Editor)',
                            'Updated schedule',
                            combo.getValue()
                        );
                        self.dirtyConfig();
                    }
                }
            }
        });

        cmbSchedule.setValue(0);

        var delivery_store =  new Ext.data.SimpleStore({
            fields: ['method'],
            data: [['E-mail' ]]
        });

        var cmbDelivery = new Ext.form.ComboBox({
            editable: false,
            width: 140,
            fieldLabel: 'Delivery',
            mode: 'local',
            store: delivery_store,
            triggerAction: 'all',
            displayField: 'method',
            valueField: 'method',
            emptyText: 'No Format Selected',
            hidden: true,

            listeners: {
                change: function (cb) {
                    XDMoD.TrackEvent(
                        'Report Generator (Report Editor)',
                        'Updated delivery type',
                        cb.getValue()
                    );
                    self.dirtyConfig();
                }
            }
        });

        var lblDelivery = new Ext.form.Label({
            html: '<div style="font-size: 12px; padding-top: 5px">Delivery Method: <b>E-Mail</b><br /><br /></div>'
        });

        cmbDelivery.setValue(0);

        var flushReloadReportCharts = function (report_id) {
            var objParams = {
                operation: 'fetch_report_data',
                flush_cache: true,
                selected_report: report_id
            };

            var conn = new Ext.data.Connection();

            conn.request({
                url: 'controllers/report_builder.php',
                params: objParams,
                method: 'POST',

                callback: function (options, success, response) {
                    var reportData;
                    if (success) {
                        reportData = CCR.safelyDecodeJSONResponse(response);
                        success = CCR.checkDecodedJSONResponseSuccess(reportData);
                    }

                    if (success) {
                        self.reportCharts.reportStore.loadData(reportData.results);
                    } else {
                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: 'Report Pool',
                            wrapperMessage: 'There was a problem trying to prepare the report editor.'
                        });
                    }
                }
            });
        };

        var saveReport = function (save_callback, override_config) {
            // Initialize variables.
            override_config = typeof override_config === 'undefined' ? {} : override_config;

            var generateCopy = typeof override_config.generateCopy !== 'undefined';
            var copyName = override_config.generateCopy;

            // Set up an array and functions for disabling and re-enabling fields.
            // Certain form fields may not be used if this is called from
            // somewhere other than the main form.
            var disabledFields = [];
            var disableReportField = function (field) {
                if (disabledFields.indexOf(field) !== -1) {
                    return;
                }

                field.disable();
                disabledFields.push(field);
            };
            var reenableReportFields = function () {
                var numDisabledFields = disabledFields.length;
                for (var i = 0; i < numDisabledFields; i++) {
                    disabledFields[i].enable();
                }
                disabledFields = [];
            };

            // Sanitization

            // If a report is being copied, don't check the main form's name field.
            if (generateCopy) {
                disableReportField(txtReportName);
            }

            // If any fields in the form are invalid, stop.
            if (!self.getForm().isValid()) {
                var errorMessage = 'One or more fields are invalid.';
                if (override_config.callback) {
                    override_config.callback(false, errorMessage);
                }

                CCR.xdmod.ui.reportGeneratorMessage(
                    'Report Editor',
                    errorMessage,
                    false
                );

                reenableReportFields();
                return;
            }

            // If there are no reports in the chart, stop.
            if (self.reportCharts.reportStore.data.length == 0) {
                if (override_config.callback != undefined) {
                    override_config.callback(false, 'Report needs charts');
                }

                CCR.xdmod.ui.reportGeneratorMessage(
                    'Report Editor',
                    'You must have at least one chart in this report.'
                );

                reenableReportFields();
                return;
            }

            var reportData = {};

            reportData.operation = 'save_report';

            reportData.phase =
                (self.getReportID().length > 0) ? 'update' : 'create';

            reportData.report_id = self.getReportID();

            reportData.report_name   = txtReportName.getValue();
            reportData.report_title  = txtReportTitle.getValue();
            reportData.report_header = txtReportHeader.getValue();
            reportData.report_footer = txtReportFooter.getRawValue();

            reportData.charts_per_page =
                rdoChartLayout.getValue().charts_per_page;

            reportData.report_font     = cmbFont.getRawValue();
            reportData.report_format   = cmbFormat.getValue();
            reportData.report_schedule = cmbSchedule.getRawValue();
            reportData.report_delivery = cmbDelivery.getRawValue();

            if (generateCopy) {
                reportData.phase       = 'create';
                reportData.report_id   = '';
                reportData.report_name = copyName;
            }

            var chartCount = 1;

            // Iteration occurs such that the the store is traversed
            // top-down (store order complies with grid ordering)

            self.reportCharts.reportStore.data.each(function () {
                var chartData = [];
                var tf_start, tf_end, cache_ref;

                if (this.data.thumbnail_link.indexOf("type=cached") != -1) {
                    tf_start = XDMoD.Reporting.getParamIn(
                        'start',
                        this.data.thumbnail_link,
                        '&'
                    );

                    tf_end = XDMoD.Reporting.getParamIn(
                        'end',
                        this.data.thumbnail_link,
                        '&'
                    );

                    cache_ref = XDMoD.Reporting.getParamIn(
                        'ref',
                        this.data.thumbnail_link,
                        '&'
                    );

                    // When the report chart in question has had its
                    // timeframe updated, then saved, the backend needs
                    // to know where the blob was stored so it can
                    // transfer it accordingly.

                    reportData['chart_cacheref_' + chartCount] =
                        tf_start + ';' + tf_end + ';' + cache_ref;
                }

                if (this.data.thumbnail_link.indexOf("type=volatile") != -1) {
                    tf_start =
                        this.data.chart_date_description.split(' to ')[0];
                    tf_end =
                        this.data.chart_date_description.split(' to ')[1];

                    cache_ref = XDMoD.Reporting.getParamIn(
                        'ref',
                        this.data.thumbnail_link,
                        '&'
                    );

                    var duplicate_id = (this.data.duplicate_id) ?
                        this.data.duplicate_id : '';

                    reportData['chart_cacheref_' + chartCount] = tf_start + ';' + tf_end + ';' + 'xd_report_volatile_' + cache_ref + duplicate_id;
                }

                // Encode semicolon for active_role value
                chartData.push(this.data.chart_id.replace(/;/g, '%3B'));
                chartData.push(this.data.chart_title.replace(/;/g, '%3B'));
                chartData.push(this.data.chart_drill_details.replace(/;/g, '%3B'));
                chartData.push(this.data.chart_date_description);
                chartData.push(this.data.timeframe_type);
                chartData.push(this.data.type);

                reportData['chart_data_' + chartCount++] = chartData.join(';');
            });

            Ext.Ajax.request({
                url: 'controllers/report_builder.php',
                params: reportData,
                method: 'POST',

                callback: function (options, success, response) {
                    var responseData;
                    if (success) {
                        responseData = CCR.safelyDecodeJSONResponse(response);
                        success = CCR.checkDecodedJSONResponseSuccess(responseData);
                    }

                    if (success) {
                        self.parent.reportsOverview.reportStore.reload();

                        if (!generateCopy) {
                            btnSaveReport.setDisabled(true);
                            self.needsSave = false;
                            CCR.xdmod.reporting.dirtyState = false;

                            self.setReportID(responseData.report_id);

                            // This reload triggers (server-side)
                            // cache cleanup
                            flushReloadReportCharts(responseData.report_id);

                            var action =
                                responseData.phase.slice(0,1).toUpperCase() +
                                responseData.phase.slice(1) + 'd';

                            XDMoD.TrackEvent(
                                'Report Generator (Report Editor)',
                                'Report ' + action + ' successfully',
                                reportData.report_name
                            );

                            CCR.xdmod.ui.reportGeneratorMessage(
                                'Report Editor',
                                'Report ' + action + ' Successfully',
                                true,
                                function () {
                                    if (save_callback) {
                                        save_callback();
                                    }
                                }
                            );
                        } else {
                            XDMoD.TrackEvent(
                                'Report Generator (Report Editor)',
                                'Report successfully saved as a copy',
                                reportData.report_name
                            );

                            if (typeof override_config.callback !== "undefined") {
                                override_config.callback(
                                    true,
                                    'Report saved successfully'
                                );
                            }

                            CCR.xdmod.ui.reportGeneratorMessage(
                                'Report Editor',
                                'Report successfully saved as a copy',
                                true
                            );
                        }
                    } else {
                        if (typeof override_config.callback !== "undefined") {
                            override_config.callback(
                                false,
                                'Problem saving report'
                            );
                        }

                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: 'Report Editor',
                            wrapperMessage: 'There was a problem creating / updating this report.'
                        });
                    }
                }
            });

            reenableReportFields();
        };

        var saveReportAs = function (el) {
            var saveAsDialog = new XDMoD.SaveReportAsDialog({
                executeHandler: function (report_filename, callback) {
                    saveReport(undefined, {
                        generateCopy: report_filename,
                        callback: callback
                    });
                }
            });

            saveAsDialog.present(el, txtReportName.getValue());
        };

        var previewReport = function () {
            if (self.getReportID().length == 0) {
                CCR.xdmod.ui.reportGeneratorMessage(
                    'Report Editor',
                    'You must save this report before you can preview it.'
                );
                return;
            }

            if (self.isDirty()) {
                CCR.xdmod.ui.reportGeneratorMessage(
                    'Report Editor',
                    'You have made changes to this report which you must save before previewing.'
                );
                return;
            }

            if (self.reportCharts.reportStore.data.length == 0) {
                CCR.xdmod.ui.reportGeneratorMessage(
                    'Report Editor',
                    'You must have at least one chart in this report.'
                );
                return;
            }

            var reportData = {};

            if (self.isDirty() == true) {

                // Generate data for preview on the client-side
                reportData.report_id = self.getReportID();
                reportData.success   = true;
                reportData.charts    = [];

                // Iteration occurs such that the the store is traversed
                // top-down (store order complies with grid ordering)

                var chartCount = 0;
                var date_utils = new DateUtilities();

                var chartData = {};

                self.reportCharts.reportStore.data.each(function () {
                    var chart_page_position =
                        chartCount % rdoChartLayout.getValue().charts_per_page;

                    if (chart_page_position == 0) {
                        chartData = {};
                        chartData.report_title = (chartCount == 0) ? '<span style="font-family: arial; font-size: 22px">' + Ext.util.Format.trim(txtReportTitle.getValue()) + '</span><br />' : '';
                        chartData.header_text  = '<span style="font-family: arial; font-size: 12px">' + Ext.util.Format.trim(txtReportHeader.getValue()) + '</span>';
                        chartData.footer_text  = '<span style="font-family: arial; font-size: 12px">' + Ext.util.Format.trim(txtReportFooter.getRawValue()) + '</span>';
                    }

                    chartData['chart_title_'  + chart_page_position] =
                        '<span style="font-family: arial; font-size: 16px">' +
                        this.data.chart_title + '</span>';

                    if (this.data.chart_drill_details.length == 0) {
                        this.data.chart_drill_details = CCR.xdmod.org_abbrev;
                    }

                    chartData['chart_drill_details_' + chart_page_position] =
                        '<span style="font-family: arial; font-size: 12px">' +
                        this.data.chart_drill_details + '</span>';

                    var s_date, e_date;

                    if (
                        this.data.timeframe_type.toLowerCase() == 'user defined'
                    ) {
                        s_date =
                            this.data.chart_date_description.split(' to ')[0];
                        e_date =
                            this.data.chart_date_description.split(' to ')[1];
                    } else {
                        var endpoints =
                            date_utils.getEndpoints(this.data.timeframe_type);

                        s_date = endpoints.start_date;
                        e_date = endpoints.end_date;
                    }

                    // Overwrite chart_id and chart_date_description as
                    // necessary

                    this.data.chart_date_description = s_date + ' to ' + e_date;

                    chartData['chart_timeframe_' + chart_page_position] =
                        '<span style="font-family: arial; font-size: 14px">' +
                        this.data.chart_date_description + '</span>';

                    chartData['chart_id_' + chart_page_position] =
                        this.data.thumbnail_link;

                    chartCount++;

                    if (
                        chartCount %
                        rdoChartLayout.getValue().charts_per_page == 0
                    ) {
                        reportData.charts.push(chartData);
                    }
                });

                var remaining_slots =
                    chartCount % rdoChartLayout.getValue().charts_per_page;

                if (remaining_slots != 0) {

                    // Pad the remaining slots

                    for (
                        i = remaining_slots;
                        i < rdoChartLayout.getValue().charts_per_page;
                        i++
                    ) {
                        chartData['chart_title_'  + i] = '';
                        chartData['chart_id_' + i] = 'img_placeholder.php?';
                        chartData['chart_timeframe_' + i] = '';
                        chartData['chart_drill_details_' + i] = '';
                    }

                    reportData.charts.push(chartData);
                }

                self.parent.reportPreview.initPreview(
                    txtReportName.getValue(),
                    self.getReportID(),
                    XDMoD.REST.token,
                    1,
                    reportData,
                    rdoChartLayout.getValue().charts_per_page
                );
            } else {
                self.parent.reportPreview.initPreview(
                    txtReportName.getValue(),
                    self.getReportID(),
                    XDMoD.REST.token,
                    1,
                    undefined,
                    rdoChartLayout.getValue().charts_per_page
                );
            }

            self.parent.switchView(2);

        };

        var rdoChartLayoutGroupID = Ext.id() + '-chart-layout-group';

        var rdoChartLayout = new Ext.form.RadioGroup({
            defaultType: 'radio',
            columns: 1,
            margins: '23 0 0 0',
            cls: 'custom_search_mode_group',
            flex: 1,
            vertical: true,

            items: [
                {
                    boxLabel: '1 Chart Per Page',
                    checked: true,
                    name: rdoChartLayoutGroupID,
                    inputValue: '1_up',
                    charts_per_page: 1
                },
                {
                    boxLabel: '2 Charts Per Page',
                    name: rdoChartLayoutGroupID,
                    inputValue: '2_up',
                    charts_per_page: 2
                }
            ],

            changeEventHandler: function (rg, rc) {
                XDMoD.TrackEvent(
                    'Report Generator (Report Editor)',
                    'Updated layout for report',
                    rc.charts_per_page + ' chart(s) per page'
                );

                thumbnailChartLayoutPreview =
                    'gui/images/report_generator/report_layout_' +
                    rc.inputValue + '.png';

                document.getElementById(layoutThumbnailId).src =
                    thumbnailChartLayoutPreview;

                self.dirtyConfig();
            }
        });

        rdoChartLayout.on('change', rdoChartLayout.changeEventHandler);

        var reportConfigBox = {marginLeft: '4px', marginTop: '7px'};

        var handleTextFieldChange = function (t, newValue, oldValue, fieldTrackingName) {
            XDMoD.utils.trimOnBlur(t);

            var trimmedValue = t.getValue();

            if (trimmedValue === oldValue) {
                return;
            }

            XDMoD.TrackEvent(
                'Report Generator (Report Editor)',
                'Updated ' + fieldTrackingName + ' for report',
                trimmedValue
            );

            self.dirtyConfig();
        };

        var txtReportName = new Ext.form.TextField(Ext.apply({}, {
            listeners: {
                change: function (t, newValue, oldValue) {
                    handleTextFieldChange(t, newValue, oldValue, 'file name');
                }
            }
        }, self.nameFieldConfig));

        var maxReportTitleLength = XDMoD.constants.maxReportTitleLength;
        var txtReportTitle = new Ext.form.TextField({
            name: 'report_title',
            fieldLabel: 'Report Title',
            emptyText: 'Optional, ' + maxReportTitleLength + ' max',
            msgTarget: 'under',

            maxLength: maxReportTitleLength,
            maxLengthText: 'Maximum length (' + maxReportTitleLength + ' characters) exceeded.',

            listeners: {
                change: function (t, newValue, oldValue) {
                    handleTextFieldChange(t, newValue, oldValue, 'title');
                }
            }
        });

        var maxReportHeaderLength = XDMoD.constants.maxReportHeaderLength;
        var txtReportHeader = new Ext.form.TextField({
            name: 'report_header',
            fieldLabel: 'Header Text',
            emptyText: 'Optional, ' + maxReportHeaderLength + ' max',
            msgTarget: 'under',

            maxLength: maxReportHeaderLength,
            maxLengthText: 'Maximum length (' + maxReportHeaderLength + ' characters) exceeded.',

            listeners: {
                change: function (t, newValue, oldValue) {
                    handleTextFieldChange(t, newValue, oldValue, 'header text');
                }
            }
        });

        var maxReportFooterLength = XDMoD.constants.maxReportFooterLength;
        var txtReportFooter = new Ext.form.TextField({
            name: 'report_footer',
            fieldLabel: 'Footer Text',
            emptyText: 'Optional, ' + maxReportFooterLength + ' max',
            msgTarget: 'under',

            maxLength: maxReportFooterLength,
            maxLengthText: 'Maximum length (' + maxReportFooterLength + ' characters) exceeded.',
            
            listeners: {
                change: function (t, newValue, oldValue) {
                    handleTextFieldChange(t, newValue, oldValue, 'footer text');
                }
            }
        });

        this.reportInfo = new Ext.Panel({
            labelWidth: 95,
            frame: true,
            title: 'General Information',
            width: 220,
            defaults: {width: 200},
            labelAlign: 'top',
            defaultType: 'textfield',
            style: reportConfigBox,
            layout: 'form',

            items: [
                txtReportName,
                txtReportTitle,
                txtReportHeader,
                txtReportFooter
            ]
        });

        this.sectionChartLayout = new Ext.Panel({
            height: 120,
            width: 220,
            frame: true,
            title: 'Chart Layout',
            region: 'center',
            cls: 'report_generator_chart_layout',
            style: reportConfigBox,

            layout: {
                type: 'hbox',
                pack: 'start',
                align: 'stretch'
            },

            items: [
                rdoChartLayout,
                {
                    html: '<img id="' + layoutThumbnailId +
                        '" src="' + thumbnailChartLayoutPreview + '">',
                    width: 70
                }
            ]
        });

        this.scheduleOptions = new Ext.Panel({
            labelWidth: 95,
            frame: true,
            title: 'Scheduling',
            bodyStyle: 'padding:5px 5px 0',
            width: 220,
            layout: 'form',

            style: reportConfigBox,

            defaults: {width: 200},
            labelAlign: 'top',
            cls: 'user_profile_section_general',
            defaultType: 'textfield',

            items: [
                cmbFont,
                cmbFormat,
                cmbSchedule,
                cmbDelivery,
                cmbFormat,
                lblDelivery
            ]
        });

        this.reportOptions = new Ext.Panel({
            region: 'west',
            baseCls: 'x-plain',
            width: 243,
            autoScroll: true,
            cls: 'no-underline-invalid-fields-form',

            items: [
                this.reportInfo,
                this.sectionChartLayout,
                this.scheduleOptions
            ]
        });

        var sendReport = function (build_only, format) {

            // If build_only is set (and set to true), then the report
            // will be built and not e-mailed

            var action = build_only ? 'download' : 'send';

            if (self.getReportID().length == 0) {
                CCR.xdmod.ui.reportGeneratorMessage(
                    'Report Editor',
                    'You must save this report before you can ' + action + ' it.'
                );

                return;
            }

            if (self.isDirty() == true) {
                CCR.xdmod.ui.reportGeneratorMessage(
                    'Report Editor',
                    'You have made changes to this report which you must save before ' + action  + 'ing.'
                );

                return;
            }

            var report_name = txtReportName.getValue();

            self.parent.buildReport(report_name, self.getReportID(), self, build_only, format);

        };

        var returnToOverview = function () {
            if (self.isDirty() == true) {
                XDMoD.TrackEvent(
                    'Report Generator (Report Editor)',
                    'Presented with Unsaved Changes dialog'
                );

                Ext.Msg.show({
                    maxWidth: 800,
                    minWidth: 400,

                    title: 'Unsaved Changes',
                    msg: "There are unsaved changes.<br />Do you wish to save this report before closing the Report Editor?<br /><br />If you press <b>No</b>, you will lose all your changes.",
                    buttons: Ext.MessageBox.YESNOCANCEL,

                    fn: function (resp) {
                        if (resp == 'cancel') {
                            XDMoD.TrackEvent(
                                'Report Generator (Report Editor)',
                                'User cancelled Unsaved Changes dialog'
                            );

                            return;
                        }

                        if (resp == 'yes') {
                            XDMoD.TrackEvent(
                                'Report Generator (Report Editor)',
                                'User chose to save changes via Unsaved Changes dialog'
                            );

                            saveReport(function () {
                                self.parent.switchView(0);
                            });
                        }

                        if (resp == 'no') {
                            XDMoD.TrackEvent(
                                'Report Generator (Report Editor)',
                                'User chose to not save changes via Unsaved Changes dialog'
                            );

                            btnSaveReport.setDisabled(true);
                            self.needsSave = false;
                            self.parent.switchView(0);
                        }
                    },

                    icon: Ext.MessageBox.QUESTION
                });
            } else {
                self.parent.switchView(0);
            }
        };

        this.on('activate', function (p) {

            // Make sure that the 'General Information' panel is visible
            // (by default) when the Report Editor becomes active (we
            // don't want this happening during report previewing,
            // however)

            if (p.expandGeneralInfo == true) {
                p.reportInfo.expand();
            }

            p.expandGeneralInfo = false;
        });

        var btnSaveReport = new Ext.Button({
            iconCls: 'btn_save',
            text: 'Save',
            disabled: true,

            handler: function () {
                XDMoD.TrackEvent(
                    'Report Generator (Report Editor)',
                    'Clicked on the Save button'
                );
                saveReport();
            }
        });

        var btnSaveReportAs = new Ext.Button({
            iconCls: 'btn_save',
            text: 'Save As',
            tooltip: 'Create and save a copy of this report.',
            disabled: true,

            handler: function () {
                XDMoD.TrackEvent(
                    'Report Generator (Report Editor)',
                    'Clicked on the Save As button'
                );

                saveReportAs(this);
            }
        });

        this.allowSaveAs = function (b) {
            btnSaveReportAs.setDisabled(!b);
        };

        Ext.apply(this, {
            title: 'Report Editor',
            layout: 'border',
            cls: 'report_edit',

            items: [ this.reportOptions, this.reportCharts ],

            plugins: [new Ext.ux.plugins.ContainerMask ({ masked:false })],

            tbar: {
                items: [
                    btnSaveReport,
                    btnSaveReportAs,
                    {
                        xtype: 'button',
                        iconCls: 'btn_preview',
                        text: 'Preview',
                        tooltip: 'See a visual representation of the selected report.',
                        handler: previewReport
                    },

                    new XDMoD.Reporting.ReportExportMenu({
                        instance_module: 'Report Editor',
                        sendMode: true,
                        exportItemHandler: sendReport
                    }),

                    new XDMoD.Reporting.ReportExportMenu({
                        instance_module: 'Report Editor',
                        exportItemHandler: sendReport
                    }),

                    '->',

                    {
                        xtype: 'button',
                        iconCls: 'btn_return_to_overview',
                        text: 'Return To <b>My Reports</b>',

                        handler: function () {
                            XDMoD.TrackEvent(
                                'Report Generator (Report Editor)',
                                'Clicked on Return To My Reports'
                            );
                            returnToOverview();
                        }
                    }
                ]
            }
        });

        XDMoD.ReportCreator.superclass.initComponent.call(this);
    }
});

