<?php

namespace ReportTemplates;

abstract class aReportTemplate
{

   protected $_report_skeleton = array();
   protected $_user = NULL;

   // -----------------------------------
   
   // @function buildReportFromTemplate
   
   abstract public function buildReportFromTemplate(array &$additional_params = array());

   // -----------------------------------
   
   function __construct($user, $report_skeleton) {
   
		$this->_report_skeleton = $report_skeleton;     
		$this->_user = $user;
		
   }//__construct
	
}//aReportTemplate

?>