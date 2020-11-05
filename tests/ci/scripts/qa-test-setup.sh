#!/usr/bin/env bash

# Make sure that we exit if an error occurs.
set -e

# Only run if we're doing an upgrade
if [[ "$XDMOD_TEST_MODE" == "upgrade" ]]; then

    # Check if an XDMOD_QA_BRANCH env variable has been set, if not then set a default.
    if [[ -z "$XDMOD_QA_BRANCH" ]]; then
        XDMOD_QA_BRANCH="v1"
    fi

    # Check if XDMOD_SOURCE_DIR env variable exists, if not then we can't continue.
    if [[ -z "$XDMOD_SOURCE_DIR" ]]; then
        echo "XDMOD_SOURCE_DIR must be set before running this script."
        exit 1
    fi

    # Make sure that we're in the XDMOD_SOURCE_DIR before continuing
    pushd "$XDMOD_SOURCE_DIR" >/dev/null || exit 1

    # Clone a current copy of the xdmod-qa repo.
    git clone --depth=1 --branch="$XDMOD_QA_BRANCH" https://github.com/ubccr/xdmod-qa.git .qa

    # Setup the xdmod-qa environment / requirements.
    .qa/travis/install.sh

    # Run the xdmod-qa tests.
    .qa/travis/build.sh

    # Make sure that we go back to whatever directory we were in pre-pushd.
    popd >/dev/null || exit 1
fi
