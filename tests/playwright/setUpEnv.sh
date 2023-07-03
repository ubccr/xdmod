#!/bin/bash
set -e
set -o pipefail
export WDIO_MODE=headless
export JUNIT_OUTDIR=~/phpunit
pkill node
export IP_ADDRESS=localhost
cd /tmp/saml-idp/
node app.js  --acs https://"$IP_ADDRESS"/simplesaml/module.php/saml/sp/saml2-acs.php/xdmod-sp --aud https://"$IP_ADDRESS"/simplesaml/module.php/saml/sp/metadata.php/xdmod-sp --httpsPrivateKey idp-private-key.pem --httpsCert idp-public-cert.pem  --https true > /var/log/xdmod/samlidp.log 2>&1 &
cd /xdmod
~/bin/services restart
