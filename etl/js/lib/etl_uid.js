/**
 * class etl_uid
 * implements a function that can retrieve a unique identifier for the specified
 * schema in the datawarehouse.
 */

module.exports = function(outConfig) {

    var dbHandle;
    switch(outConfig.dbEngine) {
        case 'mysqldb':
            dbHandle =  require('mysql');
            break;
        default:
            throw new Error('unsupported database type');
    }

    var queryStr = "SELECT uuid FROM etl_uid" +
                   "   WHERE NOW() BETWEEN valid_from AND valid_to" +
                   "   ORDER BY valid_from DESC LIMIT 1";

    return {
        getProfileUuid: function(callback) {
            var dbConnection = dbHandle.createConnection(outConfig.config);
            dbConnection.query(queryStr, function(err, result) {
                    dbConnection.end();
                    if(err) {
                        callback(err, null);
                    } else {
                        callback(err, result[0].uuid);
                    }
                });
        }
    };
};
