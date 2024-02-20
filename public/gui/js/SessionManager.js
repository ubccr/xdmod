/**
 * SessionManager.js
 *
 * Global functionality for handling session issues (expiration, etc.) in the
 * browser client.
 */

Ext.namespace('XDMoD.SessionManager');

/**
 * A window for notifying the user that their session has expired and providing
 * them options for how to handle it.
 */
XDMoD.SessionManager._sessionExpiredNotification = new Ext.Window({

    title: 'Session Expired',
    closable: false,
    resizable: false,
    modal: true,
    autoHeight: true,
    autoWidth: true,
    padding: 4,

    html: '<p>You have been logged out due to inactivity.</p><p>Please log in again to continue.</p>',

    bbar: {
        items: [

            '->',

            new Ext.Button({
                text: 'Complete Logout',
                handler: function () {
                    location.href = location.href.split('#')[0];
                }
            })

        ]
    }

}); //XDMoD.SessionManager._sessionExpiredNotification

// Add a global listener to Ext.data.Connection calls to handle session timeout
// error responses from the server.
Ext.util.Observable.observeClass(Ext.data.Connection);
Ext.data.Connection.on('requestexception', function (conn, response, options) {

    var responseObject = CCR.safelyDecodeJSONResponse(response);
    if (responseObject === null || typeof responseObject !== "object") {
        return;
    }

    if ((responseObject.success === false) && (responseObject.code === XDMoD.Error.SessionExpired)) {
        XDMoD.SessionManager._sessionExpiredNotification.show();
        response.exceptionHandledGlobally = true;
    }
});
