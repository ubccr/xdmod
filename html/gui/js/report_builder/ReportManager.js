Ext.namespace('XDMoD');

XDMoD.ReportManager = Ext.extend(Ext.Panel, {
    switchView: function (id) {
        rpanel = this.getLayout();

        if (rpanel != 'card') {
            rpanel.setActiveItem(id);
        }
    },

    buildReport: function (
        report_name,
        report_id,
        target_child,
        build_only,
        format
    ) {
        if (format == undefined) { format = 'pdf'; }

        if (build_only) {
            XDMoD.TrackEvent(
                'Report Generator',
                'Attempting to build and download report'
            );
        } else {
            XDMoD.TrackEvent(
                'Report Generator',
                'Attempting to build and send report'
            );
        }

        var action = build_only ? 'downloading' : 'sending';

        XDMoD.TrackEvent(
            'Report Generator',
            'Building report',
            Ext.encode({name: report_name, action: action, format: format})
        );

        var activity = build_only ?
            'Preparing report for download' : 'Generating and sending report';

        target_child.showMask(
            '<center>' + activity + '<br /><b>' + report_name +
            '</b><br /><img src="gui/images/progbar_2.gif">' +
            '<br />Please Wait</center>'
        );

        var conn = new Ext.data.Connection({
            // allow for generous 'execution time' so that lengthy
            // reports can be compiled (10 min.)
            timeout: 600000
        });

        conn.request({
            url: 'controllers/report_builder.php',

            params: {
                operation: 'send_report',
                report_id: report_id,
                build_only: build_only,
                export_format: format
            },

            method: 'POST',

            callback: function (options, success, response) {
                var responseData;
                if (success) {
                    responseData = CCR.safelyDecodeJSONResponse(response);
                    success = CCR.checkDecodedJSONResponseSuccess(responseData);
                }

                if (success) {
                    XDMoD.TrackEvent(
                        'Report Generator',
                        'Building of report complete',
                        Ext.encode({name: report_name, format: format})
                    );

                    var location = 'controllers/report_builder.php/' +
                        responseData.report_name +
                        '?operation=download_report&report_loc=' +
                        responseData.report_loc + '&format=' + format;

                    var activeTemplate =
                        '<center><br />' +
                        '<img src="gui/images/checkmark.png"><br /><br />' +
                        '<div style="color: #080; border: none">' +
                        responseData.message +
                        '</div>' +
                        '</center>';

                    if (responseData.build_only) {
                        var w = new Ext.Window({
                            title: 'Report Built',
                            width: 220,
                            height: 120,
                            resizable: false,
                            closeAction: 'close',
                            layout: 'border',
                            cls: 'wnd_report_built',

                            listeners: {
                                close: function () {
                                    XDMoD.TrackEvent(
                                        'Report Generator',
                                        'Closed Report Built confirmation window'
                                    );
                                }
                            },

                            items: [
                                new Ext.Panel({
                                    region: 'west',
                                    width: 70,
                                    html: '<img src="gui/images/report_icon_wnd.png">',
                                    baseCls: 'x-plain'
                                }),
                                new Ext.Panel({
                                    region: 'center',
                                    width: 150,
                                    layout: 'border',
                                    margins: '5 5 5 5',
                                    items: [
                                        new Ext.Panel({
                                            region: 'center',
                                            html: 'Your report has been built and can now be viewed.',
                                            baseCls: 'x-plain'
                                        }),
                                        new Ext.Button({
                                            region: 'south',
                                            text: 'View Report',
                                            handler: function () {
                                                XDMoD.TrackEvent(
                                                    'Report Generator',
                                                    'Clicked on View Report button in Report Built window'
                                                );
                                                window.open(location);
                                            }
                                        })
                                    ]
                                })
                            ]
                        });

                        w.show();
                    }

                    target_child.showMask(activeTemplate);

                    // Hide / dismiss the mask after 3 seconds ...
                    (function () { target_child.hideMask(); }).defer(3000);
                } else {
                    CCR.xdmod.ui.presentFailureResponse(response, {
                        title: 'Report Manager',
                        wrapperMessage: 'There was a problem trying to prepare the report.'
                    });

                    target_child.hideMask();
                }
            }
        });
    },

    initComponent: function () {
        this.reportsOverview = new XDMoD.ReportsOverview({parent: this});

        this.reportCreator = new XDMoD.ReportCreator({parent: this});

        // Pass the reportCreator reference to the ChartDateEditor so
        // the ChartDateEditor knows what store to work with (during the
        // logic associated with the 'Update' handler).  Same reasoning
        // goes for the ReportEntryTypeMenu.

        this.reportCreator.on('show', function (p) {
            XDMoD.Reporting.Singleton.ChartDateEditor.setCreatorPanel(p);
            XDMoD.Reporting.Singleton.ReportEntryTypeMenu.setCreatorPanel(p);
        });

        this.reportPreview = new XDMoD.ReportPreview({parent: this});

        Ext.apply(this, {
            layout: 'card',
            margins: '2 5 5 2',
            activeItem: 0,
            border: false,
            items: [
                this.reportsOverview,
                this.reportCreator,
                this.reportPreview
            ]
        });

        XDMoD.ReportManager.superclass.initComponent.call(this);
    }
});

