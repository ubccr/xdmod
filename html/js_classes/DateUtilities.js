/* 
   
   DateUtilities.js  (Date Utilities Class)
   
   Extends the capabilities of the Date JavaScript class
   
*/

function DateUtilities (config) {

   // Use current year if no config.year has been supplied
   var active_year = (config && config.year) ? config.year : new Date().getFullYear();
   
   // ----------------------------------
      
   this.getFormalString = function(date_obj) {
   
      return this.getFormalMonthName(date_obj.getMonth() + 1) + ' ' + date_obj.getDate() + ', ' + date_obj.getFullYear();   
      
   };//getFormalString

   // ----------------------------------
         
   this.getISOFormat = function(date_obj) {
   
      // The international format defined by ISO (ISO 8601)
      // defines a numerical date system as follows: YYYY-MM-DD

      return date_obj.getFullYear() + '-' + 
             StringUtilities.pad((date_obj.getMonth() + 1).toString(), 2) + '-' + 
             StringUtilities.pad(date_obj.getDate().toString(), 2);
      
   };//getISOFormat

   // ----------------------------------
   
   this.getPreviousMonthName = function() {
   
      var d = new Date();
      
      d.setDate(1);
      d.setMonth(d.getMonth() - 1);
      
      return this.getFormalMonthName(d.getMonth() + 1);
      
   };//getPreviousMonthName
   
   // ----------------------------------
         
   this.getCurrentQuarter = function() {
   
      var n = new Date();
      
      n.setDate(1);
      n.setMonth(Math.floor(n.getMonth() / 3) * 3);
      
      return n;
      
   };//getCurrentQuarter

   // ----------------------------------
   
   this.getPreviousQuarter = function() {
   
      var current = this.getCurrentQuarter();
      
      current.setMonth(current.getMonth() - 3);
      
      return current;
      
   };//getPreviousQuarter
      
   // ----------------------------------
      
   this.setYear = function(year) {
   
      this.active_year = year;
      this.buildMonthData();
   
   };//setYear

   // ----------------------------------
   
   this.getCurrentDate = function() {
   
      var g = new Date();
   
      return g.getFullYear() + '-' + 
             StringUtilities.pad((g.getMonth() + 1).toString(), 2) + '-' + 
             StringUtilities.pad(g.getDate().toString(), 2);
   
   };//getCurrentDate
         
   // ----------------------------------
   
   this.isLeapYear = function() {
   
      return (new Date(this.active_year, 1, 29).getDate() == 29);
   
   };//isLeapYear

   // ----------------------------------
            
   this.isValidMonthIndex = function (month_index) {

      var regex = /^\d{1,2}$/;
      
      var result = month_index.toString().match(regex);
   
      if (result == null) return false;
      
      if (month_index < 1 || month_index > 12) return false;
      
      return true;
         
   };//isValidMonthIndex
   
   // ----------------------------------   
      
   this.getFormalMonthName = function(month_index) {

      if (!this.isValidMonthIndex(month_index))
         return 'invalid month index';
         
      return this.monthData[month_index - 1].formal_name;
   
   };//getFormalMonthName

   // ----------------------------------
               
   this.getDaysInMonth = function(month_index) {

      if (!this.isValidMonthIndex(month_index))
         return 'invalid month index';
            
      return this.monthData[month_index - 1].days_in_month;
   
   };//getDaysInMonth
               
   // ----------------------------------
 
   this.isValidDateFormat = function (date_string) {

      var regex = /^\d{4}-\d{2}-\d{2}$/;

      var result = date_string.match(regex);   
      
      if (result == null) return false;
      
      return true;

   };//isValidDateFormat

   // ----------------------------------
        
   this.parseDate = function (date_string) {
   
      var response = {};
      
      response.year = date_string.split('-')[0];
      response.month = date_string.split('-')[1];
      response.day = date_string.split('-')[2];
      
      return response;
   
   };//parseDate

   // ----------------------------------   
   
   this.displayTimeframeDates = function (formal_timeframe) {
   
      var endpoints = this.getEndpoints(formal_timeframe, true);
   
      return endpoints.start_date + ' to ' + endpoints.end_date;
      
   };//displayTimeframeDates
   
   // ----------------------------------      
   
   this.getEndpoints = function(formal_timeframe, present_formally) {
   
      if (!present_formally) present_formally = false;
      
      var end_date = new Date();
      var start_date = new Date();
                  
      switch(formal_timeframe.toLowerCase()) {
                        
         case 'yesterday':

            start_date.setDate(start_date.getDate() - 1);
            end_date.setTime(start_date.getTime());

            break;

         case 'month to date':
         
            start_date.setDate(1);
            
            break;
            
         case 'quarter to date':
            
            start_date = this.getCurrentQuarter();
            
            break;
            
         case 'year to date':
            
            start_date.setDate(1);
            start_date.setMonth(0);   
                  
            break;
            
         case 'previous month':
         
            start_date.setDate(1);
            start_date.setMonth(start_date.getMonth() - 1);
            
            end_date.setDate(0);
            
            break;

         case 'previous quarter':

            start_date = this.getPreviousQuarter();
            end_date = this.getCurrentQuarter();
            end_date.setDate(0);

            break;

         case 'previous year':

            start_date.setFullYear(start_date.getFullYear() - 1);
            start_date.setMonth(0);
            start_date.setDate(1);
            
            end_date.setMonth(0);
            end_date.setDate(0);
            
            break;
                                 
         default:
         
            // Check if the argument passed in was a 4-digit year
            
            if (formal_timeframe.match(/^\d{4}$/) != null && formal_timeframe >= 1900) {
                           
               start_date.setFullYear(parseInt(formal_timeframe, 10));
               start_date.setMonth(0);
               start_date.setDate(1);
            
               end_date.setFullYear(parseInt(formal_timeframe, 10));
               end_date.setMonth(11);
               end_date.setDate(31);
            
            }
            
            // 30/60/90/etc. day
            
            else if (formal_timeframe.match(/^\d{1,} day$/i) != null) {
            
               start_date.setDate(start_date.getDate() - parseInt(formal_timeframe.split(' ')[0], 10));

            }
            
            // 1/2/5/etc. year
            
            else if (formal_timeframe.match(/^\d{1,} year$/i) != null) {
               
               start_date.setFullYear(start_date.getFullYear() - parseInt(formal_timeframe.split(' ')[0], 10));

            }
            else {
            
               alert('Invalid timeframe specified: \'' + formal_timeframe + '\'');
            
            }
            
            break;
      
      }//switch
      
      return {
         start_date: (present_formally == true) ? this.getFormalString(start_date) : this.getISOFormat(start_date), 
         end_date: (present_formally == true) ? this.getFormalString(end_date) : this.getISOFormat(end_date)
      };
   
   };//getEndpoints
   
   // ----------------------------------   

   this.buildMonthData = function() {
   
      this.monthData = [];

      this.monthData.push({index: 1,   formal_name: 'January',    days_in_month: 31});
      this.monthData.push({index: 2,   formal_name: 'February',   days_in_month: (this.isLeapYear()) ? 29 : 28});
      this.monthData.push({index: 3,   formal_name: 'March',      days_in_month: 31});
      this.monthData.push({index: 4,   formal_name: 'April',      days_in_month: 30});
      this.monthData.push({index: 5,   formal_name: 'May',        days_in_month: 31});
      this.monthData.push({index: 6,   formal_name: 'June',       days_in_month: 30});
      this.monthData.push({index: 7,   formal_name: 'July',       days_in_month: 31});
      this.monthData.push({index: 8,   formal_name: 'August',     days_in_month: 31});
      this.monthData.push({index: 9,   formal_name: 'September',  days_in_month: 30});
      this.monthData.push({index: 10,  formal_name: 'October',    days_in_month: 31});
      this.monthData.push({index: 11,  formal_name: 'November',   days_in_month: 30});
      this.monthData.push({index: 12,  formal_name: 'December',   days_in_month: 31});
   
   };//buildMonthData
   
   this.buildMonthData();
   
}//DateUtilities

// Static Methods ------------------------------------------------

DateUtilities.convertDateToProperString = function(d) {

   var month = StringUtilities.pad(d.getMonth() + 1, 2);
   var day = StringUtilities.pad(d.getDate(), 2);
   var year = d.getFullYear();
   
   var hour = StringUtilities.pad(d.getHours(), 2);
   var minutes = StringUtilities.pad(d.getMinutes(), 2);
   var seconds = StringUtilities.pad(d.getSeconds(), 2);
   
   var date = year + '-' + month + '-' + day; 
   var time = hour + ':' + minutes + ':' + seconds;
   
   return date + ' ' + time;
   
};//convertDateToProperString
   
   
