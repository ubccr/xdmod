#!/usr/bin/env bash
set -e
ASSETS=/tmp/assets

# Copy required assets not stored in Git repository.
if [ ! -e $ASSETS/chromedriver_linux64.zip ]; then
    echo 'Chrome driver not found' >&2
    exit 1
fi
mv $ASSETS/chromedriver_linux64.zip /root

# Download PhantomJS if it isn't in the assets directory.
if [ ! -e $ASSETS/phantomjs-2.1.1-linux-x86_64.tar.bz2 ]; then
    wget -q -P $ASSETS https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2
fi
tar -xf $ASSETS/phantomjs-2.1.1-linux-x86_64.tar.bz2 -C /tmp
mv /tmp/phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/local/bin

# Copy optional assets.
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
