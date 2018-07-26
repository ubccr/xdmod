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
    yum -y install ~/rpmbuild/RPMS/*/*.rpm
    ~/bin/services start
    mysql -e "CREATE USER 'root'@'gateway' IDENTIFIED BY '';
    GRANT ALL PRIVILEGES ON *.* TO 'root'@'gateway' WITH GRANT OPTION;
    FLUSH PRIVILEGES;"
    #Copying roles file so Cloud realm shows up
    cp $BASEDIR/../../../../../configuration/datawarehouse.d/cloud.json /etc/xdmod/datawarehouse.d/
    cp $BASEDIR/../../../../../configuration/roles.d/cloud.json /etc/xdmod/roles.d/
    expect $BASEDIR/xdmod-setup.tcl | col -b
    for resource in $REF_DIR/*.log; do
        xdmod-shredder -r `basename $resource .log` -f slurm -i $resource;
    done
    xdmod-ingestor
    xdmod-import-csv -t names -i $REF_DIR/names.csv
    xdmod-ingestor
    php /root/bin/createusers.php
    #Adding open stack resource since there is no way to automatically add a cloud resource.
    mysql -e "INSERT INTO modw.resourcefact (resourcetype_id, organization_id, name, code, resource_origin_id) VALUES (1,1,'OpenStack', 'openstack', 6);
    UPDATE modw.minmaxdate SET max_job_date = '2018-07-01';"
    #Set path to Open Stack test data in Open Stack ingestion file
    sed -i "s%/path/to/data%/root/assets/referencedata/openstack%" /etc/xdmod/etl/etl.d/jobs_cloud_openstack.json
    #Ingesting cloud data from references folder
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-common
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-ingest-openstack -r openstack
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-extract-openstack
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p cloud-state-pipeline
    acl-import
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
    #Copying roles file so Cloud realm shows up
    cp $BASEDIR/../../../../../configuration/datawarehouse.d/cloud.json /etc/xdmod/datawarehouse.d/
    cp $BASEDIR/../../../../../configuration/roles.d/cloud.json /etc/xdmod/roles.d/
    #Adding open stack resource since there is no way to automatically add a cloud resource.
    mysql -e "INSERT INTO modw.resourcefact (resourcetype_id, organization_id, name, code, resource_origin_id) VALUES (1,1,'OpenStack', 'openstack', 6);
    UPDATE modw.minmaxdate SET max_job_date = '2018-07-01';"
    #Set path to Open Stack test data in Open Stack ingestion file
    sed -i "s%/path/to/data%/root/assets/referencedata/openstack%" /etc/xdmod/etl/etl.d/jobs_cloud_openstack.json
    #Ingesting cloud data from references folder
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-common
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-ingest-openstack -r openstack
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p jobs-cloud-extract-openstack
    php /usr/share/xdmod/tools/etl/etl_overseer.php -p cloud-state-pipeline
fi
