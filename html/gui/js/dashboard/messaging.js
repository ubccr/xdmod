var Dashboard = {};

Dashboard.mdmFlag = false;

// ---------------------------------------------

/*
   Dashboard.ControllerProxy is to be used when requesting data to be populated into an ExtJS data store.
   This function has the ability to intercept and inspect any status messages that may be returned prior
   to delivering the 'response data' to the 'target store'
*/

Dashboard.ControllerProxy = function(targetStore, parameters) {

   if (!parameters.operation) {
      Ext.MessageBox.alert('Controller Proxy', 'An operation must be specified');
      return;
   }

   Ext.Ajax.request({

      url : targetStore.url,
      method : 'POST',
      params : parameters,
      timeout: 60000,  // 1 Minute,
      async: false,

      callback: function (options, success, response) {
         var responseData;
         if (success) {
            responseData = CCR.safelyDecodeJSONResponse(response);
            success = CCR.checkDecodedJSONResponseSuccess(responseData);
         }

         if (!success) {
            CCR.xdmod.ui.presentFailureResponse(response, {
               title: 'XDMoD Dashboard',
               wrapperMessage: 'Error occurred with request.'
            });
            return;
         }

         targetStore.loadData(responseData);
      }

   });//Ext.Ajax.request

};//Dashboard.ControllerProxy

