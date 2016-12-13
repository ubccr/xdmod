Ext.ns('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

// Column that will be used as the 'auto_expand_column' by default.
_DEFAULT_AUTO_EXPAND_COLUMN = 'acctgridvalue';

// Global defaults for this component.
_DEFAULTS = {
    closable: false,
    enableDD: true,
    autoScroll: true,
    layout: 'fit',
    autoExpandColumn: _DEFAULT_AUTO_EXPAND_COLUMN,
    updateHistory: true,
    preferWindowPath: false
};

// Global column defaults for this component.
var _DEFAULT_COLUMN = {
    width: 200,
    sortable: true,
    editor: new Ext.form.TextField({
        allowBlank: false
    }),
    documentationColumns: ['documentation', 'help'],
    renderer: function(value, metadata, record, rowIndex, colIndex, store) {
        var columns = this.documentationColumns;
        if (CCR.isType(columns, CCR.Types.Array) && columns.length > 0) {
            for (var i = 0; i < columns.length; i++) {
                var column = columns[i];
                var columnValue = record.get(column);
                if (CCR.exists(columnValue)) {
                    metadata.attr = 'ext.qtip"' + columnValue + '"';
                    break;
                }
            }
        }
        return value;
    }
};

/**
 * This component is used to display information that benefits from a tabular
 * horizontal representation but also contains elements that have multiple
 * internal groupings such that a tree visualization would be useful.
 */
XDMoD.Module.JobViewer.NestedViewPanel = Ext.extend(Ext.ux.tree.TreeGrid, {

    /**
     * This components constructor.
     */
    initComponent: function() {

        Ext.applyIf(this, _DEFAULTS);

        this.columns = this._generateColumns(this);

        XDMoD.Module.JobViewer.NestedViewPanel.superclass.initComponent.apply(this, arguments);


    }, // initComponent

    /**
     * Helper function that returns an array of newly generated columns to be
     * used when constructing this component.
     *
     * @param {Object} options
     * @returns {Array}
     * @private
     */
    _generateColumns: function(options) {
        var results = [];
        if (CCR.isType(options, CCR.Types.Object) && CCR.isType(options.columns, CCR.Types.Array)) {
            var columns = options.columns;
            for (var i = 0; i < columns.length; i++) {
                var columnOptions = columns[i];
                var column = this._generateColumn(columnOptions);
                results.push(column);
            }
        }
        return results;
    }, // _generateColumns

    /**
     * Helper function that generates a column object by combining the provided
     * options with the default column options.
     *
     * @param {Object} options
     * @returns {Object}
     * @private
     */
    _generateColumn: function(options) {
        if (!CCR.exists(options)) return null;
        if (!CCR.exists(options.header)) return null;
        if (!CCR.exists(options.dataIndex)) return null;

        var column = {};
        Ext.apply(column, options);
        Ext.applyIf(column, _DEFAULT_COLUMN);
        return column;
    } // _generateColumn
});