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

# Shred, ingest and aggregate storage data for a single day and check to make
# sure that only one period is aggregated for each unit.
for storage_dir in $REF_DIR/storage_upgrade/*; do
    sudo -u xdmod xdmod-shredder -f storage -r $(basename $storage_dir) -d $storage_dir
done
last_modified_start_date=$(date +'%F %T')
sudo -u xdmod xdmod-ingestor --datatype storage
agg_output=$(mktemp --tmpdir storage-aggregation-XXXXXXXX)
sudo -u xdmod xdmod-ingestor --aggregate=storage --last-modified-start-date "$last_modified_start_date" | tee $agg_output
for unit in day month quarter year; do
    if ! grep -q "unit: $unit, periods: 1," $agg_output; then
        echo Did not aggregate 1 period of storage data for unit $unit
        exit 1
    fi
done
