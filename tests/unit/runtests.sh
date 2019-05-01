#!/bin/sh

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

$phpunit ${PHPUNITARGS} .
exit $?
