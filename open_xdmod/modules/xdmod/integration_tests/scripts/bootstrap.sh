#!/bin/bash
# Bootstrap script that either sets up a fresh XDMoD test instance or upgrades
# an existing one.  This code is only designed to work inside the XDMoD test
# docker instances. However, since it is designed to test a real install, the
# set of commands that are run would work on a real production system.

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REF_SOURCE=`realpath $BASEDIR/../../tests/artifacts/xdmod-test-artifacts/xdmod/referencedata`
REF_DIR=/var/tmp/referencedata

cp -r $REF_SOURCE /var/tmp/

set -e
set -o pipefail

if [ "$XDMOD_TEST_MODE" = "fresh_install" ];
then
    rpm -qa | grep ^xdmod | xargs yum -y remove
    rm -rf /etc/xdmod
    rm -rf /var/lib/mysql && mkdir -p /var/lib/mysql
    yum -y install ~/rpmbuild/RPMS/*/*.rpm
    ~/bin/services start
    mysql -e "CREATE USER 'root'@'gateway' IDENTIFIED BY '';
    GRANT ALL PRIVILEGES ON *.* TO 'root'@'gateway' WITH GRANT OPTION;
    FLUSH PRIVILEGES;"
    expect $BASEDIR/xdmod-setup.tcl | col -b
    xdmod-import-csv -t hierarchy -i $REF_DIR/hierarchy.csv
    xdmod-import-csv -t group-to-hierarchy -i $REF_DIR/group-to-hierarchy.csv
    for resource in $REF_DIR/*.log; do
        sudo -u xdmod xdmod-shredder -r `basename $resource .log` -f slurm -i $resource;
    done
    sudo -u xdmod xdmod-shredder -r openstack -d $REF_DIR/openstack -f openstack
    sudo -u xdmod xdmod-ingestor
    sudo -u xdmod xdmod-import-csv -t names -i $REF_DIR/names.csv
    sudo -u xdmod xdmod-ingestor
    php /root/bin/createusers.php
    # This will ensure that the users created in `/root/bin/createusers.php`
    # have their organizations set correctly.
    sudo -u xdmod php /usr/share/xdmod/tools/etl/etl_overseer.php -p xdmod.acls-import
fi

if [ "$XDMOD_TEST_MODE" = "upgrade" ];
then
    yum -y install ~/rpmbuild/RPMS/*/*.rpm
    ~/bin/services start
    expect $BASEDIR/xdmod-upgrade.tcl | col -b
fi
