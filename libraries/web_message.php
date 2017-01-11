<?php

   namespace xd_web_message;

function displayMessage($message, $exception_message = '', $include_structure_tags = false)
{
 
    if (!empty($exception_message)) {
        $exception_message = '<br><br><span style="color: #888">(' . $exception_message . ')</span>';
    }
      
    $message =  '<center>'.
            '<br>'.
            '<img src="gui/images/xdmod_main.png">'.
            '<br><br>'.
            $message.
            $exception_message.
            '</center>';
            
    if ($include_structure_tags == true) {
        $message = '<html><body>'.$message.'</body></html>';
    }
      
    print $message;
}//displayMessage
