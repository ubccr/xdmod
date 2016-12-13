/*node.js javascript document
 *
 * @authors: Amin Ghadersohi, 
 *	  	 	 Thomas Yearke
 * @date: 2/8/2014
 *
 * Class to encapsulate functionality related to a dataset mapping.
 * A dataset mapping is used to map a set of heterogeneous data sources
 * to a homogeneous dataset as defined by etlProfile.schema
 *
 * @requirements:
 *	node.js
 *
 */
var events = require('events'),
    util = require('util'),
	DynamicTable = require('./mysql.js').DynamicTable,
	Mapping = require('./mapping.js'),
	Schema = require('./schema.js'),
	metricErrors = require('./metric_errors.js'),
    typeValidation = require('./type_validation.js');

var DatasetMap = function (etlProfile, dataset) {
    events.EventEmitter.call(this);
    this.etlProfile = etlProfile;
	this.schema = etlProfile.schema;
	this.dataset = dataset;
    this.originalMapping = dataset.mapping;
    this.mapping = new Mapping(this.originalMapping);
    var valid = this.validate();
    if (true !== valid.value) {
        var err = new Error('Error validating mapping for ' + dataset.name + ': ' + dataset.mapping + ': ' + valid.error);
        throw err;
        //terminal error
    }
};

util.inherits(DatasetMap, events.EventEmitter);

/*
 * @authors: Joseph P White
 * @date: 2014-02-07
 * @requirements:
 *	node.js
 *	tv4
 * @brief Check if a resource mapping conforms to the schema
 * @returns object {value: boolean(true if the resource mapping conforms. false otherwise), error: string}
 */
DatasetMap.prototype.validate = function () {
	try {
        var tv4 = require('tv4');
        var result = tv4.validate(this.originalMapping, this.etlProfile.mappingValidator);
        return {value: result, error: util.inspect(tv4.error)};
    } catch(e) {
		return {value: result, error: e};
    }
}

/**
 * Traverse the schema and retrieve insert statements from mapping/schema fieldss
 *
 * @param doc The summary document to retrieve values from.
 * @return An object containing a property for each attribute in the mapping,
 *     each of which points to an object specifiying a value and an error.
 */
DatasetMap.prototype.getDimensionInsertStatements = function (transformed, queryFormatFn, __volatile_cache) {
	var insertStatements = []; 
	function addInsertStatement(query, values, cacheable) {
		var s = queryFormatFn(query, values);
		if(cacheable) {
			if( !(s in __volatile_cache)) {
				__volatile_cache[s] = 1;
				insertStatements.push(s);
			}
		} else {
			insertStatements.push(s);
		}
	}
	for (var f in transformed.data) {
		if (transformed.schema[f].dim_insert) {
			var insertStatement = transformed.schema[f].dim_insert(transformed.data);
			if (insertStatement instanceof Array) {
				for (var ins = 0; ins < insertStatement.length; ins++) {
					var cacheable = insertStatement[ins].cacheable !== undefined ? insertStatement[ins].cacheable : true;
					addInsertStatement(insertStatement[ins].query, insertStatement[ins].values, cacheable);
				}
			} else {
				var cacheable = insertStatement.cacheable !== undefined ? insertStatementcacheable : true;
				addInsertStatement(insertStatement.query, insertStatement.values, cacheable);
			}
		}
	}
	return insertStatements;
}

/**
 * Traverse the mapping and retrieve values for each field from a summary
 * document using the method specified in the mapping for the field.
 *
 * @param doc The summary document to retrieve values from.
 * @return An object containing a property for each attribute in the mapping,
 *     each of which points to an object specifiying a value and an error.
 */
DatasetMap.prototype.transform = function (doc) {
    var ret = {
        data: {},
		schema: {}
    };

    // For each attribute in the mapping, attempt to find the value and error
    // code for the attribute.
    var mappingAttributes = this.mapping.attributes;
    for (var mappingAttributeName in mappingAttributes) {
        var mappingAttributeField = mappingAttributes[mappingAttributeName],
            mappingAttributeValue = {
                value: null,
                error: 0
            },
            mappingAttributeErrors = [],
            mappingAttributeWarnings = [],
            schemaField = this.schema.getField(mappingAttributeName);
			
        if (!schemaField) {
            // Lack of a schema field is a fatal error.
            console.error(util.format('Attribute "%s" does not have a default schema field.', mappingAttributeName));
            process.exit(1);
        }

		// Do stuff in order of most common/easiest to least common/hardest
        // If a reference to a single point in the document is being used,
        // use the reference function to retrieve the value. 
        if ('ref' in mappingAttributeField) {
            mappingAttributeValue = this.refDefault(doc, mappingAttributeField.ref, schemaField);
        } 
		
		// If a static value is being used, grab the value and use the OK code.
        else if ('value' in mappingAttributeField) {
            mappingAttributeValue = {
                value: mappingAttributeField.value,
                error: metricErrors.codes.metricOK.value
            };
        }
		
		// If an error is being used, grab the error and use the default
        // attribute value.
        else if ('error' in mappingAttributeField) {
            mappingAttributeValue = {
                value: schemaField.def,
                error: mappingAttributeField.error
            };
        } 
		
        // If a formula is being used to calculate the value, call it.
        // If the formula fails, substitute the default value and a 
        // summarization error.
        else if ('formula' in mappingAttributeField) {
            var v = null;
            try {
                v = mappingAttributeField.formula.call(this.mapping, doc);
            } catch (e) {
                if (e instanceof TypeError) {
                    v = {
                        value: schemaField.def,
                        error: metricErrors.codes.metricMappingFunctionError.value
                    };
                    mappingAttributeWarnings.push(util.format('Attribute %s "%s" mapping function encountered a TypeError exception. The default schema value will be used. Exception: %s', schemaField.type, mappingAttributeName, e));
                } else {
                    mappingAttributeErrors.push(util.format('Attribute %s "%s" mapping function encountered an exception: %s', schemaField.type, mappingAttributeName, e));
                }
            }
            if (v && v.value !== undefined) {
                mappingAttributeValue = v;
            } else {
                mappingAttributeErrors.push(util.format('Attribute %s "%s": mapping function must return object like {value: v, error: e}. %s returned', schemaField.type, mappingAttributeName, JSON.stringify(v, null)));
            }
        }

        // Otherwise, log an error.
        else {
            mappingAttributeErrors.push(util.format('Attribute %s "%s" does not have a valid mapping property.', schemaField.type, mappingAttributeName));
        }
		
		if (mappingAttributeValue.value !== undefined &&
		    mappingAttributeValue.value !== null) {
			var typeCheckResult = typeValidation.check(mappingAttributeValue.value, schemaField.type, mappingAttributeName);
            if (!typeCheckResult.isValid) {
                mappingAttributeValue.value = schemaField.def;
                mappingAttributeValue.error |= metricErrors.codes.metricTypeError.value;
                if (typeCheckResult.error.severity === "warning") {
                    mappingAttributeWarnings.push(typeCheckResult.error.message);
                } else {
                    mappingAttributeErrors.push(typeCheckResult.error.message);
                }
            }
		}

        if (mappingAttributeValue.value === undefined ||
		    mappingAttributeValue.value === null ) { // === is needed here to prevent the empty string confused for null
            if (((schemaField.nullable !== undefined && schemaField.nullable === false) || 
			    (mappingAttributeField.required !== undefined && mappingAttributeField.required === true)) &&
				 schemaField.def === null) { // if value is not nullable or required, and a default value is not available
                mappingAttributeErrors.push(util.format('Attribute %s "%s" cannot be null: %s', schemaField.type, mappingAttributeName, JSON.stringify(mappingAttributeValue, null)));
            } else { // otherwise, use default. 
                mappingAttributeValue.value = schemaField.def;
            }
        }
		
		ret.schema[mappingAttributeName] = schemaField;
        ret.data[mappingAttributeName] = mappingAttributeValue;
        if (mappingAttributeErrors.length > 0) {
            if (!ret.errors)
                ret.errors = {};
            ret.errors[mappingAttributeName] = mappingAttributeErrors;
        }
        if (mappingAttributeWarnings.length > 0) {
            if (!ret.warnings) {
                ret.warnings = {};
            }
            ret.warnings[mappingAttributeName] = mappingAttributeWarnings;
        }
    }

    return ret;
}

/**
 * This function is a wrapper for Mapping's ref function which will replace
 * the returned value with the default schema value for an attribute if the
 * returned value is undefined.
 *
 * @param obj The object to search.
 * @param docPaths The path(s) within the object to search, with properties
 *     separated by dots.
 * @param schemaField The schema field for the attribute being searched for.
 * @return An object containing the value found and an error code.
 */
DatasetMap.prototype.refDefault = function (obj, docPaths, schemaField) {
	var refResult = Mapping.prototype.ref(obj, docPaths, schemaField.type);

    // If referencing with type checking fails, try again without type checking
    // so that type errors may propogate later if any values are present.
    if (refResult.value === undefined) {
        refResult = Mapping.prototype.ref(obj, docPaths);
    }

    // If searching for a string and a number is found, convert it.
    if ((schemaField.type === "string") && (typeof(refResult.value) === "number")) {
        refResult.value = refResult.value.toString();
    }

    // If no value was found, use the default schema value.
	if (refResult.value === undefined) {
		refResult.value = schemaField.def;
	}

	return refResult;
}

/*
* @returns the dynamic tables for the etl profile. 
*/
DatasetMap.prototype.getTables = function () {
    var unionFields = util._extend({}, this.schema.derivedFields),
        tables = {};
    util._extend(unionFields, this.dataset.mapping.attributes);
    
    for (var field in unionFields) {
        var f = unionFields[field] = this.schema.getField(field);
        if (f === undefined || f === null) {
            f = unionFields[field] = this.schema.getDerivedField(field);
        }
        if (f !== undefined && f !== null && f.table !== undefined && f.table !== null && this.schema.tables[f.table].definition === 'dynamic') {
            if (tables[f.table] === undefined) {
                tables[f.table] = new DynamicTable({
                    name: f.table,
                    meta: this.schema.tables[f.table],
                    columns: {}
                });
            }
            tables[f.table].columns[field] = f;
        }
    }
    return tables;
}

module.exports = DatasetMap;
