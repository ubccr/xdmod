Ext.namespace('XDMoD');

XDMoD.ChartAnnotator = Ext.extend(Ext.Panel,  {

   initComponent: function(){

      Ext.apply(this, {

         title: 'Annotate Chart',
         layout: 'fit',
         width: 200,
         height: 200


         /*
         items:[ queueGrid ],

         tbar: {
            items: [
               {
                  xtype: 'button',
                  id: 'btn_annotate_chart',
                  iconCls: 'btn_annotate',
                  text: 'Annotate Selected Chart',
                  disabled: true,
                  handler: deleteSelectedCharts
               },
               '->',
               {
                  xtype: 'button',
                  id: 'btn_delete_chart_from_report',
                  iconCls: 'btn_delete',
                  text: 'Remove',
                  disabled: true,
                  handler: deleteSelectedCharts
               }
            ]
         }
         */

      });//Ext.apply

      //this.reportStore.reload();
      XDMoD.ChartAnnotator.superclass.initComponent.call(this);

   }

});//XDMoD.ChartAnnotator
