/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-Feb-1
 *
 * This class is an extension of json store
 *
 */
CCR.xdmod.CustomJsonStore = Ext.extend(Ext.data.JsonStore, {
    constructor: function (config) {

        CCR.xdmod.CustomJsonStore.superclass.constructor.call(this, config);
    }
});
Ext.reg('xdmodstore', CCR.xdmod.CustomJsonStore);
