#!/bin/sh

set -e

REGUSER=""
PI=""
CD=""
CS=""
PUB=""

if [ "$1" = "--junit-output-dir" ];
then
    REGUSER="--log-junit $2/xdmod-regression-user.xml"
    PI="--log-junit $2/xdmod-regression-principalinvestigator.xml"
    CD="--log-junit $2/xdmod-regression-centerdirector.xml"
    CS="--log-junit $2/xdmod-regression-centerstaff.xml"
    PUB="--log-junit $2/xdmod-regression-public.xml"
fi

cd $(dirname $0)

if [ ! -e ../integration_tests/.secrets.json ];
then
    echo "ERROR missing .secrets.json file." >&2
    echo >&2
    cat README.md >&2
    false
fi

phpunit="$(readlink -f ../../../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi
if [ "$REG_TEST_ALL" == "1" ]; then
    set +e
    $phpunit $PUB lib/Controllers/UsageChartsTest.php
    $phpunit $PUB lib/Controllers/UsageExplorerTest.php
    REG_TEST_USER_ROLE=usr $phpunit $REGUSER lib/Controllers/UsageExplorerTest.php
    REG_TEST_USER_ROLE=pi $phpunit $PI lib/Controllers/UsageExplorerTest.php
    REG_TEST_USER_ROLE=cd $phpunit $CD lib/Controllers/UsageExplorerTest.php
    REG_TEST_USER_ROLE=cs $phpunit $CS lib/Controllers/UsageExplorerTest.php

    $phpunit $PUB lib/Controllers/UsageExplorerCloudTest.php
    REG_TEST_USER_ROLE=usr $phpunit $REGUSER lib/Controllers/UsageExplorerCloudTest.php
    REG_TEST_USER_ROLE=pi $phpunit $PI lib/Controllers/UsageExplorerCloudTest.php
    REG_TEST_USER_ROLE=cd $phpunit $CD lib/Controllers/UsageExplorerCloudTest.php
    REG_TEST_USER_ROLE=cs $phpunit $CS lib/Controllers/UsageExplorerCloudTest.php
else
    pids=()
    $phpunit $PUB lib/Controllers/UsageChartsTest.php & pids+=("$!")
    $phpunit $PUB lib/Controllers/UsageExplorerTest.php & pids+=("$!")
    REG_TEST_USER_ROLE=usr $phpunit $REGUSER lib/Controllers/UsageExplorerTest.php & pids+=("$!")
    REG_TEST_USER_ROLE=pi $phpunit $PI lib/Controllers/UsageExplorerTest.php & pids+=("$!")
    REG_TEST_USER_ROLE=cd $phpunit $CD lib/Controllers/UsageExplorerTest.php & pids+=("$!")
    REG_TEST_USER_ROLE=cs $phpunit $CS lib/Controllers/UsageExplorerTest.php & pids+=("$!")

    REG_TEST_USER_ROLE=usr $phpunit $REGUSER lib/Controllers/UsageExplorerCloudTest.php & pids+=("$!")
    REG_TEST_USER_ROLE=pi $phpunit $PI lib/Controllers/UsageExplorerCloudTest.php & pids+=("$!")
    REG_TEST_USER_ROLE=cd $phpunit $CD lib/Controllers/UsageExplorerCloudTest.php & pids+=("$!")
    REG_TEST_USER_ROLE=cs $phpunit $CS lib/Controllers/UsageExplorerCloudTest.php & pids+=("$!")
    $phpunit $PUB lib/Controllers/UsageExplorerCloudTest.php & pids+=("$!")

    EXIT_STATUS=0
    for pid in "${pids[@]}";
    do
        wait "$pid"
        if [ "$?" -ne "0" ];
        then
            EXIT_STATUS=1
        fi
    done
    echo "Parallel Tests Finished."
    exit $EXIT_STATUS
fi
