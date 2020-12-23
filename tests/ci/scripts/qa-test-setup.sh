#!/usr/bin/env bash

set -e

# Only run if we're doing an upgrade
if [[ "$XDMOD_TEST_MODE" == "upgrade" ]]; then

    # Set default values for the environment variables we're going to use.
    QA_BRANCH=${QA_BRANCH:-v1}
    QA_GIT_URL=${QA_GIT_URL:-https://github.com/ubccr/xdmod-qa.git}


    # Check if XDMOD_SOURCE_DIR env variable exists, if not then we can't continue.
    if [[ -z "$SHIPPABLE_BUILD_DIR" ]]; then
        echo "XDMOD_SOURCE_DIR must be set before running this script."
        exit 1
    fi

    # Clone a current copy of the xdmod-qa repo.
    git clone --depth=1 --branch="$QA_BRANCH" "$QA_GIT_URL" $HOME/.qa

    pushd "$SHIPPABLE_BUILD_DIR" >/dev/null || exit 1

    # Setup the xdmod-qa environment / requirements.
    $HOME/.qa/scripts/install.sh

    # If we're running on Shippable then make sure to include the
    # base branch that we're merging into.
    build_args=""
    if [ "$SHIPPABLE" = "true" ]; then
        build_args="-r $BASE_BRANCH"
    fi

    # Run the xdmod-qa tests.
    $HOME/.qa/scripts/build.sh $build_args

    popd >/dev/null || exit 1
fi
