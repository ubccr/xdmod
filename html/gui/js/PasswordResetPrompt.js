// FIXME: Finish refactoring out custom form functionality (formatMessage,
//        custom validation, etc.) in favor of built-in methods. (Refactoring
//        was not finished due to this module not currently being used.)

var SUCCESS = '#080';
var FAIL = '#f00';

XDMoD.PasswordResetPrompt = Ext.extend(Ext.Panel,  {

   width:333,
   height:180,
   cls: 'win_password_reset_prompt',
   layout:'table',
   layoutConfig:{ columns:1 },
   frame: true,
   resizable:false,
   title:'Reset Your Password',
   padding: '10 0 0 10',

    initComponent: function(){

      var self = this;

      // -----------------------------------------------------------------

      var presentOverlay = function(status, message, customdelay, cb) {

         var delay = customdelay ? customdelay : 2000;

         Ext.getCmp('panel_outer_container').showMask('<div class="overlay_message" style="color:' + status + '">' + message + '</div>');

         (function() {

            Ext.getCmp('panel_outer_container').hideMask();
            if (cb) cb();

         }).defer(delay);

      };//presentOverlay

      // -----------------------------------------------------------------

      var updateAccount = function() {

         var fieldsToValidate = ['new_password', 'new_password_repeat'];

         // Sanitization --------------------------------------------

         var incomplete_fields = false;

         for (i = 0; i < fieldsToValidate.length; i++) {

            if (Ext.getCmp(fieldsToValidate[i]).validate() == false) {

               presentOverlay(
                  FAIL,
                  Ext.getCmp(fieldsToValidate[i]).formatMessage,
                  3000,
                  function(){
                     Ext.getCmp(fieldsToValidate[i]).focus(true);
                  }
               );

               return;

            }//if

         }//for

         if (Ext.getCmp('new_password').getValue() != Ext.getCmp('new_password_repeat').getValue()) {

            presentOverlay(
               FAIL,
               'The passwords you have specified do not match',
               2000,
               function(){
                  Ext.getCmp('new_password').focus(true);
               }
            );

            return;

         }//if (mismatch)

            // Process update on backend --------------------------------


            var objParams = {
                    operation: 'update_pass',
                    rid: self.rid,
                    password: Ext.getCmp('new_password').getValue()
                };

                var conn = new Ext.data.Connection();
                conn.request({

                     url: 'controllers/user_auth.php',
                    params: objParams,
                    method: 'POST',
                     callback: function(options, success, response) {

               if (success) {

                            var json = Ext.util.JSON.decode(response.responseText);

                            if (json.status == "success"){

                        presentOverlay(
                           SUCCESS,
                           'Password updated successfully',
                           2000,
                           function(){
                              location.href = 'index.php';
                           }
                        );

                           }
                           else {
                        Ext.MessageBox.alert('Password Update', "There was a problem updating your password");
                            }

                        }
                        else {
                            Ext.MessageBox.alert('Password Update', 'There was a problem connecting to the portal service provider.');
                        }


                    }//callback

                });


      };

      // -----------------------------------------------------------------

      var btnUpdateAccount = new Ext.Button({

         text: 'Update',
         flex: 1,
         cls: 'btnUpdateAccount',

         handler: updateAccount

      });//btnUpdateAccount

      // -----------------------------------------------------------------

      var passwordStrengthMeter = new Ext.form.TextField ({

         cls: 'passStrength',
         //disabled: true,
         readOnly: true,

         listeners: {

            'render' : function() {

               var domEl = document.getElementById(this.id).parentNode;

               var newDiv = document.createElement('div');
               newDiv.className = 'lockIndicator';
               newDiv.innerHTML = '&nbsp';

               domEl.insertBefore(newDiv,domEl.firstChild);

            }//render

         }//listeners

      });

      // -----------------------------------------------------------------

      var fieldWidth = 180;

      var minPasswordLength = XDMoD.constants.minPasswordLength;
      var maxPasswordLength = XDMoD.constants.maxPasswordLength;

      var sectionGeneral = new Ext.FormPanel({

         labelWidth: 95,
         frame:true,
         title: 'Create a new password below',
         width: 300,
         defaults: {width: 200},
         defaultType: 'textfield',
         id: 'panel_outer_container',
         plugins: [new Ext.ux.plugins.ContainerMask ({ msg:'', masked:false })],

         items: [

            {
               xtype: 'textfield',
               id: 'new_password',
               fieldLabel: 'Password',
               inputType: 'password',
               width: fieldWidth,

               minLength: minPasswordLength,
               minLengthText: 'Minimum length (' + minPasswordLength + ' characters) not met.',
               maxLength: maxPasswordLength,
               maxLengthText: 'Maximum length (' + maxPasswordLength +  ' characters) exceeded.',

               listeners: {

                  'keydown': function (a,e) {

                     if (e.getCharCode() == 13) {
                        Ext.getCmp('new_password_repeat').focus(true);
                     }

                  }, //keydown

                  'keyup': function (a,e) {

                     // Analyze (score) the password typed and present the results

                     var results = testPassword(this.getValue());

                     passwordStrengthMeter.setRawValue(results.verdict);

                     passwordStrengthMeter.removeClass('veryweak');
                     passwordStrengthMeter.removeClass('weak');
                     passwordStrengthMeter.removeClass('mediocre');
                     passwordStrengthMeter.removeClass('strong');

                     passwordStrengthMeter.addClass(results.indicator);

                  }

               }//listeners

            },

            passwordStrengthMeter,

            {
               xtype: 'textfield',
               id: 'new_password_repeat',
               fieldLabel: 'Password Again',
               inputType: 'password',
               width: fieldWidth,

               listeners: {

                  'keydown': function (a,e) {

                     if (e.getCharCode() == 13) {
                        this.blur();
                        updateAccount();
                     }

                  }//keydown

               }//listeners
            },

            btnUpdateAccount

            ]

      });

      this.on('render', function() {

         (function() {
            Ext.getCmp('new_password').focus(true);
         }).defer(500);

      });


      Ext.apply(this, {

         items:[
            sectionGeneral
          ]
        });

         XDMoD.PasswordResetPrompt.superclass.initComponent.call(this);

    }

});
