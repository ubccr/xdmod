#!/bin/bash

#Used for docker build, cache file will need to be upgraded if newer version is needed
CACHE_FILE='/root/saml-idp.tar.gz'

DEFAULT_INSTALL_DIR=/usr/share/xdmod
DEFAULT_VENDOR_DIR=$DEFAULT_INSTALL_DIR/vendor

INSTALL_DIR=${INSTALL_DIR:-$DEFAULT_INSTALL_DIR}
VENDOR_DIR=${VENDOR_DIR:-$DEFAULT_VENDOR_DIR}

HOSTNAME=""
PORT=""
# valid values: local, keycloak
DEFAULT_TYPE=local
while getopts h:p:t: flag
do
    case "${flag}" in
        h) HOSTNAME=${OPTARG};;
        p) PORT=${PORT:-${OPTARG}};;
        t) TYPE=${DEFAULT_TYPE:-${OPTARG}};;
        *) echo "Invalid argument"; exit 1;
    esac
done

declare -A colors=(["red"]=30 ["green"]=32 ["blue"]=34 ["magenta"]=35 ["cyan"]=36 ["light_gray"]=37 ["gray"]=90 ["light_red"]=91 ["light_green"]=92 ["light_yellow"]=93 ["light_blue"]=94 ["light_magenta"]=95 ["light_cyan"]=96 ["white"]=97)
color () {
    message=$1;
    color="${colors["$2"]}";
    echo "\e[${color}m${message}\e[0m"
}

log () {
    header=$1
    message=$2
    prefix=$(color $header 'green')
    printf "[$prefix] $message\n"
}

function configureSimplesamlPHP()
{
    log "SimpleSamlPHP" "Stopping HTTPD"
    httpd -k stop

    log "SimpleSamlPHP" "Bootstrapping SimplesAMLPHP cache directory"
    # Directory Setup, SimpleSamlPHP want's it's own cache directory so we create that here.
    mkdir -p /var/cache/simplesamlphp/core
    chown -R apache:root /var/cache/simplesamlphp

    log "SimpleSamlPHP" "Uncommenting SimpleSamlPHP in HTTPD config."
    # Uncomment the simplesamlphp endpoint
    sed -i -- "s%#    SetEnv SIMPLESAMLPHP_CONFIG_DIR $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/config%    SetEnv SIMPLESAMLPHP_CONFIG_DIR $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/config%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#    Alias /simplesaml $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/public%    Alias /simplesaml $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/public%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#    <Directory $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/public>%    <Directory $DEFAULT_VENDOR_DIR/simplesamlphp/simplesamlphp/public>%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#        Options FollowSymLinks%        Options FollowSymLinks%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#        AllowOverride All%        AllowOverride All%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#        <IfModule mod_authz_core.c>%        <IfModule mod_authz_core.c>%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#            Require all granted%            Require all granted%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#        </IfModule>%        </IfModule>%" /etc/httpd/conf.d/xdmod.conf
    sed -i -- "s%#    </Directory>%    </Directory>%" /etc/httpd/conf.d/xdmod.conf

    # Copy in the default SimplesSamlPHP config file.
    log "SimpleSamlPHP" "Copying SimpleSamlPHP config file into place"
    cp "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/config.php.dist" "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/config.php"

    log "SimpleSamlPHP" "Configuring trusted url domains and a default admin password for testing."
    # Ensure that we add `localhost` and 'xdmod' to the trusted url domains
    sed -i -- "s|'trusted.url.domains' => \[\]|'trusted.url.domains' => \['localhost', 'xdmod'\]|g" "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/config.php"

    # Change the default password so that we can test authentication
    sed -i -- "s%'auth.adminpassword' => '123'%'auth.adminpassword' => 'zaq12wsx'%" "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/config.php"

    log "SimpleSamlPHP" "Copying authsources config into place."
    # Create the authsources config file for saml Authentication
    cat > "$VENDOR_DIR/simplesamlphp/simplesamlphp/config/authsources.php" <<EOF
    <?php
    \$config = array(
      'xdmod-sp' => array(
        'saml:SP',
        'entityID' => 'https://$HOSTNAME/xdmod-sp',
        'idp' => 'urn:example:idp',
        //'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
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
          )
        )
      ),
      'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.
        'core:AdminPassword',
      ),
    );
EOF
    log "SimpleSamlPHP" "Retrieving the new x509 Cert to be used in SimpleSamlPHP"
    NEW_CERT=`sed -n '2,21p' idp-public-cert.pem | perl -ne 'chomp and print'`

    log "SimpleSamlPHP" "Copying config file for SimpleSamlPHP remote IDP"
    cat > "$VENDOR_DIR/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php" <<EOF
    <?php

    \$metadata['urn:example:idp'] = array (
      'entityid' => 'urn:example:idp',
      'contacts' =>
      array (
      ),
      'metadata-set' => 'saml20-idp-remote',
      'SingleSignOnService' =>
      array (
        0 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://$HOSTNAME:7000',
        ),
        1 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
          'Location' => 'https://$HOSTNAME:7000',
        ),
      ),
      'SingleLogoutService' =>
      array (
        0 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://$HOSTNAME:7000/signout',
        ),
      ),
      'ArtifactResolutionService' =>
      array (
      ),
      'NameIDFormats' =>
      array (
        0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
        2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
      ),
      'keys' =>
      array (
        0 =>
        array (
          'encryption' => false,
          'signing' => true,
          'type' => 'X509Certificate',
          'X509Certificate' => '$NEW_CERT',
        ),
      ),
    );
EOF
    sleep 1
    log "SimpleSamlPHP" "Starting HTTPD"
    httpd -k start
}

localSSO() {
    # For using the SSO locally we need the HOSTNAME to be localhost
    #HOSTNAME=localhost:8181
    # For using the SSO via playwright then we need `xdmod`
    #HOSTNAME=$(hostname)

    cd /tmp || exit

    log "setup" "installing saml idp server"
    if [ -f $CACHE_FILE ];
    then
        log "setup" "using cached copy"
        tar -zxf $CACHE_FILE
        cd saml-idp || exit
    else
        git clone https://github.com/mcguinness/saml-idp/
        cd saml-idp || exit
        git checkout 8ff807a91f4badc3c0a10551e1d789df140a66cc
        rm -f package-lock.json
        npm set progress=false
        npm install --quiet --silent
    fi

    log "setup" "Generating new x509 cert"
    openssl req -x509 -new -newkey rsa:2048 -nodes -subj '/C=US/ST=New York/L=Buffalo/O=UB/CN=CCR Test Identity Provider' -keyout idp-private-key.pem -out idp-public-cert.pem -days 7300

    log "setup" "Adding IDP config file"
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
    log "setup" "Configuring SimpleSamlPHP"
    # Configure SimplesamlPHP, stops / starts httpd as appropriate
    configureSimplesamlPHP;

    AUD_URL=https://$HOSTNAME/xdmod-sp

    # The ACS url is the only one that needs the port specified.
    if [ -n "$PORT" ]; then
            HOSTNAME="${HOSTNAME}:${PORT}"
    fi

    log "setup" "Spinning up for $HOSTNAME"
    ACS_URL=https://$HOSTNAME/simplesaml/module.php/saml/sp/saml2-acs.php/xdmod-sp


    log "setup" "Starting local IDP"
    node app.js  --acs "$ACS_URL" --aud "$AUD_URL" --httpsPrivateKey idp-private-key.pem --httpsCert idp-public-cert.pem  --https true > /var/log/xdmod/samlidp.log 2>&1 &
    EXIT_CODE=$?
    exit $EXIT_CODE
}

keycloakSSO() {
    echo ""
}


if [[ "$TYPE" == 'local'  ]]; then
    log "settings" "Type: $TYPE"
    log "settings" "Host: $HOSTNAME"
    log "settings" "Port: $PORT"
    localSSO
elif [ "$TYPE" == "keycloak" ]; then
    keycloakSSO
else
    echo "You must provide a type of setup ( -t ) to continue ";
fi




