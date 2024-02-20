Ext.ns('XDMoD');

XDMoD.RecipientVerificationPrompt = Ext.extend(Ext.Window, {

   initComponent: function() {

      var self = this;

      if (self.recipients == undefined) self.recipients = [];
      if (self.verificationCallback == undefined) self.verificationCallback = function(){alert('dummy');};

      var myData = {};
      myData.records = [];

      for (var i = 0; i < self.recipients.length; i++)
         myData.records.push({name: self.recipients[i]});

      var fields = [
         {name: 'name', mapping : 'name'}
      ];

      var gridStore = new Ext.data.JsonStore({
         fields : fields,
         data   : myData,
         root   : 'records'
      });

      var cols = [
         { id : 'name', header: "Recipient", width: 100, sortable: true, dataIndex: 'name'}
      ];

      var grid = new Ext.grid.GridPanel({
         store            : gridStore,
         columns          : cols,
         stripeRows       : true,
         autoExpandColumn : 'name',
         width            : 200,
         height           : 200,
         region           : 'west'
      });

      var pnlDesc = new Ext.Panel({

         region: 'center',
         baseCls: 'x-plain',
         html: 'The message you are about to send will be e-mailed to every single address listed on the left.<br><br>If you ' +
               'click on <b>Send Now</b>, the message will be immediately delivered to these intended recipients.<br><br>Otherwise, click <b>Cancel</b> and adjust the ' +
               'group / role settings accordingly.',
         padding: '15 15 15 15',
         layout: 'fit'

      });

      Ext.apply(this, {

         width: 500,
         height: 270,
         resizable: false,
         modal: true,

         title: 'Confirm Recipients (' + self.recipients.length + ' total)',
         layout: 'border',

         items: [
            grid,
            pnlDesc
         ],

         bbar: {

            items: [

               new Ext.Button({

                  text: 'Cancel',
                  iconCls: 'btn_email_cancel',
                  handler: function() {

                     self.close();

                  }//handler

               }),

               '->',

               new Ext.Button({

                  text: 'Send Now',
                  iconCls: 'btn_email_send',
                  handler: function() {

                     self.verificationCallback(self.recipients.join(','));
                     self.close();

                  }//handler

               })

             ]

         }

       });//Ext.apply

      XDMoD.RecipientVerificationPrompt.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.RecipientVerificationPrompt
