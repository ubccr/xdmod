Ext.namespace('XDMoD');

XDMoD.ChartThumbPreview = Ext.extend(Ext.Window,  {
   
   initComponent: function(){
       
      var self = this;
      
      // ----------------------------------------  

      var btnClose = new Ext.Button({
      
         text: 'Close',
         iconCls: 'general_btn_close', 
         handler: function(){ 
         
            self.close();
            
         }
         
      });//btnClose
      
      self.on('close', function() {
      
         XDMoD.TrackEvent('Report Generator', 'Closed chart preview window');
      
      });
      
      // ----------------------------------------      

      Ext.apply(this, {
      
         resizable: false,
         width: 815,
         height: 660,
         modal: true,
         title: self.title,
      
         bbar: {
         
            items: [
               
               {
                  xtype: 'tbtext', 
                  text: self.timeframe_desc
               },
               
               '->',
               
               btnClose
               
            ]
            
         },
      
         html: '<img src="' + self.ref + '">'
            
      });//Ext.apply
      
      XDMoD.ChartThumbPreview.superclass.initComponent.call(this);
   
   }//initComponent

});//XDMoD.ChartThumbPreview