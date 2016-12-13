XDMoD.CaptchaField = Ext.extend(Ext.Panel, {
         
   frame: false,
   border: false,
   height: 150,
   width: 335,
   
   baseCls: 'x-plain', 

   initComponent: function(){

      var frame_id = Ext.id();
      var self = this;
      
      self.getChallengeField = function() {

         return document.getElementById(frame_id).contentWindow.document.getElementById('recaptcha_challenge_field').value;
                           
      };//self.getChallengeField

      self.getResponseField = function() {
         
         return document.getElementById(frame_id).contentWindow.document.getElementById('recaptcha_response_field').value;
      
      };//self.getResponseField      
      
      Ext.apply(this, {
       
         html: '<iframe id="' + frame_id + '" width=100% frameborder=0 src="recaptcha.php"></iframe>'
       
      });
              
      XDMoD.CaptchaField.superclass.initComponent.call(this);

   }//initComponent           
               
});//XDMoD.CaptchaField
