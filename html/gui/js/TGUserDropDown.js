CCR.xdmod.ui.TGUserDropDown = Ext.extend(Ext.form.ComboBox,  {

   controllerBase: 'controllers/sab_user.php',
   triggerAction: 'all',

   user_management_mode: false,

   displayField: 'person_name',
   valueField: 'person_id',

   width: 275,
   minListWidth: 310,
   pageSize: 300,
   hideTrigger:false,
   forceSelection: true,
   minChars: 1,

   piOnly: false,

   getDisplayPIsOnly: function() {

      return this.piOnly;

   },

   setSearchMode: function (search_mode) {

      // Valid 'search_mode' values: 'formal_name' or 'username'
      this.store.baseParams.search_mode = search_mode;

      this.bindStore(this.store, false);

   },

   getValue: function () {

      // Username-based searches use the following format for the person ID:
      // id;username@host to ensure distinction.  This override parses out the
      // person ID from whatever value is returned via the superclass.

      var value = CCR.xdmod.ui.TGUserDropDown.superclass.getValue.call(this);

      var person_id = value.split(';')[0];

      return person_id;

   },

   setValue : function(v, def){

      var text = v;

      CCR.xdmod.ui.TGUserDropDown.superclass.setValue.call(this, text);

      if (def) this.lastSelectionText = def;

      return this;

   },

   initializeWithValue: function(v, l) {

      this.setValue(v, l);
      this.setRawValue(l);

   },

   displayPIsOnly: function(pi_only) {

      this.piOnly = pi_only;

      this.store.baseParams.pi_only = pi_only ? 'y' : 'n';
      this.store.baseParams.limit = this.pageSize;
      this.store.baseParams.start = 0;

      // Forces the next 'click' on the combobox to populate with relevant values
      // (and not re-use the previous data it was populated with)

      this.bindStore(this.store, false);

      if (this.getValue().length > 0) {

         this.store.baseParams.query = this.getRawValue();

         this.store.on('load',function(s)
         {

            if (s.getTotalCount() == 1) {

               // The person specified in the drop down is also a member of this group

               this.setRawValue(s.getAt(0).get('person_name'));
               this.setValue(s.getAt(0).get('person_id'));

            }
            else {

               // The person specified in the drop down is NOT a member of this group,
               // so reset the combobox and force the end-user to make a valid selection.

               // ... However, this conflicts with the intended functionality of the Search
               // Usage tab, so it is commented out.

               // this.reset();

            }

         }, this, {single: true});

         this.store.load();

      }//if (this.getValue().length > 0)

   },

   initComponent: function(){

      var self = this;

      var bParams = {
         operation: 'enum_tg_users',
         pi_only: 'n',
         search_mode: 'formal_name'
      };

      if (self.user_management_mode == true)
         bParams.userManagement = 'y';

      this.userStore = new Ext.data.JsonStore({

         url: self.controllerBase,

         autoDestroy: false,

         baseParams: bParams,

         root: 'users',
         fields: ['person_id', 'person_name'],
         totalProperty:'total_user_count',
         successProperty: 'success',
         messageProperty: 'message',

         listeners:
         {

            'exception': function(dp, type, action, options, response, arg)
            {
               CCR.xdmod.ui.presentFailureResponse(response, {
                  title: 'XDMoD'
               });
            }

         }

      });

      Ext.apply(this, {

         store: this.userStore

      });

      if (this.dashboardMode)
         this.store.baseParams.dashboard_mode = 1;

      CCR.xdmod.ui.TGUserDropDown.superclass.initComponent.apply(this, arguments);

   }, //initComponent
   listeners: {
      select: function(component, record, index) {
          var personId = component.getValue();
          var organizationComponent = this.organizationComponent;
          var organizationChangeCallback = this.organizationChangeCallback;
          if (organizationComponent) {
              Ext.Ajax.request({
                  url: XDMoD.REST.prependPathBase('persons/' + personId + '/organization'),
                  method: 'GET',
                  scope: self,
                  callback: function (options, success, response) {
                      var json;
                      if (success) {
                          json = CCR.safelyDecodeJSONResponse(response);
                          success = CCR.checkDecodedJSONResponseSuccess(json);
                      }

                      if (!success) {
                          CCR.xdmod.ui.presentFailureResponse(response, {
                              title: 'User Management',
                              wrapperMessage: 'Setting user mapping failed.'
                          });
                          return;
                      }

                      if (organizationComponent.getValue() !== json.results.organization_id &&
                          organizationChangeCallback !== undefined) {
                          organizationChangeCallback(organizationComponent.getValue(), json.results.organization_id);
                      }

                      organizationComponent.setValue(json.results.organization_id);
                  }
              });
          }
      }
   }

});//CCR.xdmod.ui.TGUserDropDown
