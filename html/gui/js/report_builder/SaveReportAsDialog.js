XDMoD.SaveReportAsDialog = Ext.extend(Ext.Window, {

   closable: false,
   draggable: false,
   resizable: false,

   title: 'Save Report As',

   initComponent: function(){

      var self = this;

      // ----------------------------------------

      var presentOverlay = function(status, message) {

         var styleColor = (status) ? '#080' : '#f00';

         mainContainer.getEl().mask('<div class="overlay_message" style="color:' + styleColor + '">' + message + '</div>');

         (function() {

            mainContainer.getEl().unmask();

         }).defer(2000);

      };//presentOverlay

      // ----------------------------------------

      var txtReportFileName = new Ext.form.TextField(Ext.apply({}, {
         width: 200,

         listeners: {
            blur: XDMoD.utils.trimOnBlur,
            invalid: XDMoD.utils.syncWindowShadow,
            valid: XDMoD.utils.syncWindowShadow
         }
      }, XDMoD.ReportCreator.prototype.nameFieldConfig));//txtReportFileName

      // ----------------------------------------

      self.present = function(evt, default_filename) {

         if (default_filename.length > 0)
            txtReportFileName.setValue(default_filename + ' (Copy)');

         self.show();

         var targetLocation = evt.el.getXY();
         targetLocation[0] += 25;
         targetLocation[1] += 15;

         self.setPosition(targetLocation);

      };//present

      // ----------------------------------------

      self.checkClick = function(event) {

         // Behave like a menu in that, when the window no longer becomes the target of a 'click',
         // the window should disappear

         if (!event.within(self.getEl())) {
            self.close();
         }

      };

      self.on('afterrender', function() {

         (function() {
            Ext.EventManager.on(document, 'click', self.checkClick, self);
         }).defer(400);

      });

      self.on('close', function() {

         XDMoD.TrackEvent('Report Generator (Report Editor)', 'Dismissed Save Report As dialog via un-focus');
         Ext.EventManager.un(document, 'click', self.checkClick, self);

      });

      // ----------------------------------------

      var pnlForm = new Ext.FormPanel({

         labelWidth: 95,
         frame: false,
         border: false,
         //title: 'Save Report As',
         bodyStyle:'padding:5px 7px 0',
         defaults: {width: 200},
         labelAlign: 'top',
         baseCls: 'x-plain',
         cls: 'no-underline-invalid-fields-form',

         items: [ txtReportFileName ]

      });//pnlForm

      // ----------------------------------------

      var aCallback = function(success, msg) {

         presentOverlay(success, msg);

      };//aCallback

      // ----------------------------------------

      var mainContainer = new Ext.Panel({

         width: 223,
         border: false,
         baseCls: 'x-plain',

         items: [

            pnlForm

         ],

         bbar: {

            items: [

               new Ext.Button({

                  iconCls: 'report_edit btn_save_as',
                  text: 'Save',
                  handler: function(){

                     if (!pnlForm.getForm().isValid()) {
                        return;
                     }

                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on Save in Save Report As dialog');

                     self.executeHandler(txtReportFileName.getValue(), aCallback);

                  }

               }),

               '->',

               new Ext.Button({

                  text: 'Close',
                  iconCls: 'general_btn_close',

                  handler: function(){

                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on the Close button in the Save Report As dialog');
                     self.hide();

                  }

               })

            ]

         }

      });//mainContainer

      // ----------------------------------

      Ext.apply(this,
      {

         //layout: 'border',
         showSeparator: false,
         width: 237,

         items: [

            mainContainer

         ]//items

      });

      XDMoD.SaveReportAsDialog.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.SaveReportAsDialog
