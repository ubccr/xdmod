Ext.ns('XDMoD', 'XDMoD.Module', 'XDMoD.Module.MetricExplorer');

defaultButtonHandler = function() {
    var exists = CCR.exists;
    var event = this.event;

    var parent = exists(this.ownerCt) && exists(this.ownerCt.ownerCt)
            ? this.ownerCt.ownerCt
            : null;

    if (exists(parent)) {
        parent.fireEvent(event);
    }
};

XDMoD.Module.MetricExplorer.StatusButton = Ext.extend(Ext.Button, {
    id  : 'me_chart_status_button',
    cls : 'x-btn-text-icon',
    menu: {
        items: [
            {
                text    : 'Save Changes',
                icon    : '../gui/images/disk.png',
                disabled: true,
                event: 'save_changes',
                handler : defaultButtonHandler
            },
            {
                text    : 'Revert Changes',
                icon    : '../gui/images/query_stop.png',
                disabled: true,
                event: 'discard_changes',
                handler : defaultButtonHandler
            }
        ]
    },
    tooltip: 'Indicates whether or not the current set of changes have been saved.',

    dirtyIcon : '../gui/images/exclamation.png',
    cleanIcon : '../gui/images/accept.png',

    initComponent: function () {
        XDMoD.Module.MetricExplorer.StatusButton.superclass.initComponent.call(this, arguments);
    },

    setButtonState: function(saveAllowed, revertAllowed) {

        this.menu.items.items[0].setDisabled(!saveAllowed);
        this.menu.items.items[1].setDisabled(!revertAllowed);

        if(saveAllowed) {
            this.setIcon(this.dirtyIcon);
        } else {
            this.setIcon(this.cleanIcon);
        }
    }

});

