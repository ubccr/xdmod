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

var mysql = require('mysql'),
	util = require('util');

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
                var unit = column.unit ? column.unit : '-';
                if (column.nullable === false) {
                    if (column.def === null) {
                        reqDims.push(['DIMENSION', column.name, comments, unit]);
                    } else {
                        restDims.push(['DIMENSION', column.name, comments, unit]);
                    }
                } else {
                    metrics.push(['FACT', column.name, comments, unit]);
                }
            }
        }

        return reqDims.concat(restDims).concat(metrics);
    };

	this.getCreateTableStatement = function() {
		var reqDims = [], 
			restDims = [], 
			metrics = [],
			colKeys = Object.keys(this.columns).sort();
		for(var col in colKeys) {
			var column = this.columns[colKeys[col]];
			column.name = colKeys[col];
			column.sqlType = sqlType(column.type, column.length);

			var comments = 'COMMENT ' + mysql.escape(column.comments?column.comments:'');
			if(column.nullable === false ) {
				if(column.def === null) {
					reqDims.push(column.name + ' ' + column.sqlType + ' NOT NULL ' + comments);
				} else {
					restDims.push(column.name + ' ' + column.sqlType + ' NOT NULL DEFAULT \'' + column.def + '\' ' + comments);
				}
			} else{
				metrics.push(column.name + ' ' + column.sqlType + ' DEFAULT ' + (column.def === null? 'NULL':'\'' + column.def + '\'') + ' ' + comments);
			}
		}
		
		var allDims = [
			'    _id INT NOT NULL AUTO_INCREMENT',
			reqDims.join(',\n    '), 
			restDims.join(',\n    '), 
			metrics.join(',\n    '), 
			'_version INT NOT NULL',
			'UNIQUE KEY pk_index (' + this.meta.unique.join(',') + ')',
			this.extras.join(',\n    '),
			'PRIMARY KEY (_id)'
		];
		
    var ret = ['CREATE TABLE IF NOT EXISTS ' + this.name + '(\n' + allDims.join(',\n    ') + '\n) engine = myisam'];

        if(this.triggers) {
            var verbs = { before: "BEFORE", after: "AFTER" };
            var nouns = { insert: "INSERT", update: "UPDATE", del: "DELETE" };

            for(var verb in verbs) {
                for(var noun in nouns) {
                    var action = verb + "_" + noun;
                    if(this.triggers.hasOwnProperty(action)) {
                        var triggername = "`"+this.meta.schema+ "`.`" + this.name + verb + noun + "`";
                        var stmt = "DELIMITER $$\n";
                        stmt += "DROP TRIGGER IF EXISTS " + triggername + "$$\n";
                        stmt += "USE `"+this.meta.schema+"`$$\n";
                        stmt += "CREATE TRIGGER " + triggername + "\n";
                        stmt += verbs[verb] + ' ' + nouns[noun] + ' ON `' + this.name + '`\n';
                        stmt += "FOR EACH ROW\nBEGIN\n";
                        stmt += this.triggers[action];
                        stmt += "END$$\nDELIMITER ;\n";
                        ret.push(stmt);
                    }
                }
            }
        }
		
		return ret;
	},
	this.getAggregationTableFields = function() {
		var ret = [],
			colKeys = Object.keys(this.columns).sort();
		for(var col in colKeys) {
			var column = this.columns[colKeys[col]];
			
			var aggColumn = {};
            for( x in column ){
                aggColumn[x] = column[x];
            }
			ret.push(aggColumn);		
		}	
		
		ret.sort(dynamicSortMultiple("name"));	
		return ret;
	},
	this.getErrorInsertStatement = function(replace_, ignore, values, _version) {
		if(_version === undefined || _version === null) throw Error('_version cannot be undefined or null');
		//if(_id === undefined || _id === null) throw Error('_id cannot be undefined or null');
		var allEntries = [];

		for(var c in values) {
			var column = this.columns[c];
			allEntries.push(c);
		}
		return (replace_ ? 'replace' : ('insert' + (ignore ? ' ignore' : '' )))
			+ ' into ' + this.meta.schema + '.' + this.name + '_errors (_id,' + allEntries.join(',') + ',_version)'
			+ ' values (:_id, :' + allEntries.join(',:') + ',' + _version + ')'; 
	},
	this.getCreateErrorTableStatement = function() {
		var reqDims = [], 
			restDims = [], 
			metrics = [],
			colKeys = Object.keys(this.columns).sort();
		for(var col in colKeys) {
			var column = this.columns[colKeys[col]];
			column.sqlType = sqlType(column.type, column.length);
			
			if(column.nullable === false) {
				if(column.def === null) {
					reqDims.push(colKeys[col] + ' ' + column.sqlType + ' NOT NULL COMMENT \'DIMENSION VALUE\'');
				} else {
					restDims.push(colKeys[col] + ' int DEFAULT NULL COMMENT \'ERROR CODE\'');
				}
			} else
			{
				metrics.push(colKeys[col] + ' int DEFAULT NULL COMMENT \'ERROR CODE\'');
			}
		}
		
		var allDims = [
			'    _id INT NOT NULL', 
			reqDims.join(',\n    '),
			restDims.join(',\n    '), 
			metrics.join(',\n    '), 
			'_version INT NOT NULL'
		];
		allDims.push( this.meta.extras.join(',\n    '), '    PRIMARY KEY (_id)'/*, 'KEY version_index (_version)'*/);

    var ret = 'CREATE TABLE IF NOT EXISTS ' + this.name + '_errors (' + allDims.join(',\n    ') + '\n) engine = myisam';
		
		return ret;
	}
}

var sqlType = module.exports.sqlType = function(type, length) {
	switch(type) {
		case 'uint32':
			return 'int unsigned';
			break;
		case "int32":
			return 'int';
			break;
        case 'tinyint':
            return 'tinyint';
            break;
		case "double":
			return 'double';
			break;
		case 'string':				
			return 'varchar(' + (length !== undefined ? length : 50) + ')';
			break;
		case "array":
			throw Error('Type '+ type + ' should not be in a table as a column');				
		default:
			throw Error('Type '+ type + ' is unknown');
	}
}

var queryFormat = module.exports.queryFormat = function (query, values) {
    if (!values) return query;
    var ret = query.replace(/\:(\w+)/g, function (txt, key) {
        if (values.hasOwnProperty(key)) {
            return this.escape(values[key]);
        }
        return txt;
    }.bind(require('mysql')));
    return ret;
}
