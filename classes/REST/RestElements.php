<?php
    
    /*
	 * @Class RestElements
	 *
	 * An instance of this class is returned when running RestController->parseRequest()
	 *
	 */
     
class RestElements
{
      
    private $_url;
    private $_status;
    private $_realm;
    private $_category;
    private $_action;
    private $_action_arguments;
    private $_output_format;
    private $_token;
    private $_api_key;
    private $_ip_address;

   // ----------------------------------------
            
    function __construct()
    {
      
        $this->_url = '';
        $this->_status = '';
        $this->_realm = '';
        $this->_category = '';
        $this->_action = '';
        $this->_action_arguments = '';
        $this->_output_format = '';
        $this->_token = '';
        $this->_api_key = '';
        $this->_ip_address = '';
    }//__construct
            
   // ----------------------------------------
   // Mutator methods
   // ----------------------------------------
      
    public function setUrl($url)
    {
        $this->_url = $url;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
    }

    public function setRealm($realm)
    {
        $this->_realm = $realm;
    }
      
    public function setCategory($category)
    {
        $this->_category = $category;
    }
      
    public function setAction($action)
    {
        $this->_action = $action;
    }

    public function setActionArguments($action_arguments)
    {
        $this->_action_arguments = $action_arguments;
    }

    public function setOutputFormat($output_format)
    {
        $this->_output_format = $output_format;
    }
      
    public function setToken($token)
    {
        $this->_token = $token;
    }

    public function setAPIKey($api_key)
    {
        $this->_api_key = $api_key;
    }

    public function setIPAddress($ip_address)
    {
        $this->_ip_address = $ip_address;
    }

   // ----------------------------------------
   // Accessor methods
   // ----------------------------------------
      
    public function getUrl()
    {
        return $this->_url;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getRealm()
    {
        return $this->_realm;
    }
      
    public function getCategory()
    {
        return $this->_category;
    }
      
    public function getAction()
    {
        return $this->_action;
    }

    public function getActionArguments()
    {
        return $this->_action_arguments;
    }

    public function getOutputFormat()
    {
        return $this->_output_format;
    }

    public function getToken()
    {
        return $this->_token;
    }

    public function getAPIKey()
    {
        return $this->_api_key;
    }
      
    public function getIPAddress()
    {
        return $this->_ip_address;
    }
}//RestElements
