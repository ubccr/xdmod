Ext.namespace('XDMoD.DataWarehouse');

/**
 * Create a button containing a list of quick filters.
 *
 * Depends on XDMoD.DataWarehouse.quickFilterStore.
 *
 * @param [object] config A set of configuration settings which will be applied
 *                        to the button. Custom settings include:
 *                        * autoAddMostPrivilegedRoleFilters: Controls whether
 *                          the user's most privileged role quick filters are
 *                          automatically added on creation.
 *                          (Defaults to true.)
 */
XDMoD.DataWarehouse.createQuickFilterButton = function (config) {
    // Create the button with a loading indicator.
    config = Ext.isDefined(config) ? config : {};

    var quickFilterButton = new Ext.Button(Ext.apply({
        text: 'Quick Filters',
        iconCls: 'quick_filter',
        disabled: true,

        autoAddMostPrivilegedRoleFilters: true,

        menu: new Ext.menu.Menu({
            showSeparator: false
        })
    }, config));

    // Create an empty copy of the quick filter store for this button's use.
    var quickFilterStore = new Ext.data.Store({
        reader: new Ext.data.JsonReader({
        }, XDMoD.DataWarehouse.quickFilterStore.recordType),

        listeners: {
            update: function () {
                quickFilterButton.menu.items.each(function (menuItem) {
                    if (!Ext.isDefined(menuItem.filterRecord)) {
                        return;
                    }

                    menuItem.setChecked(menuItem.filterRecord.get('checked'), true);
                });
            }
        },

        findQuickFilterRecordIndex: function (quickFilterRecord) {
            return this.findBy(function (storeRecord) {
                return (quickFilterRecord.get('dimensionId') === storeRecord.get('dimensionId')) &&
                    (quickFilterRecord.get('valueId') === storeRecord.get('valueId'));
            });
        },

        setFilterChecked: function (quickFilterRecord, checked) {
            var quickFilterRecordIndex = this.findQuickFilterRecordIndex(quickFilterRecord);
            if (quickFilterRecordIndex < 0) {
                return;
            }

            this.getAt(quickFilterRecordIndex).set('checked', checked);
        }
    });
    quickFilterButton.quickFilterStore = quickFilterStore;

    // Create a callback for after the quick filters have been loaded.
    XDMoD.DataWarehouse.quickFilterStore.callAfterLoaded(function () {
        // Copy the main store's filters into this button's store.
        var filterRecordCopies = [];
        XDMoD.DataWarehouse.quickFilterStore.each(function (filterRecord) {
            filterRecordCopies.push(filterRecord.copy());
        });
        quickFilterStore.add(filterRecordCopies);

        // If no filters were loaded, stop.
        if (Ext.isEmpty(filterRecordCopies)) {
            return;
        }

        // Add each quick filter to the list.
        var currentDimensionName = null;
        quickFilterButton.quickFilterStore.each(function (filterRecord) {
            // If this is the first filter on the current dimension,
            // add a header item.
            var dimensionName = filterRecord.get('dimensionName');
            if (dimensionName !== currentDimensionName) {
                if (currentDimensionName !== null) {
                    quickFilterButton.menu.add('-');
                }

                var encodedDimensionName = Ext.util.Format.htmlEncode(dimensionName);
                quickFilterButton.menu.add(new Ext.menu.TextItem({
                    text: encodedDimensionName,
                    style: {
                        'font-size': '120%',
                        'font-weight': 'bold'
                    }
                }));
                currentDimensionName = dimensionName;
            }

            // Add an item to the button's menu for this filter.
            var filterItemStyle = {};
            if (filterRecord.get('isUserSpecificFilter')) {
                filterItemStyle['font-weight'] = 'bold';
            }
            quickFilterButton.menu.add(new Ext.menu.CheckItem({
                text: Ext.util.Format.htmlEncode(filterRecord.get('valueName')),
                style: filterItemStyle,

                filterRecord: filterRecord,

                listeners: {
                    checkchange: function (thisItem, checked) {
                        thisItem.filterRecord.set('checked', checked);
                    }
                }
            }));
        });

        // If enabled, automatically enable most privileged role filters.
        if (quickFilterButton.autoAddMostPrivilegedRoleFilters) {
            quickFilterStore.each(function (quickFilterRecord) {
                if (!quickFilterRecord.get('isMostPrivilegedRoleFilter')) {
                    return;
                }

                quickFilterRecord.set('checked', true);
            });
        }

        // Enable the button.
        quickFilterButton.enable();
    });

    // Return the button.
    return quickFilterButton;
};
