#!/bin/bash
# Bootstrap script that either sets up a fresh XDMoD test instance or upgrades
# an existing one.  This code is only designed to work inside the XDMoD test
# docker instances. However, since it is designed to test a real install, the
# set of commands that are run would work on a real production system.

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REF_DIR=/root/assets/referencedata

set -e
set -o pipefail

if [ "$XDMOD_TEST_MODE" = "fresh_install" ];
then
    rpm -qa | grep ^xdmod | xargs yum -y remove
    rm -rf /etc/xdmod
    rm -rf /var/lib/mysql && mkdir -p /var/lib/mysql
    yum -y remove java-1.7.0-openjdk java-1.7.0-openjdk-devel
    yum -y install ~/rpmbuild/RPMS/*/*.rpm
    ~/bin/services start
    expect $BASEDIR/xdmod-setup.tcl | col -b
    for resource in $REF_DIR/*.log; do
        xdmod-shredder -r `basename $resource .log` -f slurm -i $resource;
    done
    xdmod-ingestor --ingest-shredded
    xdmod-ingestor --ingest-staging
    #this will be moved into the xdmod-ingestor after testing is complete
    php /usr/share/xdmod/tools/etl/etl_overseer.php -c /etc/xdmod/etl/etl.json -p hpcdb-modw.ingest
    xdmod-import-csv -t names -i $REF_DIR/names.csv
    #this will be moved into the xdmod-ingestor after testing is complete
    php /usr/share/xdmod/tools/etl/etl_overseer.php -c /etc/xdmod/etl/etl.json -p hpcdb-modw.ingest
    #this will be moved into the xdmod-ingestor after testing is complete
    php /usr/share/xdmod/tools/etl/etl_overseer.php -c /etc/xdmod/etl/etl.json -p hpcdb-modw.aggregate
    #this will be removed when the ingest and aggregate processes are moved to xdmod-ingestor
    xdmod-ingestor --build-filter-lists
    php /root/bin/createusers.php
fi

if [ "$XDMOD_TEST_MODE" = "upgrade" ];
then
    yum -y install ~/rpmbuild/RPMS/*/*.rpm
    ~/bin/services start
    expect $BASEDIR/xdmod-upgrade.tcl | col -b
fi
