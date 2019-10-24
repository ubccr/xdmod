/*node.js javascript document
 *
 * @authors: Amin Ghadersohi
 * @date: 2/8/2014
 * 
 * Class to encapsulate functionality related to interacting with a mysql database
 *
 * @requirements:
 *	node.js
 *  node.js mysql: npm install mysql
 *
 */

var util = require('util');

function dynamicSort(property) { 
    return function (obj1,obj2) {
        return obj1[property] > obj2[property] ? 1
            : obj1[property] < obj2[property] ? -1 : 0;
    }
}

function dynamicSortMultiple() {
    /*
     * save the arguments object as it will be overwritten
     * note that arguments object is an array-like object
     * consisting of the names of the properties to sort by
     */
    var props = arguments;
    return function (obj1, obj2) {
        var i = 0, result = 0, numberOfProperties = props.length;
        /* try getting a different result from 0 (equal)
         * as long as we have extra properties to compare
         */
        while(result === 0 && i < numberOfProperties) {
            result = dynamicSort(props[i])(obj1, obj2);
            i++;
        }
        return result;
    }
}

var DynamicTable = module.exports.DynamicTable = function(table) {
    util._extend(this,table);
    this.extras = this.meta.extras || [];
    this.triggers = this.meta.triggers || {};
    this.getInsertStatement = function(replace_, ignore, values, _version) {
        if(_version === undefined || _version === null) {
            throw Error('_version cannot be undefined or null');
        }
        var allEntries = [];
        var updateEntries = [];

        var c;
        for(c in values) {
            if(values.hasOwnProperty(c)) {
                allEntries.push(c);
                if(this.meta.unique.indexOf(c) === -1) {
                    updateEntries.push(c + "=VALUES(" + c + ")");
                }
            }
        }

        var stmt = "INSERT " + (ignore ? "IGNORE " : "") + "INTO " + this.meta.schema + "." + this.name + 
            "(" + allEntries.join(",") + ",_version) VALUES (:" + allEntries.join(",:") + "," + _version + ")";

        if(replace_) {
            stmt += " ON DUPLICATE KEY UPDATE _id=LAST_INSERT_ID(_id),_version=VALUES(_version)," + updateEntries.join(",");
        }

        return stmt;
    };

    /**
     * Return the column descriptors in order. Required
     * dimensions first then optional dimensions then the metrics.
     */
    this.getColumns = function () {
        var requiredDimensions = [];
        var optionalDimensions = [];
        var metrics = [];

        var columnNames = Object.keys(this.columns).sort();
        for (let i = 0; i < columnNames.length; i++) {
            let colname = columnNames[i];
            if (this.columns.hasOwnProperty(colname)) {
                let column = this.columns[colname];

                column.name = colname;

                if (column.nullable === false) {
                    if (column.def === null) {
                        column.dimension_type = 'required';
                        requiredDimensions.push(column);
                    } else {
                        column.dimension_type = 'optional';
                        optionalDimensions.push(column);
                    }
                } else {
                    column.dimension_type = 'metric';
                    metrics.push(column);
                }
            }
        }
        return requiredDimensions.concat(optionalDimensions, metrics);
    };

    /**
     * return the dynamic table fields.
     * @param isErrorTable whether to return the structure of the error table (true) or the main table (false)
     */
    this.getDynamicTableFields = function (isErrorTable) {
        if (isErrorTable) {
            return this.getDynamicErrorTableFields();
        }
        return this.getDynamicFactTableFields();
    };

    /**
     * return the dynamic table fields for the fact table
     */
    this.getDynamicFactTableFields = function () {
        var tableFields = [{
            name: '_id',
            type: 'int32',
            nullable: false,
            extra: 'auto_increment'
        }];

        tableFields.push(...this.getColumns());

        tableFields.push(...[{
            name: '_version',
            type: 'int32',
            nullable: false
        }, {
            name: 'last_modified',
            type: 'timestamp',
            nullable: false,
            def: 'CURRENT_TIMESTAMP',
            extra: 'ON UPDATE CURRENT_TIMESTAMP'
        }]);

        return tableFields;
    };

    /**
     * return the dynamic table fields for the error table
     */
    this.getDynamicErrorTableFields = function () {
        var tableFields = [{
            name: '_id',
            type: 'int32',
            nullable: false
        }];

        var columns = this.getColumns();
        for (let i = 0; i < columns.length; i++) {
            if (columns[i].dimension_type === 'required') {
                tableFields.push({
                    name: columns[i].name,
                    type: columns[i].type,
                    nullable: columns[i].nullable,
                    comments: 'DIMENSION VALUE'
                });
            } else {
                tableFields.push({
                    name: columns[i].name,
                    type: 'int32',
                    nullable: true,
                    comments: 'ERROR CODE'
                });
            }
        }

        tableFields.push({
            name: '_version',
            type: 'int32',
            nullable: false
        });

        return tableFields;
    };

    /**
     * return an array of arrays containing the colunm type, name, description and units
     */
    this.getTableDocumentation = function () {
        var reqDims = [];
        var restDims = [];
        var metrics = [];
        var colKeys = Object.keys(this.columns).sort();

        for (var col in colKeys) {
            if (colKeys.hasOwnProperty(col)) {
                var column = this.columns[colKeys[col]];
                column.name = colKeys[col];

                var comments = column.comments ? column.comments : '-';
                if (column.developer_comment) {
                    comments += ' ' + column.developer_comment;
                }
                var unit = column.unit ? column.unit : '-';
                var per = column.per ? column.per : '-';
                if (column.nullable === false) {
                    if (column.def === null) {
                        reqDims.push(['DIMENSION', column.name, comments, unit, per]);
                    } else {
                        restDims.push(['DIMENSION', column.name, comments, unit, per]);
                    }
                } else {
                    metrics.push(['FACT', column.name, comments, unit, per]);
                }
            }
        }

        return reqDims.concat(restDims).concat(metrics);
    };

    this.getAggregationTableFields = function () {
        var ret = [];
        var colKeys = Object.keys(this.columns).sort();
        for (let i = 0; i < colKeys.length; i++) {
            if (this.columns.hasOwnProperty(colKeys[i])) {
                let column = this.columns[colKeys[i]];

                let aggColumn = {};
                for (let x in column) {
                    if (column.hasOwnProperty(x)) {
                        aggColumn[x] = column[x];
                    }
                }
                ret.push(aggColumn);
            }
        }

        ret.sort(dynamicSortMultiple('name'));
        return ret;
    };

    this.getErrorInsertStatement = function (replace_, ignore, values, _version) {
        if (_version === undefined || _version === null) {
            throw Error('_version cannot be undefined or null');
        }
        var allEntries = [];
        for (let c in values) {
            if (values.hasOwnProperty(c)) {
                allEntries.push(c);
            }
        }

        return (replace_ ? 'replace' : ('insert' + (ignore ? ' ignore' : '')))
            + ' into ' + this.meta.schema + '.' + this.name + '_errors (_id,' + allEntries.join(',') + ',_version)'
            + ' values (:_id, :' + allEntries.join(',:') + ',' + _version + ')';
    };
};

module.exports.sqlType = function (type, length) {
    switch (type) {
        case 'uint32':
            return 'int(11) unsigned';
        case 'int32':
            return 'int(11)';
        case 'tinyint':
            return 'tinyint(4)';
        case 'double':
            return 'double';
        case 'string':
            return 'varchar(' + (length !== undefined ? length : 50) + ')';
        case 'timestamp':
            return 'timestamp';
        case 'array':
            throw Error('Type ' + type + ' should not be in a table as a column');
        default:
            throw Error('Type ' + type + ' is unknown');
    }
};

module.exports.queryFormat = function (query, values) {
    if (!values) return query;
    var ret = query.replace(/\:(\w+)/g, function (txt, key) {
        if (values.hasOwnProperty(key)) {
            return this.escape(values[key]);
        }
        return txt;
    }.bind(require('mysql')));
    return ret;
}
