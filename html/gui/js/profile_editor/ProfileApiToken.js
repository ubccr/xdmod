XDMoD.ProfileApiToken = Ext.extend(Ext.form.FormPanel, {

    id: 'xdmod-profile-api-token',
    autoHeight: true,
    border: false,
    frame: true,
    resizable: false,
    title: 'API Token',
    cls: 'user_profile_api_token',

    init: function () {
        this.getToken();
    },

    initComponent: function () {
        var self = this;

        var PADDING = 3;
        var BTN_MARGINS = {
            top: 0,
            right: 0,
            bottom: PADDING,
            left: PADDING
        };
        var EXT_MSG_MIN_WIDTH = 400;
        var EXT_MSG_MAX_WIDTH = 800;

        var token = '';
        var creationDate = '';
        var expirationDate = '';

        // ----- DECLARE SUB-COMPONENTS ----- //

        var panelMsg;
        var btnCopyToClipboard;
        var btnDeleteNew;
        var panelCopiedMsg;
        var panelCopyDelete;
        var btnRetry;
        var btnGenerate;
        var btnDeleteOld;
        var panelOtherBtns;

        // ----- DECLARE UTILITY FUNCTIONS ----- //

        var getServerFailureMsg = function (actionText) {
            return 'Server failure ' + actionText + '. Please try again.';
        };

        var showServerFailureMsg = function (actionText) {
            Ext.Msg.show({
                title: 'Server Failure',
                msg: getServerFailureMsg(actionText),
                icon: Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK,
                minWidth: EXT_MSG_MIN_WIDTH,
                maxWidth: EXT_MSG_MAX_WIDTH
            });
        };

        var request = function (params) {
            XDMoD.REST.connection.request({
                url: '/users/current/api/token',
                method: params.method,
                callback: function (options, success, response) {
                    var data;
                    if (success) {
                        data = CCR.safelyDecodeJSONResponse(response);
                        if (CCR.checkDecodedJSONResponseSuccess(data)) {
                            params.onSuccess(data);
                        } else if (params.onFailure) {
                            params.onFailure();
                        } else {
                            showServerFailureMsg(params.actionText);
                        }
                    } else if (params.onServerFailure) {
                        params.onServerFailure();
                    } else {
                        showServerFailureMsg(params.actionText);
                    }
                } // function callback [XDMoD.REST.connection.request]
            }); // XDMoD.REST.connection.request
        }; // function request

        var newRequestBtn = function (params) {
            return new Ext.Button({
                iconCls: params.iconCls,
                text: params.text,
                margins: BTN_MARGINS,
                hidden: true,
                handler: function () {
                    if (params.confirmMsg) {
                        Ext.Msg.show({
                            title: params.text,
                            msg: params.confirmMsg,
                            icon: Ext.MessageBox.QUESTION,
                            buttons: Ext.Msg.YESNO,
                            minWidth: EXT_MSG_MIN_WIDTH,
                            maxWidth: EXT_MSG_MAX_WIDTH,
                            fn: function (resp) {
                                if (resp === 'yes') {
                                    request(params);
                                }
                            }
                        }); // Ext.Msg.show
                    } else {
                        request(params);
                    } // if (params.confirmMsg)
                } // function handler [Ext.Button]
            }); // new Ext.Button
        }; // function newRequestBtn

        var updateHtml = function (obj, html) {
            if (obj.rendered) {
                obj.update(html);
            } else {
                obj.html = html;
            }
        };

        var actionTextGetToken = 'fetching API token';
        var paramsGetToken = {
            method: 'GET',
            actionText: actionTextGetToken,
            onSuccess: function (data) {
                creationDate = data.data.created_on;
                expirationDate = data.data.expiration_date;
                var msg = '';
                if (new Date(creationDate) < new Date(expirationDate)) {
                    msg = 'Your API token was generated on'
                        + ' <b>' + creationDate + '</b>'
                        + '<br/>and expires on'
                        + ' <b>' + expirationDate + '</b>'
                        + '<br/>If you have lost your API token,'
                        + ' please delete it and generate a new one.';
                } else {
                    msg = 'Your API token expired on'
                        + ' <b>' + expirationDate + '</b>'
                        + '<br/>Please delete it and generate a new one.';
                }
                updateHtml(panelMsg, msg);
                btnRetry.hide();
                btnDeleteOld.show();
                XDMoD.utils.syncWindowShadow(self);
            }, // function onSuccess [paramsGetToken]
            onFailure: function () {
                updateHtml(panelMsg, 'You currently have no API token.');
                panelCopyDelete.hide();
                btnRetry.hide();
                btnDeleteOld.hide();
                btnGenerate.show();
                XDMoD.utils.syncWindowShadow(self);
            },
            onServerFailure: function () {
                updateHtml(panelMsg, getServerFailureMsg(actionTextGetToken));
                btnRetry.show();
                XDMoD.utils.syncWindowShadow(self);
            }
        }; // var paramsGetToken

        this.getToken = function () {
            request(paramsGetToken);
        };

        var initialParentWindowWidth;
        self.addListener('render', function () {
            initialParentWindowWidth = self.parentWindow.getWidth();
        });

        // ----- INITIALIZE SUB-COMPONENTS ----- //

        panelMsg = new Ext.Panel({
            cls: 'panel_msg',
            html: 'Loading...',
            padding: PADDING
        });

        btnCopyToClipboard = new Ext.Button({
            iconCls: 'btn_copy_icon',
            text: 'Copy API Token to Clipboard',
            margins: BTN_MARGINS,
            handler: function () {
                navigator.clipboard.writeText(token);
                updateHtml(panelCopiedMsg, 'Copied!');
            }
        });

        btnDeleteNew = newRequestBtn({
            iconCls: 'btn_delete_icon',
            text: 'Delete API Token',
            method: 'DELETE',
            actionText: 'deleting API token',
            confirmMsg: (
                'Are you sure you want to delete your API token?<br>'
                + '<b>This action cannot be undone,'
                + ' but a new token can be generated.</b>'
            ),
            onSuccess: function (data) {
                updateHtml(panelCopiedMsg, '');
                self.parentWindow.setWidth(initialParentWindowWidth);
                self.getToken();
            }
        }); // btnDeleteNew = newRequestBtn()
        btnDeleteNew.show();

        panelCopiedMsg = new Ext.Panel({
            cls: 'copied_msg',
            html: '',
            padding: PADDING
        });
        this.addListener('deactivate', function () {
            updateHtml(panelCopiedMsg, '');
        });

        panelCopyDelete = new Ext.Panel({
            layout: { type: 'hbox' },
            hidden: true,
            items: [
                btnCopyToClipboard,
                btnDeleteNew,
                panelCopiedMsg
            ]
        });

        btnRetry = newRequestBtn(Object.assign(
            {
                iconCls: 'btn_retry_icon',
                text: 'Retry'
            },
            paramsGetToken
        ));

        btnGenerate = newRequestBtn({
            iconCls: 'btn_generate_icon',
            text: 'Generate API Token',
            method: 'POST',
            actionText: 'generating API token',
            onSuccess: function (data) {
                token = data.data.token;
                expirationDate = data.data.expiration_date;
                updateHtml(
                    panelMsg,
                    'Your API token is:<br/><b>' + token + '</b><br/>'
                    + ' It will not be obtainable later,'
                    + ' so please copy it now before clicking away.'
                    + '<br/>(You can generate a new one if you lose this one.).'
                    + '<br/>Your API token will expire on'
                    + ' <b>' + expirationDate + '</b>'
                );
                btnGenerate.hide();
                panelCopyDelete.show();
                self.parentWindow.addListener(
                    'resize',
                    function () {
                        XDMoD.utils.syncWindowShadow(self);
                    },
                    self, {
                        delay: 50,
                        single: true
                    }
                ); // self.parentWindow.addListener
                self.parentWindow.setWidth(550);
            } // function onSuccess [newRequestBtn]
        }); // btnGenerate = newRequestBtn()

        btnDeleteOld = btnDeleteNew.cloneConfig();

        panelOtherBtns = new Ext.Panel({
            padding: '0 0 ' + PADDING + 'px ' + PADDING + 'px',
            items: [
                btnRetry,
                btnGenerate,
                btnDeleteOld
            ]
        });

        // ----- INITIALIZE COMPONENT ----- //

        Ext.apply(this, {
            items: [
                panelMsg,
                panelCopyDelete,
                panelOtherBtns
            ],
            bbar: {
                items: [
                    '->',
                    self.parentWindow.getCloseButton()
                ]
            }
        });

        XDMoD.ProfileApiToken.superclass.initComponent.call(this);
    } // function initComponent
}); // XDMoD.ProfileApiToken
