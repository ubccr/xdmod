#!/bin/sh

set -e

if [ ! -e ../integration_tests/.secrets.json ];
then
    echo "ERROR missing .secrets.json file." >&2
    echo >&2
    cat README.md >&2
    false
fi

PHPUNITARGS=""
if [ "$1" = "coverage" ];
then
    PHPUNITARGS="${PHPUNITARGS} --coverage-html ../../../../html/phpunit"
fi

cd $(dirname $0)
phpunit="../../../../vendor/bin/phpunit"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

$phpunit ${PHPUNITARGS} .
