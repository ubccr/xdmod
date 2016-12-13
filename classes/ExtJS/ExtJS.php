<?php
    
   class ExtJS {
      
      // loadSupportScripts: Provides a convenient way to introduce the base ExtJS scripts and stylings 
      // into a page which requires the ExtJS javascript library
      
      public static function loadSupportScripts($path, $version = 'extjs') {
      
         $extData = <<<EXT
         
         <!-- ExtJS $version support files START -->
         
         <!-- Include Ext stylesheets here: --> 
         <link rel="stylesheet" type="text/css" href="$path/$version/resources/css/ext-all.css">
         <link rel="stylesheet" type="text/css" href="$path/$version/resources/css/xtheme-gray.css">
      
         <link rel="stylesheet" type="text/css" href="$path/$version/resources/css/debug.css">
      
         <!-- Include custom script to stop Ext from checking for Flash: -->

         <script type="text/javascript" src="$path/ext-stop-flash-check.js"></script>

         <!-- Include Ext and app-specific scripts: -->
		 
        
         
         <script type="text/javascript" src="$path/$version/adapter/ext/ext-base.js"></script>
         
         <script type="text/javascript" src="$path/$version/ext-all-debug-w-comments.js"></script>

         <script type="text/javascript" src="$path/$version/src/debug.js"></script>
         
         <!-- ExtJS $version support files END -->
         
         <script language="javascript">
            Ext.BLANK_IMAGE_URL = '$path/$version/resources/images/default/s.gif';   
         </script>
         
EXT;
      
         echo $extData;
      
      }//loadSupportScripts
            
   }//ExtJS

?>