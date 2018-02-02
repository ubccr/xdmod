#!/bin/sh

PHPUNITARGS="$@"

cd $(dirname $0)
phpunit="$(readlink -f ../../../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

./artifacts/update-artifacts.sh

$phpunit ${PHPUNITARGS} .
