var chartDropNotification = new Ext.menu.Menu({
   items: [{text: 'Drop Here', iconCls: 'down_arrow'}]
});
         
// ===========================================================

var resetDropStyling = function() {

   var n = Ext.get('main_tab_panel__report_tab_panel');
   n.removeClass('highlighted_tab');
       
   chartDropNotification.hide();
   
};

function rds(evt) {
   alert('done');
}

// ===========================================================

var chartDroppedAtTarget = function(dd, e, data){
             
   resetDropStyling();
      
   return true;
               
};//chartDroppedAtTarget   
 
// ===========================================================
              
ImageDragZone = function(view, config){

    this.view = view;
    ImageDragZone.superclass.constructor.call(this, view.getEl(), config);
    
};//ImageDragZone

// ===========================================================

Ext.extend(ImageDragZone, Ext.dd.DragZone, {
    
    getDragData : function(e){
 
      // ----------------------------------
      
      //todo: Bounds enforcement for dragging charts onto the 'Report Generator' tab
             
      //var v = CCR.xdmod.ui.Viewer.getViewer();
      
      //this.constrainTo("xdmod_viewer");
      //this.setXConstraint(0, 1000);
      //this.setYConstraint(0, 800);
      
      // ----------------------------------
      
      // Ignore any invocations of getDragData triggered by a right-click
      if (e.button == 2) return false;
      
      // We only care about the single chart at the moment
      CCR.xdmod.ui.dd.target = e.getTarget('.single-chart-container');
      
      if(CCR.xdmod.ui.dd.target){
         
         var view = this.view;
            
         if(!view.isSelected(CCR.xdmod.ui.dd.target)){
            view.onClick(e);
         }
            
         var selNodes = view.getSelectedNodes();

         var dragData = {
            nodes: selNodes
         };
            
         if(selNodes.length == 1){
                  
            var n = Ext.get('main_tab_panel__report_tab_panel');
            n.addClass('highlighted_tab');
                        
            chartDropNotification.show(n, 'bl-tl');
            
            var div = document.createElement('div');
               
            var dragThumb = CCR.xdmod.ui.dd.target.firstChild.cloneNode(true);
            dragThumb.width = 200;
            dragThumb.height = 100;
                  
            div.appendChild(dragThumb);
            div.appendChild(document.createElement('br'));
               
            var caption = document.createElement('div'); 
            caption.innerHTML = 'Drag to the <b>Report Generator</b> tab to use<br>this chart during report building.';

            div.appendChild(caption);

            dragData.ddel = div;
                
            dragData.single = true;
                
         }//if(selNodes.length == 1)
            
         return dragData;

      }//if(CCR.xdmod.ui.dd.target)
        
      return false;
        
    },
    
    // generate 'highlighting' effect (on bad drop)
    afterRepair:function(){
    
      resetDropStyling();
               
      for(var i = 0, len = this.dragData.nodes.length; i < len; i++)
         Ext.fly(this.dragData.nodes[i]).frame('#8db2e3', 1);

      this.dragging = false;    

    },
    
    // inhibit the 'drag backtracking' animation
    getRepairXY : function(e){
    
      return false;

    }
    
});//Ext.extend(ImageDragZone, ...)
