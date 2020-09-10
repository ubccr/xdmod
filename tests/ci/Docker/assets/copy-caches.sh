#!/usr/bin/env bash
set -e
ASSETS=/tmp/assets

# Copy optional assets.
if [ -e $ASSETS/chromedriver_linux64.zip ]; then
    mv $ASSETS/chromedriver_linux64.zip /root
fi
if [ -e $ASSETS/saml-idp.tar.gz ]; then
    mv $ASSETS/saml-idp.tar.gz /root
fi
if [ -e $ASSETS/browser-tests-node-modules.tar.gz ]; then
    mv $ASSETS/browser-tests-node-modules.tar.gz /root
fi
if [ -e $ASSETS/composer-cache.tar.gz ]; then
    mkdir -p /root/.composer
    tar -xf $ASSETS/composer-cache.tar.gz -C /root/.composer
fi

# Copy assets stored in Git repository.
mv $ASSETS/mysql-server.cnf /etc/my.cnf.d/server.cnf
mv $ASSETS/vimrc /etc/vimrc
mv $ASSETS/npmrc /root/.npmrc
