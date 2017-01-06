---
title: Setting up XDMoD for LDAP Authentication
---

For XDMoD to authenticate against LDAP XDMoD has to be set up as an IDP.

*Note: For this example we are going to use the [ForumSystems Online LDAP Test Server][forumsys-ldap] You will need to change settings to your ldap as appropriate*

Make sure you have read and setup [OpenXDMoD for SimpleSAML][simplesamlphp.html].
More information on [SimpleSAMLphp LDAP][ssp-ldap].


## Configure Authsources.php

You will need to replace `{% raw %}{{HOSTNAME}}{% endraw %}` with the hostname of this machine.

/etc/xdmod/simplesamlphp/config/authsources.php

```php
<?php
$config = array(
  'xdmod-sp' => array(
    'saml:SP',
    'host'=> '{% raw %}{{HOSTNAME}}{% endraw %}',
    'entityID' => 'https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/module.php/saml/sp/metadata.php/xdmod-sp',
    'privatekey'  => 'xdmod-sp.key',
    'idp' => 'xdmod-hosted-idp-ldap',
    'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
    'authproc' => array(
      40 => array(
        'class' => 'core:AttributeMap',
        /*
         *  These will need to map to the fields you have in your ldap
         */
        'mail' => 'email_address',
        'givenName' => 'first_name',
        'sn' => 'last_name',
        'department' => 'field_of_science',
        'uid' => 'username'
      )
    )
  ),
  'ldap-forumsys' => array(
    'ldap:LDAP',
    /*
     * testing username and pass:
     * Username: tesla
     * Password: password
     */

    /* The hostname of the LDAP server. */
    'hostname' => 'ldap.forumsys.com',

    /* Whether SSL/TLS should be used when contacting the LDAP server. */
    'enable_tls' => FALSE,

    /*
    * Which attributes should be retrieved from the LDAP server.
    * This can be an array of attribute names, or NULL, in which case
    * all attributes are fetched.
    */
    'attributes' => NULL,

    /*
    * The pattern which should be used to create the user's DN given the username.
    * %username% in this pattern will be replaced with the user's username.
    *
    * This option is not used if the search.enable option is set to TRUE.
    */
    'dnpattern' => 'uid=%username%,dc=example,dc=com',

    /*
    * As an alternative to specifying a pattern for the users DN, it is possible to
    * search for the username in a set of attributes. This is enabled by this option.
    */
    'search.enable' => FALSE,

    /*
    * The DN which will be used as a base for the search.
    * This can be a single string, in which case only that DN is searched, or an
    * array of strings, in which case they will be searched in the order given.
    */
    'search.base' => 'dc=example,dc=com',

    /*
    * The attribute(s) the username should match against.
    *
    * This is an array with one or more attribute names. Any of the attributes in
    * the array may match the value the username.
    */
    'search.attributes' => array('uid', 'mail'),

    /*
    * The username & password where SimpleSAMLphp should bind to before searching. If
    * this is left NULL, no bind will be performed before searching.
    */
    'search.username' => NULL,
    'search.password' => NULL
  ),
  'admin' => array(
    // The default is to use core:AdminPassword, but it can be replaced with
    // any authentication source.
    'core:AdminPassword',
  ),
);
```

## Setup XDMoD as a [SimpleSAMLphp IDP][ssp-idp]

*  Enable saml2idp functionality by editing /etc/xdmod/simplesamlphp/config/config.php

from:

```php
'enable.saml20-idp' => false,
```

to:

```php
'enable.saml20-idp' => true,
```

*  Create a SSL self signed certificate
  *  `openssl req -newkey rsa:2048 -new -x509 -days 3652 -nodes -out /etc/xdmod/simplesamlphp/cert/xdmod-idp.crt -keyout /etc/xdmod/simplesamlphp/cert/xdmod-idp.pem`
*  Create /etc/xdmod/simplesamlphp/metadata/saml20-idp-hosted.php with content like the following (you will want to change the auth if you changed it in authsources.php)

*note if you have not changed your SIMPLESAMLPHP_CONFIG_DIR these files will need to be located in /usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/{% raw % }{cert,config,metadata}{% endraw %}*

```php
<?php
  $metadata['xdmod-hosted-idp-ldap'] = array(
    /*
     * The hostname for this IdP. This makes it possible to run multiple
     * IdPs from the same configuration. '__DEFAULT__' means that this one
     * should be used by default.
     */
    'host' => '__DEFAULT__',

    /*
     * The private key and certificate to use when signing responses.
     * These are stored in the cert-directory.
     */
    'privatekey' => 'xdmod-idp.pem',
    'certificate' => 'xdmod-idp.crt',

    /*
     * The authentication source which should be used to authenticate the
     * user. This must match one of the entries in config/authsources.php.
     */
    'auth' => 'ldap-forumsys',
);
```

## Add XDMoD SP Metadata to the IDP

edit `/etc/xdmod/simplesamlphp/metadata/saml20-sp-remote.php` to contain (replacing `{% raw %}{{HOSTNAME}}{% endraw %}`)

```php
<?php
$metadata['https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/module.php/saml/sp/metadata.php/xdmod-sp'] = array(
    'AssertionConsumerService' => 'https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/module.php/saml/sp/saml2-acs.php/xdmod-sp',
    'SingleLogoutService'      => 'https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/module.php/saml/sp/saml2-logout.php/xdmod-sp',
);
```

## Add IDP Metadata to XDMoD SP

Goto the SimpleSAMLphp installation page and login as the administrative user.

`https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/module.php/core/frontpage_welcome.php`

Go to the `Federation` tab
Under the section labeled `SAML 2.0 IdP Metadata`
click on `xdmod-hosted-idp-ldap`
copy the contents of the page beginning with the $metadata and put it into `/etc/xdmod/simplesamlphp/metadata/saml20-idp-remote.php` this is a php file so make sure that the beging of the file start with `<?php`

This example WILL NOT WORK IT IS JUST FOR COMPARISON
```php
<?php

$metadata['xdmod-hosted-idp-ldap'] = array (
  'metadata-set' => 'saml20-idp-remote',
  'entityid' => 'xdmod-hosted-idp-ldap',
  'SingleSignOnService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/saml2/idp/SSOService.php',
    ),
  ),
  'SingleLogoutService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/saml2/idp/SingleLogoutService.php',
    ),
  ),
  'certData' => '...',
  'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
);
```
You should now be able to go to the `Authentication` tab and test the `xdmod-sp` authentication source.
If you are using the forumsys example to test the username / password to use will be tesla / password

[forumsys-ldap]: http://www.forumsys.com/tutorials/integration-how-to/ldap/online-ldap-test-server/
[ssp-idp]: https://simplesamlphp.org/docs/stable/simplesamlphp-idp
[ssp-ldap]: https://simplesamlphp.org/docs/stable/ldap:ldap
