/*
   CollapsedPanelTitlePlugin

   Allows for titles to appear on collapsed Ext.panels
*/


Ext.ux.collapsedPanelTitlePlugin = function (overrideTitle) {

   this.renderVerticalText = function(t) {

      var markup = '<div><svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="15" height="792" style="width: 15px; height: 792px;">' +
                   '<defs/>' +
                   '<rect width="100%" height="100%" fill="#000" stroke="none" opacity="0" />' +
                   '<text zIndex="0" text="" font-family="tahoma,arial,verdana,sans-serif" font-weight="bold" ' +
                   'font-size="11px" fill="rgb(51, 51, 51)" x="0" y="0" text-anchor="start" transform="matrix(0, 1, -1, 0, 6.75, 1)">' +
                   '<tspan x="5" dy="1">' + t + '</tspan></text>' +
                   '</svg></div>';

      return markup;

   },//renderVerticalText

   // ---------------------------------------

   this.renderHorizontalText = function(t) {

      return '<div style="font-family: tahoma,arial,verdana,sans-serif; font-weight: bold; font-size: 11px; padding: 2px 0 0 5px">' + t + '</div>';

   },//renderHorizontalText

   // ---------------------------------------

   this.renderText = function(p, t) {

      var active_title = t.replace('<h1>', '').replace('</h1>', '');

      if ((p.region == 'north') || (p.region == 'south'))
         return this.renderHorizontalText(active_title);

      if ((p.region == 'east') || (p.region == 'west'))
         return this.renderVerticalText(active_title);

   },//renderText

   // ---------------------------------------

   this.initTextLabel = function(ct, r, p) {

      var self = this;

      p.collapsedTitleEl = ct.layout[r].collapsedEl.createChild ({
         tag: 'div',
         cls: 'x-panel-collapsed-text',
         html: self.renderText(p, p.title)
      });

      p.setTitle = Ext.Panel.prototype.setTitle.createSequence (function(t){
         p.collapsedTitleEl.dom.innerHTML = self.renderText(p, t);
      });

   },//initTextLabel

   // ---------------------------------------

   this.init = function(p) {

      var self = this;

      if (p.title == undefined) p.title = '';

      if (p.collapsible) {

         var r = p.region;

         p.on('render', function() {

            var ct = p.ownerCt;

            ct.on ('afterlayout', function() {

               if (ct.layout[r].collapsedEl)
                  self.initTextLabel(ct, r, p);

            }, false, {single:true});

            p.on ('collapse', function() {

               if (ct.layout[r].collapsedEl && !p.collapsedTitleEl)
                  self.initTextLabel(ct, r, p);

            }, false, {single:true});

         });//p.on('render', ...)

      }//if (p.collapsible)

   };//init

};//Ext.ux.collapsedPanelTitlePlugin
