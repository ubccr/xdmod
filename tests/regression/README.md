# Open XDMoD Regression Tests

The regression tests use the integration test framework, which is designed to
run tests against an installed and working Open XDMoD instance.

The tests require valid user accounts on Open XDMoD for every user role that
will be tested. The username and password pairs are read from a file called
`testing.json` in the `tests/ci` directory. Please follow the instructions in
the `README.md` file in that directory to setup the password file.

Run the tests with `./runtests.sh`.

# Generating Expected Test Results

Run this command on an installed and working Open XDMoD instance:

```sh
XDMOD_REALMS='jobs,storage,cloud' REG_TEST_FORCE_GENERATION=1 REG_TEST_ALL=1 ./runtests.sh
```

This will generate the expected test output data in
`tests/artifacts/xdmod/regression/current/expected/{host_name}`.  To update the
reference dataset copy these files to the artifact reference directory
(`tests/artifacts/xdmod/regression/current/expected/reference/`).

The hashes in `tests/artifacts/xdmod/regression/images/expected.json` will also
be updated.

## Environment Variables

### `XDMOD_REALMS`

Comma separated list of realms that should be tested.  Valid realm names can be
found in the `name` column of the `moddb.realms` database table and must be in
lower case.

### `REG_TEST_ALL`

Set to `1` to run all regression tests.  Otherwise only a randomly selected
fraction of the tests will run.

### `REG_TEST_FORCE_GENERATION`

Set to `1` to force generation of test data.

### `REG_TEST_BASE`

Used to override the base directory for test artifacts.

### `REG_TIME_LOGDIR`

If set, timing data will be written to files in the specified directory.

### `REG_TEST_ALT_EXPECTED`

Used to specify an alternate hostname that will be used to validate data.

### `REG_TEST_USER_ROLE`

This environment variable is set by the `runtests.sh` script and should not be
set unless manually running individual tests for a single user role.

The role in `testing.json` to use.  If not set the public user is assumed and
no authentication is done.

### `REG_TEST_REGEX`

Comma separated list of regular expressions to replace in results.

e.g. `/(?:Instance One|OpenXDMoD - Federation)/,/\s((?:daywalk|sunscrn))/,/\s((?:daywalk-instanceo))/,/\s((?:sunscrn-instanceb))/,/(?:Instance One|OpenXDMoD - Federation)\s/`

### `REG_TEST_REPLACE`

Comma separated list of replacements for the regular expressions.
