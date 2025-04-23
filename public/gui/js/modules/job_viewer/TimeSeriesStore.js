//Ext.namespace('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

/**
 * This component provides a new class to use for the charts that are going to
 * display time series data.
 */
XDMoD.Module.JobViewer.TimeSeriesStore = Ext.extend(Ext.data.JsonStore, {

    /**
     * the default configuration options that we're going to be providing
     */
    _DEFAULT_CONFIG: {
        autoLoad: false,
        root: 'data',
        idProperty: 'id',
        fields: ["series", "schema"]
    },

    /**
     * Construct an instance of a new 'TimeSeriesStore'.
     *
     * @param {Array} config array of configuration parameters.
     */
    constructor: function(config) {
        config = config || {};

        var metric = config['metric'] ? config['metric'] : '';
        var token = config['token'] ? config['token'] : null;
        var url = config['url'] ? config['url'] : null;

        this._addDefaultId(metric, config);
        this._addBaseParams(token, config);
        this._addProxy(url, metric, config);

        Ext.apply(config, this._DEFAULT_CONFIG);

        XDMoD.Module.JobViewer.TimeSeriesStore.superclass.constructor.call(
            this, config
        );
    }, // constructor

    /**
     *
     * @param url
     * @param metric
     * @param config
     * @private
     */
    _addProxy: function(url, metric, config) {
        var u = url;
        var m = metric;
        if (typeof config === 'object' &&
            (config['proxy'] === undefined || config['proxy'] === null)) {
            Ext.apply(config, {
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: u + 'metric=' + m
                })
            });
        }
    }, // _addProxy

    /**
     * Update the provided config object if it does not contain a property
     * 'baseParams' with a property baseParams:
     * {
     *   token: token
     * }
     *
     * @param token
     * @param config
     * @private
     */
    _addBaseParams: function(token, config) {
        var t = token;
        if ( typeof config === 'object' &&
             (config['baseParams'] === undefined
              || config['baseParams'] === null)) {
            Ext.apply(config, {
                baseParams: {
                    token: t
                }
            });
        }
    }, // _addBaseParams

    /**
     * Add a default id to the provided config object. In this case the default
     * id is a property 'storeId' with a value: 'TimeSeriesStore' + metric.
     *
     * @param metric
     * @param config
     * @private
     */
    _addDefaultId: function(metric, config) {
        var m = metric;
        if (typeof config === 'object') {
            Ext.apply(config, {
                storeId: 'TimeSeriesStore' + m
            });
        }
    } // _addDefaultId
});
XDMoD.Module.TimeSeriesStore = XDMoD.Module.JobViewer.TimeSeriesStore;
