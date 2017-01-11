<?php

namespace Portal;

class General extends \aRestAction
{

   // --------------------------------------------------------------------------------
   // @see aRestAction::__call()
   // --------------------------------------------------------------------------------

    public function __call($target, $arguments)
    {
         
        // Verify that the target method exists and call it.

        $method = $target . ucfirst($this->_operation);
    
        if (! method_exists($this, $method)) {
            if ($this->_operation == 'Help') {
                // The help method for this action does not exist, so attempt to generate a response
                // using that action's Documentation() method
            
                $documentationMethod = $target.'Documentation';
            
                if (! method_exists($this, $documentationMethod)) {
                    throw new \Exception("Help cannot be found for action '$target'");
                }
            
                return $this->$documentationMethod()->getRESTResponse();
            } elseif ($this->_operation == "ArgumentSchema") {
                $schemaMethod = $target.'ArgumentSchema';
         
                if (! method_exists($this, $schemaMethod)) {
                    throw new \Exception("Argument schema information cannot be found for action '$target'");
                }
         
                return $this->$schemaMethod();
            } else {
                throw new \Exception("Unknown action '$target' in category '" . strtolower(__CLASS__)."'");
            }
        }
         
        return $this->$method($arguments);
    }//__call

   // --------------------------------------------------------------------------------
   // @see aRestAction::factory()
   // --------------------------------------------------------------------------------

    public static function factory($request)
    {
        return new General($request);
    }


   // ACTION: fieldsOfScienceAction ================================================================================

    private function fieldsOfScienceVisibility()
    {
      
        return false;
    }//fieldsOfScienceVisibility
   
   // -----------------------------------------------------------
      
    private function fieldsOfScienceAction()
    {
                  
        $warehouse = new \XDWarehouse();
   
        $sciences = $warehouse->enumerateFieldsOfScience();
                     
        return array(
         'success' => true,
         'results' => $sciences
        );
    }//fieldsOfScienceAction

   // -----------------------------------------------------------

    private function fieldsOfScienceDocumentation()
    {
      
        $documentation = new \RestDocumentation();
      
        $documentation->setDescription('Retrieve the various fields of science');
       
        $documentation->setAuthenticationRequirement(false);
      
        $documentation->setOutputFormatDescription('An array of records, each having the following components:');
      
        $documentation->addReturnElement("field_id", "The numeric identifier for this field");
        $documentation->addReturnElement("field_label", "The textual description of the field");
      
        return $documentation;
    }//fieldsOfScienceDocumentation
}// class General
