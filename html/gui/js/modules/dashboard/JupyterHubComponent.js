/* global moment */
/**
 * XDMoD.Module.Dashboard.JupyterHubComponent
 *
 */

Ext.namespace('XDMoD.Module.Dashboard');

XDMoD.Module.Dashboard.JupyterHubComponent = Ext.extend(CCR.xdmod.ui.Portlet, {

    layout: 'fit',
    title: 'Running JupyterLab Servers',
    tools: [{
        id: 'gear',
        hidden: CCR.xdmod.publicUser,
        qtip: 'Launch JupyterLab',
        scope: this,
        handler: function (event, toolEl, panel) {
            XDMoD.Module.JupyterHub.launchJupyterLab();
        }
    }],

    initComponent: function () {
        var page_size = 9;

        this.help = {
            title: 'JupyterHub'
        };

        this.title = 'JupyterLab Servers'

        var defaultParams = {};

        var gridpanel = {
            xtype: 'xdmod-jobgrid',
            config: {
                realm: CCR.xdmod.ui.rawDataAllowedRealms[0],
                start_date: date.start.format('Y-m-d'),
                end_date: date.end.format('Y-m-d'),
                params: defaultParams,
                multiuser: this.config.multiuser,
                page_size: page_size
            }
        };

        var self = this;

        if (this.config.multiuser) {
            gridpanel.tbar = {
                items: [
                    'Filter: ',
                    ' ',
                    new Ext.form.ClearableComboBox({
                        emptyText: 'Filter by Person...',
                        triggerAction: 'all',
                        selectOnFocus: true,
                        displayField: 'long_name',
                        valueField: 'id',
                        typeAhead: true,
                        mode: 'local',
                        forceSelection: true,
                        enableKeyEvents: true,
                        store: new Ext.data.JsonStore({
                            url: XDMoD.REST.url + '/warehouse/dimensions/person',
                            restful: true,
                            autoLoad: true,
                            baseParams: {
                                realm: CCR.xdmod.ui.rawDataAllowedRealms[0]
                            },
                            root: 'results',
                            fields: [
                                { name: 'id', type: 'string' },
                                { name: 'name', type: 'string' },
                                { name: 'short_name', type: 'string' },
                                { name: 'long_name', type: 'string' }
                            ],
                            listeners: {
                                exception: function (proxy, type, action, exception, response) {
                                    switch (response.status) {
                                        case 403:
                                        case 500:
                                            var details = Ext.decode(response.responseText);
                                            Ext.Msg.alert('Error ' + response.status + ' ' + response.statusText, details.message);
                                            break;
                                        case 401:
                                            // Do nothing
                                            break;
                                        default:
                                            Ext.Msg.alert(response.status + ' ' + response.statusText, response.responseText);
                                    }
                                }
                            }
                        }),
                        listeners: {
                            select: function (combo, record) {
                                self.getComponent(0).fireEvent('resetStore', { person: [record.id] });
                            },
                            reset: function () {
                                self.getComponent(0).fireEvent('resetStore', defaultParams);
                            }
                        }
                    })
                ]
            };
        }
        this.items = [gridpanel];

        this.height = (this.width * 11.0) / 17.0;

        XDMoD.Module.Dashboard.JupyterHubComponent.superclass.initComponent.apply(this, arguments);
    }
});

Ext.reg('xdmod-dash-jupyterhub-cmp', XDMoD.Module.Dashboard.JupyterHubComponent);
