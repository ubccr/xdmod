#!/bin/bash
pushd /xdmod || exit
#Setup & Run QA Tests
./tests/ci/scripts/qa-test-setup.sh

#Make sure that the Composer Test Depedencies are installed
composer install --no-progress

./tests/regression/runtests.sh

#Setup Configuration Files for Integration Tests
mv ./configuration/organization.json ./configuration/organization.json.old
mv ./configuration/portal_settings.ini ./configuration/portal_settings.ini.old
cp /etc/xdmod/portal_settings.ini ./configuration/portal_settings.ini
cp /etc/xdmod/organization.json ./configuration/organization.json

./tests/integration/runtests.sh --junit-output-dir ~/phpunit
./tests/regression/post_ingest_test.sh --junit-output-dir ~/phpunit
./tests/component/runtests.sh --junit-output-dir ~/phpunint

# Ensure that no unexpected Apache errors were gathered
test `fgrep -v ' [ssl:warn] ' /var/log/xdmod/apache-error.log | wc -l` = 0

popd || exit
