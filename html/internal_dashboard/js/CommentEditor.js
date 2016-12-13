Ext.ns('XDMoD');

XDMoD.CommentEditor = Ext.extend(Ext.Window, {
   
   entryID: -1,
   parent: null,
   
   initWithData: function(data) {
   
      this.txtFirstName.setValue(data.first_name);
      this.txtLastName.setValue(data.last_name);
      this.txtEmailAddress.setValue(data.email_address);

      this.txtFieldOfScience.setValue(data.field_of_science);
      this.txtOrganization.setValue(data.organization);
      this.txtTitle.setValue(data.title);
            
      this.lblTimeframe.html = 'Submitted: <b>' + Ext.util.Format.htmlEncode(data.time_submitted) + '</b>';
      
      this.txtAdditionalInformation.setValue(data.additional_information);
      
      this.txtComments.setValue(data.comments);
      
      this.entryID = data.id;
      
   },
   
   setParent: function(p) {
      this.parent = p;
   },
   
   initComponent: function(){

      var self = this;
      
      var updateEntry = function(id, comments) {
      
         Ext.Ajax.request({
         
            url: 'controllers/controller.php', 
            params: {
               'operation' : 'update_request', 
               'id': id,
               'comments': comments
            },	
            method: 'POST',
            callback: function(options, success, response) { 
               var json;
               if (success) {
                  json = CCR.safelyDecodeJSONResponse(response);
                  success = CCR.checkDecodedJSONResponseSuccess(json);
               }

               if (!success) {
                  CCR.xdmod.ui.presentFailureResponse(response, {
                     title: 'Account Request',
                     wrapperMessage: 'Failed to update account request.'
                  });
                  return;
               }
                     
               self.parent.storeProvider.reload(); 
               self.close();
         
            }//callback
         
         });//Ext.Ajax.request

      };//updateEntry
      
      // =========================================
      
      this.txtFirstName = new Ext.form.TextField({ 
         fieldLabel: 'First Name',  
         readOnly: true 
      });
            
      this.txtLastName = new Ext.form.TextField({ 
         fieldLabel: 'Last Name', 
         readOnly: true  
      });

      this.txtEmailAddress = new Ext.form.TextField({ 
         fieldLabel: 'E-Mail Address', 
         readOnly: true  
      });
                        
      var sectionGeneral = new Ext.FormPanel({

         labelWidth: 95,
         frame:true,
         title: 'User Information',
         bodyStyle:'padding:5px 5px 0',
         width: 300,
         defaults: {width: 170},
         cls: 'user_profile_section_general',
         defaultType: 'textfield',

         items: [

            self.txtFirstName,
            self.txtLastName,
            self.txtEmailAddress
            
         ]

      });//sectionGeneral
      
      // ---------------------------------------------------

      this.txtFieldOfScience = new Ext.form.TextField({ 
         fieldLabel: 'Field Of Science',  
         readOnly: true,
         hidden: true
      });
            
      this.txtOrganization = new Ext.form.TextField({ 
         fieldLabel: 'Organization', 
         readOnly: true  
      });

      this.txtTitle = new Ext.form.TextField({ 
         fieldLabel: 'Title', 
         readOnly: true  
      });      

      var sectionAffiliation = new Ext.FormPanel({

         margins: '10 0 0 0',
         
         labelWidth: 95,
         frame:true,
         title: 'Affiliation',
         bodyStyle:'padding:5px 5px 0',
         width: 300,
         defaults: {width: 170},
         cls: 'user_profile_section_general',
         defaultType: 'textfield',

         items: [

            self.txtFieldOfScience,
            self.txtOrganization,
            self.txtTitle
                     
         ]

      });//sectionAffiliation

      // ---------------------------------------------------

      this.txtAdditionalInformation = new Ext.form.TextArea({ 
         width: 277,
         height: 70, 
         readOnly: true,
         emptyText: 'No additional information has been specified'
      }); 
               
      var sectionThree = new Ext.Panel({

         margins: '10 0 0 0',
         
         labelWidth: 95,
         frame:true,
         title: 'Additional Information',
         bodyStyle:'padding:5px 5px 0',
         width: 300,
         height: 120,
         //defaults: {width: 170},
         cls: 'user_profile_section_general',
         defaultType: 'textfield',

         items: [
         
            self.txtAdditionalInformation
            
         ]

      });//sectionThree

      // ---------------------------------------------------

      this.txtComments = new Ext.form.TextArea({ 
         width: 277,
         height: 70
      }); 
                  
      var sectionFour = new Ext.Panel({

         margins: '10 0 0 0',
         
         labelWidth: 95,
         frame:true,
         title: 'Comments',
         bodyStyle:'padding:5px 5px 0',
         width: 300,
         height: 120,
         //defaults: {width: 170},
         cls: 'user_profile_section_general',
         defaultType: 'textfield',

         items: [

            self.txtComments
            
         ]
      
      });//sectionFour

      // ---------------------------------------------------
                  
      this.lblTimeframe = new Ext.Toolbar.TextItem({
         html: ''
      });
              
      Ext.apply(this, {
      
         padding: '5 5 5 5',
         title: 'Account Request',
         width: 642,
         height: 360,
         resizable: false,
         layout: 'border',
         
         tbar: {
         
            items: [
               //'->',
               self.lblTimeframe
            ]
            
         },
            
         bbar: {
         
            items: [
               new Ext.Button({
                  text: 'Cancel', 
                  handler: function() {
                     self.close();
                  }
               }),
               '->',
               new Ext.Button({
                  text: 'Update',
                  handler: function() {
                     updateEntry(self.entryID, self.txtComments.getValue());
                  }
               })
            ]
         
         },
         
         items: [
         
            new Ext.Panel({
            
               margins: '7 7 7 7',
               region: 'west',
               baseCls: 'x-plain',
               height: 400,
               width: 300,
               items: [   
                  sectionGeneral,
                  {xtype: 'tbtext', html: '&nbsp;'},
                  sectionAffiliation
               ]
               
            }),
            
            new Ext.Panel({
         
               margins: '7 7 7 7',
               region: 'center',
               baseCls: 'x-plain',
               height: 400,
               items: [   
                  sectionThree,
                  {xtype: 'tbtext', html: '&nbsp;'},
                  sectionFour
               ]
               
            })
            
         ]     

      });//Ext.apply
      
      XDMoD.CommentEditor.superclass.initComponent.call(this);
      
   }//initComponent
   
});//XDMoD.CommentEditor
