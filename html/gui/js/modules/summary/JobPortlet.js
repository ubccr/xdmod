/* global moment */
/**
 * XDMoD.Modules.SummaryPortlets.JobPortlet
 *
 */

Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.JobPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    collapsible: false,
    title: 'Jobs',
    tools: [{
        id: 'help'
    }],

    initComponent: function () {
        var page_size = 9;

        var formatDateWithTimezone = function (value) {
            return moment(value * 1000).format('Y-MM-DD HH:mm:ss z');
        };

        var jobEfficiency = function (value, metadata, record) {
            var getDataColor = function (data) {
                var color = 'gray';
                var steps = [{
                    value: 0.25,
                    color: '#FF0000'
                }, {
                    value: 0.50,
                    color: '#FFB336'
                }, {
                    value: 0.75,
                    color: '#DDDF00'
                }, {
                    value: 1,
                    color: '#50B432'
                }];

                var i;
                var step;
                for (i = 0; i < steps.length; i++) {
                    step = steps[i];
                    if (data <= step.value) {
                        color = step.color;
                        break;
                    }
                }
                return color;
            };

            if (record.data.cpu_user < 0) {
                return 'N/A';
            }

            metadata.attr = 'ext:qtip="CPU Usage ' + (record.data.cpu_user * 100.0).toFixed(1) + '%"';

            return String.format('<div class="circle" style="background-color: {0}"></div>', getDataColor(record.data.cpu_user));
        };

        // Sync date ranges
        var dateRanges = CCR.xdmod.ui.DurationToolbar.getDateRanges();

        var timeframe = this.config.timeframe ? this.config.timeframe : '30 day';

        var date = dateRanges.find(function (element) {
            return element.text === timeframe;
        }, this);

        this.title += ' - ' + date.start.format('Y-m-d') + ' to ' + date.end.format('Y-m-d');

        if (!this.config.multiuser) {
            this.tools[0].qtip = 'This panel shows a list of your HPC jobs for which there is data in XDMoD. Click on a row to view the detailed information about a job.';
            page_size = 10;
        } else {
            this.tools[0].qtip = 'This panel shows a list of the HPC jobs that ran under your account for which there is data in XDMoD. Click on a row to view the detailed information about a job.';
        }

        // The default search parameters are set to all jobs - this
        // will result in all of the jobs that the user has permission to
        // view.
        var defaultParams = {};

        var jobStore = new Ext.data.JsonStore({
            restful: true,
            url: XDMoD.REST.url + '/warehouse/search/jobs',
            root: 'results',
            autoLoad: true,
            totalProperty: 'totalCount',
            baseParams: {
                start_date: date.start.format('Y-m-d'),
                end_date: date.end.format('Y-m-d'),
                realm: CCR.xdmod.ui.rawDataAllowedRealms[0],
                limit: page_size,
                start: 0,
                verbose: true,
                params: JSON.stringify(defaultParams)
            },
            fields: [
                { name: 'dtype', mapping: 'dtype', type: 'string' },
                { name: 'resource', mapping: 'resource', type: 'string' },
                { name: 'name', mapping: 'name', type: 'string' },
                { name: 'jobid', mapping: 'jobid', type: 'int' },
                { name: 'local_job_id', mapping: 'local_job_id', type: 'int' },
                { name: 'text', mapping: 'text', type: 'string' },
                'cpu_user',
                'start_time_ts',
                'end_time_ts'
            ]
        });

        /* Set new search parameters for the job store and reset the
         * paging to the beginning. Note that it is necessary to modify
         * the baseParams because the paging toolbar is used. */
        var resetStore = function (newParams) {
            jobStore.setBaseParam('params', JSON.stringify(newParams));
            jobStore.load({
                params: {
                    start: 0,
                    limit: page_size
                }
            });
        };

        var columns = [{
            header: 'Job Identifier',
            width: 140,
            tooltip: 'The job identifier includes the resource that ran the job and the id provided by the resource manager.',
            dataIndex: 'text'
        }, {
            header: 'Start',
            renderer: formatDateWithTimezone,
            tooltip: 'The start time of the job',
            width: 115,
            fixed: true,
            dataIndex: 'start_time_ts'
        }, {
            header: 'End',
            renderer: formatDateWithTimezone,
            tooltip: 'The end time of the job',
            width: 115,
            fixed: true,
            dataIndex: 'end_time_ts'
        }, {
            header: 'CPU',
            renderer: jobEfficiency,
            tooltip: 'The average CPU usage for the job. The text NA indicates that the metric is unavailable.',
            width: 40,
            fixed: true,
            dataIndex: 'cpu_user'
        }];

        if (this.config.multiuser) {
            columns.splice(0, 0, {
                header: 'Person',
                width: 90,
                sortable: true,
                dataIndex: 'name'
            });
        }

        var gridpanel = {
            xtype: 'grid',
            frame: true,
            store: jobStore,
            enableHdMenu: false,
            loadMask: true,
            stripeRows: true,
            cls: 'job-portlet-grid',
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },
                columns: columns
            }),
            viewConfig: {
                emptyText: 'No Job Records found for the specified time range',
                forceFit: true
            },
            bbar: new Ext.PagingToolbar({
                store: jobStore,
                displayInfo: true,
                pageSize: page_size,
                prependButtons: true
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true
            }),
            listeners: {
                rowclick: function (panel, rowIndex) {
                    var store = panel.getStore();
                    var info = store.getAt(rowIndex);
                    var params = {
                        action: 'show',
                        realm: store.baseParams.realm,
                        jobref: info.data[info.data.dtype]
                    };
                    Ext.History.add('job_viewer?' + Ext.urlEncode(params));
                }
            }
        };

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
                                resetStore({ person: [record.id] });
                            },
                            reset: function () {
                                resetStore(defaultParams);
                            }
                        }
                    })
                ]
            };
        }
        this.items = [gridpanel];

        this.height = (this.width * 11.0) / 17.0;

        XDMoD.Modules.SummaryPortlets.JobPortlet.superclass.initComponent.apply(this, arguments);
    }
});

Ext.reg('JobPortlet', XDMoD.Modules.SummaryPortlets.JobPortlet);
