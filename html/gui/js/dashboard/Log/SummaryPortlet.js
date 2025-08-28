/**
 * Log summary portlet.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Log');

XDMoD.Log.SummaryPortlet = Ext.extend(XDMoD.Summary.Portlet, {

    constructor: function (config) {
        config = config || {};

        if (this.store === undefined) {
            this.store = new XDMoD.Log.SummaryStore({ ident: config.ident });
        }

        XDMoD.Log.SummaryPortlet.superclass.constructor.call(this, config);
    },

    getHtml: function (record) {
        var startTime = record.get('process_start_time'),
            endTime = record.get('process_end_time'),
            dataStartTime = record.get('data_start_time'),
            dataEndTime = record.get('data_end_time'),
            contents = [],
            countMessages = [],
            logLevels,
            statuses,
            examinedDetails = [],
            examinedCount;


        if (startTime) {
            contents.push('Start time: ' + startTime.format('Y-m-d H:i:s'));
        }

        if (endTime) {
            contents.push('End time: ' + endTime.format('Y-m-d H:i:s'));
        } else {
            contents.push('<span style="color:red">Not complete</span>');
        }

        if (startTime && endTime) {
            contents.push('Run time: ' +
                this.formatDuration(endTime.getTime() - startTime.getTime()));
        }

        if (dataStartTime && dataEndTime) {
            contents.push(
                'Data range: ' + dataStartTime + ' - ' + dataEndTime
            );
        }

        logLevels = [
            // record field     label              text color
            ['emergency_count', 'Emergencies',     'red'],
            ['alert_count',     'Alerts',          'red'],
            ['critical_count',  'Critical Errors', 'red'],
            ['error_count',     'Errors',          'red'],
            ['warning_count',   'Warnings',        'orange']
        ];

        Ext.each(logLevels, function (item) {
            var field = item[0],
                label = item[1],
                color = item[2],
                count = record.get(field);

            if (count > 0) {
                countMessages.push('<span style="color:' + color + ';">' +
                    count + ' ' + label + '</span>');
            }
        }, this);

        if (countMessages.length > 0) {
            contents.push(countMessages.join(', '));
        }

        statuses = [
            // record field               label           text color
            ['records_loaded_count',      'Loaded',       'black'],
            ['records_incomplete_count',  'Incomplete',   'orange'],
            ['records_parse_error_count', 'Parse Errors', 'red'],
            ['records_queued_count',      'Queued',       'red'],
            ['records_error_count',       'Errors',       'red'],
            ['records_sql_error_count',   'SQL Errors',   'red'],
            ['records_unknown_count',     'Unknowns',     'red'],
            ['records_duplicate_count',   'Duplicates',   'red'],
            ['records_exception_count',   'Exceptions',   'red']
        ];

        Ext.each(statuses, function (item) {
            var field = item[0],
                label = item[1],
                color = item[2],
                count = record.get(field);

            if (count > 0) {
                examinedDetails.push('<span style="color:' + color + ';">' +
                    count + ' ' + label + '</span>');
            }
        }, this);

        examinedCount = record.get('records_examined_count');

        if (examinedDetails.length > 0 || examinedCount > 0) {
            contents.push(examinedCount + ' Records Examined' + (
                examinedDetails.length > 0 ? (
                    ' (' + examinedDetails.join(', ') + ')'
                ) : ''
            ));
        }

        return contents.join('<br/>');
    }
});

