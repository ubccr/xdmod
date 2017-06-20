Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

XDMoD.Module.JobViewer.GanttChart = Ext.extend(XDMoD.Module.JobViewer.ChartTab, {

    initComponent: function () {
        this.chartSettings = {
            chart: {
                type: 'columnrange',
                grouping: false,
                inverted: true
            },
            yAxis: {
                type: 'datetime',
                minTickInterval: 1000,
                title: {
                    text: 'Time (' + self.displayTimezone + ')'
                }
            }
        };

        this.panelSettings = {
            pageSize: 11,
            url: this.url,
            baseParams: this.baseParams,
            store: {
                fields: ['series', 'schema', 'categories']
            }
        };

        XDMoD.Module.JobViewer.GanttChart.superclass.initComponent.call(this, arguments);

        this.addListener('updateChart', function (store) {
            if (store.getCount() < 1) {
                return;
            }
            var record = store.getAt(0);

            this.updateTimezone(record.data.schema.timezone);

            var clickEvent = function (evt) {
                var info = {
                    title: 'Peers of ' + record.data.schema.ref.text,
                    realm: evt.point.ref.realm,
                    text: evt.point.category,
                    job_id: evt.point.ref.jobid,
                    local_job_id: evt.point.ref.local_job_id,
                    resource: evt.point.ref.resource
                };
                Ext.History.add('job_viewer?job=' + window.btoa(JSON.stringify(info)));
            };

            while (this.chart.series.length > 0) {
                this.chart.series[0].remove(false);
            }

            this.chart.colorCounter = 0;
            this.chart.symbolCounter = 0;
            this.chart.xAxis[0].setCategories(record.data.categories, false);
            this.chart.yAxis[0].update({
                title: {
                    text: 'Time (' + record.data.schema.timezone + ')'
                },
                min: record.data.series[0].data[0].low,
                max: record.data.series[0].data[0].high
            }, false);

            var i;
            for (i = 0; i < record.data.series.length; i++) {
                if (i > 0) {
                    // Only add clicks for the other peer jobs not this job.
                    record.data.series[i].cursor = 'pointer';
                    record.data.series[i].events = {
                        click: clickEvent
                    };
                }
                this.chart.addSeries(record.data.series[i], false);
            }

            this.chart.hideLoading();
            this.chart.redraw();
        });
    }
});
