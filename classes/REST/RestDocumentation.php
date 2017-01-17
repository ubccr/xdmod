<?php

   /*
    * RestDocumentation encapsulates a documentation 'block' primarily employed by the
    * REST catalog
    *
    */
   
class RestDocumentation
{
      
    private $_description;               // @string    A description as to what the action does
                                                 
    private $_action_arguments;          // @array     The arguments required by the REST call, along with their meaning

    private $_output_format_description; // @string    The text that will be used in describing the structure of the action's response
      
    private $_return_elements;           // @array     The elements returned by the REST call (on success), along with their meaning
      
    private $_authentication_required;   // @boolean   A flag used to indicate whether token-based authentication is required to use this REST call
      
   // ----------------------------------------------

    public function __construct()
    {
      
        $this->_description = '';
        $this->_action_arguments = array();
         
        $this->_output_format_description = '';
        $this->_return_elements = array();

        $this->_authentication_required = false;
    }//__construct

   // ----------------------------------------------

   // getRESTResponse:
   // Render a response suitable for REST
   // getRESTResponse() will be called by an action's documentation() method when help()
   // isn't defined for that action, yet requested
      
    public function getRESTResponse()
    {
      
        $response = array();
         
        $response['description'] = $this->_description;
        $response['needs_authentication'] = ($this->_authentication_required);
        $response['arguments'] = $this->_action_arguments;
        $response['output_format_description'] = $this->_output_format_description;
        $response['output'] = $this->_return_elements;
                               
        return array (
          'success' => true,
          'results' => $response
        );
    }//getRESTResponse
      
   // ----------------------------------------------
   
   // setDescription:
   //
   // @param $description (string) -- The purpose for the action.  In the REST API catalog, this
   //                                 $description will be displayed after 'Purpose:'
      
    public function setDescription($description)
    {
        $this->_description = $description;
    }

    public function getDescription()
    {
        return $this->_description;
    }
      
   // ----------------------------------------------
   
   // setAuthenticationRequirement:
   // If the action requires authentication (is user-centric, requiring a token),
   // set $is_required (boolean) to true.
   //
   // @param $is_required (boolean)

    public function setAuthenticationRequirement($is_required)
    {
        $this->_authentication_required = $is_required;
    }

    public function getAuthenticationRequirement()
    {
        return $this->_authentication_required;
    }
            
   // ----------------------------------------------

   // setOutputFormatDescription:
   //
   // @param $description (string) -- the text that will be used in describing the structure of the action's response

    public function setOutputFormatDescription($description)
    {
        $this->_output_format_description = $description;
    }
      
    public function getOutputFormatDescription()
    {
        return $this->_output_format_description;
    }
            
   // ----------------------------------------------
      
   // addReturnElement:
   // For every element returned as part of the action's response, a call to addReturnElement(...) should be made.
   //
   // @param $element (string) -- the name of the returned element
   // @param $description (string) -- a description explaining what that returned element is in lamens terms
      
    public function addReturnElement($element, $description)
    {
        $this->_return_elements[$element] = $description;
    }
      
    public function getReturnElements()
    {
        return $this->_return_elements;
    }
 
   // ----------------------------------------------
                  
   // addArgument:
   // Any inputs (arguments) to an action are made available to the REST API Catalog by calling addArgument(...)
   //
   // @param $argument (string) -- the name of the argument to be passed in (and will subsequently be sought by the action's implementation)
   // @param $description (string) -- the description of the argument to be passed in
   // @param $is_required (boolean) -- used to indicate whether the argument in question is required or optional
      
    public function addArgument($argument, $description, $is_required = true)
    {
        $this->_action_arguments[$argument] = array('description' => $description, 'is_required' => $is_required);
    }
      
    public function getArguments()
    {
        return $this->_action_arguments;
    }
}//RestDocumentation
