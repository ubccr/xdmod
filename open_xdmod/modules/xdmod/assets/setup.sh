#!/bin/bash

assets_dir="$(
    cd "$(dirname "$0")"
    pwd
)"
module_dir="$assets_dir/.."
xdmod_dir="$module_dir/../../.."

echo Installing composer managed dependencies
cd $xdmod_dir
composer install --no-dev

echo Installing npm managed dependencies
npm install --production --prefix etl/js

echo Compiling report builder code
cd "$xdmod_dir/reporting/jasper_builder"
bash ReportBuilder.sh -C
