#!/usr/bin/env bash

# If PHP 5.3.3 is installed, SSL/TLS isn't available to PHP.
# Use a newer version of PHP for installing Composer dependencies.
using_php_533="false"; [[ "$(php --version)" == PHP\ 5.3.3\ * ]] && using_php_533="true"
if "$using_php_533"; then
    echo "Using newer version of PHP for installing dependencies"
    phpenv global 5.3
    php --version

    # Update Composer since the attempt made by the Travis setup script
    # using PHP 5.3.3 would have failed.
    #
    # First, we update to the latest developer version since the version
    # of Composer pre-installed on Travis systems (1.0.0) doesn't support
    # selecting an update channel. Then, we use this version to rollback to
    # the latest stable version.
    composer self-update
    composer self-update --stable
fi

# Install Composer dependencies.
composer install

# If using PHP 5.3.3 for testing purposes, stop using the newer PHP version.
if "$using_php_533"; then
    echo "Reverting back to PHP 5.3.3 for testing"
    phpenv global 5.3.3
    php --version
fi

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
