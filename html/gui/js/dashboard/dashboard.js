var current_users;

var actionLogout = function () {
    Ext.Ajax.request({
        url: '/internal_dashboard/controllers/controller.php',
        params: {operation: 'logout'},
        method: 'POST',
        callback: function(options, success, response) {
            if (success) {
                success = CCR.checkJSONResponseSuccess(response);
            }

            if (!success) {
                CCR.xdmod.ui.presentFailureResponse(response, {
                    title: 'XDMoD Dashboard',
                    wrapperMessage: 'There was a problem connecting to the dashboard service provider.'
                });
                return;
            }

            location.href = 'index.php';
        }//callback
    });//Ext.Ajax.request
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

