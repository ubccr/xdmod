XDMoD.ProfileApiToken = Ext.extend(Ext.form.FormPanel, {
    // Ext parameters
    title: 'API Token',
    id: 'xdmod-profile-api-token',
    cls: 'user_profile_api_token',
    autoHeight: true,
    frame: true,
    border: false,
    resizable: false,

    // Constants
    ACTION_TEXT_FOR_GET: 'fetching API token',
    SUB_COMPONENT_PADDING: 3,
    EXT_MSG_MIN_WIDTH: 400,
    EXT_MSG_MAX_WIDTH: 800,

    // Sub-components
    panelMsg: null,
    btnCopyToClipboard: null,
    btnDeleteNew: null,
    panelCopiedMsg: null,
    panelCopyDelete: null,
    btnRetry: null,
    btnGenerate: null,
    btnDeleteOld: null,
    panelOtherBtns: null,

    // Member variables
    tokenText: '',
    creationDate: '',
    expirationDate: '',
    initialParentWindowWidth: undefined,

    // Methods
    initComponent: function () {
        this.initSubComponents();
        this.addSubComponents();
        this.getParentWindowWidthOnNextRender();
        XDMoD.ProfileApiToken.superclass.initComponent.call(this);
    },

    init: function () {
        this.getToken();
    },

    initSubComponents: function () {
        this.initPanelMsg();
        this.initBtnCopyToClipboard();
        this.initBtnDeleteNew();
        this.initPanelCopiedMsg();
        this.initPanelCopyDelete();
        this.initBtnRetry();
        this.initBtnGenerate();
        this.initBtnDeleteOld();
        this.initPanelOtherBtns();
    },

    addSubComponents: function () {
        Ext.apply(this, {
            items: [
                this.panelMsg,
                this.panelCopyDelete,
                this.panelOtherBtns
            ],
            bbar: {
                items: [
                    '->',
                    this.parentWindow.getCloseButton()
                ]
            }
        });
    },

    getParentWindowWidthOnNextRender: function () {
        this.addListener('render', function () {
            this.initialParentWindowWidth = this.parentWindow.getWidth();
        });
    },

    getToken: function () {
        this.request(this.getParamsForGetRequest());
    },

    initPanelMsg: function () {
        this.panelMsg = new Ext.Panel({
            cls: 'panel_msg',
            html: 'Loading...',
            padding: this.SUB_COMPONENT_PADDING
        });
    },

    initBtnCopyToClipboard: function () {
        var self = this;
        this.btnCopyToClipboard = new Ext.Button({
            iconCls: 'btn_copy_icon',
            text: 'Copy API Token to Clipboard',
            margins: this.getBtnMargins(),
            handler: function () {
                self.clickCopyToClipboard();
            }
        });
    },

    initBtnDeleteNew: function () {
        this.btnDeleteNew = this.buildRequestBtn({
            iconCls: 'btn_delete_icon',
            text: 'Delete API Token',
            method: 'DELETE',
            actionText: 'deleting API token',
            confirmMsg: (
                'Are you sure you want to delete your API token?<br>'
                + '<b>This action cannot be undone,'
                + ' but a new token can be generated.</b>'
            ),
            onSuccess: this.processSuccessfulDeleteRequest
        });
        this.btnDeleteNew.show();
    },

    initPanelCopiedMsg: function () {
        this.panelCopiedMsg = new Ext.Panel({
            cls: 'copied_msg',
            html: '',
            padding: this.SUB_COMPONENT_PADDING
        });
        this.clearCopiedMessageOnWindowClose();
    },

    initPanelCopyDelete: function () {
        this.panelCopyDelete = new Ext.Panel({
            layout: 'hbox',
            hidden: true,
            items: [
                this.btnCopyToClipboard,
                this.btnDeleteNew,
                this.panelCopiedMsg
            ]
        });
    },

    initBtnRetry: function () {
        var params = Object.assign(
            {
                iconCls: 'btn_retry_icon',
                text: 'Retry'
            },
            this.getParamsForGetRequest()
        );
        this.btnRetry = this.buildRequestBtn(params);
    },

    initBtnGenerate: function () {
        this.btnGenerate = this.buildRequestBtn({
            iconCls: 'btn_generate_icon',
            text: 'Generate API Token',
            method: 'POST',
            actionText: 'generating API token',
            onSuccess: this.processSuccessfulGenerateRequest
        });
    },

    initBtnDeleteOld: function () {
        this.btnDeleteOld = this.btnDeleteNew.cloneConfig();
    },

    initPanelOtherBtns: function () {
        this.panelOtherBtns = new Ext.Panel({
            padding: '0 0 ' + this.SUB_COMPONENT_PADDING + 'px '
                            + this.SUB_COMPONENT_PADDING + 'px',
            items: [
                this.btnRetry,
                this.btnGenerate,
                this.btnDeleteOld
            ]
        });
    },

    request: function (params) {
        var self = this;
        XDMoD.REST.connection.request({
            url: '/users/current/api/token',
            method: params.method,
            callback: function (options, success, response) {
                self.processResponse(params, success, response);
            }
        });
    },

    getParamsForGetRequest: function () {
        return {
            method: 'GET',
            actionText: this.ACTION_TEXT_FOR_GET,
            onSuccess: this.processSuccessfulGetRequest,
            onFailure: this.processFailedGetRequest,
            onServerError: this.processGetRequestServerError
        };
    },

    getBtnMargins: function () {
        return {
            top: 0,
            right: 0,
            bottom: this.SUB_COMPONENT_PADDING,
            left: this.SUB_COMPONENT_PADDING
        };
    },

    clickCopyToClipboard: function () {
        navigator.clipboard.writeText(this.tokenText);
        this.updateCopiedMsg('Copied!');
    },

    buildRequestBtn: function (params) {
        var self = this;
        return new Ext.Button({
            iconCls: params.iconCls,
            text: params.text,
            margins: this.getBtnMargins(),
            hidden: true,
            handler: function () {
                self.clickRequestBtn(params);
            }
        });
    },

    processSuccessfulDeleteRequest: function (self) {
        self.updateCopiedMsg('');
        self.parentWindow.setWidth(self.initialParentWindowWidth);
        self.getToken();
    },

    clearCopiedMessageOnWindowClose: function () {
        this.addListener('deactivate', function () {
            this.updateCopiedMsg('');
        });
    },

    processSuccessfulGenerateRequest: function (self, data) {
        self.tokenText = data.data.token;
        self.expirationDate = data.data.expiration_date;
        self.showNewToken();
    },

    processResponse: function (params, success, response) {
        if (success) {
            this.processSuccessfulResponse(params, response);
        } else if (params.onServerError) {
            params.onServerError(this);
        } else {
            this.showServerErrorMsg(params.actionText);
        }
    },

    processSuccessfulGetRequest: function (self, data) {
        self.creationDate = data.data.created_on;
        self.expirationDate = data.data.expiration_date;
        self.showReceivedToken();
    },

    processFailedGetRequest: function (self) {
        self.showMsg('You currently have no API token.');
        self.panelCopyDelete.hide();
        self.btnRetry.hide();
        self.btnDeleteOld.hide();
        self.btnGenerate.show();
        XDMoD.utils.syncWindowShadow(self);
    },

    processGetRequestServerError: function (self) {
        self.showMsg(self.getServerErrorMsg(self.ACTION_TEXT_FOR_GET));
        self.btnRetry.show();
        XDMoD.utils.syncWindowShadow(self);
    },

    clickRequestBtn: function (params) {
        if (params.confirmMsg) {
            this.confirmRequest(params);
        } else {
            this.request(params);
        }
    },

    updateCopiedMsg: function (msg) {
        this.updateHtml(this.panelCopiedMsg, msg);
    },

    showNewToken: function () {
        this.showMsg('Your API token is:<br/><b>' + this.tokenText
                     + '</b><br/>It will not be obtainable later,'
                     + ' so please copy it now before clicking away.'
                     + '<br/>(You can generate a new one if you lose this'
                     + ' one.).<br/>Your API token will expire on'
                     + ' <b>' + this.expirationDate + '</b>');
        this.btnGenerate.hide();
        this.panelCopyDelete.show();
        this.prepareToSyncWindowShadow();
        this.parentWindow.setWidth(550);
    },

    processSuccessfulResponse: function (params, response) {
        var data = CCR.safelyDecodeJSONResponse(response);
        if (CCR.checkDecodedJSONResponseSuccess(data)) {
            params.onSuccess(this, data);
        } else if (params.onFailure) {
            params.onFailure(this);
        } else {
            this.showServerErrorMsg(params.actionText);
        }
    },

    showServerErrorMsg: function (actionText) {
        Ext.Msg.show({
            title: 'Server Error',
            msg: this.getServerErrorMsg(actionText),
            icon: Ext.MessageBox.ERROR,
            buttons: Ext.Msg.OK,
            minWidth: this.EXT_MSG_MIN_WIDTH,
            maxWidth: this.EXT_MSG_MAX_WIDTH
        });
    },

    showReceivedToken: function () {
        var isTokenExpired = new Date(this.creationDate)
                          >= new Date(this.expirationDate);
        if (isTokenExpired) {
            this.showMsg(this.getExpiredTokenMsg());
        } else {
            this.showMsg(this.getUnexpiredTokenMsg());
        }
        this.btnRetry.hide();
        this.btnDeleteOld.show();
        XDMoD.utils.syncWindowShadow(this);
    },

    showMsg: function (msg) {
        this.updateHtml(this.panelMsg, msg);
    },

    getServerErrorMsg: function (actionText) {
        return 'Server error ' + actionText + '. Please try again.';
    },

    confirmRequest: function (params) {
        var self = this;
        Ext.Msg.show({
            title: params.text,
            msg: params.confirmMsg,
            icon: Ext.MessageBox.QUESTION,
            buttons: Ext.Msg.YESNO,
            minWidth: this.EXT_MSG_MIN_WIDTH,
            maxWidth: this.EXT_MSG_MAX_WIDTH,
            fn: function (resp) {
                if (resp === 'yes') {
                    self.request(params);
                }
            }
        });
    },

    updateHtml: function (obj, html) {
        if (obj.rendered) {
            obj.update(html);
        } else {
            obj.html = html;
        }
    },

    prepareToSyncWindowShadow: function () {
        this.parentWindow.addListener(
            'resize',
            function () {
                XDMoD.utils.syncWindowShadow(this);
            },
            this, {
                delay: 50,
                single: true
            }
        );
    },

    getExpiredTokenMsg: function () {
        return 'Your API token expired on'
               + ' <b>' + this.expirationDate + '</b>'
               + '<br/>Please delete it and generate a new one.';
    },

    getUnexpiredTokenMsg: function () {
        return 'Your API token was generated on'
               + ' <b>' + this.creationDate + '</b>'
               + '<br/>and expires on'
               + ' <b>' + this.expirationDate + '</b>'
               + '<br/>If you have lost your API token,'
               + ' please delete it and generate a new one.';
    }
});
