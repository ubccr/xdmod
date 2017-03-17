Ext.namespace('XDMoD.DataWarehouse');

/**
 * A shared store containing the set of quick filters available to a user.
 */
XDMoD.DataWarehouse.quickFilterStore = new Ext.data.Store({
    isLoaded: false,

    hasMultiSort: true,
    multiSortInfo: {
        sorters: [
            {
                field: 'dimensionUserSpecificRatio',
                direction: 'DESC'
            },
            {
                field: 'dimensionName',
                direction: 'ASC'
            },
            {
                field: 'isUserSpecificFilter',
                direction: 'DESC'
            }
        ]
    },

    reader: new Ext.data.JsonReader({
        root: 'results'
    }, [
        'dimensionId',
        'dimensionName',
        'valueId',
        'valueName',
        'isUserSpecificFilter',
        'dimensionUserSpecificRatio',
        'checked'
    ]),

    listeners: {
        'load': {
            fn: function(thisStore) {
                thisStore.isLoaded = true;
            },
            single: true
        }
    },

    /**
     * Call the given function after this store has first loaded.
     *
     * @param  {Function} fn    The function to call.
     * @param  {mixed}    scope (Optional) The scope to call the function with.
     */
    callAfterLoaded: function (fn, scope) {
        scope = Ext.isDefined(scope) ? scope : this;
        if (this.isLoaded) {
            fn.call(scope);
        } else {
            this.addListener('load', fn, scope, {
                single: true
            });
        }
    }
});

XDMoD.REST.connection.request({
    url: 'warehouse/quick_filters',
    method: 'GET',
    callback: function (options, success, response) {
        var data;
        if (success) {
            data = CCR.safelyDecodeJSONResponse(response);
            success = CCR.checkDecodedJSONResponseSuccess(data);
        }

        if (!success) {
            XDMoD.DataWarehouse.quickFilterStore.loadData(Ext.encode({
                success: true,
                results: []
            }));
            return;
        }

        var filters = [];
        Ext.iterate(data.results.filters, function (dimensionId, dimensionFilters) {
            if (Ext.isEmpty(dimensionFilters)) {
                return;
            }

            var dimensionName = data.results.dimensionNames[dimensionId];

            var numUserSpecificFilters = 0;
            Ext.each(dimensionFilters, function (dimensionFilter) {
                if (dimensionFilter.isUserSpecificFilter) {
                    numUserSpecificFilters++;
                }
            });
            var dimensionUserSpecificRatio = numUserSpecificFilters / dimensionFilters.length;

            Ext.each(dimensionFilters, function (dimensionFilter) {
                filters.push(Ext.apply(dimensionFilter, {
                    dimensionId: dimensionId,
                    dimensionName: dimensionName,
                    dimensionUserSpecificRatio: dimensionUserSpecificRatio,
                    checked: false
                }));
            });
        });

        XDMoD.DataWarehouse.quickFilterStore.loadData({
            success: true,
            results: filters
        });
    }
});
