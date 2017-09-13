
// Monkey patching in Date.now in case it's not here already
// This is for IE8-. In IE9+ ( even IE9 w/ IE8 compatability mode on ) this works
// just fine.
Date.now = Date.now || function() { return +new Date; };

// JavaScript Document
Ext.namespace('CCR', 'CCR.xdmod', 'CCR.xdmod.ui', 'CCR.xdmod.ui.dd', 'XDMoD', 'XDMoD.constants', 'XDMoD.Module', 'XDMoD.regex', 'XDMoD.validator', 'XDMoD.utils', 'CCR.xdmod.reporting');

// ==============================================================

XDMoD.Tracking = {
    sequence_index: 0,
    timestamp: new Date().getTime(),
    suppress_close_handler: false
};

XDMoD.TrackEvent = function (category, action, details, suppress_close_handler) {
    // Tracking is not implemented outside of the XSEDE XDMoD instance.
    if (!CCR.xdmod.features) {
        return;
    }
    if (!CCR.xdmod.features.xsede) {
        return;
    }

    details = details || '';
    suppress_close_handler = suppress_close_handler || false;

    XDMoD.Tracking.suppress_close_handler = suppress_close_handler;

    if (typeof details !== 'string') {
        details = JSON.stringify(details);
    }

    //console.log("[TrackEvent]: " + [XDMoD.REST.token,XDMoD.Tracking.sequence_index,CCR.xdmod.ui.username,category,action,details,details.length].join(' -- '));

    var dimension_limit = 150; // dimension limit imposed by Google
    var action_dimension_slots = 3; // how many custom dimensions are dedicated to storing action details

    var action_details = [];
    var i = 0;

    for (i = 0; i < action_dimension_slots; i++) {
        action_details.push((details.substr(0, dimension_limit).length > 0) ? details.substr(0, dimension_limit) : '-');
        details = details.substr(dimension_limit);
    }

    XDMoD.Tracking.sequence_index++;

    var current_date = new Date();
    var current_timestamp = current_date.getTime();
    var timezone_offset = current_date.getTimezoneOffset();

    var time_delta = current_timestamp - XDMoD.Tracking.timestamp;

    ga('send', 'event', category, action, {
        'dimension1': XDMoD.Tracking.sequence_index,
        'dimension2': CCR.xdmod.ui.username,
        'dimension3': current_timestamp.toString(),
        'dimension4': XDMoD.REST.token,
        'dimension5': (timezone_offset / 60).toString(),
        'dimension6': action_details[0],
        'dimension7': action_details[1],
        'dimension8': action_details[2],
        'metric1': time_delta.toString()
    });

    XDMoD.Tracking.timestamp = current_timestamp;

    _gaq.push(['_trackEvent', CCR.xdmod.ui.username, category, action]);

}; //XDMoD.TrackEvent

// ==============================================================

XDMoD.GeneralOperations = {

    disableButton: function (button_id) {
        Ext.getCmp(button_id).setDisabled(true);
    }, //disableButton

    contactSuccessHandler: function (window_id) {
        var w = Ext.getCmp(window_id);
        w.hide();
        CCR.xdmod.ui.generalMessage('Message Sent', 'Thank you for contacting us.<br>We will get back to you as soon as possible.', true);

    } //contactSuccessHandler

}; //XDMoD.GeneralOperations

// ==============================================================

XDMoD.GlobalToolbar = {};

XDMoD.GlobalToolbar.Logo = {
    xtype: 'tbtext',
    cls: 'logo93',
    id: 'logo',
    width: 93,
    height: 32,
    border: false
}; //XDMoD.GlobalToolbar.Logo

// -------------------------------------------------

XDMoD.GlobalToolbar.CustomCenterLogo = {
    xtype: 'tbtext',
    cls: 'custom_center_logo',
    height: 32,
    border: false
}; //XDMoD.GlobalToolbar.CustomCenterLogo

// -------------------------------------------------

XDMoD.GlobalToolbar.Profile = {
    text: 'My Profile',
    scale: 'small',
    iconCls: 'user_profile_16',
    id: 'global-toolbar-profile',
    tooltip: 'Profile Editor',
    handler: function () {
        XDMoD.TrackEvent("Portal", "My Profile Button Clicked");
        var profileEditor = new XDMoD.ProfileEditor();
        profileEditor.init();
    }

}; //XDMoD.GlobalToolbar.Profile

// -------------------------------------------------

XDMoD.GlobalToolbar.Dashboard = {
    text: 'Dashboard',
    scale: 'small',
    iconCls: 'btn_dashboard',
    id: 'global-toolbar-dashboard',
    tooltip: 'Internal Dashboard',
    handler: function () {
        XDMoD.TrackEvent("Portal", "Dashboard Button Clicked");
        CCR.xdmod.initDashboard();
    } //handler

}; //XDMoD.GlobalToolbar.Dashboard

// -------------------------------------------------

XDMoD.GlobalToolbar.SignUp = {
    text: 'Sign Up',
    tooltip: 'New User? Sign Up Today',
    scale: 'small',
    iconCls: 'signup_16',
    id: 'global-toolbar-signup',
    handler: function () {
        XDMoD.TrackEvent("Portal", "Sign Up Button Clicked");
        CCR.xdmod.ui.actionSignUp();
    }
}; //XDMoD.GlobalToolbar.SignUp

// -------------------------------------------------

XDMoD.GlobalToolbar.About = function (tabPanel) {
    return {
        text: 'About',
        tooltip: 'About',
        scale: 'small',
        iconCls: 'about_16',
        id: 'global-toolbar-about',
        handler: function () {
            XDMoD.TrackEvent("Portal", "About Button Clicked");
            Ext.History.add('#main_tab_panel:about_xdmod?XDMoD');
        }
    };
}; //XDMoD.GlobalToolbar.About

// -------------------------------------------------

XDMoD.GlobalToolbar.Roadmap = {
    text: 'Roadmap',
    iconCls: 'roadmap',
    id: 'global-toolbar-roadmap',
    handler: function() {
        Ext.History.add('#main_tab_panel:about_xdmod?Roadmap');
    }
};

XDMoD.GlobalToolbar.Contact = function () {
    var contactHandler = function(){
        XDMoD.TrackEvent('Portal', 'Contact Us -> ' + this.text + ' Button Clicked');
        switch(this.text){
            case 'Send Message':
                new XDMoD.ContactDialog().show();
                break;
            case 'Request Feature':
                new XDMoD.WishlistDialog().show();
                break;
            case 'Submit Support Request':
                new XDMoD.SupportDialog().show();
                break;
        }
    };

    return {
        text: 'Contact Us',
        tooltip: 'Contact Us',
        scale: 'small',
        iconCls: 'contact_16',
        id: 'global-toolbar-contact-us',
        menu: new Ext.menu.Menu({
            items: [{
                text: 'Send Message',
                iconCls: 'contact_16',
                id: 'global-toolbar-contact-us-send-message',
                handler: contactHandler
            },
            {
                text: 'Request Feature',
                iconCls: 'bulb_16',
                id: 'global-toolbar-contact-us-request-feature',
                handler: contactHandler
            },
            {
                text: 'Submit Support Request',
                iconCls: 'help_16',
                id: 'global-toolbar-contact-us-submit-support-request',
                handler: contactHandler
            }]
        }) //menu
    };
}; //XDMoD.GlobalToolbar.Contact

// -------------------------------------------------

XDMoD.GlobalToolbar.Help = function (tabPanel) {
    var menuItems = [
        {
            text: 'User Manual',
            iconCls: 'user_manual_16',
            id: 'global-toolbar-help-user-manual',
            handler: function () {
                if (tabPanel === undefined) {
                    window.open("user_manual.php");
                    return;
                }
                var searchTerms = tabPanel.getActiveTab().userManualSectionName;
                XDMoD.TrackEvent("Portal", "Help -> User Manual Button Clicked with " + searchTerms || "no" + " tab selected");
                window.open('user_manual.php?t=' + encodeURIComponent(searchTerms));
            }
        },
        {
            text: 'YouTube Channel',
            iconCls: 'youtube_16',
            id: 'global-toolbar-help-youtube',
            handler: function () {
                XDMoD.TrackEvent("Portal", "Help -> YouTube Channel Button Clicked");
                window.open('https://www.youtube.com/channel/UChm_AbEcBryCdIfebN5Kkrg');
            }
        }
    ];

    if (CCR.xdmod.features.xsede) {
        menuItems.splice(1, 0, {
            text: 'FAQ',
            iconCls: 'help_16',
            id: 'global-toolbar-help-faq',
            handler: function () {
                XDMoD.TrackEvent("Portal", "Help -> FAQ Button Clicked");
                window.open('faq');
            }
        });
    }

    return {
        text: 'Help',
        tooltip: 'Help',
        scale: 'small',
        id: 'help_button',
        iconCls: 'help_16',
        menu: new Ext.menu.Menu({
            items: menuItems
        }) //menu
    };

}; //XDMoD.GlobalToolbar.Help

// =====================================================================

// XDMoD.constants
//
// A namespace containing constants used across various components.
// Ideally, some of these should be defined in one place, as they are used
// by both the browser client and the server.

/**
 * The maximum length of a first name.
 */
XDMoD.constants.maxFirstNameLength = 50;

/**
 * The maximum length of a last name.
 */
XDMoD.constants.maxLastNameLength = 50;

/**
 * The maximum length of a full name.
 */
XDMoD.constants.maxNameLength = XDMoD.constants.maxFirstNameLength + XDMoD.constants.maxLastNameLength;

/**
 * The minimum length of an email address.
 */
XDMoD.constants.minEmailLength = 6;

/**
 * The maximum length of an email address.
 */
XDMoD.constants.maxEmailLength = 200;

/**
 * The minimum length of a username.
 */
XDMoD.constants.minUsernameLength = 5;

/**
 * The maximum length of a username.
 */
XDMoD.constants.maxUsernameLength = 200;

/**
 * The minimum length of a password.
 */
XDMoD.constants.minPasswordLength = 5;

/**
 * The maximum length of a password.
 */
XDMoD.constants.maxPasswordLength = 20;

/**
 * The maximum length of a report name.
 */
XDMoD.constants.maxReportNameLength = 50;

/**
 * The maximum length of a report title.
 */
XDMoD.constants.maxReportTitleLength = 50;

/**
 * The maximum length of a report header.
 */
XDMoD.constants.maxReportHeaderLength = 40;

/**
 * The maximum length of a report footer.
 */
XDMoD.constants.maxReportFooterLength = 40;

/**
 * The maximum length of a user's position (job title).
 */
XDMoD.constants.maxUserPositionLength = 200;

/**
 * The maximum length of a user's organization.
 */
XDMoD.constants.maxUserOrganizationLength = 200;

// =====================================================================

// XDMoD.regex
//
// A namespace containing commonly-used regular expressions.

/**
 * Regular expression that matches if a string contains no reserved characters.
 */
XDMoD.regex.noReservedCharacters = /^[^$^#<>":\/\\!]*$/;

/**
 * Regular expression that matches allowed username characters.
 */
XDMoD.regex.usernameCharacters = /^[a-zA-Z0-9@.\-_+\']*$/;

// XDMoD.validator
//
// A namespace for form field validator functions.

/**
 * Validate an email address.
 *
 * @param {String} email The email address to validate.
 *
 * @return {Boolean|String} True if the email is valid.  An error message if
 *                          it is not valid.
 */
XDMoD.validator.email = function (email) {
    return Ext.form.VTypes.email(email) || 'You must specify a valid email address. (e.g. user@domain.com)';
}

// =====================================================================

// XDMoD.utils
//
// A namespace containing commonly-used utility functions.

/**
 * A listener for input fields that will trim white space from their values
 * before they lose focus.
 *
 * @param Ext.form.field thisField The field being operated on.
 */
XDMoD.utils.trimOnBlur = function (thisField) {
    thisField.setValue(Ext.util.Format.trim(thisField.getValue()));
};

/**
 * Calls the syncShadow function on a component's containing window.
 *
 * This can be attached as a listener to window components that change their
 * height in a window where at least one component is using autoHeight in order
 * to fix the shadow not being redrawn on height change.
 *
 * Based on: http://www.sencha.com/forum/showthread.php?49200-How-to-invoke-panel-syncShadow-with-autoHeight-true&p=234394&viewfull=1#post234394
 *
 * @param Ext.Component thisComponent The component requesting its window sync.
 */
XDMoD.utils.syncWindowShadow = function (thisComponent) {
    thisComponent.bubble(function (currentComponent) {
        if (currentComponent instanceof Ext.Window) {
            currentComponent.syncShadow();
            return false;
        }
        return true;
    });
};

// =====================================================================

Ext.Ajax.timeout = 86400000;

CCR.xdmod.ui.tokenDelimiter = ':';

CCR.xdmod.ui.minChartScale = 0.5;
CCR.xdmod.ui.maxChartScale = 5;

CCR.xdmod.ui.deltaChartScale = 0.2;

CCR.xdmod.ui.thumbChartScale = 0.76;
CCR.xdmod.ui.thumbAspect = 3.0 / 5.0;
CCR.xdmod.ui.thumbPadding = 15.0;
CCR.xdmod.ui.scrollBarWidth = 15;

CCR.xdmod.ui.deltaThumbChartScale = 0.3;
CCR.xdmod.ui.highResScale = 2.594594594594595;

CCR.xdmod.ui.hd1280Scale = 1.72972972972973;
CCR.xdmod.ui.hd1920cale = 2.594594594594595;
CCR.xdmod.ui.print300dpiScale = 4.662162162162162;
CCR.xdmod.ui.smallChartScale = 0.61;

CCR.xdmod.ui.thumbWidth = 400;
CCR.xdmod.ui.thumbHeight = CCR.xdmod.ui.thumbWidth * CCR.xdmod.ui.thumbAspect;

CCR.xdmod.XSEDE_USER_TYPE = 700;

CCR.xdmod.UserTypes = {
    ProgramOfficer: 'po',
    CenterDirector: 'cd',
    CenterStaff: 'cs',
    CampusChampion: 'cc',
    PrincipalInvestigator: 'pi',
    User: 'usr'
};

CCR.xdmod.reporting.dirtyState = false;

CCR.xdmod.catalog = {
    metric_explorer: {},
    report_generator: {}
};

CCR.xdmod.ui.invertColor = function (hexTripletColor) {
    var color = hexTripletColor;
    color = parseInt(color, 16); // convert to integer
    color = 0xFFFFFF ^ color; // invert three bytes
    color = color.toString(16); // convert to hex
    color = ("000000" + color).slice(-6); // pad with leading zeros
    return color;
};
// ------------------------------------

// Global reference to login prompt

CCR.xdmod.ui.login_prompt = null;

// ------------------------------------

CCR.xdmod.ui.createUserManualLink = function (tags) {

    return '<div style="background-image: url(\'gui/images/user_manual.png\'); background-repeat: no-repeat; height: 36px; padding-left: 40px; padding-top: 10px">' +
            'For more information, please refer to the <a href="javascript:void(0)" onClick="CCR.xdmod.ui.userManualNav(\'' + tags + '\')">User Manual</a>' +
            '</div>';

}; //CCR.xdmod.ui.createUserManualLink

CCR.xdmod.ui.userManualNav = function (tags) {
    window.open('user_manual.php?t=' + tags);
};

CCR.xdmod.ui.shortTitle = function (name) {
    if (name.length > 50) {
        return name.substr(0, 47) + '...';
    }
    return name;
};

CCR.xdmod.ui.randomBuffer = function () {
    return 300 * Math.random();
};

CCR.ucfirst = function (str) {
    return str.toLowerCase().replace(/\b([a-z])/gi, function (c) {
        return c.toUpperCase();
    });
};

CCR.xdmod.ui.userAssumedCenterRole = function () {
    var role_id = CCR.xdmod.ui.activeRole.split(';')[0];

    return (role_id == CCR.xdmod.UserTypes.CenterDirector || role_id == CCR.xdmod.UserTypes.CenterStaff);

}; //CCR.xdmod.ui.userAssumedCenterRole

CCR.xdmod.enumAssignedResourceProviders = function () {

    var assignedResourceProviders = {};

    for (var x = 0; x < CCR.xdmod.ui.allRoles.length; x++) {
        var role_data = CCR.xdmod.ui.allRoles[x].param_value.split(':');
        var role_id = role_data[0];

        if (role_id == CCR.xdmod.UserTypes.CenterDirector || role_id == CCR.xdmod.UserTypes.CenterStaff) {
            assignedResourceProviders[role_data[1]] = CCR.xdmod.ui.allRoles[x].description.split(' - ')[1];
        }
    } //for

    return assignedResourceProviders;
}; //enumAssignedResourceProviders

CCR.xdmod.ui.createMenuCategory = function (text) {
    return new Ext.menu.TextItem({
        html: '<div style="height: 20px; vertical-align: middle; background-color: #ddd; font-weight: bold"><span style="color: #00f; position: relative; top: 4px; left: 3px">' + text + '</span></div>'
    });

}; //CCR.xdmod.ui.createMenuCategory

// -----------------------------------

/**
 * Safely decode the JSON contents of a request response.
 *
 * @param object response A response passed to a request callback.
 * @returns The decoded contents of the response, or null if unable to decode.
 */
CCR.safelyDecodeJSONResponse = function (response) {
    var responseObject = null;
    try {
        responseObject = Ext.decode(response.responseText);
    }
    catch (e) {}

    return responseObject;
};

/**
 * Check if the value returned by CCR.safelyDecodeJSONResponse indicates
 * that the request was successful.
 *
 * @param responseObject Decoded response contents.
 * @returns boolean True if response indicates success, otherwise false.
 */
CCR.checkDecodedJSONResponseSuccess = function (responseObject) {
    var responseSuccessful = false;
    try {
        responseSuccessful = responseObject.success === true;
    }
    catch (e) {}

    return responseSuccessful;
};

/**
 * Check if a request response containing JSON indicates success. (This is a
 * shortcut for calling CCR.safelyDecodeJSONResponse followed by
 * CCR.checkDecodedJSONResponseSuccess. If you wish to use contents of the
 * response other than the success indicator, use those functions directly.)
 *
 * @param object response A response passed to a request callback.
 * @returns boolean True if response indicates success, otherwise false.
 */
CCR.checkJSONResponseSuccess = function (response) {
    return CCR.checkDecodedJSONResponseSuccess(CCR.safelyDecodeJSONResponse(response));
};

// -----------------------------------

/**
 * Send a request by creating a temporary form and submitting the
 * provided values. This function will not check if the client's session
 * is alive first.
 *
 * @param string url The URL to submit the request to.
 * @param string method The request method to use.
 * @param object params A set of parameters to submit with the request.
 */
CCR.submitHiddenFormImmediately = function (url, method, params) {

    var temp = document.createElement("form");

    temp.action = url;
    temp.method = method;
    temp.style.display = "none";

    for (var param in params) {
        if(params.hasOwnProperty(param)){
            var opt = document.createElement("textarea");
            opt.name = param;
            opt.value = params[param];
            temp.appendChild(opt);
        }
    }

    document.body.appendChild(temp);
    temp.submit();
    document.body.removeChild(temp);

}; //CCR.invokePostImmediately

// -----------------------------------

/**
 * Send a POST request by creating a temporary form and submitting the
 * provided values. This function will not check if the client's session
 * is alive first.
 *
 * @param string url The URL to submit the request to.
 * @param object params A set of parameters to submit with the request.
 */
CCR.invokePostImmediately = function (url, params) {
    CCR.submitHiddenFormImmediately(url, "POST", params);
};

// -----------------------------------

/**
 * Send a request by creating a temporary form and submitting the
 * provided values. This function will first check if the client's
 * session is alive and will only submit the form if it is.
 *
 * @param string url The URL to submit the request to.
 * @param string method The request method to use.
 * @param object params A set of parameters to submit with the request.
 * @param object options (Optional) A set of options, including:
 *        boolean checkDashboardUser Check the dashboard user session instead
 *                                   of the main user session. (Default: false)
 */
CCR.submitHiddenForm = function (url, method, params, options) {

    options = options || {};

    var checkDashboardUser = typeof options.checkDashboardUser === "undefined" ? false : options.checkDashboardUser;
    var checkUrlPrefix = checkDashboardUser ? "../" : "";

    Ext.Ajax.request({
        url: checkUrlPrefix + "controllers/user_auth.php",
        params: {
            operation: "session_check",
            public_user: typeof params.public_user === "undefined" ? false : params.public_user,
            session_user_id_type: checkDashboardUser ? "Dashboard" : ""
        },

        callback: function (options, success, response) {
            if (success) {
                success = CCR.checkJSONResponseSuccess(response);
            }

            if (success) {
                CCR.submitHiddenFormImmediately(url, method, params);
            } else {
                CCR.xdmod.ui.presentFailureResponse(response);
            }
        }
    });

}; //CCR.invokePost

// -----------------------------------

/**
 * Send a POST request by creating a temporary form and submitting the
 * provided values. This function will first check if the client's
 * session is alive and will only submit the form if it is.
 *
 * @param string url The URL to submit the request to.
 * @param object params A set of parameters to submit with the request.
 * @param object options (Optional) A set of options, including:
 *        boolean checkDashboardUser Check the dashboard user session instead
 *                                   of the main user session. (Default: false)
 */
CCR.invokePost = function (url, params, options) {
    CCR.submitHiddenForm(url, "POST", params, options);
};

// -----------------------------------

CCR.xdmod.ui.AssistPanel = Ext.extend(Ext.Panel, {

    layout: 'fit',
    margins: '2 2 2 0',
    bodyStyle: {
        overflow: 'auto'
    },
    initComponent: function () {

        var self = this;

        self.html = '<div class="x-grid-empty">';

        if (self.headerText) {
            self.html += '<b style="font-size: 150%">' + self.headerText + '</b><br/><br/>';
        }
        if (self.subHeaderText) {
            self.html += self.subHeaderText + '<br/><br/>';
        }
        if (self.graphic) {
            self.html += '<img src="' + self.graphic + '"><br/><br/>';
        }
        if (self.userManualRef) {
            self.html += CCR.xdmod.ui.createUserManualLink(self.userManualRef);
        }

        self.html += '</div>';
        CCR.xdmod.ui.AssistPanel.superclass.initComponent.call(this);
    } //initComponent
}); //CCR.xdmod.ui.AssistPanel

// -----------------------------------

CCR.WebPanel = Ext.extend(Ext.Window, {
    onRender: function () {
        this.bodyCfg = {
            tag: 'iframe',
            src: this.src,
            cls: this.bodyCls,
            style: {
                border: '0px none'
            }
        };

        if (this.frameid) {
            this.bodyCfg.id = this.frameid;
        }

        CCR.WebPanel.superclass.onRender.apply(this, arguments);

    }, //onRender

    // -----------------------------

    initComponent: function () {
        CCR.WebPanel.superclass.initComponent.call(this);
    } //initComponent

}); //CCR.WebPanel

// -----------------------------------

CCR.xdmod.sponsor_message = 'This work was sponsored by NSF under grant number OCI 1025159';

//Used in html/gui/general/login.php
var toggle_about_footer = function (o) {
    o.innerHTML = (o.innerHTML == CCR.xdmod.version) ? CCR.xdmod.sponsor_message : CCR.xdmod.version;
}; //toggle_about_footer

CCR.BrowserWindow = Ext.extend(Ext.Window, {
    modal: true,
    resizable: false,
    closeAction: 'hide',
    versionStamp: false, // Set to 'true' during instantiation to display version stamp
    // in lower-left region of window (left of bbar)
    onRender: function () {
        this.bodyCfg = {
            tag: 'iframe',
            src: this.src,
            cls: this.bodyCls,
            style: {
                border: '0px none'
            }
        };
        if (this.frameid) {
            this.bodyCfg.id = this.frameid;
        }
        CCR.BrowserWindow.superclass.onRender.apply(this, arguments);
    }, //onRender
    initComponent: function () {
        var self = this;
        var window_items = [];

        if (self.nbutton) {
            window_items.push(self.nbutton);
        }

        if (self.versionStamp) {
            window_items.push({
                xtype: 'tbtext',
                html: '<span style="color: #000; cursor: default" onClick="toggle_about_footer(this)">' + CCR.xdmod.sponsor_message + '</span>'
            });
        }
        window_items.push('->');
        window_items.push(
            new Ext.Button({
                text: 'Close',
                iconCls: 'general_btn_close',
                handler: function () {
                    if (self.closeAction == 'close'){
                        self.close();
                    }
                    else {
                        self.hide();
                    }
                }
            })
        );

        Ext.apply(this, {
            bbar: {
                items: window_items
            }
        });

        CCR.BrowserWindow.superclass.initComponent.call(this);
    } //initComponent
}); //CCR.BrowserWindow

// -----------------------------------

var logoutCallback = function () {
    location.href = 'index.php';
};

CCR.xdmod.ui.actionLogout = function () {
    XDMoD.TrackEvent("Portal", "logout link clicked");
    XDMoD.REST.Call({
        action: 'auth/logout',
        method: 'POST',
        callback: logoutCallback
    });
}; //actionLogout


// Used in html/gui/general/login.php
var presentLoginResponse = function (message, status, target, cb) {
    var messageColor = status ? '#080' : '#f00';
    var targetCmp = Ext.getCmp(target);

    targetCmp.update('<p style="color:' + messageColor + '">' + message + '</p>');
    targetCmp.show();

    if (cb) {
        cb();
    }
}; //presentLoginResponse

// Used in html/gui/general/login.php
var clearLoginResponse = function (target) {

    var targetCmp = Ext.getCmp(target);
    targetCmp.hide();
}; //clearLoginResponse

// Used in html/gui/general/login.php
var presentContactFormViaLoginError = function () {
    XDMoD.TrackEvent('Login Window', 'Clicked on Conact Us');
    CCR.xdmod.ui.login_prompt.close();

    var contact = new XDMoD.ContactDialog();
    contact.show();
}; //presentContactFormViaLoginError

// Used in html/gui/general/login.php
var presentSignUpViaLoginPrompt = function () {

    XDMoD.TrackEvent('Login Window', 'Clicked on Sign Up button');
    CCR.xdmod.ui.login_prompt.close();
    CCR.xdmod.ui.actionSignUp();

}; //presentSignUpViaLoginPrompt

// -----------------------------------

CCR.xdmod.ui.actionSignUp = function () {

    var wndSignup = new XDMoD.SignUpDialog();
    wndSignup.show();

}; //CCR.xdmod.ui.actionSignUp

// -----------------------------------

CCR.xdmod.ui.FadeInWindow = Ext.extend(Ext.Window, { //experimental
    animateTarget: true,
    setAnimateTarget: Ext.emptyFn,
    animShow: function () {
        this.el.fadeIn({
            duration: 0.55,
            callback: this.afterShow.createDelegate(this, [true], false),
            scope: this
        });
    },
    animHide: function () {
        if (this.el.shadow) {
            this.el.shadow.hide();
        }
        this.el.fadeOut({
            duration: 0.55,
            callback: this.afterHide,
            scope: this
        });
    }
});


CCR.xdmod.ui.actionLogin = function (config, animateTarget) {

    XDMoD.TrackEvent("Portal", "Sign In link clicked");

    var width = 280,
        promptHeight = 348,
        iframeHeight = 318;
    // Adjsut window size depending on if the XSEDE login is enabled.
    if (CCR.xdmod.features.xsede || CCR.xdmod.isFederationConfigured) {
        width = 540;
        promptHeight = 486;
        iframeHeight = 500;
    }
    //reset referer
    XDMoD.referer = document.location.hash;

    CCR.xdmod.ui.login_prompt = new Ext.Window({
        title: "Welcome To XDMoD",
        width: width,
        height: promptHeight,
        modal: true,
        animate: true,
        resizable: false,
        tbar: {
            items: [{
                xtype: 'tbtext',
                html: '<span style="color: #000">Close this window to view public information</span>'
            }]
        },
        items: [
            new Ext.Panel({
                id: 'wnd_login',
                layout: 'fit',
                html: '<iframe src="gui/general/login.php" frameborder=0 width=100% height=' + iframeHeight + '></iframe>'
            })
        ],
        listeners: {
            close: function () {
                XDMoD.TrackEvent('Login Window', 'Closed Window');
            } //close
        } //listeners
    }); //CCR.xdmod.ui.login_prompt

    CCR.xdmod.ui.login_prompt.show(animateTarget);
    CCR.xdmod.ui.login_prompt.center();
}; //actionLogin

// -----------------------------------

CCR.xdmod.ControllerBase = "controllers/";

// -----------------------------------

// For handling the cases where a UI element is bound to a datastore, and usage of that UI element alone determines when
// that data store reloads

CCR.xdmod.ControllerUIDataStoreHandler = function (activeStore) {
    CCR.xdmod.ControllerResponseHandler('{"status" : "' + activeStore.reader.jsonData.status + '"}', null);
};

// -----------------------------------

CCR.xdmod.ControllerResponseHandler = function (responseText, targetStore) {

    var responseData = Ext.decode(responseText);

    if (responseData.status == 'not_logged_in') {
        var newPanel = new XDMoD.LoginPrompt();
        newPanel.show();
        return false;
    }

    if (targetStore == null) {
        return true;
    }
    targetStore.loadData(responseData);

}; //CCR.xdmod.ControllerResponseHandler

// -----------------------------------

CCR.xdmod.ControllerProxy = function (targetStore, parameters) {

    if (parameters.operation == null) {
        Ext.MessageBox.alert('Controller Proxy', 'An operation must be specified');
        return;
    }

    Ext.Ajax.request({
        url: targetStore.url,
        method: 'POST',
        params: parameters,
        timeout: 60000, // 1 Minute,
        async: false,
        success: function (response) {
            CCR.xdmod.ControllerResponseHandler(response.responseText, targetStore);
        },
        failure: function () {
            Ext.MessageBox.alert('Error', 'There has been a request error');
        }
    });
}; //CCR.xdmod.ControllerProxy

// -----------------------------------

CCR.xdmod.initDashboard = function () {

    // Opening the window before the AJAX request is necessary to prevent
    // it being treated as a popup. Solution from: http://stackoverflow.com/a/20822754
    var dashboardWindow = window.open("", "_blank");
    dashboardWindow.focus();

    Ext.Ajax.request({
        url: 'controllers/dashboard_launch.php',
        method: 'POST',
        callback: function (options, success, response) {
            if (success && CCR.checkJSONResponseSuccess(response)) {
                dashboardWindow.location.href = 'internal_dashboard';
            }
            else {
                dashboardWindow.close();
                window.focus();
                CCR.xdmod.ui.presentFailureResponse(response, {
                    title: 'XDMoD Dashboard'
                });
            }
        }
    });
}; //CCR.xdmod.initDashboard

// -----------------------------------

CCR.xdmod.ui.generalMessage = function (msg_title, msg, success, show_delay) {
    show_delay = show_delay || 2000;

    var styleColor = success ? '#080' : '#f00';

    var x_offset = -1 * ((Ext.getBody().getViewSize().width - 300) / 2);
    var y_offset = -1 * ((Ext.getBody().getViewSize().height - 40) / 2);

    new Ext.ux.window.MessageWindow({
        title: msg_title,
        width: 300,
        html: '<b style="color: ' + styleColor + '">' + msg + '</b>' || 'No information available',
        origin: {
            offY: y_offset,
            offX: x_offset
        },
        iconCls: '',
        autoHeight: true,
        draggable: false,
        help: false,
        hideFx: {
            delay: show_delay,
            mode: 'standard'
        }
    }).show(Ext.getDoc());
}; //CCR.xdmod.ui.generalMessage

// -----------------------------------

CCR.xdmod.ui.userManagementMessage = function (msg, success) {

    var styleColor = success ? '#080' : '#f00';

    var x_offset = -1 * ((Ext.getBody().getViewSize().width - 300) / 2);
    var y_offset = -1 * ((Ext.getBody().getViewSize().height - 40) / 2);

    new Ext.ux.window.MessageWindow({
        title: 'User Management',
        width: 300,
        html: '<b style="color: ' + styleColor + '">' + msg + '</b>' || 'No information available',
        origin: {
            offY: y_offset,
            offX: x_offset
        },
        iconCls: 'user_management_message_prompt',
        autoHeight: true,
        draggable: false,
        help: false,
        hideFx: {
            delay: 2000,
            mode: 'standard'
        }

    }).show(Ext.getDoc());
}; //CCR.xdmod.ui.userManagementMessage

// -----------------------------------

CCR.xdmod.ui.reportGeneratorMessage = function (title, msg, success, callback) {

    var styleColor = success ? '#080' : '#f00';
    var x_offset = -1 * ((Ext.getBody().getViewSize().width - 200) / 2);
    var y_offset = -1 * ((Ext.getBody().getViewSize().height - 40) / 2);

    new Ext.ux.window.MessageWindow({
        title: title || 'You have clicked:',
        id: 'report_generator_message',
        html: '<b style="color: ' + styleColor + '">' + msg + '</b>' || 'No information available',
        origin: {
            offY: y_offset,
            offX: x_offset
        },
        iconCls: 'report_generator_message_prompt',
        autoHeight: true,
        draggable: false,
        help: false,
        hideFx: {
            delay: 3000,
            mode: 'standard'
        },
        listeners: {
            hide: function () {
                if (callback) {
                    callback();
                }
            }
        }
    }).show(Ext.getDoc());

}; //CCR.xdmod.ui.reportGeneratorMessage

// -----------------------------------

CCR.xdmod.ui.toastMessage = function (title, msg) {
    if (CCR.xdmod.ui.isDeveloper) {
        new Ext.ux.window.MessageWindow({
            title: title || 'You have clicked:',
            html: msg || 'No information available',
            origin: {
                offY: -5,
                offX: -5
            },
            autoHeight: true,
            iconCls: 'load_time_message_prompt',
            help: false,
            hideFx: {
                delay: 1000,
                mode: 'standard'
            }
        }).show(Ext.getDoc());
    }
};

// -----------------------------------

/**
 * Attempt to extract an error message from a response object.
 *
 * @param  {object}  response   A response object that was passed to a callback of
 *                              an Ext.data.Connection (Ext.AJAX, etc.) request.
 * @param  {object}  options    (Optional) A set of optional options, including:
 *         {boolean} htmlEncode HTML encode the response message for safe
 *                              display on a page. (Default: true)
 * @return {mixed}              The message contained in the response, or null if
 *                              not found.
 */
CCR.xdmod.ui.extractErrorMessageFromResponse = function (response, options) {

    // Set default values for unused, optional parameters.
    options = typeof options === "undefined" ? {} : options;

    options.htmlEncode = typeof options.htmlEncode === "undefined" ? true : options.htmlEncode;

    var responseMessage = null;
    var responseObject = response;

    try {
        if (response.responseText) {
            responseObject = Ext.decode(response.responseText);
        }
    }
    catch (e) {}

    try {
        responseMessage = responseObject.message || responseObject.status || responseMessage;
    }
    catch (e) {}

    if (options.htmlEncode) {
        responseMessage = Ext.util.Format.htmlEncode(responseMessage);
    }

    return responseMessage;
};

// -----------------------------------

/**
 * Display a generic alert box using the contents of a response object.
 * A box will not be displayed if the response represents an exception and
 * that exception has been handled globally.
 *
 * @param object response A response object that was passed to a callback of
 *                        an Ext.data.Connection (Ext.AJAX, etc.) request.
 * @param object options (Optional) A set of optional settings, including:
 *        string title A title to use in the alert box.
 *        string wrapperMessage A user-friendly message to precede the
 *                              contents of the response.
 */
CCR.xdmod.ui.presentFailureResponse = function (response, options) {

    // Set default values for unused, optional parameters.
    options = typeof options === "undefined" ? {} : options;

    var title = typeof options.title === "undefined" ? "Error" : options.title;

    // If this response was already handled globally, stop and do nothing.
    if (response.exceptionHandledGlobally) {
        return;
    }

    // Attempt to extract an error message from the response object.
    var responseMessage = CCR.xdmod.ui.extractErrorMessageFromResponse(response);
    if (responseMessage === null) {
        responseMessage = "Unknown Error";
    }

    // If a user-friendly message was given, add it to the displayed message.
    var outputMessage;
    if (options.wrapperMessage) {
        outputMessage = options.wrapperMessage + " (" + responseMessage + ")";
    } else {
        outputMessage = responseMessage;
    }

    // Display the message in an Ext message box.
    Ext.MessageBox.alert(title, outputMessage);
};

// -----------------------------------

CCR.xdmod.ui.intersect = function (a, b) {
    var result = [];
    for (var i = 0; i < a.length; i++) {
        for (var j = 0; j < b.length; j++) {
            if (a[i] == b[j]) {
                result.push(a[i]);
            }
        }
    }
    return result;
};

CCR.xdmod.ui.getComboBox = function (data, fields, valueField, displayField, editable, emptyText) {
    return new Ext.form.ComboBox({
        typeAhead: true,
        triggerAction: 'all',
        lazyRender: true,
        mode: 'local',
        emptyText: emptyText,
        editable: editable,
        store: new Ext.data.ArrayStore({
            id: 0,
            fields: fields,
            data: data
        }),
        valueField: valueField,
        displayField: displayField
    });
};
CCR.xdmod.ui.gridComboRenderer = function (combo) {
    return function (value) {
        var idx = combo.store.find(combo.valueField, value);
        var rec = combo.store.getAt(idx);
        if (!rec) {
            return combo.emptyText;
        }
        return rec.get(combo.displayField);
    };
};

CCR.isBlank = function (value) {
    return !value || value === 'undefined' || !value.trim() ? true: false;
};

CCR.Types = {};
CCR.Types.String = '[object String]';
CCR.Types.Number = '[object Number]';
CCR.Types.Array = '[object Array]';
CCR.Types.Object = '[object Object]';
CCR.Types.Null = '[object Null]';
CCR.Types.Undefined = '[object Undefined]';
CCR.Types.Function = '[object Function]';

CCR.isType = function (value, type) {
    if (typeof type === 'string') {
        return Object.prototype.toString.call(value) === type;
    } else {
        return Object.prototype.toString.call(value) ===
                Object.prototype.toString.call(type);
    }
};

CCR.exists = function (value) {
    if (CCR.isType(value, '[object String]') && value === '') {
        return false;
    }
    if (value === undefined || value === null) {
        return false;
    }
    return true;
};

CCR.merge = function (obj1, obj2) {
    var obj3 = {};
    for (var attrname1 in obj1) {
        if(obj1.hasOwnProperty(attrname1)){
            obj3[attrname1] = obj1[attrname1];
        }
    }
    for (var attrname in obj2) {
        if(obj2.hasOwnProperty(attrname)){
            obj3[attrname] = obj2[attrname];
        }
    }
    return obj3;
};
CCR.getParameter = function (name, source) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(source);
    return results === null
            ? ""
            : decodeURIComponent(results[1].replace(/\+/g, " "));
};
/*
 * Process the location hash string. The string should have the form:
 *  PATH?PARAMS
 *
 * with an optional # prefix. PATH is a ':' delimited path leading to a component of the form
 *   ROOT:TAB:SUBTAB
 *
 *  If the path only contains one entry then it is treated as
 *   TAB
 */
CCR.tokenize = function (hash) {
    var raw = (typeof hash !== 'string')
      ? String(hash)
      : hash;

    var matches = raw.match(/^#?(([^:\\?]*):?([^:\\?]*):?([^:\\?]*)\??(.*))/);

    var tokens = {
        raw: raw,
        content: matches[1],
        root: matches[2],
        tab: matches[3],
        subtab: matches[4],
        params: matches[5]
    };
    if (tokens.tab === '') {
        // Support for legacy path syntax
        tokens.tab = tokens.root;
        tokens.root = '';
    }
    return tokens;
};

/**
 * Helper function that converts a string to an array of items. It does this
 * based on the provided delims array. For each delimiter provided there will be
 * one level of arrays in the result. So for example:
 * value = "key1=value1&key2=value2",
 * delims = ['&', '=']
 *
 * would result in:
 * [
 *   ["key1", "value1"],
 *   ["key2", "value2"]
 * ];
 *
 * @param {String} value
 * @param {Array} delims
 * @returns {Array}
 */
CCR.toArray = function(value, delims) {
    if (!CCR.exists(value) || !CCR.isType(value, CCR.Types.String) || value.length < 1) {
        return [];
    }

    delims = delims || ['&', '='];

    if (delims.length < 1) {
        return [value];
    }

    var results = [];
    var temp = value.split(delims[0]);
    if (delims.length > 1) {
        for (var i = 1; i < delims.length; i++) {
            var delim = delims[i];
            for (var j = 0; j < temp.length; j++) {
                var entry = temp[j];
                if (entry && entry.indexOf(delim) >= 0) {
                    results.push(entry.split(delim));
                }
            }
        }
    }
    return results;
};

CCR.objectToArray = function(object) {
    if (!CCR.exists(object)) {
        return [];
    }
    var results;
    for (var property in object) {
        if(object.hasOwnProperty(property)) {
            results = [property, object[property]];
        }
    }
    return results;
};

CCR.join = function(values, joiners) {

    if (!CCR.exists(values) || !CCR.isType(values, CCR.Types.Array)) {
        return "";
    }

    joiners = joiners || ['&', '='];

    if (values.length < 1) {
        return "";
    }
    if (joiners.length < 1) {
        return values.join('');
    }

    var joinerIndex = 0;
    var result;

    var joinValue = function(value, joiners, joinerIndex) {
        var isArray = CCR.isType(value, CCR.Types.Array);
        var holdsArrays = isArray && value.length > 0 && CCR.isType(value[0], CCR.Types.Array);
        var result = [];
        if (isArray && holdsArrays) {
            for (var i = 0; i < value.length; i++) {
                result.push(joinValue(value[i], joiners, joinerIndex + 1));
            }
        } else if (isArray && !holdsArrays) {
            result = value;
        } else {
            result.push(value, joiners[joinerIndex]);
        }
        return result.join(joiners[joinerIndex]);
    };

    result = joinValue(values, joiners, joinerIndex);

    return result;
};

CCR.STR_PAD_LEFT = 1;
CCR.STR_PAD_RIGHT = 2;
CCR.STR_PAD_BOTH = 3;

CCR.pad = function (str, len, pad, dir) {

    len = len || 0;
    pad = pad || ' ';
    dir = dir || CCR.STR_PAD_RIGHT;

    if (len + 1 >= str.length) {

        switch (dir) {
            case CCR.STR_PAD_LEFT:
                str = Array(len + 1 - str.length).join(pad) + str;
                break;
            case CCR.STR_PAD_BOTH:
                var right = Math.ceil((len = len - str.length) / 2);
                var left = len - right;
                str = Array(left + 1).join(pad) + str + Array(right + 1).join(pad);
                break;
            default:
                str = str + Array(len + 1 - str.length).join(pad);
                break;
        } // switch
    }
    return str;
};

CCR.deepEncode = function(values, options) {
    if (!CCR.exists(values)) {
        return '';
    }

    options = options || {};
    var delim = options.delim || '&';
    var left = options.left || '';
    var right = options.right || '';

    if (CCR.isType(values, CCR.Types.Array)) {
        return CCR._encodeArray(values, {
            delim: delim,
            left: left,
            right: right
        });
    } else if (CCR.isType(values, CCR.Types.Object)) {
        return CCR._encodeObject(values, {
            delim: delim,
            left: left,
            right: right
        });
    } else {
        return '';
    }
};

CCR._encodeArray = function(values, options) {
    if (!CCR.exists(values)) {
        return JSON.stringify([]);
    }
    options = options || {};
    var delim = options.delim || ',';
    var left = options.left || '[';
    var right = options.right || ']';
    var results = [];

    for (var i = 0; i < values.length; i++) {
        var value = values[i];
        if (CCR.isType(value, CCR.Types.Array)) {
            results.push(CCR._encodeArray(value, options));
        } else if (CCR.isType(value, CCR.Types.Object)) {
            results.push(CCR._encodeObject(value, options));
        } else {
            var isNumber = CCR.isType(value, CCR.Types.Number);
            var encoded = isNumber ? value : '"' + value + '"';
            results.push(encoded);
        }
    }
    return (left + results.join(delim) + right).trim();
};

CCR._encodeObject = function(value, options) {
    if (!CCR.exists(value)){
        return JSON.stringify({});
    }
    options = options || {};
    var delim = options.delim || ',';
    var left = options.left || '{';
    var right = options.right || '}';
    var wrap = options.wrap || false;
    var separator = options.seperator || '=';
    var results = [];

    for (var property in value ) {
        if(value.hasOwnProperty(property)){
            var propertyValue = value[property];
            if (CCR.isType(propertyValue, CCR.Types.Array)) {
                results.push(property + '=' + encodeURIComponent(CCR._encodeArray(propertyValue, {
                            wrap: true,
                            seperator: ':'
                        })));
            } else if (CCR.isType(propertyValue, CCR.Types.Object)) {
                results.push(property + '=' + encodeURIComponent(CCR._encodeObject(propertyValue, {
                            wrap: true
                        })));
            } else {
                var key = wrap ? '"' + property + '"' : property;
                results.push(key + separator + propertyValue);
            }
        }
    }
    return (left + results.join(delim) + right).trim();
};

/**
 * Encode the provided Array of values for inclusion as query parameters.
 *
 * @param {Object} values
 */
CCR.encode = function (values) {
    if (CCR.exists(values)) {
        var parameters = [];
        for (var property in values) {
            if (values.hasOwnProperty(property)) {
                var isArray = CCR.isType(values[property], CCR.Types.Array);
                var isObject = CCR.isType(values[property], CCR.Types.Object);
                var value = isArray || isObject
                        ? encodeURIComponent(CCR.deepEncode(values[property]))
                        : values[property];
                parameters.push(property + '=' + value);
            }
        }
        return parameters.join('&');
    }
    return undefined;
};

/**
 * Apply implements an immutable merge (ie. returns a new object containing
 * the properties of both objects.) of two javascript objects,
 * lhs ( left hand side) and rhs ( right hand side). A property that exists
 * in both lhs and rhs will default to the value of rhs.
 *
 * @param {Object} lhs Left hand side of the merge.
 * @param {Object} rhs Right hand side of the merge.
 *
 **/
CCR.apply = function (lhs, rhs) {
    if (typeof lhs === 'object' && typeof rhs === 'object') {
        var results = {};
        for (var property in lhs) {
            if (lhs.hasOwnProperty(property)) {
                results[property] = lhs[property];
            }
        }
        for (property in rhs) {
            if (rhs.hasOwnProperty(property)) {
                var rhsExists = rhs[property] !== undefined
                        && rhs[property] !== null;
                if (rhsExists) {
                    results[property] = rhs[property];
                }
            }
        }
        return results;
    }
    return lhs;
};

CCR.toInt = function (value) {
    var isType = CCR.isType;
    var result;
    if (isType(value, CCR.Types.Number)) {
        result = value;
    } else if (isType(value, CCR.Types.String)) {
        result = parseInt(value);
    } else {
        result = value;
    }
    return result;
};

/**
 * Displays a MessageBox to the user with the error styling.
  *
 * @param {String}   title   of the Error Dialog Box.
 * @param {String}   message that will be displayed to the user.
 * @param {Function} success function that will be executed if the user does not
 *                           click 'no' or 'cancel'.
 * @param {Function} failure function that will be executed if the user does
 *                           click 'no' or 'cancel'.
 * @param {Object}   buttons the buttons that will
 */
CCR.error = function (title, message, success, failure, buttons) {
    buttons = buttons || Ext.MessageBox.OK;
    success = success || function(){};
    failure = failure || function(){};

    Ext.MessageBox.show({
        title: title,
        msg: message,
        buttons: buttons,
        icon: Ext.MessageBox.ERROR,
        fn: function(buttonId, text, options) {
            var compare = CCR.compare;
            if (compare.strings(buttonId, Ext.MessageBox.buttonText['no'])
                    || compare.strings(buttonId, Ext.MessageBox.buttonText['cancel'])) {
                failure(buttonId, text, options);
            } else {
                success(buttonId, text, options);
            }
        }
    });
};

CCR.compare = {
    method: {
        String: {
            LowerCase: 'toLowerCase',
            UpperCase: 'toUpperCase',
            None: 'toString'
        }
    },
    strings: function(left, right, method) {
        if (!CCR.exists(left) || !CCR.exists(right)) {
            return false;
        }
        method = method || CCR.compare.method.String.LowerCase;
        return left[method]() === right[method]();
    }
};

/**
 * Retrieve a reference to the static ( namespaced ) JavaScript property
 * identified by the 'instancePath' argument that will be an instance of the class
 * identified by the 'classPath' argument constructed with 'config' as a
 * constructor argument, if provided.
 * Example:
 *   CCR.getInstance(
 *   'CCR.xdmod.ui.jobViewer', // <- static namespace where an instance of 'classPath' should live.
 *   'XDMoD.Module.JobViewer', // <- if nothing is found @ 'instancePath' then use this so there is.
 *   {
 *      id: 'job_viewer',
 *      title: 'Job Viewer',   // <- If we're invoking 'classPath' then use this as a configuration object.
 *      ... etc. etc.
 *   });
 *
 * @param {String} instancePath    the namespace to retrieve / assign the newly
 *                                 created instance of classPath to.
 * @param {String} classPath       the namespace path of the class to
 *                                 create should the 'create' parameter be set
 *                                 to true.
 * @param {Object} [config=null]   the configuration constructor param used
 *                                 when instantiating 'classPath'.
 * @return {*} either the value reference by 'instancePath' if it exists
 *             or an instance of 'classPath' instantiated with 'config'
 *             as a constructor argument.
 **/
CCR.getInstance = function(instancePath, classPath, config) {
    if ( !instancePath || typeof instancePath !== 'string' ) {
        return;
    }
    if ( !classPath || typeof classPath !== 'string' ) {
        return;
    }

    /**
     * Walk the provided 'path' parameter, executing the provided callback
     * at each index. The callback has the following parameters:
     *   - previousValue: The value previously returned in the last invocation
     *   of the callback, or if this is the first invocation, 'window'.
     *   - currentValue:  The current element being processed.
     *
     * @param {String}   path     the '.' delimited string to be walked.
     * @param {Function} callback the function to be executed for each split
     *                            item.
     *
     * @return {*} the result of walking the provided 'path'.
     **/
    var getReference = function(path, callback) {
            callback = callback !== undefined
                ? callback
                : function(previous, current) {
                    return previous[current];
                };

        return path.split('.').reduce( callback, window );
    };

    /**
     * Attempt to invoke the result of walking the provided 'classPath'.
     * 'config' is passed as a parameter to this invocation. If the result of
     * walking the 'classPath' is not a function then instead of invoking it
     * it is itself, returned.
     *
     * @param {String} classPath          the '.' delimited string of the
     *                                    'class' that is to be instantiated.
     * @param {Object} [config=undefined] an object that will be passed to the
     *                                    invocation of 'classPath' should it
     *                                    be found.
     *
     * @return {*} The return value of invoking 'classPath' or, failing that,
     *             the value of 'classPath'.
     **/
    var instantiateClass = function(classPath, config) {
        var Class = getReference(classPath);
        return typeof Class === 'function' ? new Class(config) : Class;
    };

    var result = getReference(instancePath);
    if (!result) {
        result = getReference(
            instancePath,
            function(previous, current) {
                if ( previous[current] === undefined ) {
                    return previous[current] = instantiateClass(classPath, config);
                } else {
                    return previous[current];
                }
            }
        );
    }
    return result;
};


// override 3.4.0 to be able to restore column state
Ext.override(Ext.grid.ColumnModel, {
    setState: function (col, state) {
        state = Ext.applyIf(state, this.defaults);
        if (this.columns && this.columns[col]) {
            Ext.apply(this.columns[col], state);
        } else if (this.config && this.config[col]) {
            Ext.apply(this.config[col], state);
        }
    }
});

// override 3.4.0 to fix layout bug with composite fields (field width too narrow)
Ext.override(Ext.form.TriggerField, {
    onResize: function (w, h) {
        Ext.form.TriggerField.superclass.onResize.call(this, w, h);
        var tw = this.getTriggerWidth();
        if (Ext.isNumber(w)) {
            this.el.setWidth(w - tw);
        }
        if (this.rendered && !this.readOnly && this.editable && !this.el.getWidth()) {
            this.wrap.setWidth(w);
        }
        else {
            this.wrap.setWidth(this.el.getWidth() + tw);
        }
    }
});

// override 3.4.0 to:
// * fix issue with tooltip text wrapping in IE9 (tooltip 1 pixel too narrow)
// JS: I suspect this issue is caused by subpixel rendering in IE9 causing bad measurements
// * allow beforeshow to cancel a tooltip
Ext.override(Ext.Tip, {
    doAutoWidth: function (adjust) {
        // next line added to allow beforeshow to cancel tooltip (see below)
        if (!this.body) {
            return;
        }
        adjust = adjust || 0;
        var bw = this.body.getTextWidth();
        if (this.title) {
            bw = Math.max(bw, this.header.child('span').getTextWidth(this.title));
        }
        bw += this.getFrameWidth() + (this.closable ? 20 : 0) + this.body.getPadding("lr") + adjust;
        // added this line:
        if (Ext.isIE9) {
            bw += 1;
        }
        this.setWidth(bw.constrain(this.minWidth, this.maxWidth));

        if (Ext.isIE7 && !this.repainted) {
            this.el.repaint();
            this.repainted = true;
        }
    }
});

// override 3.4.0 to allow beforeshow to cancel the tooltip
Ext.override(Ext.ToolTip, {
    show: function () {
        if (this.anchor) {
            this.showAt([-1000, -1000]);
            this.origConstrainPosition = this.constrainPosition;
            this.constrainPosition = false;
            this.anchor = this.origAnchor;
        }
        this.showAt(this.getTargetXY());

        if (this.anchor) {
            this.anchorEl.show();
            this.syncAnchor();
            this.constrainPosition = this.origConstrainPosition;
            // added "if (this.anchorEl)"
        } else if (this.anchorEl) {
            this.anchorEl.hide();
        }
    },
    showAt: function (xy) {
        this.lastActive = new Date();
        this.clearTimers();
        Ext.ToolTip.superclass.showAt.call(this, xy);
        if (this.dismissDelay && this.autoHide !== false) {
            this.dismissTimer = this.hide.defer(this.dismissDelay, this);
        }
        if (this.anchor && !this.anchorEl.isVisible()) {
            this.syncAnchor();
            this.anchorEl.show();
            // added "if (this.anchorEl)"
        } else if (this.anchorEl) {
            this.anchorEl.hide();
        }
    }
});

// override 3.4.0 to ensure that the grid stops editing if the view is refreshed
// actual bug: removing grid lines with active lookup editor didn't hide editor
Ext.grid.GridView.prototype.processRows =
        Ext.grid.GridView.prototype.processRows.createInterceptor(function () {
            if (this.grid) {
                this.grid.stopEditing(true);
            }
        });

// override 3.4.0 to fix issue with chart labels losing their labelRenderer after hide/show
Ext.override(Ext.chart.CartesianChart, {
    createAxis: function (axis, value) {
        var o = Ext.apply({}, value),
                ref,
                old;

        if (this[axis]) {
            old = this[axis].labelFunction;
            this.removeFnProxy(old);
            this.labelFn.remove(old);
        }
        if (o.labelRenderer) {
            ref = this.getFunctionRef(o.labelRenderer);
            o.labelFunction = this.createFnProxy(function (v) {
                return ref.fn.call(ref.scope, v);
            });
            // delete o.labelRenderer; // <-- commented out this line
            this.labelFn.push(o.labelFunction);
        }
        if (axis.indexOf('xAxis') > -1 && o.position == 'left') {
            o.position = 'bottom';
        }
        return o;
    }
});

// override 3.4.0 to allow tabbing between editable grid cells to work correctly
Ext.override(Ext.grid.RowSelectionModel, {
    acceptsNav: function (row, col, cm) {
        if (!cm.isHidden(col) && cm.isCellEditable(col, row)) {
            // check that there is actually an editor
            if (cm.getCellEditor) {
                return !!cm.getCellEditor(col, row);
            }
            return true;
        }
        return false;
    }
});

// override to allow menu items to have a tooltip property
Ext.override(Ext.menu.Item, {
    onRender : function(container, position){
        if (!this.itemTpl) {
            this.itemTpl = Ext.menu.Item.prototype.itemTpl = new Ext.XTemplate(
                '<a id="{id}" class="{cls} x-unselectable" hidefocus="true" unselectable="on" href="{href}"',
                '<tpl if="hrefTarget">',
                ' target="{hrefTarget}"',
                '</tpl>',
                '>',
                '<img src="{icon}" class="x-menu-item-icon {iconCls}"/>',
                '<span class="x-menu-item-text">{text}</span>',
                '</a>'
                );
        }
        var a = this.getTemplateArgs();
        this.el = position ? this.itemTpl.insertBefore(position, a, true) : this.itemTpl.append(container, a, true);
        this.iconEl = this.el.child('img.x-menu-item-icon');
        this.textEl = this.el.child('.x-menu-item-text');
        if (this.tooltip) {
            this.tooltip = new Ext.ToolTip(Ext.apply({
                target: this.el
            }, Ext.isObject(this.tooltip) ? this.toolTip : { html: this.tooltip }));
        }
        Ext.menu.Item.superclass.onRender.call(this, container, position);
    }
});
