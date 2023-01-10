DashboardStore = function(config) {

   var finalConfig = Ext.apply({
      successProperty: 'success'
   }, config);

   DashboardStore.superclass.constructor.call(this, finalConfig);

   this.proxy.on('exception', function(dp, type, action, options, response, arg) {

      CCR.xdmod.ui.presentFailureResponse(response, {
         title: 'XDMoD Dashboard',
         wrapperMessage: 'Failed to ' + action + ' from ' + options.url + '.'
      });

   }, this);

};

Ext.extend(DashboardStore, Ext.data.JsonStore);
