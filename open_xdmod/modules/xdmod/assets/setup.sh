#!/bin/bash

assets_dir="$(
    cd "$(dirname "$0")"
    pwd
)"
module_dir="$assets_dir/.."
xdmod_dir="$module_dir/../../.."

echo Removing existing dependencies
rm -rf $xdmod_dir/external_libraries

echo Creating directory for external libraries
mkdir $xdmod_dir/external_libraries

echo Installing composer managed dependencies
cd $xdmod_dir
composer install --no-dev

echo Compiling report builder code
cd "$xdmod_dir/reporting/jasper_builder"
bash ReportBuilder.sh -C
