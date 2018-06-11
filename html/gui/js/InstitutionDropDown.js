CCR.xdmod.ui.InstitutionDropDown = Ext.extend(Ext.form.ComboBox,  {

   controllerBase: '../controllers/user_admin.php',
   triggerAction: 'all',

   displayField: 'name',
   valueField: 'id',

   width: 275,
   minListWidth: 310,
   pageSize: 300,
   hideTrigger:false,
   forceSelection: true,
   minChars: 1,

   getValue: function () {

      var value = CCR.xdmod.ui.InstitutionDropDown.superclass.getValue.call(this);

      return value;

   },

   setValue : function(v, def){

      var text = v;

      CCR.xdmod.ui.InstitutionDropDown.superclass.setValue.call(this, text);

      if (def) this.lastSelectionText = def;

      return this;

   },

   initializeWithValue: function(v, l) {

      this.setValue(v, l);
      this.setRawValue(l);

   },

   initComponent: function(){

      var self = this;

      var bParams = {
         operation: 'enum_institutions',
         start: 55,
         offset: 33
      };

      this.userStore = new Ext.data.JsonStore({

         url: self.controllerBase,

         autoDestroy: false,

         baseParams: bParams,

         root: 'institutions',
         fields: ['id', 'name'],
         totalProperty:'total_institution_count',
         successProperty: 'success',
         messageProperty: 'message',

         listeners:
         {

            'exception': function(dp, type, action, options, response, arg)
            //'exception': function ()
            {
               var d = Ext.util.JSON.decode(response.responseText);
               CCR.xdmod.ui.generalMessage('XDMoD', d.status, false);
            }

         }

      });

      // When the store loads for the first time then populate it with the default
      // organization value.
      this.userStore.on('load', function () {
          Ext.Ajax.request({
              url: XDMoD.REST.baseURL + 'organizations/default?token=' + XDMoD.REST.token,
              method: 'GET',
              callback: function (options, success, response) {
                  var json;
                  if (success) {
                      json = CCR.safelyDecodeJSONResponse(response);
                      // eslint-disable-next-line no-param-reassign
                      success = CCR.checkDecodedJSONResponseSuccess(json);
                  }

                  if (!success) {
                      CCR.xdmod.ui.presentFailureResponse(response, {
                          title: 'User Management',
                          wrapperMessage: 'Could not retrieve exception email addresses.'
                      });
                      // eslint-disable-next-line no-useless-return
                      return;
                  }
                  if (json.organization !== -1) {
                      self.setValue(json.organization);
                  }
              } // callback
          }); // Ext.Ajax.request
      }, this, { single: true });

      Ext.apply(this, {

         store: this.userStore

      });

      CCR.xdmod.ui.InstitutionDropDown.superclass.initComponent.apply(this, arguments);

   }//initComponent

});//CCR.xdmod.ui.InstitutionDropDown
