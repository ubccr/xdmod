XDMoD.Profile = {
   logoutOnClose: false
};

XDMoD.ProfileEditorConstants = {

   PASSWORD:             0,
    WELCOME_EMAIL_CHANGE: 1, // designates if we're displaying first time login prompt to validate email
    FEDERATED_USER: 5 // designates whether or not this is a federated user

};//XDMoD.ProfileEditorConstants

// --------------------------------------------

XDMoD.ProfileEditor = Ext.extend(Ext.Window,  {

   width:375,

   border:false,
   frame: true,

   iconCls: 'user_profile_16',

   modal:true,
   closable:true,

   closeAction:'close',
   resizable:false,

   title:'My Profile',

   tooltip: 'Profile Editor',

   init: function() {
      this.general_settings.init();
   },

   handleProfileClose: function() {

      if (XDMoD.Profile.logoutOnClose == true) {

         Ext.Msg.show({

            maxWidth: 800,
            minWidth: 400,
            title: 'Close profile and logout?',
            msg: 'If you do not supply an e-mail address, you will be logged out of XDMoD.<br/><br/>Are you sure you want to do this?',
            buttons: Ext.Msg.YESNO,

            fn: function(resp) {

               if (resp == 'yes')
                  CCR.xdmod.ui.actionLogout();

            },//fn

            icon: Ext.MessageBox.QUESTION

         });//Ext.Msg.show

         return false;

      }//if (XDMoD.Profile.logoutOnClose == true)

      return true;

   },

   getCloseButton: function() {

      var self = this;

      return new Ext.Button({
         text: 'Close',
         iconCls: 'general_btn_close',
         handler: function(){ self.close(); }
      });

   },

   initComponent: function(){

      var self = this;

      this.general_settings = new XDMoD.ProfileGeneralSettings({parentWindow: self});
      this.role_delegation = new XDMoD.ProfileRoleDelegation({parentWindow: self, id: 'tab_role_delegation' });

      // ------------------------------------------------

      this.on('beforeclose', self.handleProfileClose);

      // ------------------------------------------------

      var tabPanel = new Ext.TabPanel({

         frame: false,
         border: false,
         activeTab: 0,

         defaults: {
            tabCls: 'tab-strip'
         },

         items: [
            this.general_settings,
            this.role_delegation
         ],

         listeners: {
            tabchange: function (thisTabPanel, tab) {
               XDMoD.utils.syncWindowShadow(thisTabPanel);

               // Fix a bug where invalid field errors are displayed poorly
               // after a tab switch by forcing form validation.
               if (tab !== self.general_settings) {
                  return;
               }

               tab.cascade(function (currentComponent) {
                  if (currentComponent instanceof Ext.form.FormPanel) {
                     currentComponent.getForm().isValid();
                     return false;
                  }

                  return true;
               });
            }
         }

      });//tabPanel

      // ------------------------------------------------

      if (CCR.xdmod.ui.isCenterDirector == false) {

         tabPanel.on('afterrender', function(tp) {

            var role_delegation_tab = tp.id + '__tab_role_delegation';

            var tab = document.getElementById(role_delegation_tab);

            tab.style.display = 'none';

         });//afterrender

      }//if (CCR.xdmod.ui.isCenterDirector == false)

      // ------------------------------------------------

      Ext.apply(this, {

         items:[
            tabPanel
         ]

      });

      XDMoD.ProfileEditor.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.ProfileEditor
