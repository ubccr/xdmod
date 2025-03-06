Ext.namespace('XDMoD.Module.JupyterHub');

XDMoD.Module.JupyterHub.NotebookGrid = Ext.extend(Ext.grid.GridPanel, {

    frame: true,
    enableHdMenu: false,
    loadMask: true,
    stripeRows: true,
    cls: 'job-component-grid',
    viewConfig: {
        emptyText: '<div class="no-data-alert">No running Notebook servers found</div>',
        forceFit: true
    },

    initComponent: function () {
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

            if (record.data.cpu_user === null || record.data.cpu_user < 0) {
                return '<div class="job-metric-na">N/A</div>';
            }

            metadata.attr = 'ext:qtip="CPU Usage ' + (record.data.cpu_user * 100.0).toFixed(1) + '%"';

            return String.format('<div class="circle" style="background-color: {0}"></div>', getDataColor(record.data.cpu_user));
        };

        var config = this.config;
        var page_size = this.config.page_size;

        var jobStore = new Ext.data.JsonStore({
            url: XDMoD.REST.url + '/warehouse/search/jobs',
            restful: true,
            root: 'results',
            autoLoad: true,
            totalProperty: 'totalCount',
            baseParams: {
                start_date: config.start_date,
                end_date: config.end_date,
                realm: config.realm,
                limit: page_size,
                start: 0,
                params: JSON.stringify(config.params)
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

        if (config.multiuser) {
            columns.splice(0, 0, {
                header: 'Person',
                width: 90,
                sortable: true,
                dataIndex: 'name'
            });
        }

        this.store = jobStore;

        this.colModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: columns
        });

        this.bbar = new Ext.PagingToolbar({
            store: jobStore,
            displayInfo: true,
            pageSize: page_size,
            prependButtons: true
        });

        this.sm = new Ext.grid.RowSelectionModel({
            singleSelect: true
        });

        this.addListener('rowclick', function (panel, rowIndex) {
            var store = panel.getStore();
            var info = store.getAt(rowIndex);
            var params = {
                action: 'show',
                realm: config.job_viewer_realm ? config.job_viewer_realm : config.realm,
                jobref: info.data[info.data.dtype]
            };
            Ext.History.add('job_viewer?' + Ext.urlEncode(params));

            if (config.row_click_callback) {
                config.row_click_callback(params);
            }
        });

        this.addListener('resetStore', function (newParams) {
            /* Set new search parameters for the job store and reset the
             * paging to the beginning. Note that it is necessary to modify
             * the baseParams because the paging toolbar is used. */
            jobStore.setBaseParam('params', JSON.stringify(newParams));
            jobStore.load({
                params: {
                    start: 0,
                    limit: page_size
                }
            });
        });

        XDMoD.Module.JupyterHub.NotebookGrid.superclass.initComponent.apply(this, arguments);
    }
});

Ext.reg('xdmod-notebookgrid', XDMoD.Module.JupyterHub.NotebookGrid);
