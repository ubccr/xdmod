#!/bin/sh

set -e

if [ ! -e .secrets ];
then
    echo "ERROR missing .secrets file." >&2
    echo >&2
    cat README.md >&2
    false
fi

phpunit `dirname $0`
