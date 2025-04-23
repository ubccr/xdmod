/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-Jun-14
 *
 */
CCR.xdmod.ui.CustomTwinTriggerField = Ext.extend(Ext.form.TwinTriggerField, {
    // private
    initComponent: function () {
        var config = {
            validationEvent: false,
            validateOnBlur: false,
            trigger1Class: 'x-form-clear-trigger',
            trigger2Class: 'x-form-search-trigger',
            hideTrigger1: true,
            hasSearch: false,
            enableKeyEvents: true,
            listeners: {
                'specialkey': function (field, e) {
                    if (e.getKey() == e.ENTER) {
                        this.onTrigger2Click();
                    }
                }
            },
            onTrigger1Click: function () {
                if (this.hasSearch) {
                    this.el.dom.value = '';
                    this.store.baseParams.search_text = '';
                    this.store.load();
                    this.triggers[0].hide();
                    this.hasSearch = false;
                }
            },
            onTrigger2Click: function () {
                var v = this.getRawValue();
                if (v.length < 1) {
                    this.onTrigger1Click();
                    return;
                }
                this.store.baseParams.search_text = v;
                this.store.load();
                this.hasSearch = true;
                this.triggers[0].show();
            }
        };

        Ext.apply(this, config);
        Ext.apply(this.initialConfig, config);
        CCR.xdmod.ui.CustomTwinTriggerField.superclass.initComponent.call(this);
    }
});