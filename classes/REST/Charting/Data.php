<?php

namespace Charting;

class Data extends \aRestAction
{

   // --------------------------------------------------------------------------------
   // @see aRestAction::__call()
   // --------------------------------------------------------------------------------

    public function __call($target, $arguments)
    {
    
        // The following is required for RestArgumentSchema to access the enumeration functions
        // for the arguments of a REST action.
        
        if (method_exists($this, $target)) {
            return $this->$target($arguments);
        }
            
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
        return new Data($request);
    }


   // ACTION: getDataset ================================================================================

    private function getDatasetVisibility()
    {
      
        return false;
    }//getDatasetVisibility
   
   // -----------------------------------------------------------
   
    private function getDatasetAction()
    {

        $user = $this->_authenticateUser();

        $actionParams = $this->_parseRestArguments('query_realm/group_by/statistic/start_date/end_date');
      
        $actionParams['realm'] = 'Jobs';
      
        $queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromRequest($actionParams, $user);
      
        \xd_debug\dumpArray($queries);
      
        exit;

        $output_format = $this->_getRawFormat();

        return array(
         'headers' => $output_format->getHeaders(),
         'success' => true,
         'results' => array($chart['chart_png'])
        );
    }//getDatasetAction

   // -----------------------------------------------------------
   
    private function getDatasetArgumentSchema()
    {

        $user = $this->_authenticateUser();
         
        $schema = new \RestArgumentSchema($user);
   
        $schema->map('query_realm', TYPE_ENUM, array($this, 'enumScope'));
        $schema->map('group_by', TYPE_ENUM, array($this, 'enumGroupByValues'));
        $schema->map('start_date', TYPE_DATE);
        $schema->map('end_date', TYPE_DATE);
        $schema->map('statistic', TYPE_ENUM, array($this, 'enumStatistics'), array('group_by'));
   
        return $schema;
    }//getDatasetArgumentSchema

   // -----------------------------------------------------------

    private function enumScope()
    {
   
        $response = array();
      
        $response[] = array('id' => 'jobs', 'label' => 'Jobs');
      
        return $response;
    }//enumScope
   
   // -----------------------------------------------------------
         
    private function enumGroupByValues()
    {
   
        $user = $this->_authenticateUser();
      
        $response = array();
      
        $role = $user->getActiveRole();
        $realms = array_keys($role->getAllQueryRealms('rest'));
      
        foreach ($realms as $realm) {
            $descriptor_ids = array_keys($role->getQueryDescripters('rest', $realm));
         
            foreach ($descriptor_ids as $id) {
                $d = $role->getQueryDescripters('rest', $realm, $id);
         
                $response[] = array(
                  'id' => $id,
                  'label' => $d->getGroupByLabel()
                );
            }//foreach
        }//foreach
      
        return $response;
    }//enumGroupByValues

   // -----------------------------------------------------------
   
    function enumStatistics($groupBy)
    {
      
        $user = $this->_authenticateUser();
         
        $response = array();
      
        $role = $user->getActiveRole();
   
        $qDescriptor = $role->getQueryDescripters('rest', 'Jobs', $groupBy);
   
        $allowed_stats = $qDescriptor->getGroupByInstance()->getPermittedStatistics();

        foreach ($allowed_stats as $stat) {
            $response[] = array(
               'id' => $stat,
               'label' => $qDescriptor->getStatistic($stat)->getLabel()
              );
        }//foreach
               
        return $response;
    }//enumStatistics
       
   // -----------------------------------------------------------
   
    private function getDatasetDocumentation()
    {
      
        $documentation = new \RestDocumentation();
      
        $documentation->setDescription('Generate a dataset.');

        $documentation->setAuthenticationRequirement(true);
      
        $documentation->addArgument('query_realm', 'The realm you are interested in getting data for.');
        $documentation->addArgument('group_by', 'The context of data to be included in the dataset.');
        $documentation->addArgument('statistic', 'The context of data to be included in the dataset.');
                  
        return $documentation;
    }//getDatasetDocumentation
}// class Data
