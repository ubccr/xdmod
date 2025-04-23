/**
 * A window that allows filters for a data warehouse dimension to be toggled.
 */

Ext.namespace('XDMoD.DataWarehouse');

/**
 * Create a window that allows filters for a data warehouse dimension to be toggled.
 *
 * @param {String} dim_id The ID of the dimension being filtered.
 * @param {String} dim_label A label for the dimension being filtered.
 * @param {Array}  realms The set of realms this dimension applies to.
 * @param {Ext.data.GroupingStore} filter_store A store containing the set of
 *                                              filters in use.
 * @param {String} origin_module The name of the module that requested this window.
 *                               Used for user behavior analysis.
 * @param {String} origin_component The name of the component in the module that
 *                                  requested this window. Used for user behavior
 *                                  analysis.
 * @param {Object} config (Optional) A configuration object which will replace
 *                        default configuration options supplied by this function.
 *
 * @return Ext.Window A newly-created window.
 */
XDMoD.DataWarehouse.createAddFilterWindow = function(dim_id, dim_label, realms, filter_store, origin_module, origin_component, config) {
    config = Ext.isDefined(config) ? config : {};

    var filterDimensionPanel = new CCR.xdmod.ui.FilterDimensionPanel({

        origin_module: origin_module,
        origin_component: origin_component,
        dimension_id: dim_id,
        realms: realms,

        dimension_label: dim_label,
        filtersStore: filter_store

    }); //filterDimensionPanel

    filterDimensionPanel.on('cancel', function () {

        addFilterMenu.close();

    });

    filterDimensionPanel.on('ok', function () {

        addFilterMenu.close();

    });

    var addFilterMenu = new Ext.Window(Ext.apply({

        resizable: false,
        showSeparator: false,
        items: [filterDimensionPanel],
        closable: false,
        modal: true

    }, config));

    return addFilterMenu;
};
