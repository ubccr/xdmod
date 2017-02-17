#!/bin/bash
# This file is for running all of the tests contained in this directory. 
# It calls php unit that creates a TestRunner, which in turn uses an 
# IncludePathTestCollector to recursively discover all the tests in the
# subdirectories of this directory (classes\UnitTesting)
#
# @author: Amin Ghadersohi
# @date: 2/25/2011
#
PHPUNITARGS=""
if [ "$1" = "coverage" ];
then
    PHPUNITARGS="${PHPUNITARGS} --coverage-html ../../html/phpunit"
fi

cd $(dirname $0)
phpunit="$(readlink -f ../../vendor/bin/phpunit)"

if [ ! -x "$phpunit" ]; then
    echo phpunit not found, run \"composer install\" 1>&2
    exit 127
fi

$phpunit ${PHPUNITARGS} .
