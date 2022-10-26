/**
 * A set of convenience functions and objects to help use the REST API.
 *
 * This should be loaded after REST configuration variables have been set.
 */

Ext.namespace('XDMoD.REST');

/**
 * Download a file using a REST request.
 *
 * @param  {object} options A set of options, including:
 *         {string} url The path to download from.
 *                      (REST URL components will be added automatically.)
 *         {string} method (Optional) The request method to use.
 *                         (Defaults to GET.)
 *         {object} params (Optional) A set of parameters to send with the request.
 *         {boolean} checkDashboardUser (Optional) Check the dashboard user session
 *                                      instead of the main user session before
 *                                      downloading. (Defaults to false.)
 */
XDMoD.REST.download = function (options) {

    // Check for mandatory options.
    if (!Ext.isDefined(options)) {
        throw "Options must be specified.";
    }
    if (!Ext.isDefined(options.url)) {
        throw "URL must be specified.";
    }
    var url = XDMoD.REST.prependPathBase(options.url);

    // Check for optional options.
    var method = Ext.isDefined(options.method) ? options.method : 'GET';
    var params = Ext.isDefined(options.params) ? options.params : {};

    // Submit a hidden form to launch the download request.
    CCR.submitHiddenForm(url, method, params, options);
};

/**
 * Prepend the REST API's path to the given path component.
 *
 * @param  {string} path A path to prepend with the REST API's path.
 * @return {string}      A full path to a REST API function.
 */
XDMoD.REST.prependPathBase = function (path) {
    var pathBaseSeparator = path.startsWith('/') ? '' : '/';
    return XDMoD.REST.url + pathBaseSeparator + path;
};

/**
 * Prepend the REST API's path onto each URL in an HttpProxy's API definition.
 *
 * NOTE: This function modifies the given object and returns it for
 *       convenience purposes. It does not return a new object.
 *
 * @param  {object} api An HttpProxy API definition.
 * @return {object} The given API definition with modified URLs.
 */
XDMoD.REST.prependPathBaseOnApi = function (api) {
    for (var apiActionKey in api) {
        if (!api.hasOwnProperty(apiActionKey)) {
            continue;
        }

        var apiAction = api[apiActionKey];
        if (Ext.isString(apiAction)) {
            api[apiActionKey] = XDMoD.REST.prependPathBase(apiAction);
        } else if (Ext.isObject(apiAction)) {
            if (apiAction.url) {
                apiAction.url = XDMoD.REST.prependPathBase(apiAction.url);
            }
        }
    }
    return api;
};

/**
 * Remove parameters that are considered empty from a set of parameters.
 *
 * A parameter is considered empty if its value is:
 *     * undefined
 *     * null
 *     * an empty string
 *     * an empty array
 *
 * NOTE: This function modifies the given object and returns it for
 *       convenience purposes. It does not return a new object.
 *
 * @param  {object} parameters A mapping of parameter keys to values.
 * @return {object}            The given parameter set, modified.
 */
XDMoD.REST.removeEmptyParameters = function (parameters) {

    // Scan the object for parameters with empty values.
    var emptyParameterKeys = [];
    Ext.iterate(parameters, function (parameterKey, parameterValue) {
        if (Ext.isEmpty(parameterValue)) {
            emptyParameterKeys.push(parameterKey);
        }
    });

    // Remove the parameters with empty values.
    Ext.each(emptyParameterKeys, function (emptyParameterKey) {
        delete parameters[emptyParameterKey];
    });

    // Return the given, modified parameters.
    return parameters;
};

/**
 * A factory function that creates an instance of an Ext.data.Connection object
 * set up to handle common aspects of REST calls automatically.
 *
 * The URL used for a request is automatically prefixed with the base
 * URL component used for all REST calls.
 *
 * @param {Object} config (Optional) A configuration object to pass to
 *                        Ext.data.Connection's constructor.
 * @return {Ext.data.Connection} A REST API-using connection.
 */
XDMoD.REST.createConnection = function (config) {
    var restConnection = new Ext.data.Connection(config);
    restConnection.on('beforerequest', function (conn, options) {
        options.url = XDMoD.REST.prependPathBase(options.url);
    });

    return restConnection;
};

/**
 * A convenience singleton created by XDMoD.REST.createConnection.
 *
 * @type {Ext.data.Connection}
 */
XDMoD.REST.connection = XDMoD.REST.createConnection();

/**
 * A factory function that creates an instance of Ext.data.HttpProxy
 * that handles certain aspects of using the REST API automatically.
 *
 * The API or URL is prefixed with the base URL component used for all REST
 * calls. (Subsequent updates are not automatically taken care of - use the
 * convenience functions when changing the proxy's url or API)
 *
 * @param {Object} config (Optional) A configuration object to pass to
 *                        Ext.data.HttpProxy's constructor.
 * @return {Ext.data.HttpProxy} A REST API-using HTTP proxy.
 */
XDMoD.REST.createHttpProxy = function (config) {
    config = typeof config === "undefined" ? {} : config;

    if (config.api) {
        XDMoD.REST.prependPathBaseOnApi(config.api);
    }
    if (config.url) {
        config.url = XDMoD.REST.prependPathBase(config.url);
    }

    var restHttpProxy = new Ext.data.HttpProxy(config);

    return restHttpProxy;
};

/**
 * A subclass of Ext.tree.TreeLoader that uses the XDMoD REST API connection
 * object by default for performing loading operations.
 */
XDMoD.REST.TreeLoader = Ext.extend(Ext.tree.TreeLoader, {

    /**
     * Abort request for node data.
     *
     * Code modified from source (3.4.0) for Ext.tree.TreeLoader.abort.
     *
     * @see Ext.tree.TreeLoader.abort
     */
    abort : function(){
        if(this.isLoading()){
            XDMoD.REST.connection.abort(this.transId);
        }
    },

    /**
     * Request node data using an XDMoD REST API connection (if no request
     * function is provided).
     *
     * Code modified from source (3.4.0) for Ext.tree.TreeLoader.requestData.
     * Based on implementation from: html/gui/js/RESTTree.js
     *
     * @see Ext.tree.TreeLoader.requestData
     */
    requestData : function(node, callback, scope){
        if(this.fireEvent("beforeload", this, node, callback) !== false){
            if(this.directFn){
                var args = this.getParams(node);
                args.push(this.processDirectResponse.createDelegate(this, [{callback: callback, node: node, scope: scope}], true));
                this.directFn.apply(window, args);
            }else{
                this.transId = XDMoD.REST.connection.request({
                    method:this.requestMethod,
                    url: this.dataUrl||this.url,
                    success: this.handleResponse,
                    failure: this.handleFailure,
                    scope: this,
                    argument: {callback: callback, node: node, scope: scope},
                    params: this.getParams(node)
                });
            }
        }else{
            // if the load is cancelled, make sure we notify
            // the node that we are done
            this.runCallback(callback, scope || node, []);
        }
    },

    /**
     * Create a listener to handle a successful load of a node's data.
     *
     * @param  {Function} transformFunction (Optional) A function that converts
     *                                      a decoded response into node data.
     *
     *                                      If not provided, the results property
     *                                      of the decoded response will be
     *                                      used directly.
     *
     *                                      Arguments:
     *                                          * {Object} data The decoded
     *                                            contents of the response.
     *                                          * {Ext.tree.TreeNode} node
     *                                            The node being loaded.
     *                                      Returns:
     *                                          {mixed} A value to send to
     *                                          the node's appendChild method.
     * @return {Function}                   A listener function that will append
     *                                      received data onto the loaded node.
     */
    createStandardLoadListener: function (transformFunction) {

        // If a transform function was not given, use a function that returns
        // the decoded response's results property.
        if (!Ext.isFunction(transformFunction)) {
            transformFunction = function (data) {
                return data.results;
            };
        }

        return function (loader, node, response) {
            var data = CCR.safelyDecodeJSONResponse(response);
            var success = CCR.checkDecodedJSONResponseSuccess(data);

            if (!success) {
                loader.fireEvent('loadexception', loader, node, response);
                return;
            }

            node.appendChild(transformFunction.call(this, data, node));
        };
    }
});
