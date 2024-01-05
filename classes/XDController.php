<?php

	/*
	 * @Class XDController
	 * XDMoD Controller Class
	 */

	class XDController {

		private $_registered_operations;
		private $_operation_handler_directory;

		// ---------------------------

		function __construct(private $_requirements = [], $basePath = OPERATION_DEF_BASE_PATH) {

			$this->_registered_operations = [];

			$this->_operation_handler_directory = $basePath.'/'.substr(basename($_SERVER["SCRIPT_NAME"]), 0, -4);

		}//construct

		// ---------------------------

		public function registerOperation($operation): void {

			$this->_registered_operations[] = $operation;	

		}//registerOperation

		// ---------------------------

		public function invoke($method, $session_variable = 'xdUser'): void {


			xd_security\enforceUserRequirements($this->_requirements, $session_variable);

			// --------------------

			$params = ['operation' => RESTRICTION_OPERATION];

			$isValid = xd_security\secureCheck($params, $method);

			if (!$isValid) {
				$returnData['status'] = 'operation_not_specified';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'operation_not_specified';
				$returnData['data'] = [];
				xd_controller\returnJSON($returnData);
			};

			// --------------------

			if(!in_array($_REQUEST['operation'], $this->_registered_operations)){
				$returnData['status'] = 'invalid_operation_specified';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'invalid_operation_specified';
				$returnData['data'] = [];
				xd_controller\returnJSON($returnData);
			}

			$operation_handler = $this->_operation_handler_directory.'/'.$_REQUEST['operation'].'.php';

			if (file_exists($operation_handler)){
				include $operation_handler;
			}
			else{
				$returnData['status'] = 'operation_not_defined';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'operation_not_defined';
				$returnData['data'] = [];
				xd_controller\returnJSON($returnData);
			}

		}//invoke

	}//XDController
