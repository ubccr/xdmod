/*
 * Summary page of XDMoD.
 *
 */

XDMoD.Module.Summary = function (config) {
    XDMoD.Module.Summary.superclass.constructor.call(this, config);
};

Ext.extend(XDMoD.Module.Summary, XDMoD.PortalModule, {
    module_id: 'summary',

    initComponent: function () {
        var self = this;

        var portletStore = new Ext.data.JsonStore({
            restful: true,
            url: XDMoD.REST.url + '/summary/portlets',
            baseParams: {
                token: XDMoD.REST.token
            },
            root: 'data',
            fields: ['name', 'type', 'config', 'column'],
            listeners: {
                load: function () {
                    var i;

                    var portletWidth = 600;
                    var portalColumns = this.reader.jsonData.portalConfig.columns;

                    var portal = new Ext.ux.Portal({
                        items: [],
                        width: portletWidth * portalColumns,
                        border: false,
                        listeners: {
                            drop: function () {
                                if (CCR.xdmod.publicUser) {
                                    // Public user cannot save layout no point in trying
                                    return;
                                }

                                var row;
                                var column;
                                var portalCol;
                                var layout = {};

                                for (column = 0; column < this.items.getCount(); column++) {
                                    portalCol = this.items.get(column);
                                    for (row = 0; row < portalCol.items.getCount(); row++) {
                                        layout[portalCol.items.get(row).name] = [row, column];
                                    }
                                }
                                Ext.Ajax.request({
                                    url: XDMoD.REST.url + '/summary/layout',
                                    params: {
                                        data: Ext.encode({
                                            columns: this.items.getCount(),
                                            layout: layout
                                        })
                                    }
                                });
                            }
                        }
                    });

                    for (i = 0; i < portalColumns; i++) {
                        portal.add(new Ext.ux.PortalColumn({
                            width: portletWidth,
                            style: 'padding: 1px'
                        }));
                    }

                    var durationSelector;
                    if (self.getDurationSelector) {
                        durationSelector = self.getDurationSelector();
                    }

                    var portalwindow = self.getComponent('portalwindow');
                    portalwindow.removeAll();

                    this.each(function (record) {
                        var config = record.get('config');

                        // duration selector only exists for public user view
                        if (durationSelector) {
                            config.start_date = durationSelector.getStartDate().format('Y-m-d');
                            config.end_date = durationSelector.getEndDate().format('Y-m-d');
                            config.aggregation_unit = durationSelector.getAggregationUnit();
                            config.timeframe_label = durationSelector.getDurationLabel();
                        }

                        try {
                            var portlet = Ext.ComponentMgr.create({
                                xtype: record.get('type'),
                                name: record.get('name'),
                                config: config,
                                width: portletWidth
                            });
                            portlet.relayEvents(self, ['duration_change']);
                            portlet.relayEvents(self, ['refresh']);

                            if (record.get('column') === -1) {
                                // The -1 columm is the full width panel above the portal
                                portlet.setWidth(portletWidth * portalColumns);
                                portalwindow.add(portlet);
                            } else {
                                // All others go in the column based portlet view.
                                portal.items.itemAt(record.get('column')).add(portlet);
                            }
                        } catch (e) {
                            Ext.Msg.alert(
                                'Error loading portlets',
                                'The portlet ' + record.get('name') + ' (' + record.get('type') + ')<br />could not be loaded.'
                            );
                        }
                    });

                    portalwindow.add(portal);
                    portalwindow.doLayout();

                    if (CCR.xdmod.publicUser !== true) {
                        var userTour = self.createNewUserTour();
                        var conn = new Ext.data.Connection();

                        conn.request({
                            url: XDMoD.REST.url + '/summary/viewedUserTour',
                            params: {
                                uid: CCR.xdmod.ui.mappedPID,
                                token: XDMoD.REST.token
                            },
                            method: 'GET',
                            callback: function (options, success, response) {
                                var serverResp = Ext.decode(response.responseText);
                                if (serverResp.data.length === 0 || !serverResp.data[0].viewedTour) {
                                    Ext.Msg.show({
                                        cls: 'new-user-tour-dialog-container',
                                        title: 'New User Tour',
                                        msg: "Welcome to XDMoD. The User Tour is a short series of information tips giving an overview of some basic components of XDMoD. Would you like to view the User Tour now?<br /><br /><input type='checkbox' id='new-user-tour-checkbox' /> Please don't show this message again.",
                                        buttons: { no: 'Close', yes: 'Start Tour' },
                                        icon: Ext.Msg.INFO,
                                        fn: function (buttonValue, inputText, showConfig) {
                                            var newUserTourCheckbox = Ext.select('#new-user-tour-checkbox');
                                            if (buttonValue === 'yes') {
                                                userTour.startTour();
                                            } else if (buttonValue === 'no' && newUserTourCheckbox.elements[0].checked === true) {
                                                var connection = new Ext.data.Connection();
                                                connection.request({
                                                    url: XDMoD.REST.url + '/summary/viewedUserTour',
                                                    params: {
                                                        viewedTour: 1,
                                                        token: XDMoD.REST.token
                                                    },
                                                    method: 'POST',
                                                    callback: function (opt, suc, resp) {
                                                        Ext.Msg.alert('Status', 'This message will not be displayed again. If you wish to view the User Tour in the future a link to it can be found by clicking the Help button in the upper right corner of the page.');
                                                    } // callback
                                                }); // conn.request
                                            }
                                        }
                                    });
                                }
                            } // callback
                        }); // conn.request
                    }
                }
            }
        });

        if (CCR.xdmod.publicUser) {
            this.usesToolbar = true;
            this.toolbarItems = { durationSelector: true };
        }

        this.needsRefresh = true;

        Ext.apply(this, {
            items: [{
                region: 'center',
                bodyStyle: {
                    'background-color': '#dcdcdc'
                },
                itemId: 'portalwindow',
                autoScroll: true
            }],
            listeners: {
                activate: function () {
                    if (this.needsRefresh) {
                        this.needsRefresh = false;
                        portletStore.load();
                    }
                },
                request_refresh: function () {
                    var tabPanel = Ext.getCmp('main_tab_panel');
                    if (this === tabPanel.getActiveTab()) {
                        // This is the case where the 'Reset to defaults' button is
                        // clicked in the Profile dialog and the user is already on the
                        // summary page
                        portletStore.load();
                    } else {
                        // The refresh will happen the next time the tab is activated
                        this.needsRefresh = true;
                    }
                }
            }
        });

        XDMoD.Module.Summary.superclass.initComponent.apply(this, arguments);
        this.title = 'Dashboard';
    },

    createNewUserTour: function () {
        var self = this;
        if (self.userTour) {
            return self.userTour;
        }

        self.userTour = new Ext.ux.HelpTipTour({
            title: 'XDMoD Tour',
            items: [
                {
                    html: 'Welcome to XDMoD! This tour will guide you through some of the features of XDMoD.',
                    target: '#tg_summary',
                    position: 't-t'
                },
                {
                    html: 'XDMoD provides a wealth of information. Different functionality is provided by individual tabs listed below.' +
                        '<ul>' +
                        '<li>' +
                        'Dashboard - Pre-selected statistics, charts, and components tailored to you.' +
                        '</li>' +
                        '<li>' +
                        'Usage - A convenient way to browse all available statistics.' +
                        '</li>' +
                        '<li>' +
                        'Metric Explorer - Allows you to create complex charts containing multiple statistics and optionally apply filters.' +
                        '</li>' +
                        '<li>' +
                        'Report Generator - Create reports that may contain multiple charts. Reports may be downloaded directly or scheduled to be emailed periodically.' +
                        '</li>' +
                        '<li>' +
                        'Job Viewer - A detailed view of individual jobs that provides an overall summary of job accounting data, job performance, and a temporal view of a job\'s CPU, network, and disk I/O utilization.' +
                        '</li>' +
                        '</ul>' +
                        '* Additional modules might provide additional tabs not mentioned here.',
                    position: 'tl-bl',
                    maxWidth: 400,
                    offset: [20, 0]
                },
                {
                    html: 'The Dashboard tab presents individual component and summary charts that provide an overview of information that is available throughout XDMoD as well as the ability to access more detailed information. ' +
                        'In order to provide relevant information, XDMoD accounts have an assigned role (set by the XDMoD system administrator), the content of the dashboard is tailored to your role.' +
                        '<ul>' +
                        '<li>' +
                        'User - Information such as a list of all jobs that you have run as well as other useful information such as queue wait times. ' +
                        '</li>' +
                        '<li>' +
                        'Principal Investigator - Information about all the jobs running under your projects. ' +
                        '</li>' +
                        '<li>' +
                        'Center Staff - Information on all user jobs run at the center as well as information you can use to gauge how well the center is running. ' +
                        '</li>' +
                        '</ul>' +
                        'For more information on XDMoD roles, please refer to the XDMoD User Manual available from the Help menu.',
                    target: '#tg_summary',
                    position: 't-t'
                },
                {
                    html: 'Each component provides a toolbar that is customized to provide controls relevant to that component. ' +
                        'Hovering over each control with your mouse will display a tool-tip describing what that control does.' +
                        '<ul>' +
                        '<li>' +
                        '"?" - Display additional information about a component' +
                        '</li>' +
                        '<li>' +
                        '"*" - Open a chart in the Metric Explorer.' +
                        '</li>' +
                        '</ul>',
                    target: '.x-portlet:first .x-panel-header:first',
                    position: 'tl-br',
                    offset: [-10, 0]
                },
                {
                    html: 'The Help button provides you with the following options:' +
                        '<ul>' +
                        '<li>User Manual - A detailed help document for XDMoD.  If help is available for the section of XDMoD you currently are visiting, this' +
                        'will automatically navigate to the respective section.' +
                        '</li>' +
                        '<li>XDMoD Tour - start this tour again' +
                        '</li>' +
                        '</ul>',
                    target: '#help_button',
                    position: 'tr-bl'
                },
                {
                    html: 'The My Profile button allows you to view and update your account settings. Your role will be displayed in the title bar of the My Profile window.' +
                        '<br /><br />>Information you can update includes your Name, Email Address and Password.',
                    target: '#global-toolbar-profile',
                    position: 'tl-bl'
                },
                {
                    html: 'Thank you for viewing the XDMoD Tour. If you want to view this tour again you can find it by clicking the Help button.',
                    target: '#tg_summary',
                    position: 't-t',
                    listeners: {
                        show: function () {
                            var conn = new Ext.data.Connection();
                            conn.request({
                                url: XDMoD.REST.url + '/summary/viewedUserTour',
                                params: {
                                    viewedTour: 1,
                                    token: XDMoD.REST.token
                                },
                                method: 'POST'
                            }); // conn.request
                        }
                    }
                }
            ]
        });

        if (CCR.xdmod.publicUser !== true) {
            Ext.get('help_button').on('click', function () {
                Ext.get('global-toolbar-help-new-user-tour').on('click', function () {
                    Ext.History.add('main_tab_panel:tg_summary');
                    new Ext.util.DelayedTask(function () {
                        self.userTour.startTour();
                    }).delay(10);
                });
            });
        }

        return self.userTour;
    }
});
