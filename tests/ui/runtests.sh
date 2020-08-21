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

# This file is generally used in the docker build to speed things up.
# Set it to something different if you want to use your own.
CACHEFILE='/root/browser-tests-node-modules.tar.gz'

set -e
set -o pipefail

echo "UI tests beginning:" `date +"%a %b %d %H:%M:%S.%3N %Y"`

if [ "$1" = "--headless" ];
then
    WDIO_MODE=headless
    export WDIO_MODE
fi

if [ "$2" = "--log-junit" ];
then
    JUNIT_OUTDIR="$3"
    export JUNIT_OUTDIR
fi

pushd `dirname $0`
if [[ ! -d 'node_modules' ]]; then
    if [[ -f "${CACHEFILE}" ]]; then
        echo "using cache file"
        tar -moxf "${CACHEFILE}"
        # fibers needs to be installed because the cache file was built with a
        # different version of node that is needed for chromedriver.
        npm install fibers
    else
        echo "No cache file found."
        exit 1
    fi
fi

if [ "$4" = "--sso" ];
then
    npm run test-sso
else
    npm test
fi
