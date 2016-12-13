Ext.onReady(function(){

   var documentationPanel = {

      id: 'documentation-panel',
      title: 'Documentation For ..',
      region: 'center',
      bodyStyle: 'padding-bottom:15px;background:#fff;',
      autoScroll: true,
      html: ''

   };//documentationPanel

   // ----------------------------------

   var contentPanel = {

      region: 'center',
      id: 'content-panel',
      layout: 'card',
      margins: '2 5 5 0',
      activeItem: 0,
      border: false,
      items: [

         {
            title: 'Welcome',
            html: Ext.getDom('splash_section').innerHTML
         },

         documentationPanel

      ]

   };//contentPanel

   // ----------------------------------

   var treeTb = new Ext.Toolbar({

      items: [

         ' ',

         new Ext.form.TextField({

            width: 150,
            emptyText:'Find an action',
            enableKeyEvents: true,

            listeners:{

               render: function(f){

                  tree.filter = new Ext.tree.TreeFilter(tree, {
                     clearBlank: true,
                     autoClear: true
                  });

               },

               keydown: {
                  fn: filterTree,
                  buffer: 100,
                  scope: this
               },

               scope: this

            }

         }),

         ' ', ' ',

         {
            iconCls: 'icon-expand-all',
            tooltip: 'Expand All',
            handler: function(){ tree.root.expand(true); },
            scope: this
         },

         '-',

         {
            iconCls: 'icon-collapse-all',
            tooltip: 'Collapse All',
            handler: function(){ tree.root.collapse(true); },
            scope: this
         }

      ]//items

   });//treeTb

   // ----------------------------------

   function filterTree(t, e){

      var text = t.getValue();

      if(!text) {
         tree.filter.clear();
         return;
      }

      tree.expandAll();

      var re = new RegExp('.*' + Ext.escapeRe(text) + '.*', 'i');

      tree.filter.filterBy(function(n){

         // Only filter tree leaves, and among those leaves, hide
         // the nodes which do not comply with the regular expression...

         return (!n.attributes.isLeaf || re.test(n.text));

      });

      /*
      // hide empty items that weren't filtered
      this.hiddenCharts = [];
      var me = this;
      tree.root.cascade(function(n){
         if(!n.attributes.filter &&  n.ui.ctNode.offsetHeight < 3 ){
            n.ui.hide();
            me.hiddenCharts.push(n);
         }
      });
      */

   }//filterTree

   // ----------------------------------

   var tree = new Ext.tree.TreePanel({

      id: 'rest_catalog_tree',

      useArrows: true,
      autoScroll: true,
      animate: true,
      enableDD: false,

      // auto create TreeLoader
      dataUrl: 'controllers/catalog.php',

      root: {
         nodeType: 'async',
         text: this.title,
         draggable: false,
         //id: 'node_'+this.id,
         filter: false
      },

      rootVisible: false,
      tbar: treeTb,

      region: 'west',
      split: true,
      margins: '2 0 2 2',
      containerScroll: true,
      border: true

   });//tree

   tree.addListener('load', attemptToNavigate);

   // ----------------------------------

   tree.getSelectionModel().on('selectionchange', function(model, n){

      // var l = contentPanel.getLayout());
      var l = Ext.getCmp('content-panel').getLayout();

      if(n.leaf && n.id.split('_').length == 3) {

         Ext.Ajax.request({

            url: 'controllers/get_documentation.php',
            method: 'GET',
            params: { 'action_id' : n.id },

            success: function(response) {

               //Update the content panel with the HTML returned from the controller
               var bd = Ext.getCmp('documentation-panel').body;

               bd.update(response.responseText);
               l.setActiveItem(1);

            },

            failure: function () { alert('There was a problem retrieving documentation.'); }

         });

      }
      else {

         l.setActiveItem(0);

      }

   });//on 'selectionchange'

   // ----------------------------------

   var viewport = new Ext.Viewport({

      layout: 'border',

      items: [

         // Header bar ---
         new Ext.BoxComponent({
            region: 'north',
            height: 40,
            autoEl: {
               tag: 'div',
               html:'<img src="images/rest_api_main_banner.png">'
            }
         }),

         {
            region: 'west',
            id: 'west-panel',
            title: 'Catalog',
            layout: 'fit',
            split: true,
            width: 250,
            minSize: 175,
            maxSize: 400,
            collapsible: true,
            margins: '0 0 0 5',
            items: [ tree ]
         },

         contentPanel

      ]

   });//viewport

});//Ext.onReady
