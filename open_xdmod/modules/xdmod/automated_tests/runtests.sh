#!/bin/bash
# This file is generally used in the docker build to speed things up.
# Set it to something different if you want to use your own.
CACHEFILE='/tmp/browser-tests-node-modules.tar.gz'
set -e
set -o pipefail

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


if [[ ! -d 'node_modules' && -f $CACHEFILE ]];
then
    tar -moxf $CACHEFILE
fi
npm set progress=false
npm install --quiet

if [ "$4" = "--sso" ];
then
    npm run test-sso
else
    npm test
fi
