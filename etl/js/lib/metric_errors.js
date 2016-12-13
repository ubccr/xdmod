/*node.js javascript document
 *
 * @authors: Amin Ghadersohi
 * @date: 2/8/2014
 * 
 * Class to encapsulate a set of unified errors used during the etl process
 *
 * @requirements:
 *	node.js
 *
 */

module.exports = {
	codes: {
		metricOK: {value: 0, text: "Metric OK"},
		missingCollectionFailed: {value: 1, text: "Metric Missing: Collection Failed"},
		metricMissingNotAvailOnThisHost: {value:2, text: "Metric Missing: Not Available On This Host"},
		metricDisappearedDuringJob: {value: 4, text: "Metric Disappeared During Job"},
		metricMissingUnknownReason: {value: 8, text: "Metric Missing: Unknown Reason"},
		metricSummarizationError: {value: 16, text: "Metric Summarization Error"},
		metricOutOfBounds: {value: 32, text: "Metric Out Of Bounds"},
		metricMappingNotFound: {value: 64, text: "Metric Mapping Not Found"},
		metricMappingFunctionError: {value: 128, text: "Mapping Function Error"},
		metricAmbiguous: {value: 256, text: "Metric Ambiguous"},
		metricDeriveQueryError: {value: 512, text: "Derived Field Query Failed"},
		metricTypeError: {value: 1024, text: "Metric Value Doesn't Fit Schema Type"},
		metricCounterRollover: {value: 2048, text: "Metric Counter Rolled Over"},
		metricJitterError: {value: 4096, text: "Metric Dropped Data Due to Jitter"},
	}
};
	
