XDMoD.Admin.ExceptionLister = Ext.extend(Ext.menu.Menu,  {

   initComponent: function(){

      var self = this;

      var myData = {};
      myData.records = [];

      for (var i = 0; i < self.exceptions.length; i++)
         myData.records.push({exception: self.exceptions[i]});

      var fields = [
         {name: 'exception', mapping : 'exception'}
      ];

      var gridStore = new Ext.data.JsonStore({
         fields : fields,
         data   : myData,
         root   : 'records'
      });

      // ==================================

      var exceptionRenderer = function(v) {

         return '<span style="color: #f00">' + v + '</span>';

      };//exceptionRenderer

      // ==================================

      var cols = [{
         id : 'exception',
         header: "Exception",
         width: 100,
         sortable: false,
         dataIndex: 'exception',
         renderer: exceptionRenderer
      }];

      // ==================================

      var grid = new Ext.grid.GridPanel({
         store            : gridStore,
         enableHdMenu     : false,
         columns          : cols,
         stripeRows       : true,
         autoExpandColumn : 'exception',
         enableColumnResize: false,
         width            : 532,
         height           : 315,
         layout           : 'fit'
      });

      // ==================================

      Ext.apply(this,
      {

         width: 540,
         height: 325,

         border: false,
         header: false,

         //layout: 'border',
         showSeparator: false,

         items: [
            grid
         ]

      });

      XDMoD.Admin.ExceptionLister.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.Admin.ExceptionLister
