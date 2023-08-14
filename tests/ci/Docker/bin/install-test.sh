#!/bin/bash

#Generate OpenSSL Key
openssl genrsa -rand /proc/cpuinfo:/proc/dma:/proc/filesystems:/proc/interrupts:/proc/ioports:/proc/uptime 2048 > /etc/pki/tls/private/localhost.key

#Generate Certificate
/usr/bin/openssl req -new -key /etc/pki/tls/private/localhost.key -x509 -sha256 -days 365 -set_serial $RANDOM -extensions v3_req -out /etc/pki/tls/certs/localhost.crt -subj "/C=XX/L=Default City/O=Default Company Ltd"

#Create Test Artifact Directories
mkdir ~/phpunit
mkdir /tmp/screenshots

# make sure that we're in the right directory.
pushd /xdmod || exit

    COMPOSER=composer-el7.json composer install --no-progress

    # build the XDMoD rpm so that it can be installed.
    ~/bin/buildrpm xdmod

    #Install / Upgrade XDMoD from RPM
    export XDMOD_TEST_MODE="fresh_install"
    ./tests/ci/bootstrap.sh

    #Validate the newly installed / Upgraded XDMoD
    ./tests/ci/validate.sh

    #Make sure that the Compose Test Dependencies are installed
    COMPOSER=composer-el7.json composer install --no-progress

    # Setup SimpleSAML for testing purposes.
    ./tests/ci/samlSetup.sh

popd || exit
