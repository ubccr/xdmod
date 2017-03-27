The integration test framework is designed to be used to run tests against
an installed and working XDMoD instance.

The tests may require a valid user account on XDMoD. The username and password
are read from a file called .secrets in the test directory. This file is
intentionally not checked in to source control and must be created.
A sample configuration file is in secrets_TEMPLATE

You must specify the URL of the XDMoD instance in the .secrets file.

Run the tests with ./runtests.sh
