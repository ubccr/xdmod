#!/bin/bash

set -e

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
npm install
npm test
