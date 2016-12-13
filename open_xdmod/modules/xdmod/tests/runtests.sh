#!/bin/sh

if { ! which phpunit >/dev/null 2>&1; } then
    echo phpunit not found 1>&2
    exit 127
fi

cd $(dirname $0)
phpunit .
exit $?
