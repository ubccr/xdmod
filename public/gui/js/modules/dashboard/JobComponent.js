/* global moment */
/**
 * XDMoD.Module.Dashboard.JobComponent
 *
 */

Ext.namespace('XDMoD.Module.Dashboard');

XDMoD.Module.Dashboard.JobComponent = Ext.extend(CCR.xdmod.ui.Portlet, {

    layout: 'fit',
    title: 'Jobs',

    initComponent: function () {
        var page_size = 9;

        this.help = {
            title: 'Jobs'
        };

        if (this.config.multiuser) {
            this.help.html = '<img src="/gui/images/help/job-multi-component.svg" />';
        } else {
            this.help.html = '<img src="/gui/images/help/job-component.svg" />';
            page_size = 10;
        }

        // Sync date ranges
        var dateRanges = CCR.xdmod.ui.DurationToolbar.getDateRanges();

        var timeframe = this.config.timeframe ? this.config.timeframe : '30 day';

        var date = dateRanges.find(function (element) {
            return element.text === timeframe;
        }, this);

        this.title += ' - ' + date.start.format('Y-m-d') + ' to ' + date.end.format('Y-m-d');

        // The default search parameters are set to all jobs - this
        // will result in all of the jobs that the user has permission to
        // view.
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

        XDMoD.Module.Dashboard.JobComponent.superclass.initComponent.apply(this, arguments);
    }
});

Ext.reg('xdmod-dash-job-cmp', XDMoD.Module.Dashboard.JobComponent);
