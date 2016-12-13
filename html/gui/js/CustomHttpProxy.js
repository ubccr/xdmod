/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2013-Apr-15
 *
 */
CCR.xdmod.CustomHttpProxy = function (conn) {
    CCR.xdmod.CustomHttpProxy.superclass.constructor.call(this, conn);
};


Ext.extend(CCR.xdmod.CustomHttpProxy, Ext.data.HttpProxy, {
    buildUrl: function (action, record) {
        record = record || null;
        // conn.url gets nullified after each request. If it's NOT null here, that means the user must have intervened with a call
        // to DataProxy#setUrl or DataProxy#setApi and changed it before the request was executed. If that's the case, use conn.url,
        // otherwise, build the url from the api or this.url.
        var url = (this.conn && this.conn.url) ? this.conn.url : (this.api[action]) ? this.api[action].url : this.url;
        if (!url) {
            throw new Ext.data.Api.Error('invalid-url', action);
        }
        // look for urls having "provides" suffix used in some MVC frameworks like Rails/Merb and others. The provides suffice informs
        // the server what data-format the client is dealing with and returns data in the same format (eg: application/json, application/xml, etc)
        // e.g.: /users.json, /users.xml, etc.
        // with restful routes, we need urls like:
        // PUT /users/1.json
        // DELETE /users/1.json
        var provides = null;
        var m = url.match(/(.*)(\.json|\.xml|\.html)$/);
        if (m) {
            provides = m[2]; // eg ".json"
            url = m[1]; // eg: "/users"
        }
        // prettyUrls is deprectated in favor of restful-config
        if ((this.restful === true || this.prettyUrls === true) && record instanceof Ext.data.Record && !record.phantom) {
            url += '&id=' + record.id;
        }
        return (provides === null) ? url : url + provides;
    }

});