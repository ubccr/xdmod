<?php

namespace ExtJS;

   //
   // \ExtJS\TreeNode: 
   // Encapsulates the array representing store content required for an ExtJS tree component.
    
   class TreeNode {
      
      private $_tree_data;            // @array                                             

      // ----------------------------------------------         

      public function __construct($nodeText = '', $leafNode = false)
      {
      
         $this->_tree_data = array();
         
         $this->_tree_data['text'] = $nodeText;
         $this->_tree_data['leaf'] = $leafNode;  
         
      }//__construct

      // ----------------------------------------------
      
      public function getData() {
      
         return $this->_tree_data;
                  
      }//getData
      
      // ----------------------------------------------
      
      public function render() {
      
         // This will most likely be used by trees which have a single root node
         
         //       (L)
         //      /
         // (R)--
         //      \
         //      (L)
         
         return json_encode($this->_tree_data);
                  
      }//render
      
      // ----------------------------------------------   
   
      public function setAsLeaf($leafNode)
      {
         $this->_tree_data['leaf'] = $leafNode;
      }

      // ----------------------------------------------   
   
      public function setIconClass($iconClass)
      {
         $this->_tree_data['iconCls'] = $iconClass;
      }
            
      // ----------------------------------------------   

      public function setID($nodeID)
      {         
         $this->_tree_data['id'] = $nodeID;
      }

      // ----------------------------------------------   

      public function setText($nodeText)
      {         
         $this->_tree_data['text'] = $nodeText;
      }

      // ----------------------------------------------  

      public function addChildNode($childNode)
      {         
         
         if (!isset($this->_tree_data['children'])){
            $this->_tree_data['children'] = array();
         }
                  
         $this->_tree_data['children'][] = $childNode->getData();
         
      }//addChildNode

      // ----------------------------------------------        

      public function setAttribute($attribute, $value) {
                     
         $this->_tree_data[$attribute] = $value;   
      
      }//setAttribute
      
   }//TreeNode

?>