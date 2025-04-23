/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * This class is an extension of split button with a menu
 * remains visible until user clicks somewhere else on the page
 *
 */
CCR.xdmod.ui.CustomSplitButton = Ext.extend(Ext.Button, {

    // private
    initComponent: function () {
        CCR.xdmod.ui.CustomSplitButton.superclass.initComponent.call(this);

        this.menuAlign = 'tl-bl?';
        Ext.menu.MenuMgr.unregister(this.menu);

        Ext.getDoc().on("mousedown", function (e) {

            if (this.menu && this.menu.isVisible()) {
                var menuBox = this.menu.getBox();

                var ex = e.getPageX();
                var ey = e.getPageY();

                if ((ex > menuBox.x + menuBox.width || ex < menuBox.x ||
                    ey > menuBox.y + menuBox.height || ey < menuBox.y)) {
                    this.menu.hide();
                }
            }
        }, this);
    }
});

Ext.reg('customsplitbutton', CCR.xdmod.ui.CustomSplitButton);