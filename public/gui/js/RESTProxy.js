/*

   RESTProxy.js
   Last Updated: March 14, 2011

   RESTProxy is a client-side proxy which communicates with the XDMoD portal backend.
   ExtJS stores (e.g. jsonstore, xmlstore, etc..) will acquire data from the backend through
   the use of the RESTProxy.  In addition, single-purpose calls (e.g. profile updating, user management),
   which make explicit use of AJAX-style request mechanisms will also make use of the RESTProxy.

   The RESTProxy will serve as a first-phase interpreter of the response rendered by the
   RESTful API.  This response interpreter will deal with common (base) case responses, such
   as:  user_not_logged_in, account_disabled, and so on.

   If a base case is not accounted for by the RESTProxy, the response data will then be forwarded
   to the handler (callback) specified in XDMoD.REST.Call(...) for further interpretation.

*/

Ext.namespace('XDMoD.REST');

XDMoD.REST.token = 'token_value';                  // The REST token will be cached here
XDMoD.REST.baseURL = '/rest/';                     // Path to where the REST Front Controller resides

// -----------------------------------------------------

// XDMoD.REST.Call --
// required arguments: config.action, config.callback
// optional arguments: config.resume (defaults to 'false'), config.arguments (defaults to {})
//                     config.callbackArguments (the arguments passed as a second parameter to the function specified in config.callback, defaults to {})

XDMoD.REST.Call = function(config) {

   if (!config.action)            { Ext.MessageBox.alert('RESTProxy', 'Action Required');   return -1; }
   if (!config.callback)          { Ext.MessageBox.alert('RESTProxy', 'Callback Required'); return -1; }
   if (!config.callbackArguments) { config.callbackArguments = {}; }

   if (!config.resume) config.resume = false;
   if (!config['arguments']) config['arguments'] = {};

   var restArgumentString = '';

   for (var argName in config['arguments'])
      if (config['arguments'][argName])
         restArgumentString += '/' + argName + '=' + config['arguments'][argName];

   // If config.action has an absolute url (http://....), then do not use XDMoD.REST.baseURL
   // If config.action is using a relative url (/action/....), then use XDMoD.REST.baseURL

   var fullURL = (config.action.indexOf('http') == 0) ? '' : XDMoD.REST.baseURL;
   fullURL = fullURL + config.action + restArgumentString;

   // =================================================================

   // requestSuccess: The function that will be invoked as the result of the client successfully communicating with the
   //                 backend service.

    var requestSuccess = function(response) {

      var responseData = Ext.decode(response.responseText);

      if (responseData.success == false){

         // Base response messages associated with the REST framework
         // For all other messages, forward the response data to the callback


         // Handle issues with the structure and validity of the REST call ----------

         var restMessages = [

            "^(realm|category|action) required$",
            "^Unknown realm '(.+)'$",
            "^Category '(.+)' is not defined for realm '(.+)'$",
            "^Unknown action '(.+)' in category '(.+)'$"

         ];

         for (i = 0; i < restMessages.length; i++) {

            var re = new RegExp(restMessages[i]);

            if (responseData.message.match(re)) {

               Ext.MessageBox.alert('RESTProxy', responseData.message);
               return -1;

            }

         }//for


         // Handle issues with authenticated calls -----------------------------------

         var authenticationMessages = [

            "Invalid token specified",
            "Token invalid or expired.  You must authenticate before using this call."

         ];

         // The 'logout' action is exempt from authentication failure handling at this phase
         // (It is possible for the 'logout' action to be called using a token which has been invalidated
         //  due to a session naturally expiring.)

         var handleAuthFailure = !(responseData && responseData.action == 'logout');

         if (handleAuthFailure) {

            for (i = 0; i < authenticationMessages.length; i++) {

               if (responseData.message == authenticationMessages[i]) {

                  // Present the login panel to the user

                  if (loginPanel == null) {
                     loginPanel = new XDMoD.LoginPrompt();
                  }

                  // Pass the REST call configuration into the loginPanel object.  If the configuration has a 'resume' value
                  // of 'true', then XDMoD.REST.Call will be invoked using the passed configuration upon successfull relogin.

                  loginPanel.setRESTConfig(config);
                  loginPanel.show();

                  return -1;

               }//if

            }//for

         }//if (handleAuthFailure == true)

      }//if (responseData.success == false)

      // -----------------------------------

      // This point will have been reached for two possible reasons:

      // (1) The response has a success property value of 'true'
      // OR
      // (2) The response has a success property value of 'false', yet is not due to an improper
      // REST call or authentication issue (it is most likely an error specific to the action requested)

      // Have the callback deal with the response

      config.callback(responseData, config.callbackArguments);

   };//requestSuccess

   // =================================================================

   if (config.action == 'authentication/utilities/login' || config.action == 'portal/profile/update') {

      // Authentication or content-sensitive calls should not have credentials passed in the URL.  Instead, these credentials
      // are POSTed.  The same concept applies to updating a user's profile (in which the account holder's password
      // may have been updated).

      return Ext.Ajax.request({

         url: XDMoD.REST.baseURL + config.action + '?token=' + XDMoD.REST.token,

         method : 'POST',

         params : config['arguments'],

         timeout: 60000,  // 1 Minute,

         success : function(response)
         {
            requestSuccess.call(this, response);

            if(config.success)
            {
               response.argument = config.argument;
               config.success.call(config.scope,response);
            }
         },

         failure : function(response) {

            if(config.failure)
            {
                  response.argument = config.argument;
                  config.failure.call(config.scope, response);
            }

            CCR.xdmod.ui.presentFailureResponse(response, {
              title: 'RESTProxy',
              wrapperMessage: 'Request Error'
            });

         }

      });//Ext.Ajax.request

   }//if (config.action == 'authentication/utilities/login')

   // =================================================================

   // For local requests, Ext.Ajax.request is used.

   var request_params = {};

   if (XDMoD.REST.token != 'token_value')
      request_params.token = XDMoD.REST.token;

    return Ext.Ajax.request({

        url : fullURL,
        method : config.method || 'GET',
        params : request_params,
        timeout: 60000,  // 1 Minute,

        success : function(response)
        {
            requestSuccess.call(this, response);

            if(config.success)
            {
                 response.argument = config.argument;
                 config.success.call(config.scope,response);
            }
        },

        failure : function(response) {

            if(config.failure)
            {
                response.argument = config.argument;
                config.failure.call(config.scope, response);
            }

            CCR.xdmod.ui.presentFailureResponse(response, {
              title: 'RESTProxy',
              wrapperMessage: 'Request Error'
            });

        }

    });

};//XDMoD.REST.Call
