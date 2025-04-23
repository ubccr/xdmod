function validateField(str, vpattern) {

   if (str.length == 0) return null;
   if (vpattern == null) return true;

   var regexObj = new RegExp(vpattern);

   return regexObj.exec(str);

}//validateField

// ------------------------------------------------------------

function processInputFields(fields) {

   var validityStatus = true;

   for (i = 0; i < fields.length; i++) {

      var current_field = document.getElementById(fields[i][0]);
      var current_field_trimmed_value = current_field.value.replace(/^\s+|\s+$/g,"");

      // Update field with 'trimmed' input
      if(current_field.type == 'text' || current_field.type == 'password')
         current_field.value = current_field_trimmed_value;

      var stat_current_field = document.getElementById('stat_' + fields[i][0]);

      var validationStatus = validateField(current_field.value, fields[i][1]);


      if (validationStatus == null) {
         stat_current_field.className = 'fieldRestrictions emphasize';
         validityStatus = false;
      }
      else{
         stat_current_field.className = 'fieldRestrictions';
      }

   }//for

   return validityStatus;

}//processInputFields

// ------------------------------------------------------------

function performReset () {

   var regex_password = '^.{5,20}$';

   var fieldsToCheck = new Array(2);

   fieldsToCheck[0] = new Array('updated_password',             regex_password,       '5 characters min.');
   fieldsToCheck[1] = new Array('updated_password_repeat',      regex_password,       '5 characters min.');

   if (processInputFields(fieldsToCheck) == false) return;

   var password_a = document.getElementById('updated_password').value;
   var password_b = document.getElementById('updated_password_repeat').value;

   if (password_a != password_b){
      Ext.MessageBox.alert('Password Update', "The passwords you supplied do not match");
      return;
   }

   // ===========================

   $.post(

      "controllers/user_auth.php",

      {
         operation: 'update_pass',
         rid: reset_id,
         password: encodeURIComponent(document.getElementById('updated_password').value)
      },

      function(data){

         if (data.status == 'success') {

            Ext.MessageBox.alert('Password Update', "Your password has been updated successfully.", function(){ location.href = 'index.php'; });

         }
         else {

            Ext.MessageBox.alert('Password Update', "There was a problem updating your password");

         }

      },

      "json"

   );//post

}//performReset

// ------------------------------------------------------------

function loginNav(field, ev) {

   var tabOrder = new Array (
      'updated_password',
      'updated_password_repeat'
   );

   var key = ev.keyCode || ev.which;

   if (field.id == 'updated_password') {

      var results = testPassword(document.getElementById(field.id).value);

      document.getElementById('strengthIndicator').innerHTML = results.verdict;
      document.getElementById('strengthIndicator').className = 'passStrength ' + results.indicator;

   }

   if (key == 13) {    // 'Enter' key pressed

      var location = tabOrder.indexOf(field.id);

      if (location == -1) return;

      if (location == (tabOrder.length - 1))
         performReset();
      else
         document.getElementById(tabOrder[location + 1]).focus();

   }//if (key == 13)

}//loginNav

// ------------------------------------------------------------

function initPage() {
   document.getElementById('updated_password').focus();
}//initPage
