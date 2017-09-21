#!/usr/bin/env bash

# Install Composer dependencies.
composer self-update --stable
composer install

# Install PEAR dependencies.
#fix for https://github.com/travis-ci/travis-ci/issues/8365
pear config-set php_dir $(php -r 'echo substr(get_include_path(),2);')

pear channel-update pear.php.net
pear install --alldeps Log

# Install npm dependencies.
source ~/.nvm/nvm.sh
nvm install "$NODE_VERSION"
nvm use "$NODE_VERSION"

echo "Updating npm..."
npm update -g npm

echo "Installing npm dependencies..."
npm install
