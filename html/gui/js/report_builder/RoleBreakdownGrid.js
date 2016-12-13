XDMoD.Reporting.RoleBreakdownGrid = Ext.extend(Ext.menu.Menu,  {

   initComponent: function(){

      var self = this;

      switch (this.type) {

         case 'available_charts':

            column_2_header = '# Charts';
            column_2_reference = 'num_charts';

            break;

         case 'reports':

            column_2_header = '# Reports';
            column_2_reference = 'num_reports';

            break;

      }//switch (this.type)

      // ==================================

      var myData = {};
      myData.records = [];

      for (var i = 0; i < self.breakdown.length; i++) {
         myData.records.push({
            role: self.breakdown[i].role,
            num_entity: self.breakdown[i][column_2_reference]
         });
      }

      var gridStore = new Ext.data.JsonStore({
         fields : ['role', 'num_entity'],
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
            id: 'num_entity',
            header: column_2_header,
            width: 70,
            sortable: false,
            dataIndex: 'num_entity'
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
         height           : 195,
         layout           : 'fit',

         viewConfig: {
            rowOverCls: '',
            selectedRowClass: ''
         }

      });//grid

      // ==================================

      Ext.apply(this,
      {

         width: 390,
         height: 205,

         border: false,
         header: false,

         //layout: 'border',
         showSeparator: false,

         items: [
            grid
         ]

      });

      XDMoD.Reporting.RoleBreakdownGrid.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.Reporting.RoleBreakdownGrid
