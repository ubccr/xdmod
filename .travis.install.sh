#!/usr/bin/env bash

composerpath=`which composer`
curl https://getcomposer.org/download/1.5.1/composer.phar > $composerpath
chmod +x $composerpath

# Install Composer dependencies.
[ -e /home/travis/.phpenv/versions/5.3.3/etc/conf.d ] && echo "extension=php_openssl.so" > /home/travis/.phpenv/versions/5.3.3/etc/conf.d/openssl.ini
composer config -g -- disable-tls true
composer install

# Install PEAR dependencies.
pear install Log

# Install npm dependencies.
source ~/.nvm/nvm.sh
nvm install "$NODE_VERSION"
nvm use "$NODE_VERSION"

echo "Updating npm..."
npm update -g npm

echo "Installing npm dependencies..."
npm install
