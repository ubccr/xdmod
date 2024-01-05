<?php

namespace ReportTemplates;

abstract class aReportTemplate
{

   // -----------------------------------

   // @function buildReportFromTemplate

   abstract public function buildReportFromTemplate(array &$additional_params = []);

   // -----------------------------------

   function __construct(protected $_user, protected $_report_skeleton)
   {
   }//__construct

}//aReportTemplate
