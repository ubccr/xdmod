<?php

namespace Reporting;

class Builder extends \aRestAction
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
        return new Builder($request);
    }
  
   // ACTION: getResources ================================================================================

    private function getResourcesVisibility()
    {
      
        return false;
    }//getResourcesVisibility
   
   // -----------------------------------------------------------

    private function getResourcesAction()
    {
                  
        $user = $this->_authenticateUser();
      
        $resources = array();
      
        for ($i = 1; $i < 6; $i++) {
            $g = array();
            $g['site'] = "Site $i";
            $g['resources'] = array("site_{$i}_resource_a", "site_{$i}_resource_b", "site_{$i}_resource_c");
            $resources[] = $g;
        }
   
        return array(
         'success' => true,
         'results' => $resources
        );
    }//getResourcesAction

   // -----------------------------------------------------------
  
    private function getResourcesDocumentation()
    {
      
        $documentation = new \RestDocumentation();
      
        $documentation->setDescription('Get a listing of resource provider information and their resources.');
       
        $documentation->setAuthenticationRequirement(true);
      
        $documentation->setOutputFormatDescription('An array of records, each having the following components:');
      
        $documentation->addReturnElement("site", "A resource provider");
        $documentation->addReturnElement("resources", "Resources offered by that provider");
            
        return $documentation;
    }//getResourcesDocumentation
  
  
   // ACTION: getMetrics ================================================================================

    private function getMetricsVisibility()
    {
      
        return false;
    }//getMetricsVisibility
   
   // -----------------------------------------------------------
   
    private function getMetricsAction()
    {
                  
        $user = $this->_authenticateUser();
      
        $metrics = array();
      
        for ($i = 1; $i < 6; $i++) {
            $metricNode = new \ExtJS\TreeNode("Category $i");
         
            $categoryNodeA = new \ExtJS\TreeNode("Metric $i A", true);
            $categoryNodeB = new \ExtJS\TreeNode("Metric $i B", true);
            $categoryNodeC = new \ExtJS\TreeNode("Metric $i C", true);
         
            $metricNode->addChildNode($categoryNodeA);
            $metricNode->addChildNode($categoryNodeB);
            $metricNode->addChildNode($categoryNodeC);
          
            $metrics[] = $metricNode->getData();
        }
   
        return array(
         'success' => true,
         'results' => $metrics
        );
    }//getMetricsAction

   // -----------------------------------------------------------
   
    private function getMetricsDocumentation()
    {
      
        $documentation = new \RestDocumentation();

        $documentation->setDescription('Get a listing of metrics.');
            
        $documentation->setAuthenticationRequirement(true);
      
        $documentation->setOutputFormatDescription('An array of records, each having the following components:');
      
        $documentation->addReturnElement("category", "A category which contains metrics");
        $documentation->addReturnElement("metrics", "A particular metric");
            
        return $documentation;
    }//getMetricsDocumentation
}// class Builder
