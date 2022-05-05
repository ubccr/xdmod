#!/bin/bash
set -e
ASSETS=/tmp/assets

# Copy required assets.
if [[ ! -f $ASSETS/browser-tests-node-modules.tar.gz ]]; then
    echo browser-tests-node-modules.tar.gz is required in $ASSETS >&2
    exit 1
fi
mv $ASSETS/browser-tests-node-modules.tar.gz /root

# Copy optional assets.
if [[ -f $ASSETS/chromedriver_linux64.zip ]]; then
    mv $ASSETS/chromedriver_linux64.zip /root
fi
if [[ -f $ASSETS/saml-idp.tar.gz ]]; then
    mv $ASSETS/saml-idp.tar.gz /root
fi
if [[ -f $ASSETS/composer-cache.tar.gz ]]; then
    mkdir -p /root/.composer
    tar -xf $ASSETS/composer-cache.tar.gz -C /root/.composer
fi

# Copy assets stored in Git repository.
mv $ASSETS/mysql-server.cnf /etc/my.cnf.d/server.cnf
mv $ASSETS/vimrc /etc/vimrc
mv $ASSETS/npmrc /root/.npmrc
