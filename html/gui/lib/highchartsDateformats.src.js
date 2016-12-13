/**
 * Add custom date formats 
 */
Highcharts.dateFormats = {
    T: function (timestamp) {
        var timezone = Highcharts.getOptions().global.timezone;
        if (timezone && typeof moment !== 'undefined') {
            return moment.tz(timestamp, timezone).zoneAbbr();
        }
        return '';
    },
	W: function (timestamp) {
		var date = new Date(timestamp),
			day = date.getUTCDay() == 0 ? 7 : date.getUTCDay(),
			dayNumber;
		date.setDate(date.getUTCDate() + 4 - day);
		dayNumber = Math.floor((date.getTime() - new Date(date.getUTCFullYear(), 0, 1, -6)) / 86400000);
		return 1 + Math.floor(dayNumber / 7);
		
	},
	Q: function(timestamp) {
		if (timestamp === undefined || timestamp === null || isNaN(timestamp)) {
			return 'Invalid date';
		}
		var date = new Date(timestamp),
			month = date.getUTCMonth();
		return 1 + Math.floor(month / 3);
	}
} 

Highcharts.setOptions({
    global: {
        getTimezoneOffset: function(timestamp) {
            var timezone = Highcharts.getOptions().global.timezone;
            if (timezone && typeof moment !== 'undefined') {
                return -moment.tz(timestamp, timezone).utcOffset();
            }
            return null;
        }
    }
});
