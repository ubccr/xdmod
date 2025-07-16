#!/usr/bin/env bash

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
XDMOD_SOURCE_DIR=${XDMOD_SOURCE_DIR:-$BASEDIR/../../../}
XDMOD_URL="$( jq -r '(.url[:-1] + ":443")' "${BASEDIR}"/../testing.json)"
CD_USENAME="$( jq '.role.cd.username' "${BASEDIR}"/../testing.json)"
CD_PASSWORD="$( jq '.role.cd.username' "${BASEDIR}"/../testing.json)"
BUILDENV="ME-export-saved-charts-venv"

set -e

pushd $XDMOD_SOURCE_DIR >/dev/null || exit 1

if [ -z $BUILDENV ]; then
    mkdir $BUILDENV
fi

python3 -m venv $BUILDENV
source $BUILDENV/bin/activate

pip3 install -q --upgrade pip
pip3 install -q requests python-dotenv

echo "XDMOD_USERNAME="${CD_USENAME}"" > $XDMOD_SOURCE_DIR/docs/assets/scripts/.env
echo "XDMOD_PASSWORD="${CD_PASSWORD}"" >> $XDMOD_SOURCE_DIR/docs/assets/scripts/.env

# Fix the url, udate export dir, and disable SSL check for localhost
sed -i "s|site_address = ''|site_address = '${XDMOD_URL}'|g" $XDMOD_SOURCE_DIR/docs/assets/scripts/export-metric-explorer-charts.py
sed -i "s|export_dir = '.'|export_dir = '/tmp'|g" $XDMOD_SOURCE_DIR/docs/assets/scripts/export-metric-explorer-charts.py
sed -i '/session = requests.Session()/a session.verify = False' $XDMOD_SOURCE_DIR/docs/assets/scripts/export-metric-explorer-charts.py

chmod +x $XDMOD_SOURCE_DIR/docs/assets/scripts/export-metric-explorer-charts.py
$XDMOD_SOURCE_DIR/docs/assets/scripts/export-metric-explorer-charts.py

popd >/dev/null || exit 1
