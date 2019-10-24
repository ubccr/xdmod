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

# Check that the unknown resource is in the database with key 0
unkrescount=$(echo 'SELECT COUNT(*) from resourcetype WHERE id = 0 and abbrev = '"'"'UNK'"'" | mysql -N modw)
if [ $unkrescount -ne 1 ];
then
    echo "Missing / inconsistent 'UNK' row in modw.resourcetype"
    exitcode=1
fi

exit $exitcode
