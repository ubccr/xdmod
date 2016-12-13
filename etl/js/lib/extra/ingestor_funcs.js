/**
 * ingestor_funcs.js
 *
 * Contains potential JavaScript functions to be used during ingestion.
 * Currently a work in progress containing many stubs and incomplete
 * documentation.
 */

/**
 * Convert a ranged string of nodes to an array of nodes, similiar to 
 * running nodeset -e. Could be handled in pcp-summary instead.
 */
function nodesetStringToArray(nodesetString) {

	return [];
}

/**
 * Convert a string representing a time to a Unix timestamp in seconds.
 */
function timeStringToTimestamp(timeString) {

	return Date.parse(timeString) / 1000;
}

/**
 * Convert a string representing a length of time to a number of seconds.
 */
function timeLengthStringToSeconds(timeLengthString) {
	
	return 0;
}

/**
 * Get the sum of an array of numbers.
 */
function getArraySum(numArray) {
	var sum = 0;
	var numElements = numArray.length;
	for (var i = 0; i < numElements; i++) {
		sum += numArray[i];
	}
	return sum;
}

/**
 * Get the average value of an array of numbers.
 */
function getArrayAverage(numArray) {
	return getArraySum(numArray) / numArray.length;
}

/**
 * Function wrapper for the division operator
 */
function divide(a, b) {
	return a / b;
}

/**
 * An equivalent to Python's map function.
 *
 * @param func The function to call using elements of the given iterables.
 * @param ... The iterables whose elements are used as arguments to func.
 */
function pythonMap(func) {
	return [];
}
