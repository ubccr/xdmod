#!/bin/bash

SRC_DIR=`pwd`
BUILD_DIR=$SRC_DIR/open_xdmod/build
SCRIPT_DIR=$SRC_DIR/open_xdmod/build_scripts

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
