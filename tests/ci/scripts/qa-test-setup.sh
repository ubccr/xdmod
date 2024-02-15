#!/usr/bin/env bash

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
XDMOD_SOURCE_DIR=${XDMOD_SOURCE_DIR:-$BASEDIR/../../../}

set -e

# Only run if we're doing an upgrade
if [[ "$XDMOD_TEST_MODE" == "upgrade" ]]; then

    # Set default values for the environment variables we're going to use.
    QA_BRANCH=${QA_BRANCH:-plotly_linter}
    QA_GIT_URL=${QA_GIT_URL:-https://github.com/aestoltm/xdmod-qa.git}

    # Clone a current copy of the xdmod-qa repo.
    git clone --depth=1 --branch="$QA_BRANCH" "$QA_GIT_URL" $HOME/.qa

    # Switch to the repo root
    pushd $XDMOD_SOURCE_DIR >/dev/null || exit 1

    # Specify composer.json for xdmod-qa so xdmod dev-dependencies aren't removed.
    export COMPOSER="$HOME/.qa/composer.json"

    # Setup the xdmod-qa environment / requirements.
    $HOME/.qa/scripts/install.sh

    # Run the xdmod-qa tests.
    $HOME/.qa/scripts/build.sh

    popd >/dev/null || exit 1
fi
