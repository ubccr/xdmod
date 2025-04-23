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

      Ext.apply(this, {

         store: this.userStore

      });

      CCR.xdmod.ui.InstitutionDropDown.superclass.initComponent.apply(this, arguments);

   }//initComponent

});//CCR.xdmod.ui.InstitutionDropDown
