/**
 * User Dashboard
 *
 * Currently this defines `XDMoD.Module.Summary` because it is a drop in replacement for the summary tab
 * while it is still in beta.  This should be moved at a later date
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
            url: XDMoD.REST.url + '/dashboard/components',
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
                                    url: XDMoD.REST.url + '/dashboard/layout',
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

                    if (CCR.xdmod.publicUser !== true && !XDMoD.tourPromptShown) {
                        var conn = new Ext.data.Connection();
                        conn.request({
                            url: XDMoD.REST.url + '/dashboard/viewedUserTour',
                            success: function (response) {
                                var serverResp = Ext.decode(response.responseText);
                                if (serverResp.data.length === 0 || !serverResp.data[0].viewedTour) {
                                    Ext.Msg.show({
                                        cls: 'new-user-tour-dialog-container',
                                        title: 'XDMoD Tour',
                                        msg: "Welcome to XDMoD! The XDMoD Tour is a short series of informational tips giving an overview of some basic components of XDMoD. Would you like to view the tour now?<br /><br /><input type='checkbox' id='new-user-tour-checkbox' /> Please don't show this message again.",
                                        buttons: { no: 'Close', yes: 'Start Tour' },
                                        icon: Ext.Msg.INFO,
                                        fn: function (buttonValue, inputText, showConfig) {
                                            var newUserTourCheckbox = Ext.select('#new-user-tour-checkbox');
                                            if (newUserTourCheckbox.elements[0].checked) {
                                                Ext.Ajax.request({
                                                    url: XDMoD.REST.url + '/dashboard/viewedUserTour',
                                                    params: {
                                                        viewedTour: 1
                                                    },
                                                    success: function () {
                                                        if (buttonValue === 'no') {
                                                            Ext.Msg.alert('Status', 'This message will not be displayed again. If you wish to view the tour in the future a link to it can be found by clicking the Help button in the upper right corner of the page.');
                                                        }
                                                    }
                                                });
                                            }
                                            if (buttonValue === 'yes') {
                                                XDMoD.createTour();
                                                Ext.History.add('main_tab_panel:tg_summary');
                                                new Ext.util.DelayedTask(function () {
                                                    XDMoD.tour.startTour();
                                                }).delay(10);
                                            }
                                        }
                                    });
                                }
                                XDMoD.tourPromptShown = true;
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
    }
});
