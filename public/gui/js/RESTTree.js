/*

   RESTTree.js
   Last Updated: March 14, 2011

   RESTTree extends the Ext.tree.TreePanel class of ExtJS.  When child node data needs to be loaded by
   the REST Tree, the corresponding Ext.tree.TreeLoader hands off the request to XDMOD.REST.Call (in RESTProxy.js).

*/

XDMoD.RESTTree = Ext.extend(Ext.tree.TreePanel, {

   // The following three options must be specified when an instance of this tree is created.

   restAction: null,                // The REST action that will be invoked as the result of children loading and node clicking

   determineArguments: null,        // Type: function pointer
                                    // Expected Signature: determineArguments(node):
                                    // Prior to a node being loaded, this function will return the configuration representing the arguments
                                    // to be passed into the REST call.

   nodeClickHandler: null,          // Type: function pointer
                                    // Expected Signature: nodeClickHandler(node):
                                    // This function will be invoked when a tree node is clicked

    cbTreeData: null,               // Type: function pointer
                                    // Expected Signature: cbTreeData(response, args)
                                    // This function will be invoked to deal with the tree data returned from the rest action.

   // ==================================

   initComponent: function(){

      var self = this;

       this.cbTreeData = this.cbTreeData || function(response, args) {
           // Append the children to the cached node (the clicked node that initiated the last load)

           if (response.success)
               args.cachedNode.appendChild(response.results);
           else
               Ext.MessageBox.alert('XDMoD.RESTTree', response.message);
       }; // cbTreeData

      // ---------------------------------------------------

      var myTreeLoader = new Ext.tree.TreeLoader({

         // The 'dataUrl' property MUST be specified (non-empty) in order for 'beforeload' to be fired

         // To prevent this dataUrl from being processed, a handler needs to be defined
         // for the 'beforeload' event and must return false.

         dataUrl: 'rest_override',

        requestData : function(node, callback, scope){
            if(this.fireEvent("beforeload", this, node, callback) !== false){
                this.transId =
                 XDMoD.REST.Call({
                     scope: this,
                        action: self.restAction,
                        callback: cbData,
                        callbackArguments: {
                           cachedNode: node
                        },

                        success: this.handleResponse,
                        failure: this.handleFailure,
                        argument: {callback: callback, node: node, scope: scope},

                        'arguments': this.call_arguments,
                        resume: true

                     });


            }else{
                // if the load is cancelled, make sure we notify
                // the node that we are done
                this.runCallback(callback, scope || node, []);
            }
        }


      });//myTreeLoader

      // ---------------------------------------------------
       myTreeLoader.on('beforeload', function(loader,node,callback)
       {
          loader.call_arguments = this.determineArguments (node);
          return true;
       },this);



      // ---------------------------------------------------
       if(this.nodeClickHandler)
      {
          this.getSelectionModel().on('selectionchange', function(model, node){

             this.nodeClickHandler(node);

          }, this);//this.on('selectionchange', ...)
      }
      // ---------------------------------------------------
      var cbData = this.cbTreeData;
      /* var cbTreeData = function(response, args) {

         // Append the children to the cached node (the clicked node that initiated the last load)

         if (response.success)
            args.cachedNode.appendChild(response.results);
         else
            Ext.MessageBox.alert('XDMoD.RESTTree', response.message);

      };*///cbTreeData

      // ---------------------------------------------------

      // Required argument check

      var getSetupConfiguration = function () {

         var setupConfig;

         var argumentsToCheck = [];

         argumentsToCheck.push(new Array(self.restAction,           'restAction'));
         argumentsToCheck.push( new Array(self.determineArguments,   'determineArguments'));
        // argumentsToCheck[2] = new Array(self.nodeClickHandler,     'nodeClickHandler');

         for (i = 0; i < argumentsToCheck.length; i++) {

            if (!argumentsToCheck[i][0]){

               setupConfig = {
                  root: new Ext.tree.AsyncTreeNode({
                     text: 'Error Creating Tree: <b>' + argumentsToCheck[i][1] + '</b> not defined',
                     leaf: true
                  })
               };

               Ext.MessageBox.alert('XDMoD.RESTTree', 'You must define <b>' + argumentsToCheck[i][1] + '</b> in your <b>XDMoD.RESTTree</b> constructor');

               // Make sure this property is set so the root node containing the error message is presented to the user
               self.rootVisible = true;

               return setupConfig;

            }

         }//for

         setupConfig = {

            loader: myTreeLoader,

            useArrows: true,
            autoScroll: true,
            animate: true,

            root: self.root || new Ext.tree.AsyncTreeNode()

         };

         return setupConfig;

      };//getSetupConfiguation

      // ---------------------------------------------------

      Ext.apply(this, getSetupConfiguration());

      // ---------------------------------------------------

      XDMoD.RESTTree.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.RESTTree
