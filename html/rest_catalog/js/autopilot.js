// AutoPilot:
// Navigate a ExtJS tree structure automatically, accounting for the fact that not all nodes to be searched are immediately
// available (they may be loaded on an as-needed basis)

// Author: Ryan Gentner <rgentner@ccr.buffalo.edu>
// Last Updated:  February 8, 2011


var tree_depth_cache = 0;      // For keeping track how "deep" into the tree we currently are
                               // (used for controlling the for-loop)
                                           
var argument_cache = null;     // Stores the arguments from the initial call 
                               // (to be re-used upon subsequent invocations of autoPilot())
            
var cached_node = null;        // Stores the last "parent" node in which .findChild(...) was
                               // invoked on
                        
// ===========================================================

// NOTE:  When invoking autoPilot(), be sure to make the following call first:
// 
// tree_depth_cache = 0;
//
// to enforce proper initialization

var autoPilot = function() {
               
   var tree = Ext.getCmp('rest_catalog_tree');
               
   tree.removeListener('load', attemptToNavigate);
   tree.removeListener('load', arguments.callee);
               
   // ------------------------
               
   if (tree_depth_cache == 0) {
               
      // First invocation (initialization phase; cache intitial arguments)
      cached_node = null;
      argument_cache = arguments;
                  
   }
   else {
               
      // Subsequent invocation (retrieve previously cached arguments)    
      arguments = argument_cache;
              
   }
               
   var currentBaseNode = (tree_depth_cache == 0) ? tree.getRootNode() : cached_node;
               
   // ------------------------
               
   for (i = tree_depth_cache; i < arguments.length; i++){
                  
      var targetNode = currentBaseNode.findChild('text', arguments[i]);
               
      if (!targetNode) {
                     
         // Node has not yet been fully loaded yet. We cannot resume tree traversal until is has been loaded.
                     
         // When the node has successfully loaded its children, this function will be invoked.
         tree.addListener('load', arguments.callee);
                     
         // Cache the parent node so it is the first node visited upon the next invocation of this function
         cached_node = currentBaseNode;
                     
         return;
                          
      }

      // expandPath() will eventually fire load() in the case where "ungenerated children" are finally
      // loaded (and rendered) for a particular node...
                  
      tree.expandPath(targetNode.getPath(), null, function (success, node) {
                  
         // Assign the currentBaseNode to the node that was just expanded
         currentBaseNode = node;
         tree_depth_cache++;
                        
      });
                  
               
   }//for 
               
   tree.removeListener('load', arguments.callee);
   tree.getSelectionModel().select(currentBaseNode); 
                 
};//autoPilot
