/* 
   
   StringUtilities.js  (String Utilities Class)
   
*/

var Direction = {

   LEFT: 0,
   RIGHT: 1
   
};//Direction
   
var StringUtilities = {

   pad: function(string, pad_length, pad_character, pad_direction) {

      string = string.toString();
      
      pad_character = (pad_character) ? pad_character : '0';
      pad_direction = (pad_direction) ? pad_direction : Direction.LEFT;
      
      if (string.length >= pad_length) return string;
      
      var diff = pad_length - string.length;
      
      for (var i = 0; i < diff; i++) {
         if (pad_direction == Direction.LEFT) string = pad_character + string;
         if (pad_direction == Direction.RIGHT) string += pad_character;
      }
         
      return string;

   }//pad
   
};//StringUtilities
