#!/bin/bash

assets_dir="$(
    cd "$(dirname "$0")"
    pwd
)"
module_dir="$assets_dir/.."
xdmod_dir="$module_dir/../../.."

echo Installing npm managed dependencies
npm install --production --prefix etl/js
npm install --production --prefix background_scripts/chrome-helper
