#!/bin/sh
# Tests that cloud ETL works after upgrade + reingestion

BASEDIR=./open_xdmod/modules/xdmod/regression_tests
REF_DIR=/var/tmp/referencedata
last_modified_start_date=$(date +'%F %T')

oldCount=$(mysql -N -B -e "SELECT SUM(num_sessions_ended) FROM modw_cloud.cloudfact_by_month WHERE month = 4;")

if [ $oldCount -ne 53 ]
then
    echo " Count $oldCount did not match expected result of 53"
    exit 1
fi

sudo -u xdmod xdmod-shredder -r openstack -d $REF_DIR/openstack_upgrade -f openstack
sudo -u xdmod xdmod-ingestor --datatype=openstack
sudo -u xdmod xdmod-ingestor --aggregate=cloud --last-modified-start-date "$last_modified_start_date"

newCount=$(mysql -N -B -e "SELECT SUM(num_sessions_ended) FROM modw_cloud.cloudfact_by_month WHERE month = 4;")

if [ $newCount -ne 52 ]
then
    echo " Count $newCount did not match expected result of 52"
    exit 1

fi

newRows=$(mysql -N -B -e "SELECT SUM(num_sessions_ended) FROM modw_cloud.cloudfact_by_month WHERE month = 5;")

if [ $newRows -ne 2 ]
then
    echo " Count $newRows did not match expected result of 2"
    exit 1

fi
