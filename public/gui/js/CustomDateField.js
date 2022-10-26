/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @author Ryan Gentner
 * @date 2011-Feb-07
 *
 * This class is an extension of date field with a date menu that
 * remains visible until user clicks somewhere else on the page
 *
 */
CCR.xdmod.ui.CustomDateField = Ext.extend(Ext.form.DateField, {

    initComponent: function () {

        var self = this;
        var previousValue = self.value.format('Y-m-d');

        // ---------------------------------------

        self.didChange = function () {

            return (previousValue != self.getRawValue());

        };

        // ---------------------------------------

        self.updatePreviousValue = function (v) {

            previousValue = self.getRawValue();

        };

        // ---------------------------------------

        CCR.xdmod.ui.CustomDateField.superclass.initComponent.call(this);

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

    } //initComponent

}); //CCR.xdmod.ui.CustomDateField

Ext.reg('customdatefield', CCR.xdmod.ui.CustomDateField);