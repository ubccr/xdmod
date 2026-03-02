
Ext.namespace('XDMoD.Dashboard');

XDMoD.Dashboard.Factory = Ext.extend(Ext.util.Observable, {
    constructor: function (config) {
       this.menuStore = new XDMoD.Dashboard.MenuStore();

       XDMoD.Dashboard.Factory.superclass.constructor.call(this, config);
    },

    load: function (callback) {
        this.menuStore.load({
            callback: function () {
                callback(this.menuStore.getMenuItems());
            },
            scope: this
        });
    }
});

