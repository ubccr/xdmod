The regression tests use the integration test framework, which is designed to
be used to run tests against an installed and working XDMoD instance.

The tests require a valid user account on XDMoD. The username and password
are read from a file called .secrets in the integration test directory.  Please
follow the instructions in the integration test directory to setup the password
file.

Run the tests with ./runtests.sh

Generating test source data
---------------------------

run the maketests.js on an installed and working XDMoD instance. This will generate
test input data in a directory. Copy these test files to the artifact input directory.

Generating the expected results
-------------------------------

Edit the php class to write output data then run the tests.
