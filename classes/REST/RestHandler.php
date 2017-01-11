<?php
// ================================================================================
//
// The RestHandler class serves as a handler and dispatcher for RESTful methods.
// It assumes that a directory in the include path exists with the same name as
// the category (but with the first letter capitalized) and there is a class for
// each action that the category supports.  The action classes will include
// methods to support each target.  For example, the REST URL
// /inca/query/resources will translate into a directory named "Inca" which
// should contain a class named "Query" and that class supports a method called
// "resources".
//
// NOTE: The class to handle the action is expected to be declared with a
// namespace that matches the category.  For example: "namespace Inca;"
//
// The handler page for the category is very simple as shown in the example
// below.
// ================================================================================

class RestHandler
{

  // Handler request - must be set before
    private $_request = null;

  // Constructor is PRIVATE (can only create class instance via factory(...))
    private function __construct($request)
    {
        $this->_request = $request;
    }

  // --------------------------------------------------------------------------------
  // Instantiate a copy of this handler
  //
  // @param $request REST call elements
  //
  // @returns An instantiated object
  // --------------------------------------------------------------------------------

    public static function factory($request)
    {
        return new RestHandler($request);
    }// factory()

  // --------------------------------------------------------------------------------
  // Intercept method calls to $target that do not map to defined methods.  This
  // allows us to dynamically generate the calls.
  //
  // @param $target Target method invoked
  // @param $arguments An array of arguments passed to $target
  // --------------------------------------------------------------------------------

    public function __call($target, $arguments)
    {
  
        if (null === $this->_request) {
            // factory(..) needed to be called prior to invoking a method
            $msg = "";
            throw new Exception("REST request not supplied");
        }

        // Verify that the class to handle the operation exists and instantiate a copy

        $realmDir = xd_rest\resolveRealm($this->_request->getRealm());
      
        $categoryPath = dirname(__FILE__).'/'.$realmDir;
      
        /*
        $categoryPath = dirname(__FILE__).'/'.ucfirst(strtolower($this->_request->getRealm()));

        if (!is_dir($categoryPath))
        {
         $msg = "Unknown realm '" . $this->_request->getRealm()."'";
         throw new \Exception($msg);      
        } 
        */
   
        $absolute_class_name = "\\" . $realmDir . "\\" . ucfirst(strtolower($target));
        $class_definition_file = $categoryPath . "/" . ucfirst(strtolower($target)) . ".php";
      
        if (!file_exists($class_definition_file)) {
            $msg = "Category '$target' is not defined for realm '" . $this->_request->getRealm() . "'";
            throw new \Exception($msg);
        }
            
        require_once($class_definition_file);

        // Ensure that the class has been loaded / recognized...
      
        if (!class_exists($absolute_class_name)) {
            $msg = "Category '$target' not supported by " . $this->_request->getRealm();
            throw new \Exception($msg);
        }
   
        // Check here to make sure class in question extends the appropriate abstract class
          
        if (!is_subclass_of($absolute_class_name, 'aRestAction')) {
            $msg = "Class '".$absolute_class_name."' does not comply with aRestAction";
            throw new \Exception($msg);
        }

        return $absolute_class_name::factory($this->_request);
    } // __call()
}  // class RestHandler
