#!/usr/bin/env node

/*node.js javascript document
 *
 * @authors: Joe White, Tom Yearke
 *
 * A module containing functions for performing regression tests of the
 * resource mappings.
 *
 * @requirements:
 *  node.js
 *
 */

var util = require("util");

// attach the .compare method to Array's prototype to call it on any array
Array.prototype.compare = function (array) {
    // if the other array is a falsy value, return
    if (!array)
        return false;

    // compare lengths - can save a lot of time
    if (this.length != array.length)
        return false;

    for (var i = 0, l=this.length; i < l; i++) {
        // Check if we have nested arrays
        if (this[i] instanceof Array && array[i] instanceof Array) {
            // recurse into the nested arrays
            if (!this[i].compare(array[i]))
                return false;
        }
        else if (this[i] != array[i]) {
            // Warning - two different object instances will never be equal: {x:20} != {x:20}
            return false;
        }
    }
    return true;
}

/**
 * Performs a deep comparison of two objects.
 *
 * Note that this comparison function is not exhaustive -
 * it only covers the use cases needed for the output
 * comparison tests.
 *
 * @param left One of the objects to compare.
 * @param right The object to compare with left.
 * @param leftName (Optional) A name for the left object.
 * @param rightName (Optional) A name for the right object.
 *
 * @return An object containing objectsAreEqual, the boolean result of the
 * comparison, and unequalReasons, an array of reasons why the given objects
 * are not equal (which is empty if the objects are considered equal).
 */
var compare = module.exports = function(left, right, leftName, rightName) {
    var objectsAreEqual = true;
    var unequalReasons = [];

    if (leftName === undefined) {
        leftName = "leftObject";
    }
    if (rightName === undefined) {
        rightName = "rightObject";
    }

    for (var key in left) {
        if (!(key in right)) {
            objectsAreEqual = false;
            unequalReasons.push(util.format("%s missing key '%s'. (%s value: %j)", rightName, key, leftName, left[key]));
            continue;
        }

        var leftValue = left[key];
        var rightValue = right[key];
        if (typeof(leftValue) != typeof(rightValue)) {
            objectsAreEqual = false;
            unequalReasons.push(util.format("Type mismatch for key '%s' in %s (%j) and %s (%j).", key, leftName, leftValue, rightName, rightValue));
            continue;
        }

        if (leftValue instanceof Array) {
            if (false == leftValue.compare(rightValue)) {
                objectsAreEqual = false;
                unequalReasons.push(util.format("Arrays do not match for key '%s' in %s (%j) and %s (%j).", key, leftName, leftValue, rightName, rightValue));
            }
        } else if (leftValue instanceof Object) {
            var objCompareResult = compare(
                leftValue, 
                rightValue,
                util.format("%s.%s", leftName, key),
                util.format("%s.%s", rightName, key)
            );
            if (!objCompareResult.objectsAreEqual) {
                objectsAreEqual = false;
                objCompareResult.unequalReasons.forEach(function(reason) {
                    unequalReasons.push(reason);
                });
            }
        } else {
            if (leftValue != rightValue) {
                objectsAreEqual = false;
                unequalReasons.push(util.format(
                    "Values do not match for key '%s'. (%s: %j, %s: %j)",
                    key, leftName, leftValue, rightName, rightValue
                ));
            }
        }
    }

    for (var key in right) {
        if (!(key in left)) {
            objectsAreEqual = false;
            unequalReasons.push(util.format("%s missing key '%s'. (%s value: %j)", leftName, key, rightName, right[key]));
        }
    }

    return {
        objectsAreEqual: objectsAreEqual,
        unequalReasons: unequalReasons
    };
}

