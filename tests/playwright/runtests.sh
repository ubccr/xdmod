#!/bin/bash
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source $BASEDIR/../ci/runtest-include.sh

SHMSIZEK=`df -k /dev/shm | grep shm | awk '{print $2}'`
if (( $SHMSIZEK < 2000000 )); then
    echo "***************************************************************"
    echo "Shared memory is less than 2G, tests may fail randomly"
    echo "If you are using Docker use the option of --shm-size 2g"
    echo "***************************************************************"
fi

set -e
#ensure that playwright installed
npm update
npm install -g @playwright/test@1.51.1

echo "UI tests beginning:" `date +"%a %b %d %H:%M:%S.%3N %Y"`

#playwright automatically runs in headless

while getopts ":j:" opt; do
    case ${opt} in
        j) log_junit=${OPTARG};;
    esac
done

if [ -n ${log_junit} ];
then
    PLAYWRIGHT_JUNIT_OUTPUT_NAME=test_results-${log_junit}.xml npx playwright test --reporter=junit tests/*
else
    npx playwright test tests/*
fi
