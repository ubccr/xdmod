/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * This is a check item that also unchecks when disabled then re-checks when re-enabled
 *
 */
CCR.xdmod.ui.CustomCheckItem = Ext.extend(Ext.menu.CheckItem, {
    wasChecked: false,
    // private
    initComponent: function () {
        CCR.xdmod.ui.CustomCheckItem.superclass.initComponent.call(this);
        wasChecked = null;
    },
    setChecked: function (checked, supress) {
        CCR.xdmod.ui.CustomCheckItem.superclass.setChecked.call(this, checked, supress);
    },
    setDisabled: function (disabled) {
        if (disabled == true) {
            this.wasChecked = this.checked;
            this.setChecked(false, true);
            CCR.xdmod.ui.CustomCheckItem.superclass.setDisabled.apply(this, arguments);
        } else {
            if (this.wasChecked != null) {
                this.setChecked(this.wasChecked, true);
                this.wasChecked = null;
            }
            CCR.xdmod.ui.CustomCheckItem.superclass.setDisabled.apply(this, arguments);
        }
    }
});


Ext.reg('customcheckitem', CCR.xdmod.ui.CustomCheckItem);
