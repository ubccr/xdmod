XDMoD.Reporting.ChartRoleBreakdownGrid = Ext.extend(Ext.menu.Menu,  {

   initComponent: function(){

      var self = this;

      var myData = {};
      myData.records = [];

      for (var i = 0; i < self.breakdown.length; i++) {
         myData.records.push({
            role: self.breakdown[i].role,
            num_charts: self.breakdown[i].num_charts
         });
      }

      var gridStore = new Ext.data.JsonStore({
         fields : ['role', 'num_charts'],
         data   : myData,
         root   : 'records'
      });

      // ==================================

      var cols = [
         {
            id: 'role',
            header: 'Role',
            width: 80,
            sortable: false,
            dataIndex: 'role'
         },
         {
            id: 'num_charts',
            header: '# Charts',
            width: 70,
            sortable: false,
            dataIndex: 'num_charts'
         }
      ];

      // ==================================

      var grid = new Ext.grid.GridPanel ({

         store            : gridStore,
         enableHdMenu     : false,
         columns          : cols,
         stripeRows       : true,
         autoExpandColumn : 'role',
         enableColumnResize: false,
         width            : 382,
         height           : 215,
         layout           : 'fit'

      });

      // ==================================

      Ext.apply(this,
      {

         width: 390,
         height: 225,

         border: false,
         header: false,

         //layout: 'border',
         showSeparator: false,

         items: [
            grid
         ]

      });

      XDMoD.Reporting.ChartRoleBreakdownGrid.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.Reporting.ChartRoleBreakdownGrid
