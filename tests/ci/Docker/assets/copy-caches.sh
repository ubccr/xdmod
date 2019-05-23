#!/usr/bin/env bash
ASSETS=/tmp/assets
if [ -e $ASSETS/phantomjs-2.1.1-linux-x86_64.tar.bz2 ]; then
    tar xf /tmp/assets/phantomjs-2.1.1-linux-x86_64.tar.bz2
    mv phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/local/bin && \
    rm -rf phantomjs-2.1.1-linux-x86_64 /tmp/assets/phantomjs-2.1.1-linux-x86_64.tar.bz2
fi

if [ -e $ASSETS/vimrc ]; then
    mv $ASSETS/vimrc /etc/vimrc
fi
if [ -e $ASSETS/browser-tests-node-modules.tar.gz ]; then
    mv $ASSETS/browser-tests-node-modules.tar.gz /tmp/
fi
if [ -e $ASSETS/saml-idp.tar.gz ]; then
    mv $ASSETS/saml-idp.tar.gz /tmp/
fi
if [ -e $ASSETS/mysql-server.cnf ]; then
    mv $ASSETS/mysql-server.cnf /etc/my.cnf.d/server.cnf
fi
if [ -e $ASSETS/composer-cache.tar.gz ]; then
    mkdir -p /root/.composer
    tar -xf $ASSETS/composer-cache.tar.gz -C /root/.composer
fi
if [ -e $ASSETS/chromedriver_linux64.zip ]; then
    mv $ASSETS/chromedriver_linux64.zip /root/chromedriver_linux64.zip
fi
if [ -e $ASSETS/npmrc ]; then
    mv $ASSETS/npmrc /root/.npmrc
fi
