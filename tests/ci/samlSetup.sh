#!/bin/bash

#Used for docker build, cache file will need to be upgraded if newer version is needed
CACHE_FILE='/root/saml-idp.tar.gz'

DEFAULT_INSTALL_DIR=/usr/share/xdmod
DEFAULT_VENDOR_DIR=$DEFAULT_INSTALL_DIR/vendor

INSTALL_DIR=${INSTALL_DIR:-$DEFAULT_INSTALL_DIR}
VENDOR_DIR=${VENDOR_DIR:-$DEFAULT_VENDOR_DIR}

HOSTNAME="localhost:8080"
while getopts h: flag
do
    case "${flag}" in
        h) HOSTNAME=${OPTARG};;
        *) echo "Invalid argument"; exit 1;
    esac
done


# For using the SSO locally we need the HOSTNAME to be localhost
#HOSTNAME=localhost:8181
# For using the SSO via playwright then we need `xdmod`
#HOSTNAME=$(hostname)

httpd -k stop
cd /tmp

echo "installing saml idp server"
if [ -f $CACHE_FILE ];
then
    echo "using cached copy"
    tar -zxf $CACHE_FILE
    cd saml-idp
else
    git clone https://github.com/mcguinness/saml-idp/
    cd saml-idp
    git checkout 8ff807a91f4badc3c0a10551e1d789df140a66cc
    rm -f package-lock.json
    npm set progress=false
    npm install --quiet --silent
fi

openssl req -x509 -new -newkey rsa:2048 -nodes -subj '/C=US/ST=New York/L=Buffalo/O=UB/CN=CCR Test Identity Provider' -keyout idp-private-key.pem -out idp-public-cert.pem -days 7300

cat > /tmp/saml-idp/config.js <<EOF
/**
 * User Profile
 */
var profile = {
  userName: 'saml.jackson@example.com',
  nameIdFormat: 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
  firstName: 'Saml',
  lastName: 'Jackson',
  displayName: 'saml jackson',
  email: 'saml.jackson@example.com',
  mobilePhone: '+1-415-555-5141',
  groups: 'Simple AIdP Users, West Coast Users, Cloud Users',
  itname: 'samlj',
  system_username: 'samlj',
  orgId: 'Screwdriver',
  fieldOfScience: 'Computational Research'
}

/**
 * SAML Attribute Metadata
 */
var metadata = [{
  id: "orgId",
  optional: 'true',
  displayName: 'Organization',
  description: 'Organization the user Belongs to',
  multiValue: false
}, {
  id: "fieldOfScience",
  optional:'true',
  displayName: 'Field Of Science',
  description: 'Field of Science the user works in',
  multiValue: false
}, {
  id: "itname",
  optional:'false',
  displayName: 'Organization Username',
  description: 'The system user',
  multiValue: false
}, {
  id: 'system_username',
  optional: 'true',
  displayName: 'System Username',
  description: 'Username of account used to run jobs.',
  multiValue: false
}, {
  id: "firstName",
  optional: false,
  displayName: 'First Name',
  description: 'The given name of the user',
  multiValue: false
}, {
  id: "lastName",
  optional: false,
  displayName: 'Last Name',
  description: 'The surname of the user',
  multiValue: false
}, {
  id: "displayName",
  optional: true,
  displayName: 'Display Name',
  description: 'The display name of the user',
  multiValue: false
}, {
  id: "email",
  optional: false,
  displayName: 'E-Mail Address',
  description: 'The e-mail address of the user',
  multiValue: false
},{
  id: "mobilePhone",
  optional: true,
  displayName: 'Mobile Phone',
  description: 'The mobile phone of the user',
  multiValue: false
}, {
  id: "groups",
  optional: true,
  displayName: 'Groups',
  description: 'Group memberships of the user',
  multiValue: true
}, {
  id: "userType",
  optional: true,
  displayName: 'User Type',
  description: 'The type of user',
  options: ['Admin', 'Editor', 'Commenter']
}];

module.exports = {
  user: profile,
  metadata: metadata
}
EOF
#sed -i -- "s%#Alias /simplesaml $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/www%Alias /simplesaml $VENDOR_DIR/simplesamlphp/simplesamlphp/www%" /etc/httpd/conf.d/xdmod.conf
#sed -i -- "s%#<Directory $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/www>%<Directory $VENDOR_DIR/simplesamlphp/simplesamlphp/www>%" /etc/httpd/conf.d/xdmod.conf
#sed -i -- 's/#    Options FollowSymLinks/    Options FollowSymLinks/' /etc/httpd/conf.d/xdmod.conf
#sed -i -- 's/#    AllowOverride All/    AllowOverride All/' /etc/httpd/conf.d/xdmod.conf
#sed -i -- 's/#    <IfModule mod_authz_core.c>/    <IfModule mod_authz_core.c>/' /etc/httpd/conf.d/xdmod.conf
#sed -i -- 's/#        Require all granted/       Require all granted/' /etc/httpd/conf.d/xdmod.conf
#sed -i -- 's%#    </IfModule>%    </IfModule>%' /etc/httpd/conf.d/xdmod.conf
#sed -i -- 's%#</Directory>%</Directory>%' /etc/httpd/conf.d/xdmod.conf


#cp "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/config.php.dist" "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/config.php"
#sed -i -- "s|'trusted.url.domains' => \[\]|'trusted.url.domains' => \['localhost'\]|g" "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/config.php"


#cat > "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/authsources.php" <<EOF
#<?php
#\$config = array(
#  'xdmod-sp' => array(
#    'saml:SP',
#    'entityID' => 'https://$HOSTNAME/xdmod-sp',
#    'idp' => 'urn:example:idp',
#    //'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
#    'authproc' => array(
#      40 => array(
#        'class' => 'core:AttributeMap',
#        'email' => 'email_address',
#        'firstName' => 'first_name',
#        'middleName' => 'middle_name',
#        'lastName' => 'last_name',
#        'personId' => 'person_id',
#        'orgId' => 'organization',
#        'fieldOfScience' => 'field_of_science',
#        'itname' => 'username'
#      ),
#      60 => array(
#        'class' => 'authorize:Authorize',
#        'username' => array(
#          '/\S+/'
#        ),
#      ),
#      61 => array(
#        'class' => 'authorize:Authorize',
#        'organization' => array(
#          '/\S+/'
#        )
#      )
#    )
#  ),
#  'admin' => array(
#    // The default is to use core:AdminPassword, but it can be replaced with
#    // any authentication source.
#    'core:AdminPassword',
#  ),
#);
#EOF

NEW_CERT=`sed -n '2,21p' idp-public-cert.pem | perl -ne 'chomp and print'`
CUR_CERT=$(grep x509 /usr/share/xdmod/config/packages/nbgrp_onelogin_saml.yaml | cut -d"'" -f 2)

sed -i "s|x509cert: '$CUR_CERT'|x509cert: '$NEW_CERT'|g" /usr/share/xdmod/config/packages/nbgrp_onelogin_saml.yaml

#cat > "$VENDOR_DIR/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php" <<EOF
#<?php
#
#\$metadata['urn:example:idp'] = array (
#  'entityid' => 'urn:example:idp',
#  'contacts' =>
#  array (
#  ),
#  'metadata-set' => 'saml20-idp-remote',
#  'SingleSignOnService' =>
#  array (
#    0 =>
#    array (
#      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
#      'Location' => 'https://$HOSTNAME:7000',
#    ),
#    1 =>
#    array (
#      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
#      'Location' => 'https://$HOSTNAME:7000',
#    ),
#  ),
#  'SingleLogoutService' =>
#  array (
#    0 =>
#    array (
#      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
#      'Location' => 'https://$HOSTNAME:7000/signout',
#    ),
#  ),
#  'ArtifactResolutionService' =>
#  array (
#  ),
#  'NameIDFormats' =>
#  array (
#    0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
#    1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
#    2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
#  ),
#  'keys' =>
#  array (
#    0 =>
#    array (
#      'encryption' => false,
#      'signing' => true,
#      'type' => 'X509Certificate',
#      'X509Certificate' => '$NEW_CERT',
#    ),
#  ),
#);
#EOF

node app.js  --acs https://$HOSTNAME/saml/acs --aud https://$HOSTNAME/saml/metadata --httpsPrivateKey idp-private-key.pem --httpsCert idp-public-cert.pem  --https true > /var/log/xdmod/samlidp.log 2>&1 &
httpd -k start
