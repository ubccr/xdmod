
// --------------------------------------------------------------------

XDMoD.AdminPanel = Ext.extend(Ext.Window,  {

   initComponent: function(){

      var self = this;

      var sectionNewUser = new XDMoD.CreateUser({
         id: 'admin_tab_create_user',
         parentWindow: self,
         autoHeight: true
      });

      var sectionExistingUsers = new XDMoD.ExistingUsers({
         id: 'admin_tab_existing_user',
         parentWindow: self
      });

      var new_user_callback = undefined;

      var tabPanel = new Ext.TabPanel({

         frame: false,
         border: false,
         activeTab: 0,

         defaults: {
            tabCls: 'tab-strip',
            border: false
         },

         items: [
               sectionNewUser,
               sectionExistingUsers
         ],

         listeners: {

            'tabchange': function(tabPanel, tab) {

               // Fix a bug where invalid field errors are displayed poorly
               // after a tab switch by forcing form validation.
               tab.cascade(function (currentComponent) {
                  if (currentComponent instanceof Ext.form.FormPanel) {
                     var componentForm = currentComponent.getForm();
                     if (componentForm.isDirty()) {
                        componentForm.isValid();
                     } else {
                        componentForm.clearInvalid();
                     }
                     return false;
                  }

                  return true;
               });

               //If a new user has been created prior to switching to the 'Current Users' tab, the listing of existing users
               //should be refreshed automatically to reflect the recently created user(s)

               if (tab.id == 'admin_tab_existing_user') {

                  self.setWidth(970);
                  self.center();

               }

              if (tab.id == 'admin_tab_create_user') {

                  //self.setWidth(700);
                  self.setWidth(660);
                  self.center();

               }

               self.doLayout();

               if (tab.id == 'admin_tab_existing_user' && (sectionNewUser.usersRecentlyAdded == true)){
                  sectionNewUser.usersRecentlyAdded = false;
                  sectionExistingUsers.reloadUserList(sectionNewUser.userTypeRecentlyAdded);
               }

            }//tabchange

         }//listeners

      });

      self.initNewUser = function(config) {

        userData = config.user_data;

        tabPanel.setActiveTab(0);

        sectionNewUser.initialize({
           accountRequestID: userData.id,
           accountCreationCallback: config.callback
        });

        sectionNewUser.setFirstName(userData.first_name);
        sectionNewUser.setLastName(userData.last_name);
        sectionNewUser.setEmailAddress(userData.email_address);
        sectionNewUser.setUsername(userData.email_address);
        sectionNewUser.setOrganization(userData.organization);

        var searchCrit = {
            first_name: userData.first_name,
            last_name: userData.last_name
        };

        if (userData.email_address !== '') {
            searchCrit.email_address = userData.email_address;
        }

        if (userData.organization !== '') {
            searchCrit.organization = userData.organization;
        }

        sectionNewUser.setUserMapping(searchCrit, false);

        self.show();

      },

      self.loadExistingUser = function(config) {

        userData = config.user_data;

        tabPanel.setActiveTab(1);

        sectionExistingUsers.reloadUserList(userData.user_type, userData.id);
        sectionExistingUsers.setCallback(config.callback);

        sectionNewUser.reset();
        self.show();

      },

      self.showPanel = function(config) {

        if (config.doListReload) {

           sectionNewUser.setCallback(config.callback);
           sectionExistingUsers.setCallback(config.callback);

        }
        else {

           sectionNewUser.reset();
           sectionExistingUsers.setCallback(undefined);

        }

        self.show();

      },

      this.on('beforehide', function() {

         if (sectionExistingUsers.inDirtyState() == true) {

            Ext.Msg.show({

                  maxWidth: 800,
                  minWidth: 400,

                  title: 'Unsaved Changes',

                  msg: "There are unsaved changes to this account.<br />" +
                       "Do you wish to save these changes before closing the user editor?<br /><br />" +
                       "If you choose <b>No</b>, you will lose all your changes.",

                  buttons: {yes: "Yes (go back and save)", no: "No (discard changes)"},

                  fn: function(resp) {

                     if (resp == 'yes')
                        return;

                     if (resp == 'no') {
                        sectionExistingUsers.resetDirtyState();
                        self.hide();
                     }

                  },

                  icon: Ext.MessageBox.QUESTION

            });//Ext.Msg.show

            return false;

         }

      });//this.on('beforehide', ....)

      Ext.apply(this, {

         cls: 'xdmod_admin_panel',
         border:false,
         frame: true,
         modal:true,
         closable:true,
         closeAction:'hide',
         resizable:false,
         title:'User Management',
         width:800,
         items:[
            tabPanel
         ]

      });

      XDMoD.AdminPanel.superclass.initComponent.call(this);

   }//initComponent

});
