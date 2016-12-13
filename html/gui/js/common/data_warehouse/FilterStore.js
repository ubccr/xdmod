/**
 * A store that holds filters for data warehouse dimensions.
 */

Ext.namespace('XDMoD.DataWarehouse');

/**
 * Create a store that holds filters for data warehouse dimensions.
 *
 * @param {Object} config (Optional) A configuration object which will replace
 *                        default configuration options supplied by this function.
 *
 * @return Ext.data.GroupingStore A newly-created store.
 */
XDMoD.DataWarehouse.createFilterStore = function(config) {
    config = Ext.isDefined(config) ? config : {};

    return new Ext.data.GroupingStore(Ext.apply({
        groupField: 'dimension_id',

        sortInfo: {

            field: 'dimension_id',
            direction: 'ASC' // or 'DESC' (case sensitive for local sorting)

        },

        reader: new Ext.data.JsonReader(
            {
                totalProperty: 'totalCount',
                successProperty: 'success',
                idProperty: 'id',
                root: 'data',
                messageProperty: 'message'
            },

            [
                'id',
                'value_id',
                'value_name',
                'dimension_id',
                'realms',
                'checked'
            ]
        ),

        getCheckedCount: function () {
            var count = 0;

            this.each(function (record) {
                if (record.get('checked')) {
                    count++;
                }
            }, this);

            return count;
        }
    }, config));
};
