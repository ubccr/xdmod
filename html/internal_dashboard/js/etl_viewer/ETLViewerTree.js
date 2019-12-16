Ext.ns('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

/**
 *
 */
XDMoD.Admin.ETL.ETLViewerTree = Ext.extend(Ext.tree.TreePanel, {

    _DEFAULT_CONFIG: {
        useArrows: true,
        autoScroll: true,
        animate: true,
        method: 'GET',
        bubbleEvents: [
            'load',
            'data_loaded'
        ]
    },

    initComponent: function () {

        this.loader = this.loader || new Ext.tree.TreeLoader();
        this.loader.dataUrl = XDMoD.REST.url + '/etc/pipelines';

        Ext.apply(this, this._DEFAULT_CONFIG);

        this.loader.requestData = this._loaderRequestData;

        this.loader.parent = this;

        XDMoD.Admin.ETL.ETLViewerTree.superclass.initComponent.apply(this, arguments);
    },

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
                : [];


        if (success) {
            var nodes = rawNodes.map(function (node) {
                if (undefined === node.text) {
                    node.text = node.name ? node.name : '';
                }
                return node;
            });
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
