---
title: Single Sign On Guide
---

----------

Documentation only covers using [SimpleSAMLphp][ssp] for Single Sign On.

----------

## Assumptions

*   You have already installed Open XDMoD.
*   You have installed Open XDMoD using an RPM.  If not, you will need to change the paths used in the examples.

----------

First you will need to create the folders for the SimpleSAMLphp files to live:

```
# mkdir -p /etc/xdmod/simplesamlphp/config
# mkdir -p /etc/xdmod/simplesamlphp/metadata
# mkdir -p /etc/xdmod/simplesamlphp/cert
```

Required configuration files to be added to support Single Sign On:

*   `/etc/xdmod/simplesamlphp/config/config.php`
*   `/etc/xdmod/simplesamlphp/config/authsources.php`
*   `/etc/xdmod/simplesamlphp/metadata/saml20-idp-remote.php`

More information on how to set these up can be found in the [SimpleSAMLphp Service Provider QuickStart][ssp-config].

Information on how to set up SimpleSAMLphp to use Globus Auth can be found in the [simplesamlphp-module-authglobus documentation][ssp-globusconfig]

You will need to modify the `config.php` file and make sure you modify the `metadata.sources` and the `certdir` keys to have the **full path** to directories containing the configurations:

**note** make sure the file has opening `<?php`
```php
  ...
  'certdir' => '/etc/xdmod/simplesamlphp/cert/',
  'metadata.sources' => array(
    array(
      'type' => 'flatfile',
      'directory' => '/etc/xdmod/simplesamlphp/metadata/'
    ),
  ),
  ...
```
If you are having errors you might need to check the trusted domains setting
```php
  ...
  'trusted.url.domains' => array('f.q.dn.of.xdmod'),
  ...
```

Templates will exist inside the Open XDMoD shared data directory:

*   `/usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/config-templates/`
*   `/usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/metadata-templates/`

The properties that are required currently for login are:

*   `username`
    * This will be used to identify Users via the `moddb.Users.username` column

This is currently the main identifying piece of information that must be created, we use it with an identifier of `itname` in our authentication code, so this means you need to edit the file `/etc/xdmod/simplesamlphp/config/authsources.php` and make sure that an attribute is mapped to this index.

Conditionally, if your installation supports multiple organizations then you must provide the following:

*    `organization`
     * This will be used to identify which Organization the user should be associated with via the `modw.organization.name` column

To get a better idea of how these properties translate into expected behavior during SSO login. Please refer to the table below:

|SAML Organization Present|Person Present|Expected Behavior |
|:-----------------------:|:------------:|:-----------------|
| true                    | true         | The user is associated with the the Person's organization if found, else the SAML Organization if found, else the Unknown Organization|
| true                    | false        | The user is associated with the SAML Organization if found, else the Unknown organization|
| false                   | true         | The user is associated with the Persons organization if found, else the Unknown organization|
| false                   | false        | The user is associated with the Unknown organization|

Here is an example `authsources.php` file **note: keys 60 and 61 must be present** These check that the _mandatory_ username and organization properties are present.
The values in section `40` should be left the same.  You should change the `keys` in this section to match what your IdP returns.
To find what the IdP returns you can use the simple saml administration pages located at: `https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml`.
More specifically if you have used this guide and named things the same you can use `https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/module.php/core/authenticate.php?as=xdmod-sp`.

```php
<?php
$config = array(
  /*
   * If you want to support both local auth and Single Sign On auth look into
   * https://simplesamlphp.org/docs/stable/multiauth:multiauth
   * https://simplesamlphp.org/docs/stable/sqlauth:sql
   * An updated example will be provided when this is implemented.
   */
  'default-sp' => array(
    'saml:SP',
    // 'idp' => 'urn:example:idp',
    'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
    'authproc' => array(
      40 => array(
        'class' => 'core:AttributeMap',
        'email' => 'email_address',
        'firstName' => 'first_name',
        'middleName' => 'middle_name',
        'lastName' => 'last_name',
        'personId' => 'person_id',
        'orgId' => 'organization',
        'fieldOfScience' => 'field_of_science',
        'itname' => 'username'
      ),
      // Ensures that the 'username' property has one or more non-whitespace characters
      60 => array(
        'class' => 'authorize:Authorize',
        'username' => array(
          '/\S+/'
        ),
      ),
      // Ensures that the 'organization' property has one or more non-whitespace characters
      61 => array(
        'class' => 'authorize:Authorize',
        'organization' => array(
           '/\S+/'
        )
      )
    )
  ),
);
```

Use the above example to match the response from the IdP, the indexes (left side of `=>`) under `authproc[40]` point to values (right side of `=>`) expected by Open XDMoD.
These indexes need to be what SAML receives from the IdP not the ones known from LDAP or other sources.

These indexes can be viewed using the `Test authentication sources` page built into simplesamlphp located at
`https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/module.php/core/authenticate.php` then clicking on `xdmod-sp` after which you will be redirect to the configured IdP login page.
After you have successfully logged in to the IdP you should be redirected back to a `SAML 2.0 SP Demo Example` page.  This page will show you the attributes passed back by the IdP.

**NOTE**: If a login prompt is displayed when going to the `Test authentication sources` you will need to use the user name  `admin` and the password in `/etc/xdmod/simplesamlphp/config/config.php` under the `auth.adminpassword` index.

## Setting up IdP metadata

Get the metadata from the IdP (if you are using [SAML IdP][saml-idp] to test `https://{% raw %}{{HOSTNAME}}{% endraw %}/metadata`) and use the SimpleSAMLphp built in converter located at `https://{% raw %}{{HOSTNAME}}{% endraw %}/simplesaml/admin/metadata-converter.php` to create the `saml20-idp-remote.php` config file more information can be found on the [Simple Saml PHP website][ssp-idp-remote].

**NOTE**: You will not be able to use this configuration directly, as the cert is not real and the domains are fake as well.

```php
<?php
$metadata['urn:example:idp'] = array (
  'name' => array(
    'en' => 'idp.example.com:7000'
  ),
  'entityid' => 'urn:example:idp',
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' =>
  array (
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.example.com:7000',
    ),
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://idp.example.com:7000',
    ),
  ),
  'SingleLogoutService' =>
  array (
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.example.com:7000/logout',
    ),
  ),
  'ArtifactResolutionService' =>
  array (
  ),
  'keys' =>
  array (
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => 'MIIEMD...Bfaq9pWo0LsKwKZVmOJU+4VzD6EkJ5dtE=',
    ),
  ),
);
```

You can also add an optional `icon` property to the metadata. This defines the image that
is displayed in the login dialog box. The icon can be defined as an inline base64 encoded
image or a path to an image file (relative to the webserver root). The login box form
layout is designed for an image that is 272 pixels wide.

```php
<?php
$metadata['urn:example:idp'] = array (
   ...
   'icon' => 'gui/images/[SSO_LOGIN_ICON].png'
)
```

## Web Server Setup

### Apache

Just follow the SimpleSAMLphp documentation for [apache][ssp-apache].

Make sure to include the updated configuration locations.  This will need to be the **full path** to the configuration directory.

Uncomment the SimpleSAML configuration in your Apache VirtualHost:

```apache
  SetEnv SIMPLESAMLPHP_CONFIG_DIR /etc/xdmod/simplesamlphp/config
  <Directory /usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/www>
    Options FollowSymLinks
    AllowOverride All
    # Apache 2.4 access controls.
    <IfModule mod_authz_core.c>
      Require all granted
    </IfModule>
  </Directory>
```

### nginx

***This web server is not currently supported.***
***While we have some instances running nginx it has not been fully vetted at this time.***
***If you have updates please share, but there will be limited help if you use nginx at this time.***

To store the configuration files in a different location you will need to edit your `php-fpm.conf` file and add the following line with the path equal to the location of your SimpleSAML config directory:

```conf
env[SIMPLESAMLPHP_CONFIG_DIR] = /etc/xdmod/simplesamlphp/config/
```

The following locations will need to be added to your Open XDMoD server configuration for the Open XDMoD site.

You will need to specify the full path to `simplesamlphp` for the aliases.

```nginx
location ^~ /simplesaml {
  alias /usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/www;
  location ~ ^(?<prefix>/simplesaml)(?<phpfile>.+?\.php)(?<pathinfo>/.*)?$ {
    include fastcgi_params;
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_split_path_info ^(.+\/module\.php)(/.+)$;
    fastcgi_param SCRIPT_FILENAME $document_root$phpfile;
    fastcgi_param PATH_INFO       $pathinfo if_not_empty;
  }
}
```

## Testing

[SAML IdP][saml-idp] was used to test this functionality locally.

Run the following command line substitute where appropriate:

```
$ node app.js \
  --aud <http|https>://<XDMODHOST>/simplesaml/module.php/saml/sp/metadata.php/default-sp \
  --acs <http|https>://<XDMODHOST>/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp \
  --httpsPrivateKey <PATH TO PRIVATE KEY> --httpsCert <PATH TO PRIVATE KEY> --https true
```

## Single Sign-on (SSO) Embedding
see [Integrations][integrations] for more information

[integrations]: integrations.html
[ssp]: https://simplesamlphp.org/
[ssp-config]: https://simplesamlphp.org/docs/stable/simplesamlphp-sp
[ssp-globusconfig]: https://github.com/ubccr/simplesamlphp-module-authglobus
[ssp-idp-remote]: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
[ssp-apache]: https://simplesamlphp.org/docs/stable/simplesamlphp-install#section_6
[saml-idp]: https://github.com/mcguinness/saml-idp/
