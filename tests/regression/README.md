The regression tests use the integration test framework, which is designed to be used to run tests against an installed and working XDMoD instance.

The tests require a valid user account on XDMoD. The username and password are read from a file called testing.json in the `tests/ci` directory. Please follow the instructions there to setup the password file.

Run the tests with ./runtests.sh

# Generating test source data

run the maketests.js on an installed and working XDMoD instance. This will generate test input data in a directory. Copy these test files to the artifact input directory.

# Generating the expected results

Run the tests without anything and a folder will be created $baseDir/expected/{host_name} containing the results

To turn this into the reference dataset rename the host_name folder to reference

# Available Environment Variables

REG_TEST_USER_ROLE the role in secrets.json to use If this is not passed in public user is assume and no authentication is done

REG_TEST_BASE used to override the base directory for test artifacts

The rest are generally used for federation testing but might be useful for other tests as well

REG_TEST_RESOURCE used to set a resource to test against

REG_TEST_REGEX comma separated list of regular expresses to replace in result ex. /(?:Instance One|OpenXDMoD - Federation)/,/\s((?:daywalk|sunscrn))/,/\s((?:daywalk-instanceo))/,/\s((?:sunscrn-instanceb))/,/(?:Instance One|OpenXDMoD - Federation)\s/

REG_TEST_REPLACE comma separated list of replacements for the regular expressions

REG_TEST_FORMAT expected format default csv

REG_TEST_ALT_EXPECTED hostname to validate data against
