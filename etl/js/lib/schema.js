/*node.js javascript document
 *
 * @authors: Amin Ghadersohi,
 *			 Thomas Yearke
 * @date: 2/20/2014
 *
 * Class to encapsulate functionality related to an etl schema
 *
 * @requirements:
 *	node.js
 *
 */

var util = require('util');

var Schema = module.exports = function(schema) {
	util._extend(this,schema);
	//TODO: validate the schema, make sure referenced fields in derived fields
	//and other stuff is all valid before run time.
}


/**
 * Retrieves the schema field for a given field in the resource map's schema.
 *
 * @param mappingField The name of the mapping field to get the schema field for.
 * @return The schema field or null if mappingField not found.
 */
Schema.prototype.getField = function (mappingField) {
	var schemaFields = this.fields;

    // If the given field name is an exact match to a field in the schema,
    if (mappingField in schemaFields) {
        return schemaFields[mappingField];

        // Otherwise, treat each field name in the schema as a regular expression
        // and check if any exactly match the given field. If so, use the matching
        // field's default value.
    } else {
        for (var schemaField in schemaFields) {
            var schemaFieldRegex = new RegExp(util.format('^%s$', schemaField));

            if (schemaFieldRegex.test(mappingField)) {
                schemaFields[mappingField] = JSON.parse(JSON.stringify(schemaFields[schemaField]));
                schemaFields[mappingField].dynamictags = schemaFieldRegex.exec(mappingField);
                return schemaFields[mappingField];
            }
        }
    }

    return null;
};

function bindAttributes(query, values) {
    if (!values) return query;
    var ret = query.replace(/\:(\w+)/g, function (txt, key) {
        if (values.hasOwnProperty(key)) {
            return this.escape(values[key].value);
        }
        return txt;
    }.bind(require('mysql')));
    return ret;
}

var applyMappingToField = function (field, mapping) {
    var fieldName = '';

    if (!mapping || !mapping[field]) {
        return field;
    }

    if (typeof mapping[field] === 'string') {
        fieldName = mapping[field];
    } else {
        if (mapping[field].alias) {
            fieldName = mapping[field].alias + '.';
        }
        if (mapping[field].field) {
            fieldName += mapping[field].field;
        } else {
            fieldName += field;
        }
    }
    return fieldName + ' AS ' + field;
};

Schema.prototype.getDerivedFields = function (transformed /* , queryFormatFn */) {
    var ret = {};
    var queries = {};
    var values = {};
    var q;
    var query;
    var field;
    var derivedField;
    var fields;

    for (q in this.derivedFieldQueries) {
        if (this.derivedFieldQueries.hasOwnProperty(q)) {
            query = this.derivedFieldQueries[q];
            fields = [];
            if (query.value) {
                values[q] = query.value(transformed.data);
                continue;
            }
            for (field in this.derivedFields) {
                if (this.derivedFields.hasOwnProperty(field)) {
                    derivedField = this.derivedFields[field];
                    if (derivedField.queries.indexOf(q) > -1) {
                        fields.push(applyMappingToField(field, query.mapping));
                    }
                }
            }
            queries[q] = 'select SQL_NO_CACHE ' + fields.join(', ')
                + (query.table ? ' from ' + query.table : '')
                + (query.where ? ' where ' + query.where : '');
            queries[q] = bindAttributes(queries[q], transformed.data);
        }
    }

    for (field in this.derivedFields) {
        if (this.derivedFields.hasOwnProperty(field)) {
            derivedField = this.derivedFields[field];

            for (q in derivedField.queries) {
                if (derivedField.queries.hasOwnProperty(q)) {
                    query = queries[derivedField.queries[q]];
                    if (query !== undefined) {
                        var cacheable = this.derivedFieldQueries[derivedField.queries[q]].cacheable;
                        if (cacheable === undefined) {
                            cacheable = true;
                        }
                        ret[field] = { query: query, schemaField: derivedField, cacheable: cacheable };
                    } else {
                        ret[field] = { value: values[derivedField.queries[q]], schemaField: derivedField };
                    }
                }
            }
        }
    }

    return ret;
};

/**
 * Retrieves the schema drivedField for a given field 
 *
 * @param field The name of the field to get the schema derivedField for.
 * @return The schema derivedField or null if field not found.
 */
Schema.prototype.getDerivedField = function (field) {

    // If the given field name is an exact match to a field in the schema,
    if (field in this.derivedFields) {
        return this.derivedFields[field];

        // Otherwise, treat each field name in the schema as a regular expression
        // and check if any exactly match the given field. If so, use the matching
        // field's default value.
    } else {
        for (var schemaField in this.derivedFields) {
            var schemaFieldRegex = new RegExp(util.format('^(%s)$', schemaField));

            if (schemaFieldRegex.test(field)) {
                return this.derivedFields[field];
            }
        }
    }

    return null;
};
