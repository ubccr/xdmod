#!/bin/bash

set -e

junit_output_dir=""

if [ "$1" = "--junit-output-dir" ];
then
    junit_output_dir="$2"
fi

# Output PHPUnit logging options.  First argument is a unique identifier that
# will be used in the log file name.
log_opts() {
    if [ "$junit_output_dir" = "" ]; then
        return
    fi

    echo "--log-junit $junit_output_dir/xdmod-regression-$1.xml"
}

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

roles=( pub usr pi cd cs )

if [ "$REG_TEST_ALL" = "1" ]; then
    set +e
    $phpunit $(log_opts "UsageCharts-pub") --filter UsageChartsTest .

    for role in ${roles[@]}; do
        opts="$(log_opts "UsageExplorer-${role}") --filter 'UsageExplorer\w+Test'"
        if [ $role = "pub" ]; then
            $phpunit $opts .
        else
            REG_TEST_USER_ROLE=$role $phpunit $opts .
        fi
    done
else
    pids=()

    $phpunit $(log_opts "UsageCharts-pub") --filter UsageChartsTest . &
    pids+=($!)

    for role in ${roles[@]}; do
        opts="$(log_opts "UsageExplorer-${role}") --filter 'UsageExplorer\w+Test'"
        if [ $role = "pub" ]; then
            $phpunit $opts . &
            pids+=($!)
        else
            REG_TEST_USER_ROLE=$role $phpunit $opts . &
            pids+=($!)
        fi
    done

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
