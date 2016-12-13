/*node.js javascript document
 *
 * @authors: Tom Yearke
 * @date: 3/13/2014
 *
 * Defines type validation functions used by various other components.
 *
 * @requirements:
 *	node.js
 *
 */

var util = require("util");

// Checking method from: http://stackoverflow.com/a/1830844
var checkNumber = function (value, expectedType, label) {
    if ((isNaN(parseFloat(value))) || (!isFinite(value))) {
        return {
            isValid: false,
            error: {
                message: util.format('Attribute %s "%s" value error: must be numeric: %s', expectedType, label, value),
                severity: "warning"
            }
        };
    }
    return {
        isValid: true
    };
};

var checkUnsignedNumber = function (value, expectedType, label) {
    var checkNumberResult = checkNumber(value, expectedType, label);
    if (!checkNumberResult.isValid) {
        return checkNumberResult;
    }
    if (value < 0) {
        return {
            isValid: false,
            error: {
                message: util.format('Attribute %s "%s" value error: must be non-negative: %s', expectedType, label, value),
                severity: "warning"
            }
        };
    }
    return {
        isValid: true
    };
};

var check = function (value, expectedType, label) {
    switch (expectedType) {
        case "int32":
            return checkNumber(value, expectedType, label);
        case "tinyint":
            return checkNumber(value, expectedType, label);
        case 'uint32':
            return checkUnsignedNumber(value, expectedType, label);
        case "double":
            return checkNumber(value, expectedType, label);
        case 'string':
            if (typeof(value) !== 'string') {
                return {
                    isValid: false,
                    error: {
                        message: util.format('Attribute %s "%s" value error: must be string: %s', expectedType, label, value),
                        severity: "warning"
                    }
                };
            }
            break;
        case "array":
            if (!Array.isArray(value)) {
                return {
                    isValid: false,
                    error: {
                        message: util.format('Attribute %s "%s" value error: must be array: %s', expectedType, label, value),
                        severity: "warning"
                    }
                };
            }
            break;
        default:
            return {
                isValid: false,
                error: {
                    message: util.format('Attribute %s "%s" type error: unknown type specified', expectedType, label),
                    severity: "error"
                }
            };
    }
    return {
        isValid: true
    };
};

module.exports = {
	check: check
};
