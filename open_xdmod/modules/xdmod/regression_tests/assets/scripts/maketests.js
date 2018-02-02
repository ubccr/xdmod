/* maketest - script that generates usage explorer test inputs for all
 * Groups in a realm.
 */

/* Configuration settings:
*/
var fs = require('fs');
var path = require('path');
var dir = __dirname;
var rolesFile = '/etc/xdmod/roles.json';
var datawarehouseFile = '/etc/xdmod/datawarehouse.json';
var outputDir = path.join(dir, '../../../tests/artifacts/xdmod-test-artifacts/xdmod/regression/current/input');
if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir);
}

var referenceRequest = {
    public_user: 'true',
    realm: 'Accounts',
    group_by: 'none',
    statistic: 'open_account_count',
    /*
     * This date specifically only tests the date range of the reference data.
     * To use this on another dataset be sure to change your date range.
     */
    start_date: '2016-12-22',
    end_date: '2017-01-01',
    timeframe_label: '2016',
    scale: '1',
    aggregation_unit: 'Auto',
    dataset_type: 'aggregate',
    thumbnail: 'n',
    query_group: 'po_usage',
    display_type: 'line',
    combine_type: 'side',
    limit: '10',
    offset: '0',
    log_scale: 'n',
    show_guide_lines: 'y',
    show_trend_line: 'y',
    show_percent_alloc: 'n',
    show_error_bars: 'y',
    show_aggregate_labels: 'n',
    show_error_labels: 'n',
    show_title: 'y',
    width: '916',
    height: '484',
    legend_type: 'bottom_center',
    font_size: '3',
    inline: 'n',
    operation: 'get_data'
};

var writeTestData = function (testIndex, testData) {
    fs.writeFileSync(outputDir + '/' + testIndex + '.json', testData);
};

var run = function () {
    var roledata = JSON.parse(fs.readFileSync(rolesFile));
    var datawarehousedata = JSON.parse(fs.readFileSync(datawarehouseFile)).realms;

    if (roledata.roles.default) {
        for (var j = 0; j < roledata.roles.default.query_descripters.length; j++) {
            var desc = roledata.roles.default.query_descripters[j];

            if (datawarehousedata[desc.realm]) {
                process.stdout.write('Found ' + desc.realm + '\n');
                var stats = datawarehousedata[desc.realm].statistics;
                for (var s = 0; s < stats.length; s++) {
                    if (stats[s].name === 'weight' || stats[s].visible === false) {
                        process.stdout.write('Ignoring ' + desc.realm + ' ' + stats[s].name + '\n');
                    } else {
                        referenceRequest.realm = desc.realm;
                        referenceRequest.group_by = desc.group_by;
                        referenceRequest.statistic = stats[s].name;

                        var ref = JSON.stringify(referenceRequest, null, 4);

                        var testName = referenceRequest.realm + ':' + referenceRequest.group_by + ':' + referenceRequest.statistic;
                        writeTestData(testName, ref);
                    }
                }
            }
        }
    }
};

run();
