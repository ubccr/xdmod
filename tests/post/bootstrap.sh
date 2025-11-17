#!/bin/bash

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REF_DIR=/var/tmp/posttests

# Copy test artifacts
if [ ! -e $REF_DIR ];
then
   mkdir -p $REF_DIR
   cp $BASEDIR/../artifacts/xdmod/post/*.log $REF_DIR
   cat $BASEDIR/../artifacts/xdmod/referencedata/names.csv $BASEDIR/../artifacts/xdmod/post/names-utf8.csv > $REF_DIR/names.csv
fi

if [[ "$XDMOD_REALMS" == *"jobs"* ]];
then
    for resource in $REF_DIR/*.log; do
        sudo -u xdmod xdmod-shredder -r `basename $resource .log` -f slurm -i $resource;
    done
fi

sudo -u xdmod xdmod-import-csv -t names -i $REF_DIR/names.csv
sudo -u xdmod xdmod-ingestor
