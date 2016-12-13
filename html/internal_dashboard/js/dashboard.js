var current_users;

var processLDIFExport = function (config) {
    var sharedParams = {
        group_filter: config.group_filter,
        role_filter: config.role_filter,
        context_filter: config.context_filter
    };

    var enumParams = Ext.apply({
        operation: 'enum_existing_users'
    }, sharedParams);

    Ext.Ajax.request({
        url: 'controllers/controller.php',
        params: enumParams,
        method: 'POST',
        callback: function (options, success, response) {
            var responseObject;
            if (success) {
                responseObject = CCR.safelyDecodeJSONResponse(response);
                success = CCR.checkDecodedJSONResponseSuccess(responseObject);
            }

            if (!success) {
                CCR.xdmod.ui.presentFailureResponse(response, {
                    title: 'LDIF Export'
                });
                return;
            }

            if (responseObject.count <= 0) {
                CCR.xdmod.ui.generalMessage('LDIF Export', 'No accounts would be present in the LDIF you are attempting to export.', false);
                return;
            }

            var generateParams = Ext.apply({
                operation: 'generate_ldif'
            }, sharedParams);
            
            CCR.invokePost('controllers/controller.php', generateParams, {
                checkDashboardUser: true
            });
        }
    });
};//processLDIFExport

// ------------------------------------------------

var actionLogout = function () {
    Ext.Ajax.request({
        url: 'controllers/controller.php',
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

