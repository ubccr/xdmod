/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-4-27
 *
 * 
 *
 */
CCR.xdmod.ui.CustomMenu = function (config) {
    CCR.xdmod.ui.CustomMenu.superclass.constructor.call(this, config);
};

Ext.extend(CCR.xdmod.ui.CustomMenu, Ext.menu.Menu, {
    initComponent: function () {
		this.keepOnClick = this.keepOnClick === undefined || this.keepOnClick === true;
		if( this.keepOnClick ) {
			Ext.apply(this, {
	
				listeners: {
					beforehide : function (t) {
						if (t.el) {
							var menuBox = t.getBox();
	
							var ex = Ext.EventObject.getPageX();
							var ey = Ext.EventObject.getPageY();
	
							if (this.temporaryInvisible) {
								this.temporaryInvisible = false;
								return true;
							}
							return (ex > menuBox.x + menuBox.width || ex < menuBox.x ||
								ey > menuBox.y + menuBox.height || ey < menuBox.y);
						}
						return true;
					}
				}
			});
		}
        CCR.xdmod.ui.CustomMenu.superclass.initComponent.apply(this, arguments);
    }
});
