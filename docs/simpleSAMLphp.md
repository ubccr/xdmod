---
title: Federated Authentication Guide
---

----------

Documentation only covers using [SimpleSAMLphp][ssp] for SAML federated authentication.

----------

## Assumptions

*   You have already installed Open XDMoD.
*   You have installed Open XDMoD using an RPM.  If not, you will need to change the paths used in the examples.

----------

First you will need to create the folders for the SimpleSAMLphp files to live:

```bash
# mkdir -p /etc/xdmod/simplesamlphp/config
# mkdir -p /etc/xdmod/simplesamlphp/metadata
# mkdir -p /etc/xdmod/simplesamlphp/cert
```

Required configuration files to be added to support federated authentication:

*   `/etc/xdmod/simplesamlphp/config/config.php`
*   `/etc/xdmod/simplesamlphp/config/authsources.php`
*   `/etc/xdmod/simplesamlphp/metadata/saml20-idp-remote.php`

More information on how to set these up can be found in the [SimpleSAMLphp Service Provider QuickStart][ssp-config].

You will need to modify the `config.php` file and make sure you modify the `metadata.sources` and the `certdir` keys to have the **full path** to directories containing the configurations:

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

Templates will exist inside the Open XDMoD shared data directory:

*   `/usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/config-templates/`
*   `/usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/metadata-templates/`

The things that are required currently for login are:

*   `username`

This is currently the main identifying piece of information that must be created, we use it with an identifier of `itname` in our authentication code, so this means you need to edit the file `/etc/xdmod/simplesamlphp/config/authsources.php` and make sure that an attribute is mapped to this index.

Here is an example:

```php
<?php
$config = array(
  /*
   * If you want to support both local auth and federated auth look into
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
        'orgId' => 'organization_id',
        'fieldOfScience' => 'field_of_science',
        'parentId' => 'parent_user_id',
        'itname' => 'username'
      )
    )
  ),
);
```

Use the above example to match the response from the IdP, the indexes under `authproc[40]` point to indexes expected by Open XDMoD.

## Setting up IdP metadata

Get the metadata from the IdP (if you are using [SAML IdP][saml-idp] to test `https://<host>:<port>/metadata`) and use the SimpleSAMLphp built in converter located at `https://<hostname>/simplesaml/admin/metadata-converter.php` to create the `saml20-idp-remote.php` config file more information can be found on the [Simple Saml PHP website][ssp-idp-remote].

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

## Web Server Setup

### Apache

Just follow the SimpleSAMLphp documentation for [apache][ssp-apache].

Make sure to include the updated configuration locations.  This will need to be the **full path** to the configuration directory.

Uncomment the SimpleSAML configuration in your Apache VirtualHost:

```conf
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

```conf
location ~ ^/simplesaml/(.+\.php.*)$ {
  ###
  # If you want to set up a different SIMPLESAMLPHP_CONFIG_DIR this
  # needs to be set in the php-fpm.conf as an environment variable.
  ###
  alias /usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/www/$1;
  fastcgi_split_path_info ^(.+\/module\.php)(/.+)$;
  fastcgi_pass   127.0.0.1:9000;
  fastcgi_index  index.php;
  #fastcgi_param SCRIPT_FILENAME /var/www/simplesaml$fastcgi_script_name;
  include        fastcgi_params;
}
location ~ ^/simplesaml/(.*) {
  alias /usr/share/xdmod/vendor/simplesamlphp/simplesamlphp/www/$1;
}
```

## Testing

[SAML IdP][saml-idp] was used to test this functionality locally.

Run the following command line substitute where appropriate:

```bash
$ node app.js \
  --aud <http|https>://<XDMODHOST>/simplesaml/module.php/saml/sp/metadata.php/default-sp \
  --acs <http|https>://<XDMODHOST>/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp \
  --httpsPrivateKey <PATH TO PRIVATE KEY> --httpsCert <PATH TO PRIVATE KEY> --https true
```

[ssp]: https://simplesamlphp.org/
[ssp-config]: https://simplesamlphp.org/docs/stable/simplesamlphp-sp
[ssp-idp-remote]: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
[ssp-apache]: https://simplesamlphp.org/docs/stable/simplesamlphp-install#section_6
[saml-idp]: https://github.com/mcguinness/saml-idp/
