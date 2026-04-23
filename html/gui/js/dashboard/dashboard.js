var current_users;

var actionLogout = function () {
    location.href = '/internal_dashboard/logout';
};//actionLogout

// ------------------------------------------------

Ext.onReady(function () {
    var factory = new XDMoD.Dashboard.Factory();

    factory.load(function (items) {
        new XDMoD.Dashboard.Viewport({ items: items });
    });

    // Allowing functions since the 'item' function is ridiculous and looks to
    // be broken by design.
    Ext.ComponentMgr.all.allowFunctions = true;
}, window, true);
