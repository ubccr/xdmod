#!/usr/bin/env bash

set -e

# Only run if we're doing an upgrade
if [[ "$XDMOD_TEST_MODE" == "upgrade" ]]; then

    # Set default values for the environment variables we're going to use.
    QA_BRANCH=${QA_BRANCH:-migrate_travis}
    QA_GIT_URL=${QA_GIT_URL:-https://github.com/ryanrath/xdmod-qa.git}


    # Check if XDMOD_SOURCE_DIR env variable exists, if not then we can't continue.
    if [[ -z "$XDMOD_SOURCE_DIR" ]]; then
        echo "XDMOD_SOURCE_DIR must be set before running this script."
        exit 1
    fi

    # Clone a current copy of the xdmod-qa repo.
    git clone --depth=1 --branch="$QA_BRANCH" "$QA_GIT_URL" $HOME/.qa

    pushd "$XDMOD_SOURCE_DIR" >/dev/null || exit 1

    # Setup the xdmod-qa environment / requirements.
    $HOME/.qa/scripts/install.sh

    # Run the xdmod-qa tests.
    $HOME/.qa/scripts/build.sh

    popd >/dev/null || exit 1
fi
