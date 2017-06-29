#!/usr/bin/env bash

xdmod_test_artifacts_source="https://github.com/ubccr/xdmod-test-artifacts.git"
using_xdmod_test_artifacts_mirror="false"; [ -n "$XDMOD_TEST_ARTIFACTS_MIRROR" ] && using_xdmod_test_artifacts_mirror="true"

# Change directory to this script's directory.
cd "$(dirname $0)" || exit 1

# If using a mirror of the xdmod-test-artifacts repo, create or update it.
#
# Travis will create any directories that are set up for caching if they do
# not exist, so also check if the directory has contents.
if "$using_xdmod_test_artifacts_mirror"; then
    if [ -d "$XDMOD_TEST_ARTIFACTS_MIRROR" ] && [ -n "$(ls -A "$XDMOD_TEST_ARTIFACTS_MIRROR")" ]; then
        echo "Updating xdmod-test-artifacts mirror..."
        git -C "$XDMOD_TEST_ARTIFACTS_MIRROR" remote update
    else
        echo "Creating mirror of xdmod-test-artifacts..."
        git clone --mirror "$xdmod_test_artifacts_source" "$XDMOD_TEST_ARTIFACTS_MIRROR"
    fi
fi

# If the xdmod-test-artifacts repo already exists locally, update it.
# Otherwise, clone it.
artifacts_dir="./xdmod-test-artifacts"
if [ -d "$artifacts_dir" ]; then
    echo "Updating local xdmod-test-artifacts clone..."
    git -C "$artifacts_dir" pull
else
    echo "Cloning xdmod-test-artifacts into local directory..."
    if "$using_xdmod_test_artifacts_mirror"; then
        local_clone_source="$XDMOD_TEST_ARTIFACTS_MIRROR"
    else
        local_clone_source="$xdmod_test_artifacts_source"
    fi
    git clone "$local_clone_source" "$artifacts_dir"
fi
