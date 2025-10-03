---
title: OpenID Connect Setup
---

This documentation will only cover the additional steps required to configure XDMoD to use OpenID Connect as an SSO identity
provider. All the file paths included below assume that an RPM installation has been performed. If you have a source install
of XDMoD then `/etc/xdmod` can be replaced with `/path/to/your/xdmod/install/etc`

You will first need to modify `/etc/xdmod/simplesamlphp/metadata/saml20-idp-remote.php` according to the example below:

*NOTE: in the following examples you will need to substitute your own values where `<< ... >>` is found. Each instance of `<< ... >>` will be detailed underneath the code block in which it is found. Some of these values will be referenced in other files, these values will be denoted by a <span style='color:red;font-size=20px'>\*</span>.*

```injectablephp
<?php
$metadata['<<idp-remote-entity-id>>'] = array (
    'metadata-set' => 'saml20-idp-hosted',
    'entityid' => '<<idp-remote-entity-id>>',
    'SingleSignOnService' => array (
        0 =>
            array (
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'Location' => 'https://<<your-xdmod-installs-web-address>>/simplesaml/saml2/idp/SSOService.php',
            ),
    ),
    'SingleLogoutService' => array (
        0 =>
            array (
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'Location' => 'https://<<your-xdmod-installs-web-address>>/simplesaml/saml2/idp/SingleLogoutService.php',
            ),
    ),
    'certData' => '<<contents-of-your-cert-file-minus-the-begin-and-end-certificate-blocks>>',
    'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
    'contacts' => array (
        0 =>
            array (
                'emailAddress' => '<<tech-support-email-address>>',
                'contactType' => 'technical',
                'givenName' => '<<display-name-for-email-address>>',
            ),
    ),
    'icon' => '<<base64-encoded-png-icon-data-that-will-be-shown-in-the-xdmod-login-modal>>'
);
```

- `<<idp-remote-entity-id>>`: <span style='color:red;font-size=20px'>*</span> This is a value selected by you, it will be referenced elsewhere in the configuration.
- `<<your-xdmod-installation-web-address>>`: The external web address of your XDMoD Installation (which should contain the `host` property from your SP config in `authsources.php`), ex. `https://xdmod.example.com`.
- `<<contents-of-your-cert-file-minus-the-begin-and-end-certificate-blocks>>`: This should be from the cert file your using for simplesamlphp. Location is based on the `certdir` property found in  `/etc/xdmod/simplesamlphp/config/config.php`.
- `<<base64-encoded-png-icon-data-that-will-be-shown-in-the-xdmod-login-modal>>`: XDMoD supports displaying a custom image when users log in via SSO. This is where you would encode that image.

Next, modify `/etc/xdmod/simplsamlphp/config/authsources.php` according to the example below:


```injectablephp
<?php
$config = array(
    '<<sp-id>>' => array(
        'saml:SP',
        'idp' => '<<idp-remote-entity-id>>'
        /** Configuration from simplesSAMLphp general configuration **/
    ),
    '<<oidc-key-id>>'  => array(
        'authoidcoauth2:OIDCOAuth2',
        'entityID'            => '<<oidc-entity-id>>',
        'auth_endpoint'       => '<<auth-endpoint-url>>',
        'auth_path'           => '<<auth-path>>',
        'api_endpoint'        => '<<api-endpoint-url>>',
        'token_path'          => '<<token-path>>',
        'user_info_path'      => '<<user-info-path>>',
        'key'                 => '<<client-id-or-key>>',
        'client_id'           => '<<client-id-or-key>>',
        'client_secret'       => '<<client-secret>>',
        'secret'              => '<<client-secret>>',
        'scope'               => '<<oidc scope>>',
        'response_type'       => 'code',
        'use_header_for_auth' => false,
        'redirect_uri'        => '<<your-xdmod-installation-web-address>>/simplesaml/module.php/authoidcoauth2/linkback.php',
        'verify_ssl'          => 0
    ),
);
```
- `<<idp-remote-entity-id>>`: This is the `entityid` from `saml20-idp-remote.php`.
- `<<oidc-key-id>>`: <span style='color:red;font-size=20px'>*</span> This is a value you select and will be referenced elsewhere in the configuration files.
- `<<oidc-entity-id>>`: <span style='color:red;font-size=20px'>*</span> This is a value you select and will be referenced elsewhere in the configuration files.
- `<<auth-endpoint-url>>`: The url that points to your authentication endpoint, ex. `https://cilogon.org`.
- `<<auth-path>>`: The path element that will be appended to `<<auth-endpoint-url>>` when authorizing, ex. `/authorize`.
- `<<api-endpoint>>`: The url that points to your OpenID Connect API endpoint ( often the same as `<<auth-endpoint-url>>` ), ex. `https://cilogon.org`.
- `<<token-path>>`: The path element that will be appended to `<<auth-endpoint-url>>` when requesting a token, ex. `/oauth2/token`.
- `<<user-info-path>>`: The path element that will be appended to `<<auth-endpoint-url>>` when requesting user information, ex. `/oauth2/userinfo`.
- `<<client-id-or-key>>`: The key or client id from your IDP.
- `<<client-secret>>`: The secret from your IDP.
- `<<scope>>`: The information that will be returned from your IDP, ex. `email openid org.cilogon.userinfo profile`.
- `<<your-xdmod-installation-web-address>>`: The external web address of your XDMoD Installation (which should match the `host` property in your SP config), ex. `https://xdmod.example.com`.

and finally you will need to update `/etc/xdmod/simplesamlphp/metadata/saml20-idp-hosted.php`:

```injectablephp
<?php
$metadata['<<idp-remote-entity-id>>'] = array(
    /*
     * The hostname for this IdP. This makes it possible to run multiple
     * IdPs from the same configuration. '__DEFAULT__' means that this one
     * should be used by default.
     */
    'host' => '__DEFAULT__',

    /*
     * The private key to use when signing responses.
     * These are stored in the cert-directory.
     */
    'privatekey'  => '<<your-private-key-file-name>>',

    /*
     * The authentication source which should be used to authenticate the
     * user. This must match one of the entries in config/authsources.php.
     */
    'auth' => '<<the-oidc-key-name-in-authsources>>',
);
```

- `<<your-private-key-file-name>>`: The key file to be used by simplesamlphp. Location is based on the `certdir` property found in  `/etc/xdmod/simplesamlphp/config/config.php`.
- `<<the-oidc-key-name-in-authsources>>`: <span style='color:red;font-size=20px'>*</span> The `<<oidc-key-id>>` used in `authsources.php`.
