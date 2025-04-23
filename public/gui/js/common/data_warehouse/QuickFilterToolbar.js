Ext.namespace('XDMoD.DataWarehouse');

/**
 * Create a toolbar displaying the active filters.
 *
 * @param [Ext.data.Store] quickFilterStore A quick filter store to work with.
 * @param [object] config A set of configuration settings which will be applied
 *                        to the button.
 */
XDMoD.DataWarehouse.createQuickFilterToolbar = function (quickFilterStore, config) {
    config = Ext.isDefined(config) ? config : {};

    var internalFilterStore = new Ext.data.Store({
        hasMultiSort: true,
        multiSortInfo: {
            sorters: [
                {
                    field: 'dimensionName',
                    direction: 'ASC'
                }
            ]
        },

        reader: new Ext.data.JsonReader({
        }, XDMoD.DataWarehouse.quickFilterStore.recordType),

        containsQuickFilterRecord: function (quickFilterRecord) {
            var storeRecordIndex = this.findQuickFilterRecordIndex(quickFilterRecord);
            return storeRecordIndex >= 0;
        },

        findQuickFilterRecordIndex: function (quickFilterRecord) {
            return this.findBy(function (storeRecord) {
                return (quickFilterRecord.get('dimensionId') === storeRecord.get('dimensionId')) &&
                    (quickFilterRecord.get('valueId') === storeRecord.get('valueId'));
            });
        }
    });

    var quickFilterToolbar = new Ext.Toolbar(Ext.apply({

        addFilter: function (quickFilterRecord) {
            if (internalFilterStore.containsQuickFilterRecord(quickFilterRecord)) {
                return;
            }

            internalFilterStore.addSorted(quickFilterRecord.copy());
        },

        deleteFilter: function (quickFilterRecord) {
            var quickFilterRecordIndex = internalFilterStore.findQuickFilterRecordIndex(quickFilterRecord);
            if (quickFilterRecordIndex < 0) {
                return;
            }

            internalFilterStore.removeAt(quickFilterRecordIndex);
        },

        onStoreChange: function () {
            // Clear the items added by the toolbar.
            var itemsToRemove = [];
            this.items.each(function (item) {
                if (!item.addedByQuickFilterToolbar) {
                    return;
                }

                itemsToRemove.push(item);
            }, this);
            Ext.each(itemsToRemove, function (item) {
                this.remove(item);
            }, this);

            // Add items for each filter in the internal store.
            var currentDimensionName = null;
            internalFilterStore.each(function (quickFilterRecord) {
                // If this is the first filter on this dimension, add a label.
                var dimensionName = quickFilterRecord.get('dimensionName');
                if (dimensionName !== currentDimensionName) {
                    if (this.items.getCount() > 0) {
                        this.add(new Ext.Toolbar.Separator({
                            addedByQuickFilterToolbar: true
                        }));
                    }

                    this.add(new Ext.Toolbar.TextItem({
                        html: '<b>' + Ext.util.Format.htmlEncode(dimensionName) + ':</b> ',

                        addedByQuickFilterToolbar: true
                    }));

                    currentDimensionName = dimensionName;
                }

                // Create a label and delete button for this filter.
                this.add(new Ext.Toolbar.TextItem({
                    html: Ext.util.Format.htmlEncode(quickFilterRecord.get('valueName')),

                    addedByQuickFilterToolbar: true
                }));
                this.add(new Ext.Button({
                    iconCls: 'delete_filter',

                    addedByQuickFilterToolbar: true,

                    quickFilterRecord: quickFilterRecord,

                    listeners: {
                        click: function (button) {
                            quickFilterToolbar.deleteFilter(button.quickFilterRecord);
                        }
                    }
                }));
            }, this);

            // Set the visibility of the toolbar based on if it's empty.
            if (this.items.getCount() > 0) {
                this.show();
                this.doLayout();
            } else {
                this.hide();
            }
        }

    }, config));

    internalFilterStore.on('add', quickFilterToolbar.onStoreChange, quickFilterToolbar);
    internalFilterStore.on('load', quickFilterToolbar.onStoreChange, quickFilterToolbar);
    internalFilterStore.on('remove', quickFilterToolbar.onStoreChange, quickFilterToolbar);
    internalFilterStore.on('update', quickFilterToolbar.onStoreChange, quickFilterToolbar);

    quickFilterStore.on('update', function (thisStore, quickFilterRecord) {
        if (quickFilterRecord.get('checked')) {
            quickFilterToolbar.addFilter(quickFilterRecord);
        } else {
            quickFilterToolbar.deleteFilter(quickFilterRecord);
        }
    });

    internalFilterStore.on('remove', function (thisStore, quickFilterRecord) {
        quickFilterStore.setFilterChecked(quickFilterRecord, false);
    });

    quickFilterStore.each(function (quickFilterRecord) {
        if (!quickFilterRecord.get('checked')) {
            return;
        }

        quickFilterToolbar.addFilter(quickFilterRecord);
    });

    return quickFilterToolbar;
};
