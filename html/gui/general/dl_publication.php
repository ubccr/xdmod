<?php

   require_once dirname(__FILE__).'/../../../configuration/linker.php';

   \xd_security\assertParameterSet('file');

   $absFilename = dirname(__FILE__).'/../../../assets/publications/'.$_REQUEST['file'].'.pdf';
   
if (!file_exists($absFilename)) {
    \xd_response\presentError("The publication specified does not exist");
}

   header("Content-type: application/pdf");
   header("Content-Disposition:attachment;filename=\"{$_REQUEST['file']}.pdf\"");
   readfile($absFilename);
