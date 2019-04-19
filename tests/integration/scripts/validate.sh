#!/bin/bash
# This script is intended to be used to run tests that validate the correct
# packaging / install behavour of XDMoD. For example checking the
# file permissions are correct on the RPM packaged files.

exitcode=0

# Check that there are no development artifacts installed in the RPM
if rpm -ql xdmod | fgrep .eslintrc.json; then
    echo "Error eslintrc files found in the RPM"
    exitcode=1
fi

for file in open_xdmod/build/*.tar.gz;
do
    echo "Checking $file"
    if tar tf $file | fgrep .eslintrc.json; then
        echo "Error eslintrc files found in build tarball $file"
        exitcode=1
    fi
done

exit $exitcode
