#!/usr/bin/env bash

set -e

# Only run if we're doing an upgrade
if [[ "$XDMOD_TEST_MODE" == "upgrade" ]]; then

    # Check if an XDMOD_QA_BRANCH env variable has been set, if not then set a default.
    if [[ -z "$XDMOD_QA_BRANCH" ]]; then
        #XDMOD_QA_BRANCH="v1"
        XDMOD_QA_BRANCH="migrate_travis" # This is only here until I merge this into v1
    fi

    # Check if XDMOD_SOURCE_DIR env variable exists, if not then we can't continue.
    if [[ -z "$XDMOD_SOURCE_DIR" ]]; then
        echo "XDMOD_SOURCE_DIR must be set before running this script."
        exit 1
    fi

    # Clone a current copy of the xdmod-qa repo.
    #GIT_URL="https://github.com/ubccr/xdmod-qa.git"
    GIT_URL="https://github.com/ryanrath/xdmod-qa.git"
    git clone --depth=1 --branch="$XDMOD_QA_BRANCH" "$GIT_URL" $HOME/.qa

    pushd $HOME >/dev/null || exit 1

    # Setup the xdmod-qa environment / requirements.
    $HOME/.qa/travis/install.sh

    popd >/dev/null || exit 1

    pushd "$XDMOD_SOURCE_DIR" >/dev/null || exit 1

    # Run the xdmod-qa tests.
    $HOME/.qa/travis/build.sh

    popd >/dev/null || exit 1
fi
