#!/bin/bash
# Bootstrap script that either sets up a fresh XDMoD test instance or upgrades
# an existing one.  This code is only designed to work inside the XDMoD test
# docker instances. However, since it is designed to test a real install, the
# set of commands that are run would work on a real production system.

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REF_DIR=$BASEDIR/../../tests/artifacts/xdmod-test-artifacts/xdmod/referencedata

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
    for resource in $REF_DIR/*.log; do
        xdmod-shredder -r `basename $resource .log` -f slurm -i $resource;
    done
    xdmod-ingestor
    xdmod-import-csv -t names -i $REF_DIR/names.csv
    xdmod-ingestor
    php /root/bin/createusers.php
    # This will ensure that the users created in `/root/bin/createusers.php`
    # have their organizations set correctly.
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p xdmod.acls-import
    #Updating minmaxdate table so data for cloud realm shows up
    mysql -e "UPDATE modw.minmaxdate SET max_job_date = '2018-07-01';"
    #Ingesting cloud data from references folder
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-common
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-common
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p ingest-resources
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-ingest-openstack -r openstack -d "CLOUD_EVENT_LOG_DIRECTORY=$REF_DIR/openstack"
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-extract-openstack
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p cloud-state-pipeline
fi

if [ "$XDMOD_TEST_MODE" = "upgrade" ];
then
    yum -y install ~/rpmbuild/RPMS/*/*.rpm
    ~/bin/services start
    # Delete this once it is added to the docker build by default
    mysql -e "CREATE USER 'root'@'gateway' IDENTIFIED BY '';
    GRANT ALL PRIVILEGES ON *.* TO 'root'@'gateway' WITH GRANT OPTION;
    FLUSH PRIVILEGES;"
    expect $BASEDIR/xdmod-upgrade.tcl | col -b
    expect $BASEDIR/xdmod-upgrade-add-cloud-resource.tcl | col -b
    #Updating minmaxdate table so data for cloud realm shows up
    mysql -e "UPDATE modw.minmaxdate SET max_job_date = '2018-07-01';"
    #Ingesting cloud data from references folder
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-common
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-common
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p ingest-resources
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-ingest-openstack -r openstack -d "CLOUD_EVENT_LOG_DIRECTORY=$REF_DIR/openstack"
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-extract-openstack
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p cloud-state-pipeline
fi
