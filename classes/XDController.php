<?php

    require_once dirname(__FILE__).'/../configuration/linker.php';

    /*
	 * @Class XDController
	 * XDMoD Controller Class
	 */

class XDController
{
    
    private $_requirements;
    private $_registered_operations;
    private $_operation_handler_directory;
        
    // ---------------------------
        
    function __construct($requirements = array(), $basePath = OPERATION_DEF_BASE_PATH)
    {
        
        $this->_requirements = $requirements;
        $this->_registered_operations = array();
            
        $this->_operation_handler_directory = $basePath.'/'.substr(basename($_SERVER["SCRIPT_NAME"]), 0, -4);
    }//construct
        
    // ---------------------------
        
    public function registerOperation($operation)
    {
        
        $this->_registered_operations[] = $operation;
    }//registerOperation
        
    // ---------------------------
        
    public function invoke($method, $session_variable = 'xdUser')
    {
        
           
        xd_security\enforceUserRequirements($this->_requirements, $session_variable);
    
        // --------------------
        
        $params = array('operation' => RESTRICTION_OPERATION);

        $isValid = xd_security\secureCheck($params, $method);

        if (!$isValid) {
            $returnData['status'] = 'operation_not_specified';
            $returnData['success'] = false;
            $returnData['totalCount'] = 0;
            $returnData['message'] = 'operation_not_specified';
            $returnData['data'] = array();
            xd_controller\returnJSON($returnData);
        };
            
        // --------------------
            
        if (!in_array($_REQUEST['operation'], $this->_registered_operations)) {
            $returnData['status'] = 'invalid_operation_specified';
            $returnData['success'] = false;
            $returnData['totalCount'] = 0;
            $returnData['message'] = 'invalid_operation_specified';
            $returnData['data'] = array();
            xd_controller\returnJSON($returnData);
        }
            
        $operation_handler = $this->_operation_handler_directory.'/'.$_REQUEST['operation'].'.php';
            
        if (file_exists($operation_handler)) {
            include $operation_handler;
        } else {
            $returnData['status'] = 'operation_not_defined';
            $returnData['success'] = false;
            $returnData['totalCount'] = 0;
            $returnData['message'] = 'operation_not_defined';
            $returnData['data'] = array();
            xd_controller\returnJSON($returnData);
        }
    }//invoke
}//XDController
