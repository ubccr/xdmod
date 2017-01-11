<html>

   <head>
   
      <title>XDMoD REST API Catalog</title>
      
      <link rel="shortcut icon" href="images/favicon.ico" />

        <?php
                
        require_once dirname(__FILE__).'/../../configuration/linker.php';
        $jsCall = "";
         
        if (isset($_GET['jump_to']) && substr_count($_GET['jump_to'], '_') == 2) {
            list($jRealm, $jCategory, $jAction) = explode('_', $_GET['jump_to']);

            // Verify that the (combination of the) specified realm, category, and action is valid

            try {
                $realm = xd_rest\resolveEntity($jRealm, REST_REALM);

                $category = xd_rest\resolveEntity($jCategory, REST_CATEGORY, $realm);

                $action = xd_rest\resolveEntity($jAction, REST_ACTION, $realm, $category);

                $jsCall = "autoPilot('$realm', '$category', '$action');";
            } catch (Exception $e) {
                // The user has specified a realm_category_action combination that is not recognizable.
                // In this case, ignore the exception and load the page without any auto tree navigation
                // taking place.
            }//try
        }//if(isset($_GET...))

        ExtJS::loadSupportScripts('../gui/lib');

        ?>

      <!-- API Catalog Stylings -->
      
      <link rel="stylesheet" type="text/css" href="css/rest_api_catalog.css">
          
      <!-- AutoPilot Support -->
      
      <script type="text/javascript" src="js/autopilot.js"></script>
      
      <script type="text/javascript">
                 
         var attemptToNavigate = function(){ 
            
            tree_depth_cache = 0; 
            
            <?php
               print $jsCall;    // Hook into 'jump_to' (PHP logic)
            ?>
            
         };  

      </script>
      
      <script type="text/javascript" src="js/rest_api_catalog.js"></script>
    
   </head>

   <body>
   
      <!-- use class="x-hide-display" to prevent a brief flicker of the content -->
       
      <div id="splash_section" class="x-hide-display">
         <div style="height: 100%; background: #eeeeff">
            <table border=0 height=100% width=100%><tr><td align="center" valign="middle">
               <img src="images/rest_api_splash.png">
            </td></tr></table>
         </div>
      </div>
       
   </body>

</html>
