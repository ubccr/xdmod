// JavaScript Document
/*
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Apr-15
*
* This is the extension of Ext.data.HttpProxy for making calls to the xdmod rest API.
* It can be assigned as a data store's proxy.
   var chartStore = new Ext.data.JsonStore(
        {
            storeId: 'Performance',
            autoDestroy: false,
            root: 'results',
            totalProperty: 'num',
            successProperty: 'success',
            fields: this.chartDataFields,
            proxy: new CCR.xdmod.RESTDataProxy (
            {
                url: 'rest/appkernel/explorer/plot'
            })
        });
    chartStore.load({params: [resource: 1,....]});
*
*/
CCR.xdmod.RESTDataProxy = function (conn) {
    CCR.xdmod.RESTDataProxy.superclass.constructor.call(this, conn);
};


Ext.extend(CCR.xdmod.RESTDataProxy, Ext.data.HttpProxy, {
    doRequest: function (action, rs, params, reader, cb, scope, arg) {
        var o = {
            method: (this.api[action]) ? this.api[action].method : undefined,
            timeout: 60000, // 1 Minute,
            request: {
                callback: cb,
                scope: scope,
                arg: arg
            },
            reader: reader,
            callback: this.createCallback(action, rs),
            scope: this
        };

        // If possible, transmit data using jsonData || xmlData on Ext.Ajax.request (An installed DataWriter would have written it there.).
        // Use std HTTP params otherwise.
        if (params.jsonData) {
            o.jsonData = params.jsonData;
        } else if (params.xmlData) {
            o.xmlData = params.xmlData;
        } else {
            o.params = params || {};
        }

        var restArgumentString = '';

        for (var argName in params)
            restArgumentString += '/' + argName + '=' + params[argName];

        // Set the connection url.  If this.conn.url is not null here,
        // the user must have overridden the url during a beforewrite/beforeload event-handler.
        // this.conn.url is nullified after each request.
        this.conn.url = this.url + restArgumentString + '?token=' + XDMoD.REST.token;

        Ext.applyIf(o, this.conn);

        // If a currently running request is found for this action, abort it.
        if (this.activeRequest[action]) {
            ////
            // Disabled aborting activeRequest while implementing REST.  activeRequest[action] will have to become an array
            // TODO ideas anyone?
            //
            //Ext.Ajax.abort(this.activeRequest[action]);
        }
        this.activeRequest[action] = Ext.Ajax.request(o);

        // request is sent, nullify the connection url in preparation for the next request
        this.conn.url = null;
    }

});
