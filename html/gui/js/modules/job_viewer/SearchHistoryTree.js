 Ext.ns('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

 /**
  * This component provides the base class for the SearchHistoryPanel. The reason
  * for this particular base class is that the original CCR / XDMoD RESTTree
  * implementation did wierd things with the arguments:
  * ( url/key=value/key=value/etc. ). This class provides the same functionality
  * but treats the query parameters as well... query parameters.
  */
XDMoD.Module.JobViewer.SearchHistoryTree = Ext.extend(Ext.tree.TreePanel, {

    _DEFAULT_CONFIG: {
        useArrows: true,
        autoScroll: true,
        animate: true,
        method: 'GET',
        loader: {
            dataUrl: 'rest_override'
        },
        bubbleEvents: [
            'load',
            'data_loaded'
        ]
    },

    initComponent: function () {
        // CHECK: for all of the required properties / functions before continuing.
        if (!CCR.exists(this.url) || typeof this.url !== 'string') throw {code: 400, msg: 'Invalid object setup. [url]'};
        if (!CCR.exists(this.getParams) || typeof this.getParams !== 'function') throw {code: 400, msg: 'Invalid object setup. [params]'};
        if (!CCR.exists(this.handleTreeData) || typeof this.handleTreeData !== 'function') throw {code: 400, msg: 'Invalid object setup. [tree_data]'};
        if (!CCR.exists(this.formatParams) || typeof this.formatParams !== 'function') throw {code: 400, msg: 'Invalid object setup. [format_params]'};

        // INIT: our tree loader if one has not already been provided.
        this.loader = this.loader || new Ext.tree.TreeLoader();

        // APPLY: our default configuration.
        Ext.apply(this, this._DEFAULT_CONFIG);

        // OVERRIDE: our loaders requestData function with our own.
        this.loader.requestData = this._loaderRequestData;
        this.loader.parent = this;

        // FINISH: the component creation by calling our superclass' initComponent.
        XDMoD.Module.JobViewer.SearchHistoryTree.superclass.initComponent.apply(this, arguments);
    },

    /**
     * Function to be utilized by {XDMoD.Module.JobViewer.SearchHistoryTree} when handling a successful response.
     *
     * @param {Object} data JSON.parsed response.responseText
     * @param {Object} options an object that should contain at least property 'node': [node that was clicked]
     */
    handleTreeData: function (data, options) {
        var hasData = CCR.exists(data);
        var success = hasData && CCR.exists(data.success) && data.success;
        var rawNodes = CCR.exists(data.results) && CCR.exists(data.results.results) && CCR.isType(data.results.results, CCR.Types.Array)
                ? data.results.results
                : CCR.exists(data.results) && !CCR.exists(data.results.results) && CCR.isType(data.results, CCR.Types.Array)
                ? data.results
                : null;


        if (success) {
            var nodes = this._processNodes(rawNodes);
            var node = CCR.exists(options) && CCR.exists(options.node) ? options.node : null;

            node.appendChild(nodes);

            this.fireEvent('data_loaded', data);
        } else {
            var defaultMessage = 'There was a problem processing the Search History Results. ';
            var message = hasData && CCR.exists(data.message) ? defaultMessage + data.message : defaultMessage;
            Ext.MessageBox.alert('Search History', message);
        }
    }, // handleTreeData

    /**
     * Function that's responsible for processing the nodes as they come in and executing any modifications as necessary.
     * In this case it ensures that each node has a valid 'text' property.
     *
     * @param {Array} nodes
     * @returns {Array} of nodes
     * @private
     */
    _processNodes: function (nodes) {
        if (CCR.exists(nodes)) {
            for (var i = 0; i < nodes.length; i++) {
                var node = nodes[i];
                if (!CCR.exists(node.text)) {
                    var dtype = node.dtype;
                    var hasDtype = CCR.exists(dtype);
                    var hasLocalJobId = CCR.exists(node.local_job_id);

                    var includesJob = hasDtype && dtype.indexOf('job') >= 0;
                    var includesId = hasDtype && dtype.indexOf('id') >= 0;
                    var includesLocal = hasDtype && dtype.indexOf('local') >= 0;

                    if (hasDtype && includesJob && includesId && !includesLocal && hasLocalJobId) {
                        node['text'] = node['local_job_id'];
                    } else {
                        node['text'] = node[dtype];
                    }
                }
            }
        }
        return nodes;
    },// _processNodes

    /**
     * Returns the parameters in query parameter format.
     * i.e. ?k1=v1&k2=v2...kn=vn
     *
     * @param {Array} params
     * @return {string} in the form '?kn=vn
     */
    formatParams: function(params) {
        return "?" + params.join("&");
    }, // formatParams

    /**
     * A helper glue function that makes the whole respecting query params
     * things work.
     *
     * @param {Ext.tree.AsyncTreeNode} node
     * @param {function} callback
     * @private
     */
    _loaderRequestData: function (node, callback) {
        var tree = node.ownerTree;
        var loader = tree.loader;

        if (loader.fireEvent('beforeload', loader, node, callback) !== false) {
            loader.transId = tree._request.call(tree, node, callback, loader);
        } else {
            loader.runCallback(callback, loader || node, []);
        }
    }, // _loaderRequestData

    /**
     * Helper function that is meant to be used as a default success callback
     * for an Ext.Ajax.request.
     *
     * @param response
     * @param node
     * @private
     */
    _success: function (response, node) {
        if (CCR.exists(response) && CCR.exists(response.responseText)) {
            var data = JSON.parse(response.responseText);
            var success = CCR.exists(data) && CCR.exists(data.success) && data.success;
            if (success) {
                this.handleTreeData(data, {node: node});
            }
        }
    }, // _success

    /**
     * Helper function that is meant to be used as a default failure callback
     * for an Ext.Ajax.request.
     *
     * @param response
     * @param callback
     * @param callbackArgs
     * @private
     */
    _failure: function (response, callback, callbackArgs) {
        if (response) {

        }
    }, // _failure

    /**
     * Helper method that enables the tree to interact with a normal REST
     * backend. It also provides our XDMoD.REST.token as an additional query
     * param.
     *
     * @param node
     * @param callback
     * @param scope
     * @returns {*}
     * @private
     */
    _request: function (node, callback, scope) {
        var self = this;

        if (node.attributes.dtype) {
            var url = node.ownerTree.url + '?' + CCR.encode(self.getParams(node)) + '&token=' + XDMoD.REST.token;
        } else {
            var url = node.ownerTree.url + '?token=' + XDMoD.REST.token;
        }

        return Ext.Ajax.request({
            url: url,
            method: self.method,
            success: function (response) {
                self._success.call(self, response, node);
                response.argument = {node: node, callback: callback, scope: scope};
                scope.handleResponse.call(scope, response);
            },
            failure: function (response) {
                self._failure.call(self, response);
                response.argument =  {node: node, callback: callback, scope: scope};
                scope.handleFailure.call(scope, response);
            }
        })
    } // _request
});
