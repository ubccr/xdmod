#!/bin/bash
echo "Regression tests beginning:" `date +"%a %b %d %H:%M:%S.%3N %Y"`
set -e

junit_output_dir=""

if [ "$1" = "--junit-output-dir" ];
then
    junit_output_dir="$2"
fi

if [ -z $XDMOD_REALMS ]; then
    export XDMOD_REALMS=$(echo `mysql -Ne "SELECT name FROM moddb.realms"` | tr ' ' ',')
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

if [ ! -e ../ci/testing.json ];
then
    echo "ERROR missing testing.json file." >&2
    echo >&2
    cat README.md >&2
    false
fi

phpunit="$(readlink -f ../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

roles=( pub usr pi cd cs )

if [ "$REG_TEST_ALL" = "1" ]; then
    set +e

    if [[ "$XDMOD_REALMS" == *"Jobs"* ]];
    then
        $phpunit $(log_opts "UsageCharts-pub") --filter UsageChartsTest . #TODO: Implement UsageChartsTest for Cloud and Storage realms
    fi

    for role in ${roles[@]}; do
        opts="$(log_opts "UsageExplorer-${role}") --filter 'UsageExplorer((?i)${XDMOD_REALMS//,/$'|'})Test'"
        if [ $role = "pub" ]; then
            $phpunit $opts .
        else
            REG_TEST_USER_ROLE=$role $phpunit $opts .
        fi
    done
else
    pids=()

    if [[ "$XDMOD_REALMS" == *"Jobs"* ]];
    then
        $phpunit $(log_opts "UsageCharts-pub") --filter UsageChartsTest . & #TODO: Implement UsageChartsTest for Cloud and Storage realms
        pids+=($!)
    fi

    for role in ${roles[@]}; do
        opts="$(log_opts "UsageExplorer-${role}") --filter 'UsageExplorer((?i)${XDMOD_REALMS//,/$'|'})Test'"
        if [ $role = "pub" ]; then
            $phpunit $opts . &
            pids+=($!)
        else
            REG_TEST_USER_ROLE=$role $phpunit $opts . &
            pids+=($!)
        fi
    done

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
