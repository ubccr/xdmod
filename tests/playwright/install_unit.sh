#!/bin/bash
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REPODIR=$(realpath $BASEDIR/../../)

cp -r $REPODIR/html/unit_tests /usr/share/xdmod/html
