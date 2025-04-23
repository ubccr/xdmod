// LoginPrompt.js

// Login prompt conditionally presented to a user whose
// session has expired.

// FIXME: Finish refactoring out custom form functionality (formatMessage,
//        custom validation, etc.) in favor of built-in methods. (Refactoring
//        was not finished due to this module not currently being used.)

var SUCCESS = '#080';
var FAIL = '#f00';

XDMoD.LoginPrompt = Ext.extend(Ext.Window,  {

   rest_call_config: null,   // Needs to be overridden by making a call to setRESTConfig(...)

    width:333,
    height:180,
    layout:'table',
    layoutConfig:{ columns:1 },
    frame: true,
    modal:true,
    closable:false,
    closeAction:'hide',
    resizable:false,
    title:'Session Expired',
   padding: '10 0 0 10',

    setRESTConfig: function(config) {
      this.rest_call_config = config;
      if (config.title) this.sectionGeneral.setTitle(config.title);
    },

    initComponent: function(){

      var self = this;

      // -----------------------------------------------------------------

      var presentOverlay = function(status, message, customdelay, cb) {

         var delay = customdelay ? customdelay : 2000;

         relogin_panel_container.showMask('<div class="overlay_message" style="color:' + status + '">' + message + '</div>');

         (function() {

            relogin_panel_container.hideMask();
            if (cb) cb();

         }).defer(delay);

      };//presentOverlay

      // -----------------------------------------------------------------

      var fieldWidth = 180;

      var minUsernameLength = XDMoD.constants.minUsernameLength;
      var maxUsernameLength = XDMoD.constants.maxUsernameLength;
      var usernameField = new Ext.form.TextField({

         fieldLabel: 'Username',
         disabled: true,
         value: CCR.xdmod.ui.username,
         width: fieldWidth,

         minLength: minUsernameLength,
         minLengthText: 'Minimum length (' + minUsernameLength + ' characters) not met.',
         maxLength: maxUsernameLength,
         maxLengthText: 'Maximum length (' + maxUsernameLength +  ' characters) exceeded.',
         regex: XDMoD.regex.usernameCharacters,
         regexText: 'The username must consist of alphanumeric characters only, or it can be an e-mail address.'

      });

      var minPasswordLength = XDMoD.constants.minPasswordLength;
      var maxPasswordLength = XDMoD.constants.maxPasswordLength;
      var passwordField = new Ext.form.TextField({

         fieldLabel: 'Password',
         width: fieldWidth,
         inputType: 'password',

         minLength: minPasswordLength,
         minLengthText: 'Minimum length (' + minPasswordLength + ' characters) not met.',
         maxLength: maxPasswordLength,
         maxLengthText: 'Maximum length (' + maxPasswordLength +  ' characters) exceeded.',

         listeners: {

            'keydown': function (a,e) {

               if (e.getCharCode() == 13){
                  this.blur();
                  processLogin();
               }

            }//keydown

         }//listeners

      });


      var processLogin = function() {

         // Sanitization --------------------------------------------

         if (!(passwordField.validate())) {

            presentOverlay (

               FAIL,
               passwordField.formatMessage,
               null,

               function(){

                  passwordField.focus(true);

               }

            );//presentOverlay

            return;

         }//if

         // ---------------------------------------------------------

         var restArgs = {
            'username' : usernameField.getValue(),
            'password' : encodeURIComponent(passwordField.getValue())
         };

         XDMoD.REST.Call({
            action: 'authentication/utilities/login',
            'arguments': restArgs,
            callback: loginCallback
         });

      };//processLogin



      // -----------------------------------------------------------------

      self.sectionGeneral = new Ext.FormPanel({

         labelWidth: 95,
         frame:true,
         title: 'Log Back Into XDMoD',
         width: 300,
         defaults: {width: 200},
         defaultType: 'textfield',

         items: [

               usernameField,
              passwordField

            ]

      });

      // -----------------------------------------------------------------

      var btnCancel = new Ext.Button({
         text: 'Log in as different user',
         flex: 1,
         handler: function() {
            location.href = 'index.php';
         }
      });

      // -----------------------------------------------------------------

      var btnLogin = new Ext.Button({

         text: 'Log Back In',
         flex: 1,

         handler: function() {

            processLogin();

         }

      });


     // -----------------------------------------------------------------

      var loginCallback = function(responseData) {

            if (responseData.success) {

               // Cache the new token
               XDMoD.REST.token = responseData.results.token;

               self.hide();
               passwordField.setValue('');


               // Upon successful re-login, look in the REST call configuration to see if a custom callback (override) has
               // been specified.  If so, invoke that callback.

               if (self.rest_call_config && self.rest_call_config.successCallback) {
                  self.rest_call_config.successCallback();
                  return;
               }

               // ... Otherwise, check the REST call configuration to see if the call that triggered the login prompt
               // should be re-attempted.

               if (self.rest_call_config && self.rest_call_config.resume == true)
                  XDMoD.REST.Call(self.rest_call_config);

            }
            else{

               presentOverlay (

                  FAIL,
                  responseData.message,
                  null,

                  function(){

                     passwordField.focus(true);

                  }

               );//presentOverlay

            }

      };//loginCallback

      // -----------------------------------------------------------------

      var relogin_panel_container = new Ext.Panel({

         id: 'panel_outer_container',
         baseCls:'x-plain',
         plugins: [new Ext.ux.plugins.ContainerMask ({ msg:'', masked:false })],

         items:[

            self.sectionGeneral,

            {

               anchor:'100%',
               baseCls:'x-plain',
               layout:'hbox',
               padding: '10 0 0 0',

               items: [btnCancel, {xtype: 'spacer', width: 20}, btnLogin]

            }

          ]

        });

      // -----------------------------------------------------------------

      this.on('show', function() {

         (function() {
            passwordField.focus(true);
         }).defer(500);

      });

      // -----------------------------------------------------------------

      Ext.apply(this, {

         items:[ relogin_panel_container ]

        });

         XDMoD.LoginPrompt.superclass.initComponent.call(this);

    }

});

