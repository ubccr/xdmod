/*node.js javascript document
 *
 * @authors: Tom Yearke
 * @date: 2/19/2014
 *
 * Defines class for a resource mapping. 
 *
 * @requirements:
 *	node.js
 *
 */

var metricErrors = require("./metric_errors.js"), 
    typeValidation = require('./type_validation.js'),
	util = require("util");

/**
 * Wrapper class for a resource mapping object that adds properties used by
 * mapping functions.
 *
 * @param mappingObj The resource mapping object this instance will load the
 *     properties of.
 *
 * @throws TypeError The mapping object contains a property used by the class.
 */
var Mapping = function (mappingObj, metricErrors) {

	for (var mappingProperty in mappingObj) {
		if (mappingProperty in this) {
			throw new TypeError(util.format("'%s' is a reserved property name used by the Mapping class.", mappingProperty));
		}
		this[mappingProperty] = mappingObj[mappingProperty];
	}
};

/**
 * The metric errors used by summary documents and mapping functions.
 */
Mapping.prototype.metricErrors = metricErrors;

/**
 * This function is used to fetch values for a mapping using a simple lookup.
 * If a value is not found, the returned value will be undefined. The error
 * code returned will be the one found in the document, or if that isn't
 * available, the code will be set based on whether or not the value was found.
 *
 * Multiple paths may be attempted by passing an array of paths to the
 * function, in which case the returned object will contain the value and error
 * code for the first path to successfully point to a value in the given
 * object. If no paths find a value, the error code returned will be the first
 * error code found in the document or a default if none were found.
 *
 * @param obj The object to search.
 * @param docPaths The path(s) within the object to search, with properties
 *     separated by dots.
 * @param expectedType (Optional) The expected type of the value to return.
 *     If a path's value does not match the expected type, it is not used.
 *     If no type is specified, then no type checking is performed.
 * @return An object containing the value found and an error code.
 */
Mapping.prototype.ref = function (obj, docPaths, expectedType) {

	var checkType = expectedType !== undefined;

	// Convert docPaths to an array if it is a string.
	if (typeof docPaths === 'string') {
		docPaths = [docPaths];
	}

	// For each document path...
	var numDocPaths = docPaths.length;
	var firstErrorFound = null;
	for (var i = 0; i < numDocPaths; i++) {
		var docPath = docPaths[i];

		// Set up current object and error for this path's search.
		var currentObj = obj;
		var currentError = null;

		// Split the path into properties to access in the given object.
		var documentProperties = docPath.split('.');
		var numDocumentProperties = documentProperties.length;

		// Search for the value in the object. While searching, also keep track
		// of the deepest error in the document along the path.
		var valueFoundInThisPath = true;
		for (var j = 0; j < numDocumentProperties; j++) {
		    var nextProperty = documentProperties[j];
		    
		    if ("error" in currentObj) {
		    	currentError = currentObj.error;
		    }

		    if (nextProperty in currentObj) {
		        currentObj = currentObj[nextProperty];
		    } else {
		        valueFoundInThisPath = false;
		        break;
		    }
		}

		// If this search found a value and a certain value type is expected,
		// check that the value is the correct type. If not correct, consider
		// this search unsuccessful.
		if (valueFoundInThisPath && checkType) {
			if (!typeValidation.check(currentObj, expectedType, docPath).isValid) {
				valueFoundInThisPath = false;
			}
		}

		// If this search was successful, stop searching and return the value.
		if (valueFoundInThisPath) {
			if (currentError === null) {
				currentError = metricErrors.codes.metricOK.value;
			}
			return { value: currentObj, error: currentError };
		}

		// If a valid error code was found along this path, store it in case no
		// value is found in any remaining paths.
		if (firstErrorFound === null) {
			firstErrorFound = currentError;
		}
	}

	// If nothing was found, return an object with an undefined value and
	// either the first error code found, if any, or a generic error.
	return {
		value: undefined,
		error: (firstErrorFound !== null) ? firstErrorFound : metricErrors.codes.metricMissingUnknownReason.value
	};
};

module.exports = Mapping;
