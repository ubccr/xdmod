#!/bin/bash

if [ -z $XDMOD_REALMS ]; then
    echo "XDMOD_REALMS is not set"
    echo "Default for core xdmod is: XDMOD_REALMS='jobs,storage,cloud,resourcespecifications'"
    echo "Default for JobPerformance is: XDMOD_REALMS='jobs,storage,cloud,resourcespecifications,supremm,jobefficiency'"
    echo "Default for OnDemand is: XDMOD_REALMS='jobs,storage,cloud,resourcespecifications,ondemand'"
    exit 1
fi

junit_output_dir=""

if [ "$1" = "--junit-output-dir" ];
then
    junit_output_dir="$2"
fi

# Output PHPUnit logging options.  First argument is the test category
# and the second is used to identify the test set.
log_opts() {
    if [ "$junit_output_dir" = "" ]; then
        return
    fi

    echo "--log-junit $junit_output_dir/xdmod-$1-$2.xml"
}
