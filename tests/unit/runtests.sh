#!/bin/bash
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source $BASEDIR/../ci/runtest-include.sh
echo "Unit tests beginging:" `date +"%a %b %d %H:%M:%S.%3N %Y"`
PHPUNITARGS=""
if [ "$1" = "coverage" ];
then
    PHPUNITARGS="${PHPUNITARGS} --coverage-html ../../../../html/phpunit"
fi

cd $(dirname $0)
phpunit="$(readlink -f ../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

$phpunit $(log_opts "unit" "all") ${PHPUNITARGS} .
exit $?
