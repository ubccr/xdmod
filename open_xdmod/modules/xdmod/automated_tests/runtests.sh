#!/bin/bash

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
npm set progress=false
npm install --quiet

if [ "$4" = "--sso" ];
then
    npm run test-sso
else
    npm test
fi
