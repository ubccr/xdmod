// --- A ComboBox with a secondary trigger button that clears the contents of the ComboBox

Ext.form.ClearableComboBox = Ext.extend(Ext.form.ComboBox, {
    initComponent: function () {
        Ext.form.ClearableComboBox.superclass.initComponent.call(this);

        this.triggerConfig = {
            tag: 'span',
            cls: 'x-form-twin-triggers',
            style: 'padding-right:2px',  // padding needed to prevent IE from clipping 2nd trigger button
            cn: [
                { tag: 'img', src: Ext.BLANK_IMAGE_URL, cls: 'x-form-trigger' },
                { tag: 'img', src: Ext.BLANK_IMAGE_URL, cls: 'x-form-trigger x-form-clear-trigger' }
            ]
        };
    },

    getTrigger: function (index) {
        return this.triggers[index];
    },

    initTrigger: function () {
        var ts = this.trigger.select('.x-form-trigger', true);
        this.wrap.setStyle('overflow', 'hidden');
        var self = this;
        ts.each(function (t, all, index) {
            t.hide = function () {
                var w = self.wrap.getWidth();
                this.dom.style.display = 'none';
                self.el.setWidth(w - self.trigger.getWidth());
            };
            t.show = function () {
                var w = self.wrap.getWidth();
                this.dom.style.display = '';
                self.el.setWidth(w - self.trigger.getWidth());
            };
            var triggerIndex = 'Trigger' + (index + 1);

            if (this['hide' + triggerIndex]) {
                t.dom.style.display = 'none';
            }
            t.on('click', this['on' + triggerIndex + 'Click'], this, { preventDefault: true });
            t.addClassOnOver('x-form-trigger-over');
            t.addClassOnClick('x-form-trigger-click');
        }, this);
        this.triggers = ts.elements;
    },

    onTrigger1Click: function () {
        this.onTriggerClick();
    }, // pass to original combobox trigger handler
    onTrigger2Click: function () {
        this.reset();
        this.fireEvent('reset');
    } // clear contents of combobox
});
