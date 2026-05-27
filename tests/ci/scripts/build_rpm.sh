#!/bin/bash

SRC_DIR=$(pwd)
BUILD_DIR=$SRC_DIR/open_xdmod/build
SCRIPT_DIR=$SRC_DIR/open_xdmod/build_scripts

dnf module -y enable php:7.4
dnf install -y rpm-build make php php-devel php-pear php-zip
yes '' | pecl install mongodb-1.18.1
echo "extension=mongodb.so" > /etc/php.d/40-mongodb.ini

dnf install -y wget
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)" && \
ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")" && \
if [[ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]]; then echo 'ERROR: Invalid composer signature'; exit 1; fi && \
php composer-setup.php --install-dir=/bin --filename=composer && \
php -r "unlink('composer-setup.php');"
composer install

# Boilerplate RPM build setup
mkdir -p ~/rpmbuild/{BUILD,RPMS,SOURCES,SPECS,SRPMS}
echo '%_topdir %(echo $HOME)/rpmbuild' > ~/.rpmmacros

for module in "$@"; do
    $SCRIPT_DIR/build_package.php --module $module
done

for file in $BUILD_DIR/*.tar.gz
do
    rpmfile=$(basename $file)
    rpmname=$(basename $rpmfile .tar.gz)
    pkgname=$(echo $rpmname | egrep -o '^[a-z,-]*' | sed 's/-$//')

    cp $file $HOME/rpmbuild/SOURCES
    cd $HOME/rpmbuild/SPECS
    tar xOf $HOME/rpmbuild/SOURCES/$rpmfile $rpmname/$pkgname.spec > $pkgname.spec
    rpmbuild -bb $pkgname.spec
done
