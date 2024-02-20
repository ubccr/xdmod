// toFormalDate:
// Converts a date having this format (yyyy-mm-dd) to mm/dd/yyyy

function toFormalDate(date) {

   var elems = date.split('-');

   return elems[1] + '/' + elems[2] + '/' + elems[0];

}//toFormalDate

// ------------------------------------------

function truncateText(text, limit) {

   // Motivation: Google UA has imposed a limit on values of custom dimensions
   //(https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#customs)

   var active_limit = (limit) ? limit : 30;
   text = '' + text;

   return text.substr(0, active_limit) + ((text.length > active_limit) ? '...' : '');

}//truncateText
