#!/bin/bash

REF_DIR=/var/tmp/posttests

if [[ "$XDMOD_REALMS" == *"jobs"* ]];
then
    for resource in $REF_DIR/*.log; do
        sudo -u xdmod xdmod-shredder -r `basename $resource .log` -f slurm -i $resource;
    done
fi

sudo -u xdmod xdmod-import-csv -t names -i $REF_DIR/names.csv
sudo -u xdmod xdmod-ingestor
