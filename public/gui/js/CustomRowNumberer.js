/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Dec-09
 *
 * This class is an extension of row numberer that takes an offset.
 *
 */
CCR.xdmod.ui.CustomRowNumberer = Ext.extend(Object, {
    /**
     * @cfg {String} header Any valid text or HTML fragment to display in the header cell for the row
     * number column (defaults to '').
     */
    header: "",
    /**
     * @cfg {Number} width The default width in pixels of the row number column (defaults to 23).
     */
    width: 25,
    /**
     * @cfg {Boolean} sortable True if the row number column is sortable (defaults to false).
     * @hide
     */
    sortable: false,

    constructor: function (config) {
        Ext.apply(this, config);
        if (this.rowspan) {
            this.renderer = this.renderer.createDelegate(this);
        }
    },

    // private
    fixed: true,
    hideable: false,
    menuDisabled: true,
    dataIndex: '',
    id: 'numberer',
    rowspan: undefined,

    // private
    renderer: function (v, p, record, rowIndex) {
        if (this.rowspan) {
            p.cellAttr = 'rowspan="' + this.rowspan + '"';
        }
        return (this.scope && this.scope.offset ?
            this.scope.offset :
            (record.store.lastOptions && record.store.lastOptions.params && record.store.lastOptions.params.start ? record.store.lastOptions.params.start : 0)) + rowIndex + 1;
    }
});