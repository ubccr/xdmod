#!/bin/bash
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
XDMOD_SRC_DIR=${XDMOD_SRC_DIR:-$BASEDIR/../..}
source $XDMOD_SRC_DIR/tests/ci/runtest-include.sh
echo "Regression tests beginning:" `date +"%a %b %d %H:%M:%S.%3N %Y"`
set -e

export XDMOD_REALMS

cd $(dirname $0)

if [ ! -e $XDMOD_SRC_DIR/tests/ci/testing.json ];
then
    echo "ERROR missing testing.json file." >&2
    echo >&2
    cat README.md >&2
    false
fi

phpunit="$(readlink -f $XDMOD_SRC_DIR/vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

roles=( pub usr pi cd cs )

function run_warehouse_raw_data_tests {
    # Need to ignore the values of deidentified usernames when comparing the
    # actual and expected data because the values will be different depending
    # on whether XDMOD_TEST_MODE is set to 'upgrade' or 'fresh_install' since
    # these two testing pipelines produce different hashes for the usernames.
    REG_TEST_USER_ROLE=usr \
        REG_TEST_REGEX='/[0-9a-f]{40}/' \
        REG_TEST_REPLACE='<username>' \
        $phpunit $(log_opts "regression-$1" "WarehouseRawDataTest") \
            --filter WarehouseRawDataTest .
}

if [ "$REG_TEST_ALL" = "1" ]; then
    set +e
    if [[ "$XDMOD_REALMS" == *"jobs"* ]];
    then
        $phpunit $(log_opts "regression-all" "Charts-pub") --filter ChartsTest . #TODO: Implement UsageChartsTest for Cloud and Storage realms
    fi

    for role in ${roles[@]}; do
        opts="$(log_opts "regression-all" "UsageExplorer-${role}") --filter 'UsageExplorer((?i)${XDMOD_REALMS//,/$'|'})Test'"
        if [ $role = "pub" ]; then
            $phpunit $opts .
        else
            REG_TEST_USER_ROLE=$role $phpunit $opts .
        fi
    done

    run_warehouse_raw_data_tests 'all'
else
    pids=()

    # Make Ctrl-C interrupt all subprocesses.
    function interrupt_all_subprocesses {
        kill ${pids[@]}
    }
    trap interrupt_all_subprocesses SIGINT

    if [[ "$XDMOD_REALMS" == *"jobs"* ]];
    then
        $phpunit $(log_opts "regression-subset" "Charts-pub") --filter ChartsTest . & #TODO: Implement UsageChartsTest for Cloud and Storage realms
        pids+=($!)
    fi

    for role in ${roles[@]}; do
        opts="$(log_opts "regression-subset" "UsageExplorer-${role}") --filter 'UsageExplorer((?i)${XDMOD_REALMS//,/$'|'})Test'"
        if [ $role = "pub" ]; then
            $phpunit $opts . &
            pids+=($!)
        else
            REG_TEST_USER_ROLE=$role $phpunit $opts . &
            pids+=($!)
        fi
    done

    run_warehouse_raw_data_tests 'subset'
    pids+=($!)

    # Wait for tests to finish, if any fail, return exit status of 1
    EXIT_STATUS=0
    for pid in ${pids[@]}
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
